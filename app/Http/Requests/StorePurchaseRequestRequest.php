<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_type_id' => ['required', 'exists:purchase_types,id'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'employee_id' => ['required', 'exists:employees,id'],
            'expected_date' => ['nullable', 'date'],
            'division_id' => ['required', 'exists:divisions,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.price_estimate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
