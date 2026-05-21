<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class ReviewKpDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp']) ?? false;
    }

    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
