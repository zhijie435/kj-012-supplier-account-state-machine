<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Settlement extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_ORDER = 'order';

    const TYPE_MONTHLY = 'monthly';

    const TYPE_MANUAL = 'manual';

    const STATUS_PENDING = 'pending';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_SETTLED = 'settled';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'settlement_no',
        'type',
        'settlement_date',
        'order_no',
        'order_count',
        'total_amount',
        'product_cost',
        'platform_fee',
        'other_cost',
        'total_cost',
        'total_profit',
        'profit_rate',
        'supplier_ratio',
        'distributor_ratio',
        'platform_ratio',
        'supplier_share',
        'distributor_share',
        'platform_share',
        'status',
        'remark',
        'created_by',
        'updated_by',
        'settled_by',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'order_count' => 'integer',
            'total_amount' => 'decimal:2',
            'product_cost' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_profit' => 'decimal:2',
            'profit_rate' => 'decimal:4',
            'supplier_ratio' => 'decimal:4',
            'distributor_ratio' => 'decimal:4',
            'platform_ratio' => 'decimal:4',
            'supplier_share' => 'decimal:2',
            'distributor_share' => 'decimal:2',
            'platform_share' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(SettlementItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function settler()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeSettled($query)
    {
        return $query->where('status', self::STATUS_SETTLED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('settlement_date', [$startDate, $endDate]);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CANCELLED]);
    }

    public function canConfirm(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canSettle(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function recalculateTotals()
    {
        $orderCount = 0;
        $totalAmount = 0;
        $productCost = 0;
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($this->items as $item) {
            $orderCount++;
            $totalAmount += $item->total_sales;
            $productCost += $item->total_cost;
            $totalCost += $item->total_cost;
            $totalProfit += $item->profit;
        }

        $this->order_count = $orderCount;
        $this->total_amount = round($totalAmount, 2);
        $this->product_cost = round($productCost, 2);
        $this->other_cost = 0;
        $this->total_cost = round($totalCost, 2);
        $this->total_profit = round($totalProfit, 2);
        $this->profit_rate = $totalAmount > 0 ? round($totalProfit / $totalAmount, 4) : 0;

        $supplierRatio = (float) ($this->supplier_ratio ?? 0.5);
        $distributorRatio = (float) ($this->distributor_ratio ?? 0.2);
        $platformRatio = (float) ($this->platform_ratio ?? 0.3);

        $this->supplier_share = round($totalAmount * $supplierRatio, 2);
        $this->distributor_share = round($totalAmount * $distributorRatio, 2);
        $this->platform_share = round($totalAmount * $platformRatio, 2);

        return $this;
    }
}
