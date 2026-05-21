<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKpAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'internal_supervisor_id' => ['nullable', 'exists:lecturers,id'],
            'field_supervisor_id' => ['nullable', 'exists:field_supervisors,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
