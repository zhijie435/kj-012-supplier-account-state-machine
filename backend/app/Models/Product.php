<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'supplier_id',
        'category',
        'unit',
        'sale_price',
        'weight',
        'description',
        'image_url',
        'stock',
        'warning_stock',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'stock' => 'integer',
            'warning_stock' => 'integer',
            'status' => 'integer',
        ];
    }

    public function costs()
    {
        return $this->hasMany(ProductCost::class)->orderBy('effective_date', 'desc');
    }

    public function activeCost()
    {
        return $this->hasOne(ProductCost::class)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', now());
            })
            ->orderBy('effective_date', 'desc');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
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
        return $query->where('status', 1);
    }

    public function scopeOfSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function getPriceAttribute()
    {
        return $this->attributes['price'] ?? $this->attributes['sale_price'] ?? 0;
    }

    public function getCostAttribute()
    {
        $activeCosts = $this->costs()
            ->where('is_active', 1)
            ->whereDate('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', now());
            })
            ->get();

        return $activeCosts->sum('total_cost');
    }

    public function getTotalCostAttribute()
    {
        return $this->cost;
    }

    public function getGrossMarginAttribute()
    {
        $price = (float) ($this->price ?? 0);
        $cost = (float) ($this->cost ?? 0);
        if ($price <= 0) {
            return 0;
        }

        return round(($price - $cost) / $price, 4);
    }
}
