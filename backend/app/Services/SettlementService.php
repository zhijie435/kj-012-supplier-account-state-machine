<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettlementService
{
    protected ProductCostService $costService;

    public function __construct(ProductCostService $costService)
    {
        $this->costService = $costService;
    }

    public function generateSettlementNo(string $type): string
    {
        $prefix = $type === Settlement::TYPE_SUPPLIER ? 'SUP' : 'DIS';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        return "{$prefix}-{$date}-{$random}";
    }

    public function getUnsettledOrders(string $type, int $partyId, $startDate = null, $endDate = null)
    {
        $query = Order::with(['items', 'items.product'])
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDoesntHave('settlements', function ($q) {
                $q->whereIn('status', [Settlement::STATUS_PENDING, Settlement::STATUS_PROCESSING, Settlement::STATUS_SETTLED]);
            });

        if ($type === Settlement::TYPE_SUPPLIER) {
            $query->where('supplier_id', $partyId);
        } else {
            $query->where('distributor_id', $partyId);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    public function calculateSettlementItem(string $type, OrderItem $orderItem): array
    {
        $totalSales = $orderItem->subtotal;
        $purchasePrice = $orderItem->purchase_price;
        $platformFee = $orderItem->platform_fee;
        $commissionAmount = $orderItem->commission_amount;
        $shippingCost = $orderItem->shipping_cost;
        $otherCost = $orderItem->other_cost;

        $totalCost = $orderItem->total_cost;
        $profit = $orderItem->profit;

        if ($type === Settlement::TYPE_SUPPLIER) {
            $settlementAmount = round(
                $totalSales - $platformFee - $commissionAmount - $shippingCost - $otherCost,
                2
            );
        } else {
            $settlementAmount = round($commissionAmount + $profit * 0.3, 2);
        }

        return [
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->id,
            'order_no' => $orderItem->order->order_no,
            'product_id' => $orderItem->product_id,
            'product_name' => $orderItem->product_name,
            'product_sku' => $orderItem->product_sku,
            'quantity' => $orderItem->quantity,
            'sale_price' => $orderItem->sale_price,
            'total_sales' => $totalSales,
            'purchase_price' => $purchasePrice,
            'platform_fee' => $platformFee,
            'commission_amount' => $commissionAmount,
            'shipping_cost' => $shippingCost,
            'other_cost' => $otherCost,
            'total_cost' => $totalCost,
            'settlement_amount' => max($settlementAmount, 0),
            'profit' => $profit,
            'created_at' => now(),
        ];
    }

    public function previewSettlement(string $type, int $partyId, $startDate = null, $endDate = null): array
    {
        if ($type === Settlement::TYPE_SUPPLIER) {
            $party = Supplier::findOrFail($partyId);
            $partyName = $party->company_name ?: $party->name;
        } else {
            $party = User::findOrFail($partyId);
            $partyName = $party->name;
        }
        $orders = $this->getUnsettledOrders($type, $partyId, $startDate, $endDate);

        $items = [];
        $orderCount = 0;
        $totalSales = 0;
        $totalCost = 0;
        $totalCommission = 0;
        $settlementAmount = 0;

        foreach ($orders as $order) {
            $orderCount++;
            foreach ($order->items as $orderItem) {
                $item = $this->calculateSettlementItem($type, $orderItem);
                $items[] = $item;
                $totalSales += $item['total_sales'];
                $totalCost += $item['total_cost'];
                $totalCommission += $item['commission_amount'];
                $settlementAmount += $item['settlement_amount'];
            }
        }

        return [
            'type' => $type,
            'party_id' => $partyId,
            'party_name' => $partyName,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'order_count' => $orderCount,
            'total_sales_amount' => round($totalSales, 2),
            'total_cost_amount' => round($totalCost, 2),
            'total_commission_amount' => round($totalCommission, 2),
            'total_refund_amount' => 0,
            'settlement_amount' => round($settlementAmount, 2),
            'items' => $items,
        ];
    }

    public function createSettlement(string $type, int $partyId, array $data, int $userId = null): Settlement
    {
        if ($type === Settlement::TYPE_SUPPLIER) {
            $party = Supplier::findOrFail($partyId);
            $partyName = $party->company_name ?: $party->name;
        } else {
            $party = User::findOrFail($partyId);
            $partyName = $party->name;
        }
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        $preview = $this->previewSettlement($type, $partyId, $startDate, $endDate);

        if ($preview['order_count'] === 0) {
            throw new \Exception('该时间段内没有可结算的订单');
        }

        DB::beginTransaction();
        try {
            $settlement = Settlement::create([
                'settlement_no' => $this->generateSettlementNo($type),
                'period_type' => $type,
                'party_id' => $partyId,
                'party_name' => $partyName,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'order_count' => $preview['order_count'],
                'total_sales_amount' => $preview['total_sales_amount'],
                'total_cost_amount' => $preview['total_cost_amount'],
                'total_commission_amount' => $preview['total_commission_amount'],
                'total_refund_amount' => 0,
                'settlement_amount' => $preview['settlement_amount'],
                'status' => Settlement::STATUS_PENDING,
                'remark' => $data['remark'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($preview['items'] as $itemData) {
                $itemData['settlement_id'] = $settlement->id;
                SettlementItem::create($itemData);
            }

            DB::commit();
            return $settlement->fresh(['items']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approveSettlement(Settlement $settlement, int $userId = null): Settlement
    {
        if ($settlement->status !== Settlement::STATUS_PENDING) {
            throw new \Exception('只有待结算状态的结算单可以审批');
        }

        DB::beginTransaction();
        try {
            $settlement->update([
                'status' => Settlement::STATUS_SETTLED,
                'settled_at' => now(),
                'settled_by' => $userId,
                'updated_by' => $userId,
            ]);

            DB::commit();
            return $settlement->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rejectSettlement(Settlement $settlement, string $reason, int $userId = null): Settlement
    {
        if ($settlement->status !== Settlement::STATUS_PENDING) {
            throw new \Exception('只有待结算状态的结算单可以驳回');
        }

        DB::beginTransaction();
        try {
            $settlement->update([
                'status' => Settlement::STATUS_REJECTED,
                'remark' => $reason,
                'updated_by' => $userId,
            ]);

            DB::commit();
            return $settlement->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getStatistics(string $type = null, int $partyId = null): array
    {
        $query = Settlement::query();

        if ($type) {
            $query->ofType($type);
        }
        if ($partyId) {
            $query->ofParty($partyId);
        }

        $pendingCount = (clone $query)->ofStatus(Settlement::STATUS_PENDING)->count();
        $settledCount = (clone $query)->ofStatus(Settlement::STATUS_SETTLED)->count();
        $rejectedCount = (clone $query)->ofStatus(Settlement::STATUS_REJECTED)->count();

        $totalSettledAmount = (clone $query)
            ->ofStatus(Settlement::STATUS_SETTLED)
            ->sum('settlement_amount');

        $pendingSettlementAmount = (clone $query)
            ->ofStatus(Settlement::STATUS_PENDING)
            ->sum('settlement_amount');

        return [
            'pending_count' => $pendingCount,
            'settled_count' => $settledCount,
            'rejected_count' => $rejectedCount,
            'total_settled_amount' => round($totalSettledAmount, 2),
            'pending_settlement_amount' => round($pendingSettlementAmount, 2),
        ];
    }
}
