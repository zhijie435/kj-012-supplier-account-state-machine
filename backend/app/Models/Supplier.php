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
    use HasFactory, SoftDeletes, HasStateMachine;

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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SupplierAccountStatus::PENDING->value);
    }

    public function scopeVerifying(Builder $query): Builder
    {
        return $query->where('status', SupplierAccountStatus::VERIFYING->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SupplierAccountStatus::ACTIVE->value);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', SupplierAccountStatus::SUSPENDED->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', SupplierAccountStatus::REJECTED->value);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', SupplierAccountStatus::CANCELLED->value);
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
        return $this->stateMachine()->allowedTransitions();
    }
}
