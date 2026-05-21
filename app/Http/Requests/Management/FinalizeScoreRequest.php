<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp']) ?? false;
    }

    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:2000']];
    }
}
