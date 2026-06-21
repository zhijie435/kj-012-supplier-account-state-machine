<?php

namespace App\Models;

use App\Enums\SupplierAccountStatus;
use App\Models\Concerns\HasStateMachine;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'company_name', 'business_license', 'contact_person',
    'phone', 'email', 'address', 'bank_name', 'bank_account',
    'credit_limit', 'balance', 'status', 'remark',
    'country_code', 'tax_id', 'export_license', 'import_export_code',
    'certifications', 'serviced_markets', 'is_cross_border',
    'verifying_at', 'activated_at', 'suspended_at', 'rejected_at',
    'cancelled_at', 'operated_by',
])]
class Supplier extends Model
{
    use HasFactory, HasStateMachine, SoftDeletes;

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'certifications' => 'array',
            'serviced_markets' => 'array',
            'is_cross_border' => 'boolean',
            'status' => SupplierAccountStatus::class,
            'verifying_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SupplierAccountStatusLog::class)->latest();
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operated_by');
    }

    public function scopeCrossBorder(Builder $query): Builder
    {
        return $query->where('is_cross_border', true);
    }

    public function scopeByStatus(Builder $query, SupplierAccountStatus|string $status): Builder
    {
        $value = $status instanceof SupplierAccountStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeStatusIn(Builder $query, array $statuses): Builder
    {
        $values = array_map(
            fn ($status) => $status instanceof SupplierAccountStatus ? $status->value : $status,
            $statuses
        );

        return $query->whereIn('status', $values);
    }

    public function scopeNotTerminal(Builder $query): Builder
    {
        $nonTerminalStatuses = array_filter(
            SupplierAccountStatus::cases(),
            fn (SupplierAccountStatus $status) => ! $status->isTerminal()
        );

        return $this->scopeStatusIn($query, $nonTerminalStatuses);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->getStatusEnum()->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->getStatusEnum()->color();
    }

    public function getAllowedTransitionsAttribute(): array
    {
        return $this->allowedTransitions();
    }
}
