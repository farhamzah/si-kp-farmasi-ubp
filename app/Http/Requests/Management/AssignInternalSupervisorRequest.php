<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class AssignInternalSupervisorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'internal_supervisor_id' => ['required', 'exists:lecturers,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
