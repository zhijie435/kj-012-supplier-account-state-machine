<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Settlement;
use App\Models\SettlementItem;
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
        $prefixMap = [
            Settlement::TYPE_ORDER => 'ORD',
            Settlement::TYPE_MONTHLY => 'MON',
            Settlement::TYPE_MANUAL => 'MAN',
        ];
        $prefix = $prefixMap[$type] ?? 'SET';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        return "{$prefix}-{$date}-{$random}";
    }

    public function previewFromItems(array $items, array $ratios = []): array
    {
        $supplierRatio = (float) ($ratios['supplier_ratio'] ?? 0.5);
        $distributorRatio = (float) ($ratios['distributor_ratio'] ?? 0.2);
        $platformRatio = (float) ($ratios['platform_ratio'] ?? 0.3);

        $processedItems = [];
        $totalAmount = 0;
        $productCost = 0;
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) continue;

            $qty = (int) ($item['quantity'] ?? 1);
            $salePrice = (float) ($item['sale_price'] ?? $product->price);
            $unitCost = (float) ($item['unit_cost'] ?? $this->costService->getProductTotalCost($product->id));

            $totalSales = round($salePrice * $qty, 2);
            $itemTotalCost = round($unitCost * $qty, 2);
            $profit = round($totalSales - $itemTotalCost, 2);

            $processedItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $qty,
                'sale_price' => $salePrice,
                'total_sales' => $totalSales,
                'unit_cost' => $unitCost,
                'total_cost' => $itemTotalCost,
                'profit' => $profit,
            ];

            $totalAmount += $totalSales;
            $productCost += $itemTotalCost;
            $totalCost += $itemTotalCost;
            $totalProfit += $profit;
        }

        $profitRate = $totalAmount > 0 ? round($totalProfit / $totalAmount, 4) : 0;

        return [
            'items' => $processedItems,
            'order_count' => count($processedItems),
            'total_amount' => round($totalAmount, 2),
            'product_cost' => round($productCost, 2),
            'platform_fee' => 0,
            'other_cost' => 0,
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
            'profit_rate' => $profitRate,
            'supplier_ratio' => $supplierRatio,
            'distributor_ratio' => $distributorRatio,
            'platform_ratio' => $platformRatio,
            'supplier_share' => round($totalAmount * $supplierRatio, 2),
            'distributor_share' => round($totalAmount * $distributorRatio, 2),
            'platform_share' => round($totalAmount * $platformRatio, 2),
        ];
    }

    public function createSettlement(array $data, int $userId = null): Settlement
    {
        DB::beginTransaction();
        try {
            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new \Exception('请至少添加一条商品明细');
            }

            $preview = $this->previewFromItems($items, [
                'supplier_ratio' => $data['supplier_ratio'] ?? 0.5,
                'distributor_ratio' => $data['distributor_ratio'] ?? 0.2,
                'platform_ratio' => $data['platform_ratio'] ?? 0.3,
            ]);

            $settlement = Settlement::create([
                'settlement_no' => $this->generateSettlementNo($data['type'] ?? Settlement::TYPE_MANUAL),
                'type' => $data['type'] ?? Settlement::TYPE_MANUAL,
                'settlement_date' => $data['settlement_date'] ?? now()->toDateString(),
                'order_no' => $data['order_no'] ?? null,
                'order_count' => $preview['order_count'],
                'total_amount' => $preview['total_amount'],
                'product_cost' => $preview['product_cost'],
                'platform_fee' => 0,
                'other_cost' => 0,
                'total_cost' => $preview['total_cost'],
                'total_profit' => $preview['total_profit'],
                'profit_rate' => $preview['profit_rate'],
                'supplier_ratio' => $preview['supplier_ratio'],
                'distributor_ratio' => $preview['distributor_ratio'],
                'platform_ratio' => $preview['platform_ratio'],
                'supplier_share' => $preview['supplier_share'],
                'distributor_share' => $preview['distributor_share'],
                'platform_share' => $preview['platform_share'],
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

    public function recalculateSettlement(Settlement $settlement, int $userId = null): Settlement
    {
        if (!$settlement->canConfirm() && $settlement->status !== Settlement::STATUS_CONFIRMED) {
            throw new \Exception('当前状态不允许重新计算');
        }

        DB::beginTransaction();
        try {
            $items = $settlement->items;
            $ratios = [
                'supplier_ratio' => $settlement->supplier_ratio,
                'distributor_ratio' => $settlement->distributor_ratio,
                'platform_ratio' => $settlement->platform_ratio,
            ];

            $itemArray = $items->map(function ($i) {
                return [
                    'product_id' => $i->product_id,
                    'quantity' => $i->quantity,
                    'sale_price' => $i->sale_price,
                    'unit_cost' => $i->unit_cost,
                ];
            })->toArray();

            $preview = $this->previewFromItems($itemArray, $ratios);

            $settlement->items()->delete();
            foreach ($preview['items'] as $itemData) {
                $itemData['settlement_id'] = $settlement->id;
                SettlementItem::create($itemData);
            }

            $settlement->update([
                'order_count' => $preview['order_count'],
                'total_amount' => $preview['total_amount'],
                'product_cost' => $preview['product_cost'],
                'total_cost' => $preview['total_cost'],
                'total_profit' => $preview['total_profit'],
                'profit_rate' => $preview['profit_rate'],
                'supplier_share' => $preview['supplier_share'],
                'distributor_share' => $preview['distributor_share'],
                'platform_share' => $preview['platform_share'],
                'updated_by' => $userId,
            ]);

            DB::commit();
            return $settlement->fresh(['items']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function confirmSettlement(Settlement $settlement, int $userId = null): Settlement
    {
        if (!$settlement->canConfirm()) {
            throw new \Exception('只有待确认状态的结算单可以确认');
        }

        DB::beginTransaction();
        try {
            $settlement->update([
                'status' => Settlement::STATUS_CONFIRMED,
                'updated_by' => $userId,
            ]);
            DB::commit();
            return $settlement->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function settleSettlement(Settlement $settlement, int $userId = null): Settlement
    {
        if (!$settlement->canSettle()) {
            throw new \Exception('只有已确认状态的结算单可以完成结算');
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

    public function cancelSettlement(Settlement $settlement, string $reason = '', int $userId = null): Settlement
    {
        if (!$settlement->canCancel()) {
            throw new \Exception('当前状态不允许取消');
        }

        DB::beginTransaction();
        try {
            $settlement->update([
                'status' => Settlement::STATUS_CANCELLED,
                'remark' => $reason ?: ($settlement->remark ? $settlement->remark . ' | ' : '') . '手动取消',
                'updated_by' => $userId,
            ]);
            DB::commit();
            return $settlement->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getStatistics($type = null, $status = null, $startDate = null, $endDate = null): array
    {
        $query = Settlement::query();

        if ($type) {
            $query->ofType($type);
        }
        if ($status) {
            $query->ofStatus($status);
        }
        if ($startDate) {
            $query->whereDate('settlement_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('settlement_date', '<=', $endDate);
        }

        $totalCount = (clone $query)->count();
        $pendingCount = (clone $query)->ofStatus(Settlement::STATUS_PENDING)->count();
        $confirmedCount = (clone $query)->ofStatus(Settlement::STATUS_CONFIRMED)->count();
        $settledCount = (clone $query)->ofStatus(Settlement::STATUS_SETTLED)->count();
        $cancelledCount = (clone $query)->ofStatus(Settlement::STATUS_CANCELLED)->count();

        $totalAmount = (clone $query)->sum('total_amount');
        $totalCost = (clone $query)->sum('total_cost');
        $totalProfit = (clone $query)->sum('total_profit');

        $avgProfitRate = $totalAmount > 0 ? round($totalProfit / $totalAmount, 4) : 0;

        return [
            'total_count' => $totalCount,
            'pending_count' => $pendingCount,
            'confirmed_count' => $confirmedCount,
            'settled_count' => $settledCount,
            'cancelled_count' => $cancelledCount,
            'total_amount' => round($totalAmount, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
            'avg_profit_rate' => $avgProfitRate,
        ];
    }
}
