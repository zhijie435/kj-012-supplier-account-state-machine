<?php

namespace App\Models;

use App\Enums\SupplierAccountStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'supplier_id', 'from_status', 'to_status', 'remark', 'operated_by',
])]
class SupplierAccountStatusLog extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'from_status' => SupplierAccountStatus::class,
            'to_status' => SupplierAccountStatus::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operated_by');
    }
}
