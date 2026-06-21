<?php

namespace App\Http\Requests;

use App\Enums\SupplierAccountStatus;
use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isUpdate() ? 'sometimes|required' : 'required';
        $statusValues = array_column(SupplierAccountStatus::cases(), 'value');

        return [
            'name' => [$required, 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'business_license' => ['nullable', 'string', 'max:255'],
            'contact_person' => [$required, 'string', 'max:100'],
            'phone' => [$required, 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:'.implode(',', $statusValues)],
            'remark' => ['nullable', 'string'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'export_license' => ['nullable', 'string', 'max:100'],
            'import_export_code' => ['nullable', 'string', 'max:100'],
            'certifications' => ['nullable', 'array'],
            'serviced_markets' => ['nullable', 'array'],
            'is_cross_border' => ['nullable', 'boolean'],
        ];
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }
}
