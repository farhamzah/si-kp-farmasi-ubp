<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpDocumentRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp']) ?? false;
    }

    public function rules(): array
    {
        return [
            'kp_period_id' => ['required', 'exists:kp_periods,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['nullable', 'boolean'],
            'allowed_file_types' => ['required', 'string', 'max:255'],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }
}
