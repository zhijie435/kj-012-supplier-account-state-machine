<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_cost_id',
        'product_name',
        'product_sku',
        'product_image',
        'quantity',
        'sale_price',
        'purchase_price',
        'shipping_cost',
        'packaging_cost',
        'platform_fee',
        'commission_amount',
        'tax_amount',
        'other_cost',
        'unit_cost',
        'total_cost',
        'subtotal',
        'profit',
        'profit_rate',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sale_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'packaging_cost' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'profit' => 'decimal:2',
            'profit_rate' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productCost()
    {
        return $this->belongsTo(ProductCost::class);
    }

    public function settlementItems()
    {
        return $this->hasMany(SettlementItem::class);
    }

    public function calculateProfit()
    {
        $this->profit = $this->subtotal - $this->total_cost;
        if ($this->subtotal > 0) {
            $this->profit_rate = round(($this->profit / $this->subtotal) * 100, 2);
        } else {
            $this->profit_rate = 0;
        }
        return $this;
    }
}
