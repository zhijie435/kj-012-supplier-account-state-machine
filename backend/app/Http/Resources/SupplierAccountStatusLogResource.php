<?php

namespace App\Http\Resources;

use App\Enums\SupplierAccountStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierAccountStatusLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'from_status' => $this->from_status instanceof SupplierAccountStatus
                ? $this->from_status->value
                : $this->from_status,
            'from_status_label' => $this->from_status?->label(),
            'to_status' => $this->to_status instanceof SupplierAccountStatus
                ? $this->to_status->value
                : $this->to_status,
            'to_status_label' => $this->to_status?->label(),
            'remark' => $this->remark,
            'operated_by' => $this->operated_by,
            'operator' => new UserResource($this->whenLoaded('operator')),
            'created_at' => $this->created_at,
        ];
    }
}
