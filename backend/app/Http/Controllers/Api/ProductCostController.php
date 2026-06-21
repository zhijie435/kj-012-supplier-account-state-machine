<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCost;
use App\Services\ProductCostService;
use Illuminate\Http\Request;

class ProductCostController extends Controller
{
    protected ProductCostService $costService;

    public function __construct(ProductCostService $costService)
    {
        $this->costService = $costService;
    }

    public function index(Request $request)
    {
        $query = ProductCost::with(['product', 'creator', 'updater']);

        if ($request->filled('product_id')) {
            $query->ofProduct($request->product_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $this->applySearch($query, $request, ['remark']);

        $costs = $query->orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage($request));

        return response()->json([
            'code' => 0,
            'data' => $costs,
            'message' => 'success',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'purchase_price' => 'required|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'platform_fee' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'other_cost' => 'nullable|numeric|min:0',
            'profit_margin' => 'nullable|numeric|min:0|max:100',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_active' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        $cost = $this->costService->createProductCost(
            $request->product_id,
            $request->all(),
            auth()->id()
        );

        return response()->json([
            'code' => 0,
            'data' => $cost,
            'message' => '创建成功',
        ]);
    }

    public function batchStore(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.shipping_cost' => 'nullable|numeric|min:0',
            'items.*.packaging_cost' => 'nullable|numeric|min:0',
            'items.*.platform_fee' => 'nullable|numeric|min:0',
            'items.*.commission_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.other_cost' => 'nullable|numeric|min:0',
            'items.*.profit_margin' => 'nullable|numeric|min:0|max:100',
            'items.*.effective_date' => 'required|date',
            'items.*.expiry_date' => 'nullable|date|after:effective_date',
            'items.*.is_active' => 'nullable|boolean',
            'items.*.remark' => 'nullable|string',
        ]);

        $created = [];
        foreach ($request->items as $item) {
            $created[] = $this->costService->createProductCost(
                $item['product_id'],
                $item,
                auth()->id()
            );
        }

        return response()->json([
            'code' => 0,
            'data' => $created,
            'message' => '批量创建成功',
        ]);
    }

    public function show(ProductCost $productCost)
    {
        return response()->json([
            'code' => 0,
            'data' => $productCost->load(['product', 'creator', 'updater']),
            'message' => 'success',
        ]);
    }

    public function update(Request $request, ProductCost $productCost)
    {
        $request->validate([
            'purchase_price' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'platform_fee' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'other_cost' => 'nullable|numeric|min:0',
            'profit_margin' => 'nullable|numeric|min:0|max:100',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_active' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        $productCost = $this->costService->updateProductCost(
            $productCost,
            $request->all(),
            auth()->id()
        );

        return response()->json([
            'code' => 0,
            'data' => $productCost,
            'message' => '更新成功',
        ]);
    }

    public function toggleActive(Request $request, $id)
    {
        $productCost = ProductCost::findOrFail($id);

        $productCost->update([
            'is_active' => !$productCost->is_active,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'code' => 0,
            'data' => $productCost->fresh(),
            'message' => $productCost->is_active ? '已启用' : '已禁用',
        ]);
    }

    public function destroy(ProductCost $productCost)
    {
        $productCost->delete();

        return response()->json([
            'code' => 0,
            'data' => null,
            'message' => '删除成功',
        ]);
    }
}
