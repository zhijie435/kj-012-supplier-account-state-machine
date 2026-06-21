<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCost;
use App\Services\ProductCostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductCostService $costService;

    public function __construct(ProductCostService $costService)
    {
        $this->costService = $costService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['activeCost', 'supplier']);

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('sku', 'like', "%{$request->keyword}%")
                    ->orWhere('barcode', 'like', "%{$request->keyword}%");
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'code' => 0,
            'data' => $products,
            'message' => 'success',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:64|unique:products,sku',
            'barcode' => 'nullable|string|max:64',
            'supplier_id' => 'nullable|integer|exists:users,id',
            'category' => 'nullable|string|max:128',
            'unit' => 'nullable|string|max:32',
            'sale_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|max:512',
            'stock' => 'nullable|integer|min:0',
            'warning_stock' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $userId = auth()->id();

        $product = Product::create([
            ...$request->only([
                'name', 'sku', 'barcode', 'supplier_id', 'category',
                'unit', 'sale_price', 'weight', 'description', 'image_url',
                'stock', 'warning_stock', 'status',
            ]),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'code' => 0,
            'data' => $product->load(['activeCost', 'supplier']),
            'message' => '创建成功',
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['costs', 'activeCost', 'supplier', 'creator', 'updater']);

        return response()->json([
            'code' => 0,
            'data' => $product,
            'message' => 'success',
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'name' => 'string|max:255',
            'sku' => 'string|max:64|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:64',
            'supplier_id' => 'nullable|integer|exists:users,id',
            'category' => 'nullable|string|max:128',
            'unit' => 'nullable|string|max:32',
            'sale_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|max:512',
            'stock' => 'nullable|integer|min:0',
            'warning_stock' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $product->update([
            ...$request->only([
                'name', 'sku', 'barcode', 'supplier_id', 'category',
                'unit', 'sale_price', 'weight', 'description', 'image_url',
                'stock', 'warning_stock', 'status',
            ]),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'code' => 0,
            'data' => $product->load(['activeCost', 'supplier']),
            'message' => '更新成功',
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'code' => 0,
            'data' => null,
            'message' => '删除成功',
        ]);
    }

    public function getCosts(Product $product, Request $request): JsonResponse
    {
        $costs = $product->costs()
            ->with(['creator', 'updater'])
            ->orderBy('effective_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'code' => 0,
            'data' => $costs,
            'message' => 'success',
        ]);
    }

    public function calculateCost(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
            'purchase_price' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'platform_fee' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'other_cost' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
        ]);

        $preview = $this->costService->previewCost($request->product_id, $request->all());
        $quantity = $request->input('quantity', 1);

        $preview['quantity'] = $quantity;
        $preview['total_purchase_price'] = round($preview['purchase_price'] * $quantity, 2);
        $preview['total_shipping_cost'] = round($preview['shipping_cost'] * $quantity, 2);
        $preview['total_packaging_cost'] = round($preview['packaging_cost'] * $quantity, 2);
        $preview['total_platform_fee'] = round($preview['platform_fee'] * $quantity, 2);
        $preview['total_commission_amount'] = round($preview['commission_amount'] * $quantity, 2);
        $preview['total_tax_amount'] = round($preview['tax_amount'] * $quantity, 2);
        $preview['total_other_cost'] = round($preview['other_cost'] * $quantity, 2);
        $preview['total_cost_amount'] = round($preview['total_cost'] * $quantity, 2);
        $preview['total_sales_amount'] = round($preview['sale_price'] * $quantity, 2);
        $preview['total_profit'] = round($preview['profit'] * $quantity, 2);

        return response()->json([
            'code' => 0,
            'data' => $preview,
            'message' => 'success',
        ]);
    }

    public function previewCost(Request $request): JsonResponse
    {
        return $this->calculateCost($request);
    }

    public function addCost(Request $request, Product $product): JsonResponse
    {
        $request->validate([
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

        $cost = $this->costService->createProductCost($product->id, $request->all(), auth()->id());

        return response()->json([
            'code' => 0,
            'data' => $cost,
            'message' => '成本添加成功',
        ]);
    }

    public function updateCost(Request $request, ProductCost $cost): JsonResponse
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

        $cost = $this->costService->updateProductCost($cost, $request->all(), auth()->id());

        return response()->json([
            'code' => 0,
            'data' => $cost,
            'message' => '成本更新成功',
        ]);
    }

    public function deleteCost(ProductCost $cost): JsonResponse
    {
        $cost->delete();

        return response()->json([
            'code' => 0,
            'data' => null,
            'message' => '成本记录删除成功',
        ]);
    }
}
