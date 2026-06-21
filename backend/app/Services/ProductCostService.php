<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCost;
use Illuminate\Support\Facades\DB;

class ProductCostService
{
    public function getCostTypeMap(): array
    {
        return [
            ProductCost::COST_TYPE_PURCHASE => '采购成本',
            ProductCost::COST_TYPE_SHIPPING => '物流成本',
            ProductCost::COST_TYPE_PACKAGING => '包装成本',
            ProductCost::COST_TYPE_PLATFORM_FEE => '平台服务费',
            ProductCost::COST_TYPE_MARKETING => '营销推广费',
            ProductCost::COST_TYPE_TAX => '税费',
            ProductCost::COST_TYPE_OTHER => '其他费用',
        ];
    }

    public function getProductTotalCost(int $productId, $date = null): float
    {
        $activeCosts = ProductCost::where('product_id', $productId)
            ->active()
            ->effectiveAt($date)
            ->get();

        return round($activeCosts->sum('total_cost'), 2);
    }

    public function getProductCostBreakdown(int $productId, $date = null): array
    {
        $activeCosts = ProductCost::where('product_id', $productId)
            ->active()
            ->effectiveAt($date)
            ->get();

        $breakdown = [];
        $totalCost = 0;

        foreach ($activeCosts as $cost) {
            $breakdown[] = [
                'id' => $cost->id,
                'cost_type' => $cost->cost_type,
                'cost_name' => $cost->cost_name,
                'unit_cost' => (float) $cost->unit_cost,
                'quantity' => (int) $cost->quantity,
                'total_cost' => (float) $cost->total_cost,
            ];
            $totalCost += $cost->total_cost;
        }

        return [
            'items' => $breakdown,
            'total_cost' => round($totalCost, 2),
        ];
    }

    public function calculateProductSummary(int $productId): array
    {
        $product = Product::findOrFail($productId);
        $price = (float) ($product->price ?? 0);
        $totalCost = $this->getProductTotalCost($productId);
        $profit = round($price - $totalCost, 2);
        $grossMargin = $price > 0 ? round($profit / $price, 4) : 0;

        return [
            'product_id' => $productId,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'price' => $price,
            'total_cost' => $totalCost,
            'profit' => $profit,
            'gross_margin' => $grossMargin,
        ];
    }

    public function createProductCost(int $productId, array $data, ?int $userId = null): ProductCost
    {
        Product::findOrFail($productId);

        DB::beginTransaction();
        try {
            $costData = [
                'product_id' => $productId,
                'cost_type' => $data['cost_type'],
                'cost_name' => $data['cost_name'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'quantity' => $data['quantity'] ?? 1,
                'effective_date' => $data['effective_date'],
                'expiry_date' => $data['expiry_date'] ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'remark' => $data['remark'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ];
            $costData['total_cost'] = round($costData['unit_cost'] * $costData['quantity'], 2);

            if (! empty($costData['is_active'])) {
                ProductCost::where('product_id', $productId)
                    ->where('cost_type', $costData['cost_type'])
                    ->where('is_active', 1)
                    ->where(function ($q) use ($costData) {
                        $q->whereNull('expiry_date')
                            ->orWhereDate('expiry_date', '>=', $costData['effective_date']);
                    })
                    ->update(['expiry_date' => $costData['effective_date'], 'updated_by' => $userId]);
            }

            $productCost = ProductCost::create($costData);

            DB::commit();

            return $productCost->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function batchCreateProductCosts(int $productId, array $costItems, ?int $userId = null): array
    {
        $created = [];
        DB::beginTransaction();
        try {
            foreach ($costItems as $item) {
                $item['product_id'] = $productId;
                $created[] = $this->createProductCost($productId, $item, $userId);
            }
            DB::commit();

            return $created;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateProductCost(ProductCost $productCost, array $data, ?int $userId = null): ProductCost
    {
        DB::beginTransaction();
        try {
            $updateData = [
                'cost_type' => $data['cost_type'] ?? $productCost->cost_type,
                'cost_name' => $data['cost_name'] ?? $productCost->cost_name,
                'unit_cost' => $data['unit_cost'] ?? $productCost->unit_cost,
                'quantity' => $data['quantity'] ?? $productCost->quantity,
                'effective_date' => $data['effective_date'] ?? $productCost->effective_date,
                'expiry_date' => $data['expiry_date'] ?? $productCost->expiry_date,
                'is_active' => $data['is_active'] ?? $productCost->is_active,
                'remark' => $data['remark'] ?? $productCost->remark,
                'updated_by' => $userId,
            ];
            $updateData['total_cost'] = round($updateData['unit_cost'] * $updateData['quantity'], 2);

            $productCost->update($updateData);

            DB::commit();

            return $productCost->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function toggleActive(ProductCost $productCost, ?int $userId = null): ProductCost
    {
        $productCost->update([
            'is_active' => $productCost->is_active ? 0 : 1,
            'updated_by' => $userId,
        ]);

        return $productCost->fresh();
    }

    public function buildOrderItemCostSnapshot(Product $product, int $quantity, float $salePrice, $date = null): array
    {
        $costBreakdown = $this->getProductCostBreakdown($product->id, $date);

        $purchasePrice = 0;
        $shippingCost = 0;
        $packagingCost = 0;
        $platformFee = 0;
        $commissionAmount = 0;
        $taxAmount = 0;
        $otherCost = 0;

        foreach ($costBreakdown['items'] as $item) {
            switch ($item['cost_type']) {
                case ProductCost::COST_TYPE_PURCHASE:
                    $purchasePrice += $item['total_cost'];
                    break;
                case ProductCost::COST_TYPE_SHIPPING:
                    $shippingCost += $item['total_cost'];
                    break;
                case ProductCost::COST_TYPE_PACKAGING:
                    $packagingCost += $item['total_cost'];
                    break;
                case ProductCost::COST_TYPE_PLATFORM_FEE:
                    $platformFee += $item['total_cost'];
                    break;
                case ProductCost::COST_TYPE_TAX:
                    $taxAmount += $item['total_cost'];
                    break;
                case ProductCost::COST_TYPE_MARKETING:
                case ProductCost::COST_TYPE_OTHER:
                default:
                    $otherCost += $item['total_cost'];
                    break;
            }
        }

        $unitCost = $costBreakdown['total_cost'];
        $subtotal = round($salePrice * $quantity, 2);
        $totalCost = round($unitCost * $quantity, 2);
        $profit = round($subtotal - $totalCost, 2);
        $profitRate = $subtotal > 0 ? round($profit / $subtotal, 4) : 0;

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_image' => $product->image_url,
            'quantity' => $quantity,
            'sale_price' => round($salePrice, 2),
            'purchase_price' => $purchasePrice,
            'shipping_cost' => $shippingCost,
            'packaging_cost' => $packagingCost,
            'platform_fee' => $platformFee,
            'commission_amount' => $commissionAmount,
            'tax_amount' => $taxAmount,
            'other_cost' => $otherCost,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'subtotal' => $subtotal,
            'profit' => $profit,
            'profit_rate' => $profitRate,
        ];
    }
}
