<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\Supplier;
use App\Models\User;
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
        $query = Settlement::with(['party', 'settler', 'creator']);

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('party_id')) {
            $query->ofParty($request->party_id);
        }

        if ($request->filled('status')) {
            $query->ofStatus($request->status);
        }

        $this->applySearch($query, $request, ['settlement_no', 'party_name']);

        if ($request->filled('start_date')) {
            $query->whereDate('end_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_date', '<=', $request->end_date);
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
            'type' => 'required|string|in:supplier,distributor',
            'party_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remark' => 'nullable|string',
        ]);

        try {
            $settlement = $this->settlementService->createSettlement(
                $request->type,
                $request->party_id,
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
        $settlement->load(['items', 'party', 'settler', 'creator', 'updater']);

        return response()->json([
            'code' => 0,
            'data' => $settlement,
            'message' => 'success',
        ]);
    }

    public function update(Request $request, Settlement $settlement): JsonResponse
    {
        $request->validate([
            'remark' => 'nullable|string',
        ]);

        if ($settlement->status !== Settlement::STATUS_PENDING) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => '只有待结算状态的结算单可以编辑',
            ], 400);
        }

        $settlement->update([
            'remark' => $request->remark,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'code' => 0,
            'data' => $settlement->fresh(),
            'message' => '更新成功',
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:supplier,distributor',
            'party_id' => 'required|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $preview = $this->settlementService->previewSettlement(
            $request->type,
            $request->party_id,
            $request->start_date,
            $request->end_date
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

        if ($settlement->status !== Settlement::STATUS_PENDING) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => '只有待结算状态的结算单可以重新计算',
            ], 400);
        }

        try {
            $preview = $this->settlementService->previewSettlement(
                $settlement->period_type,
                $settlement->party_id,
                $settlement->start_date,
                $settlement->end_date
            );

            $settlement->items()->delete();

            foreach ($preview['items'] as $itemData) {
                $itemData['settlement_id'] = $settlement->id;
                \App\Models\SettlementItem::create($itemData);
            }

            $settlement->update([
                'order_count' => $preview['order_count'],
                'total_sales_amount' => $preview['total_sales_amount'],
                'total_cost_amount' => $preview['total_cost_amount'],
                'total_commission_amount' => $preview['total_commission_amount'],
                'settlement_amount' => $preview['settlement_amount'],
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'code' => 0,
                'data' => $settlement->fresh(['items']),
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

        if ($settlement->status !== Settlement::STATUS_PENDING) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => '只有待结算状态的结算单可以确认',
            ], 400);
        }

        $settlement->update([
            'status' => Settlement::STATUS_PROCESSING,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'code' => 0,
            'data' => $settlement->fresh(),
            'message' => '已确认，结算中',
        ]);
    }

    public function settle(Request $request, $id): JsonResponse
    {
        $settlement = Settlement::findOrFail($id);

        try {
            $settlement = $this->settlementService->approveSettlement($settlement, auth()->id());

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

        if ($settlement->status === Settlement::STATUS_SETTLED) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => '已完成的结算单不能取消',
            ], 400);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $settlement = $this->settlementService->rejectSettlement(
                $settlement,
                $request->reason ?? '手动取消',
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
            $request->party_id
        );

        return response()->json([
            'code' => 0,
            'data' => $stats,
            'message' => 'success',
        ]);
    }

    public function getPartyOptions(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:supplier,distributor',
        ]);

        if ($request->type === Settlement::TYPE_SUPPLIER) {
            $parties = Supplier::active()
                ->select('id', 'name', 'company_name')
                ->get()
                ->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->name,
                        'company_name' => $s->company_name,
                    ];
                });
        } else {
            $parties = User::where('guard_name', 'distributor')
                ->active()
                ->select('id', 'name', 'email')
                ->get();
        }

        return response()->json([
            'code' => 0,
            'data' => $parties,
            'message' => 'success',
        ]);
    }

    public function destroy(Settlement $settlement): JsonResponse
    {
        if ($settlement->status !== Settlement::STATUS_PENDING && $settlement->status !== Settlement::STATUS_REJECTED) {
            return response()->json([
                'code' => 1,
                'data' => null,
                'message' => '只有待结算或已驳回状态的结算单可以删除',
            ], 400);
        }

        $settlement->delete();

        return response()->json([
            'code' => 0,
            'data' => null,
            'message' => '结算单删除成功',
        ]);
    }
}
