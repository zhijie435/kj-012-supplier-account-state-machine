<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ProductCostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected ProductCostService $costService;

    public function __construct(ProductCostService $costService)
    {
        $this->costService = $costService;
    }

    public function generateOrderNo(): string
    {
        return 'ORD' . now()->format('YmdHis') . strtoupper(Str::random(4));
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'distributor', 'supplier']);

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_no', 'like', "%{$request->keyword}%")
                    ->orWhere('customer_name', 'like', "%{$request->keyword}%")
                    ->orWhere('customer_phone', 'like', "%{$request->keyword}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->distributor_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $orders = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'code' => 0,
            'data' => $orders,
            'message' => 'success',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'distributor_id' => 'nullable|integer|exists:users,id',
            'supplier_id' => 'nullable|integer|exists:users,id',
            'customer_name' => 'nullable|string|max:128',
            'customer_phone' => 'nullable|string|max:32',
            'shipping_address' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_fee' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:32',
            'remark' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.sale_price' => 'required|numeric|min:0',
            'items.*.remark' => 'nullable|string',
        ]);

        $userId = auth()->id();

        $order = Order::create([
            'order_no' => $this->generateOrderNo(),
            'distributor_id' => $request->distributor_id,
            'supplier_id' => $request->supplier_id,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'shipping_address' => $request->shipping_address,
            'discount_amount' => $request->discount_amount ?? 0,
            'shipping_fee' => $request->shipping_fee ?? 0,
            'payment_method' => $request->payment_method,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'remark' => $request->remark,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $totalAmount = 0;
        $totalCost = 0;

        foreach ($request->items as $itemData) {
            $product = \App\Models\Product::find($itemData['product_id']);
            $snapshot = $this->costService->buildOrderItemCostSnapshot(
                $product,
                $itemData['quantity'],
                $itemData['sale_price']
            );

            $orderItem = new OrderItem([
                ...$snapshot,
                'remark' => $itemData['remark'] ?? null,
            ]);
            $order->items()->save($orderItem);

            $totalAmount += $snapshot['subtotal'];
            $totalCost += $snapshot['total_cost'];
        }

        $order->update([
            'total_amount' => $totalAmount,
            'total_cost' => $totalCost,
            'total_profit' => $totalAmount - $totalCost,
            'payable_amount' => $totalAmount - ($request->discount_amount ?? 0) + ($request->shipping_fee ?? 0),
        ]);

        return response()->json([
            'code' => 0,
            'data' => $order->load('items'),
            'message' => '订单创建成功',
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['items', 'items.product', 'distributor', 'supplier', 'creator', 'updater']);

        return response()->json([
            'code' => 0,
            'data' => $order,
            'message' => 'success',
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|integer|in:0,1,2,3,4,5,6',
            'remark' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $updateData = [
            'status' => $request->status,
            'updated_by' => $userId,
        ];

        if ($request->status === Order::STATUS_PENDING_SHIPMENT && $order->status === Order::STATUS_PENDING_PAYMENT) {
            $updateData['paid_at'] = now();
            $updateData['paid_amount'] = $order->payable_amount;
        }

        if ($request->filled('remark')) {
            $updateData['remark'] = $request->remark;
        }

        $order->update($updateData);

        $statusMap = [
            0 => '待付款',
            1 => '待发货',
            2 => '已发货',
            3 => '已完成',
            4 => '已取消',
            5 => '退款中',
            6 => '已退款',
        ];

        return response()->json([
            'code' => 0,
            'data' => $order,
            'message' => "订单状态已更新为：{$statusMap[$request->status]}",
        ]);
    }

    public function recalculateCost(Order $order): JsonResponse
    {
        $totalAmount = 0;
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($order->items as $item) {
            $product = $item->product;
            $snapshot = $this->costService->buildOrderItemCostSnapshot(
                $product,
                $item->quantity,
                $item->sale_price
            );

            $item->update($snapshot);

            $totalAmount += $snapshot['subtotal'];
            $totalCost += $snapshot['total_cost'];
            $totalProfit += $snapshot['profit'];
        }

        $order->update([
            'total_amount' => $totalAmount,
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
            'payable_amount' => $totalAmount - $order->discount_amount + $order->shipping_fee,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'code' => 0,
            'data' => $order->fresh('items'),
            'message' => '成本重新计算成功',
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $query = Order::query();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->distributor_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $totalOrders = (clone $query)->count();
        $completedOrders = (clone $query)->where('status', Order::STATUS_COMPLETED)->count();
        $totalSales = (clone $query)->where('status', Order::STATUS_COMPLETED)->sum('total_amount');
        $totalCost = (clone $query)->where('status', Order::STATUS_COMPLETED)->sum('total_cost');
        $totalProfit = (clone $query)->where('status', Order::STATUS_COMPLETED)->sum('total_profit');

        return response()->json([
            'code' => 0,
            'data' => [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'total_sales' => round($totalSales, 2),
                'total_cost' => round($totalCost, 2),
                'total_profit' => round($totalProfit, 2),
                'profit_rate' => $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 2) : 0,
            ],
            'message' => 'success',
        ]);
    }
}
