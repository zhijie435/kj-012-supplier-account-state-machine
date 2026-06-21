<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCost;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductCostService
{
    public function calculateUnitCost(array $costData): float
    {
        $purchasePrice = $costData['purchase_price'] ?? 0;
        $shippingCost = $costData['shipping_cost'] ?? 0;
        $packagingCost = $costData['packaging_cost'] ?? 0;
        $platformFee = $costData['platform_fee'] ?? 0;
        $commissionAmount = $costData['commission_amount'] ?? 0;
        $taxAmount = $costData['tax_amount'] ?? 0;
        $otherCost = $costData['other_cost'] ?? 0;

        return round(
            $purchasePrice + $shippingCost + $packagingCost +
            $platformFee + $commissionAmount + $taxAmount + $otherCost,
            2
        );
    }

    public function calculateCommission(float $salePrice, float $commissionRate): float
    {
        if ($commissionRate <= 0) {
            return 0;
        }
        return round($salePrice * ($commissionRate / 100), 2);
    }

    public function calculateTax(float $baseAmount, float $taxRate): float
    {
        if ($taxRate <= 0) {
            return 0;
        }
        return round($baseAmount * ($taxRate / 100), 2);
    }

    public function calculateProfit(float $salePrice, float $totalCost): float
    {
        return round($salePrice - $totalCost, 2);
    }

    public function calculateProfitRate(float $salePrice, float $totalCost): float
    {
        if ($salePrice <= 0) {
            return 0;
        }
        $profit = $salePrice - $totalCost;
        return round(($profit / $salePrice) * 100, 2);
    }

    public function previewCost(int $productId, array $costData): array
    {
        $product = Product::findOrFail($productId);
        $salePrice = $costData['sale_price'] ?? $product->sale_price;

        $commissionRate = $costData['commission_rate'] ?? 0;
        $taxRate = $costData['tax_rate'] ?? 0;

        $commissionAmount = $this->calculateCommission($salePrice, $commissionRate);
        $taxAmount = $this->calculateTax($costData['purchase_price'] ?? 0, $taxRate);

        $costData['commission_amount'] = $commissionAmount;
        $costData['tax_amount'] = $taxAmount;

        $totalCost = $this->calculateUnitCost($costData);
        $profit = $this->calculateProfit($salePrice, $totalCost);
        $profitRate = $this->calculateProfitRate($salePrice, $totalCost);

        return [
            'product_id' => $productId,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'sale_price' => round($salePrice, 2),
            'purchase_price' => round($costData['purchase_price'] ?? 0, 2),
            'shipping_cost' => round($costData['shipping_cost'] ?? 0, 2),
            'packaging_cost' => round($costData['packaging_cost'] ?? 0, 2),
            'platform_fee' => round($costData['platform_fee'] ?? 0, 2),
            'commission_rate' => round($commissionRate, 2),
            'commission_amount' => $commissionAmount,
            'tax_rate' => round($taxRate, 2),
            'tax_amount' => $taxAmount,
            'other_cost' => round($costData['other_cost'] ?? 0, 2),
            'total_cost' => $totalCost,
            'profit' => $profit,
            'profit_rate' => $profitRate,
        ];
    }

    public function createProductCost(int $productId, array $data, int $userId = null): ProductCost
    {
        $product = Product::findOrFail($productId);

        DB::beginTransaction();
        try {
            $salePrice = $data['sale_price'] ?? $product->sale_price;
            $commissionRate = $data['commission_rate'] ?? 0;
            $taxRate = $data['tax_rate'] ?? 0;

            $commissionAmount = $this->calculateCommission($salePrice, $commissionRate);
            $taxAmount = $this->calculateTax($data['purchase_price'] ?? 0, $taxRate);

            $costData = [
                'product_id' => $productId,
                'purchase_price' => $data['purchase_price'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'packaging_cost' => $data['packaging_cost'] ?? 0,
                'platform_fee' => $data['platform_fee'] ?? 0,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'other_cost' => $data['other_cost'] ?? 0,
                'profit_margin' => $data['profit_margin'] ?? 0,
                'effective_date' => $data['effective_date'],
                'expiry_date' => $data['expiry_date'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'remark' => $data['remark'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ];

            $costData['total_cost'] = $this->calculateUnitCost($costData);

            if (!empty($data['is_active'])) {
                ProductCost::where('product_id', $productId)
                    ->where('is_active', true)
                    ->where(function ($q) use ($data) {
                        $q->whereNull('expiry_date')
                            ->orWhereDate('expiry_date', '>=', $data['effective_date']);
                    })
                    ->update(['expiry_date' => $data['effective_date']]);
            }

            $productCost = ProductCost::create($costData);

            DB::commit();
            return $productCost->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateProductCost(ProductCost $productCost, array $data, int $userId = null): ProductCost
    {
        DB::beginTransaction();
        try {
            $product = $productCost->product;
            $salePrice = $data['sale_price'] ?? $product->sale_price;
            $commissionRate = $data['commission_rate'] ?? $productCost->commission_rate;
            $taxRate = $data['tax_rate'] ?? $productCost->tax_rate;

            $commissionAmount = $this->calculateCommission($salePrice, $commissionRate);
            $taxAmount = $this->calculateTax($data['purchase_price'] ?? $productCost->purchase_price, $taxRate);

            $updateData = [
                'purchase_price' => $data['purchase_price'] ?? $productCost->purchase_price,
                'shipping_cost' => $data['shipping_cost'] ?? $productCost->shipping_cost,
                'packaging_cost' => $data['packaging_cost'] ?? $productCost->packaging_cost,
                'platform_fee' => $data['platform_fee'] ?? $productCost->platform_fee,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'other_cost' => $data['other_cost'] ?? $productCost->other_cost,
                'profit_margin' => $data['profit_margin'] ?? $productCost->profit_margin,
                'effective_date' => $data['effective_date'] ?? $productCost->effective_date,
                'expiry_date' => $data['expiry_date'] ?? $productCost->expiry_date,
                'is_active' => $data['is_active'] ?? $productCost->is_active,
                'remark' => $data['remark'] ?? $productCost->remark,
                'updated_by' => $userId,
            ];

            $updateData['total_cost'] = $this->calculateUnitCost($updateData);

            $productCost->update($updateData);

            DB::commit();
            return $productCost->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveCost(int $productId, $date = null): ?ProductCost
    {
        return ProductCost::where('product_id', $productId)
            ->active()
            ->effectiveAt($date)
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    public function buildOrderItemCostSnapshot(Product $product, int $quantity, float $salePrice, $date = null): array
    {
        $cost = $this->getActiveCost($product->id, $date);

        if (!$cost) {
            $unitCost = 0;
            $purchasePrice = 0;
            $shippingCost = 0;
            $packagingCost = 0;
            $platformFee = 0;
            $commissionAmount = 0;
            $taxAmount = 0;
            $otherCost = 0;
        } else {
            $purchasePrice = $cost->purchase_price;
            $shippingCost = $cost->shipping_cost;
            $packagingCost = $cost->packaging_cost;
            $platformFee = $cost->platform_fee;
            $commissionAmount = $this->calculateCommission($salePrice, $cost->commission_rate);
            $taxAmount = $this->calculateTax($purchasePrice, $cost->tax_rate);
            $otherCost = $cost->other_cost;

            $unitCost = $this->calculateUnitCost([
                'purchase_price' => $purchasePrice,
                'shipping_cost' => $shippingCost,
                'packaging_cost' => $packagingCost,
                'platform_fee' => $platformFee,
                'commission_amount' => $commissionAmount,
                'tax_amount' => $taxAmount,
                'other_cost' => $otherCost,
            ]);
        }

        $subtotal = round($salePrice * $quantity, 2);
        $totalCost = round($unitCost * $quantity, 2);
        $profit = $this->calculateProfit($subtotal, $totalCost);
        $profitRate = $this->calculateProfitRate($subtotal, $totalCost);

        return [
            'product_id' => $product->id,
            'product_cost_id' => $cost?->id,
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
