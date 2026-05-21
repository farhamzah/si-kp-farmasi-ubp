<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpLogbookCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp', 'pembimbing_dalam']) ?? false;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['internal', 'visible_to_student'])],
        ];
    }
}
