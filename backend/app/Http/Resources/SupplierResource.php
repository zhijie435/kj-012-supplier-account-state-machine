<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'business_license' => $this->business_license,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'credit_limit' => $this->credit_limit,
            'balance' => $this->balance,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'remark' => $this->remark,
            'country_code' => $this->country_code,
            'tax_id' => $this->tax_id,
            'export_license' => $this->export_license,
            'import_export_code' => $this->import_export_code,
            'certifications' => $this->certifications,
            'serviced_markets' => $this->serviced_markets,
            'is_cross_border' => $this->is_cross_border,
            'verifying_at' => $this->verifying_at,
            'activated_at' => $this->activated_at,
            'suspended_at' => $this->suspended_at,
            'rejected_at' => $this->rejected_at,
            'cancelled_at' => $this->cancelled_at,
            'products_count' => $this->whenCounted('products'),
            'orders_count' => $this->whenCounted('orders'),
            'allowed_transitions' => $this->when(
                $request->user()->can('supplier.edit'),
                fn () => array_map(
                    fn ($status) => ['value' => $status->value, 'label' => $status->label()],
                    $this->allowed_transitions
                )
            ),
            'status_logs' => SupplierAccountStatusLogResource::collection($this->whenLoaded('statusLogs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
