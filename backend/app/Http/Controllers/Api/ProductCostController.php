<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        if ($request->filled('cost_type')) {
            $query->ofCostType($request->cost_type);
        }

        if ($request->filled('is_active') && $request->is_active !== '') {
            $query->where('is_active', (int) $request->is_active);
        }

        $this->applySearch($query, $request, ['cost_name', 'remark']);

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
            'cost_type' => 'required|string|in:purchase,shipping,packaging,platform_fee,marketing,tax,other',
            'cost_name' => 'required|string|max:100',
            'unit_cost' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_active' => 'nullable|integer|in:0,1',
            'remark' => 'nullable|string|max:500',
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
            'product_id' => 'required|integer|exists:products,id',
            'cost_items' => 'required|array|min:1',
            'cost_items.*.cost_type' => 'required|string|in:purchase,shipping,packaging,platform_fee,marketing,tax,other',
            'cost_items.*.cost_name' => 'required|string|max:100',
            'cost_items.*.unit_cost' => 'required|numeric|min:0',
            'cost_items.*.quantity' => 'nullable|integer|min:1',
        ]);

        $created = $this->costService->batchCreateProductCosts(
            $request->product_id,
            $request->cost_items,
            auth()->id()
        );

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
            'cost_type' => 'nullable|string|in:purchase,shipping,packaging,platform_fee,marketing,tax,other',
            'cost_name' => 'nullable|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_active' => 'nullable|integer|in:0,1',
            'remark' => 'nullable|string|max:500',
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
        $productCost = $this->costService->toggleActive($productCost, auth()->id());

        return response()->json([
            'code' => 0,
            'data' => $productCost,
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
