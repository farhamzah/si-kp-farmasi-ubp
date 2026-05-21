<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp']) ?? false;
    }

    public function rules(): array
    {
        return [
            'kp_period_id' => ['required', 'exists:kp_periods,id'],
            'assessor_type' => ['required', Rule::in(['pembimbing_dalam', 'pembimbing_lapangan', 'penguji'])],
            'component_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }
}
