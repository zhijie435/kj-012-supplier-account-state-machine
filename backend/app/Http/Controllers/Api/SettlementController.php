<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    protected SettlementService $settlementService;

    public function __construct(SettlementService $settlementService)
    {
        $this->settlementService = $settlementService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Settlement::with(['items', 'creator', 'updater', 'settler']);

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('status')) {
            $query->ofStatus($request->status);
        }

        $this->applySearch($query, $request, ['settlement_no', 'order_no', 'remark']);

        if ($request->filled('start_date')) {
            $query->whereDate('settlement_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('settlement_date', '<=', $request->end_date);
        }

        $settlements = $query->orderBy('id', 'desc')->paginate($this->perPage($request));

        return response()->json([
            'code' => 0,
            'data' => $settlements,
            'message' => 'success',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:order,monthly,manual',
            'settlement_date' => 'required|date',
            'order_no' => 'nullable|string',
            'supplier_ratio' => 'nullable|numeric|min:0|max:1',
            'distributor_ratio' => 'nullable|numeric|min:0|max:1',
            'platform_ratio' => 'nullable|numeric|min:0|max:1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.sale_price' => 'nullable|numeric|min:0',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $settlement = $this->settlementService->createSettlement(
                $request->all(),
                auth()->id()
            );

            return response()->json([
                'code' => 0,
                'data' => $settlement,
                'message' => '结算单创建成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show(Settlement $settlement): JsonResponse
    {
        $settlement->load(['items', 'creator', 'updater', 'settler']);

        return response()->json([
            'code' => 0,
            'data' => $settlement,
            'message' => 'success',
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'supplier_ratio' => 'nullable|numeric|min:0|max:1',
            'distributor_ratio' => 'nullable|numeric|min:0|max:1',
            'platform_ratio' => 'nullable|numeric|min:0|max:1',
        ]);

        $preview = $this->settlementService->previewFromItems(
            $request->items,
            $request->only(['supplier_ratio', 'distributor_ratio', 'platform_ratio'])
        );

        return response()->json([
            'code' => 0,
            'data' => $preview,
            'message' => 'success',
        ]);
    }

    public function recalculate(Request $request, $id): JsonResponse
    {
        $settlement = Settlement::findOrFail($id);

        try {
            $settlement = $this->settlementService->recalculateSettlement($settlement, auth()->id());

            return response()->json([
                'code' => 0,
                'data' => $settlement,
                'message' => '重新计算成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function confirm(Request $request, $id): JsonResponse
    {
        $settlement = Settlement::findOrFail($id);

        try {
            $settlement = $this->settlementService->confirmSettlement($settlement, auth()->id());

            return response()->json([
                'code' => 0,
                'data' => $settlement,
                'message' => '确认成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function settle(Request $request, $id): JsonResponse
    {
        $settlement = Settlement::findOrFail($id);

        try {
            $settlement = $this->settlementService->settleSettlement($settlement, auth()->id());

            return response()->json([
                'code' => 0,
                'data' => $settlement,
                'message' => '结算已完成',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel(Request $request, $id): JsonResponse
    {
        $settlement = Settlement::findOrFail($id);

        try {
            $settlement = $this->settlementService->cancelSettlement(
                $settlement,
                $request->reason ?? '',
                auth()->id()
            );

            return response()->json([
                'code' => 0,
                'data' => $settlement,
                'message' => '已取消',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                               'code' => 1,
                'data' => null,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->settlementService->getStatistics(
            $request->type,
            $request->status,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'code' => 0,
            'data' => $stats,
            'message' => 'success',
        ]);
    }

    public function destroy(Settlement $settlement): JsonResponse
    {
        if (!$settlement->isEditable()) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => '只有待确认或已取消状态的结算单可以删除',
            ], 400);
        }

        $settlement->items()->delete();
        $settlement->delete();

        return response()->json([
            'code' => 0,
            'data' => null,
            'message' => '结算单删除成功',
        ]);
    }
}
