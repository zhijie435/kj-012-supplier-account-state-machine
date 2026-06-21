<?php

namespace App\Observers;

use App\Enums\SupplierAccountStatus;
use App\Models\Supplier;
use App\Models\SupplierAccountStatusLog;
use Illuminate\Support\Facades\Auth;

class SupplierObserver
{
    public function updated(Supplier $supplier): void
    {
        if (! $supplier->wasChanged('status')) {
            return;
        }

        $originalRawValue = $supplier->getRawOriginal('status');
        $originalStatus = $originalRawValue instanceof SupplierAccountStatus
            ? $originalRawValue
            : SupplierAccountStatus::tryFrom($originalRawValue);
        $newStatus = $supplier->getStatusEnum();

        if ($originalStatus && $originalStatus !== $newStatus) {
            SupplierAccountStatusLog::create([
                'supplier_id' => $supplier->id,
                'from_status' => $originalStatus->value,
                'to_status' => $newStatus->value,
                'remark' => $supplier->remark,
                'operated_by' => Auth::id() ?? $supplier->operated_by,
            ]);
        }
    }

    public function created(Supplier $supplier): void
    {
        $status = $supplier->getStatusEnum();

        SupplierAccountStatusLog::create([
            'supplier_id' => $supplier->id,
            'from_status' => null,
            'to_status' => $status->value,
            'remark' => '供应商账户创建',
            'operated_by' => Auth::id(),
        ]);
    }
}
