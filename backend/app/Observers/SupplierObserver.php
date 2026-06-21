<?php

namespace App\Observers;

use App\Models\Supplier;
use App\Models\SupplierAccountStatusLog;
use Illuminate\Support\Facades\Auth;

class SupplierObserver
{
    public function created(Supplier $supplier): void
    {
        $status = $supplier->getStatusEnum();

        SupplierAccountStatusLog::create([
            'supplier_id' => $supplier->id,
            'from_status' => null,
            'to_status' => $status,
            'remark' => '供应商账户创建',
            'operated_by' => Auth::id(),
        ]);
    }
}
