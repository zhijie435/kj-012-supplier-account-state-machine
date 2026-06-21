<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'settlement_id',
        'order_id',
        'order_item_id',
        'order_no',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'sale_price',
        'total_sales',
        'purchase_price',
        'platform_fee',
        'commission_amount',
        'shipping_cost',
        'other_cost',
        'total_cost',
        'settlement_amount',
        'profit',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sale_price' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'settlement_amount' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
