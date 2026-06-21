<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_price',
        'shipping_cost',
        'packaging_cost',
        'platform_fee',
        'commission_rate',
        'commission_amount',
        'tax_rate',
        'tax_amount',
        'other_cost',
        'total_cost',
        'profit_margin',
        'effective_date',
        'expiry_date',
        'is_active',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'packaging_cost' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'profit_margin' => 'decimal:2',
            'effective_date' => 'date',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffectiveAt($query, $date = null)
    {
        $date = $date ?: now();
        return $query->whereDate('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', $date);
            });
    }

    public function scopeOfProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
