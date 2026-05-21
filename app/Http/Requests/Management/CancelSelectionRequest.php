<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class CancelSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
