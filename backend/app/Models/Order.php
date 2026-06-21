<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING_PAYMENT = 0;

    const STATUS_PENDING_SHIPMENT = 1;

    const STATUS_SHIPPED = 2;

    const STATUS_COMPLETED = 3;

    const STATUS_CANCELLED = 4;

    const STATUS_REFUNDING = 5;

    const STATUS_REFUNDED = 6;

    protected $fillable = [
        'order_no',
        'distributor_id',
        'supplier_id',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'total_amount',
        'total_cost',
        'total_profit',
        'discount_amount',
        'shipping_fee',
        'payable_amount',
        'paid_amount',
        'payment_method',
        'paid_at',
        'status',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_profit' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'payable_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'status' => 'integer',
        ];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function distributor()
    {
        return $this->belongsTo(User::class, 'distributor_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function settlements()
    {
        return $this->belongsToMany(Settlement::class, 'settlement_items', 'order_id', 'settlement_id');
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOfDistributor($query, $distributorId)
    {
        return $query->where('distributor_id', $distributorId);
    }

    public function scopeOfSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function isSettled(): bool
    {
        return $this->settlements()->exists();
    }

    public function recalculateCostsAndProfit()
    {
        $totalCost = 0;
        $totalAmount = 0;

        foreach ($this->items as $item) {
            $totalCost += $item->total_cost;
            $totalAmount += $item->subtotal;
        }

        $this->total_amount = $totalAmount;
        $this->total_cost = $totalCost;
        $this->total_profit = $totalAmount - $totalCost;
        $this->payable_amount = $totalAmount - $this->discount_amount + $this->shipping_fee;

        return $this;
    }
}
