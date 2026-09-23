<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class CorrectExamExaminersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'koordinator_kp']) ?? false;
    }

    public function rules(): array
    {
        return [
            'examiner_ids' => ['required', 'array', 'min:2', 'max:3'],
            'examiner_ids.*' => ['required', 'integer', 'distinct', 'exists:lecturers,id'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
