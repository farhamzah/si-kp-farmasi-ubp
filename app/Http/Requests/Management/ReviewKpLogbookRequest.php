<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class ReviewKpLogbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp', 'pembimbing_lapangan']) ?? false;
    }

    public function rules(): array
    {
        return [
            'validation_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
