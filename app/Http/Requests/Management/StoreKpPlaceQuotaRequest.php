<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpPlaceQuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp']) ?? false;
    }

    public function rules(): array
    {
        return [
            'kp_period_id' => ['required', 'exists:kp_periods,id'],
            'kp_place_id' => [
                'required',
                'exists:kp_places,id',
                Rule::unique('kp_place_quotas', 'kp_place_id')->where(fn ($query) => $query->where('kp_period_id', $this->input('kp_period_id'))),
            ],
            'quota' => ['required', 'integer', 'min:0'],
            'is_open' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
