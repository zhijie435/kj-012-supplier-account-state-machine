<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Settlement extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_SUPPLIER = 'supplier';
    const TYPE_DISTRIBUTOR = 'distributor';

    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_SETTLED = 2;
    const STATUS_REJECTED = 3;

    protected $fillable = [
        'settlement_no',
        'period_type',
        'party_id',
        'party_name',
        'start_date',
        'end_date',
        'order_count',
        'total_sales_amount',
        'total_cost_amount',
        'total_commission_amount',
        'total_refund_amount',
        'settlement_amount',
        'status',
        'settled_at',
        'settled_by',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'order_count' => 'integer',
            'total_sales_amount' => 'decimal:2',
            'total_cost_amount' => 'decimal:2',
            'total_commission_amount' => 'decimal:2',
            'total_refund_amount' => 'decimal:2',
            'settlement_amount' => 'decimal:2',
            'status' => 'integer',
            'settled_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(SettlementItem::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'settlement_items', 'settlement_id', 'order_id');
    }

    public function party()
    {
        return $this->belongsTo(User::class, 'party_id');
    }

    public function settler()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('period_type', $type);
    }

    public function scopeOfParty($query, $partyId)
    {
        return $query->where('party_id', $partyId);
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function recalculate()
    {
        $orderCount = 0;
        $totalSales = 0;
        $totalCost = 0;
        $totalCommission = 0;
        $settlementAmount = 0;

        foreach ($this->items as $item) {
            $orderCount++;
            $totalSales += $item->total_sales;
            $totalCost += $item->total_cost;
            $totalCommission += $item->commission_amount;
            $settlementAmount += $item->settlement_amount;
        }

        $this->order_count = $orderCount;
        $this->total_sales_amount = $totalSales;
        $this->total_cost_amount = $totalCost;
        $this->total_commission_amount = $totalCommission;
        $this->settlement_amount = $settlementAmount;

        return $this;
    }
}
