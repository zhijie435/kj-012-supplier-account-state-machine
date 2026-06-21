<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'sale_price',
        'total_sales',
        'unit_cost',
        'total_cost',
        'profit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sale_price' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $qty = (int) ($model->quantity ?? 1);
            $model->total_sales = round(($model->sale_price ?? 0) * $qty, 2);
            $model->total_cost = round(($model->unit_cost ?? 0) * $qty, 2);
            $model->profit = round($model->total_sales - $model->total_cost, 2);
        });
    }
}
