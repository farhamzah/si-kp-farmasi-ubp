<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class AssignFieldSupervisorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'field_supervisor_id' => ['required', 'exists:field_supervisors,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
