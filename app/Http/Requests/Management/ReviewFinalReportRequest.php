<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class ReviewFinalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('pembimbing_dalam') ?? false;
    }

    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
