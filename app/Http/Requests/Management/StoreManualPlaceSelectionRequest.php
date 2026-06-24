<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualPlaceSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kp_registration_id' => ['required', 'exists:kp_registrations,id'],
            'kp_place_quota_id' => ['required', 'exists:kp_place_quotas,id'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
