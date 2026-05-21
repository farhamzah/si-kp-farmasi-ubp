<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class UnlockScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp']) ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:2000']];
    }
}
