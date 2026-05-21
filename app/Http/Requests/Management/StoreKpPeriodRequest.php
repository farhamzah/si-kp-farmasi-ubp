<?php

namespace App\Http\Requests\Management;

use App\Models\KpPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', Rule::in(['ganjil', 'genap', 'antara'])],
            'registration_start_at' => ['nullable', 'date'],
            'registration_end_at' => ['nullable', 'date', 'after:registration_start_at'],
            'document_verification_start_at' => ['nullable', 'date'],
            'document_verification_end_at' => ['nullable', 'date', 'after:document_verification_start_at'],
            'selection_start_at' => ['nullable', 'date'],
            'selection_end_at' => ['nullable', 'date', 'after:selection_start_at'],
            'kp_start_date' => ['nullable', 'date'],
            'kp_end_date' => ['nullable', 'date', 'after_or_equal:kp_start_date'],
            'status' => ['required', Rule::in(KpPeriod::STATUSES)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
