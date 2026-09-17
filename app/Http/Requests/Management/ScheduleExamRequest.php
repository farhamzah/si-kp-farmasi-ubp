<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleExamRequest extends FormRequest
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
            'chair_lecturer_id' => ['required', 'integer', 'exists:lecturers,id'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'mode' => ['required', Rule::in(['offline', 'online', 'hybrid'])],
            'room' => ['required_if:mode,offline,hybrid', 'nullable', 'string', 'max:255'],
            'meeting_link' => ['required_if:mode,online,hybrid', 'nullable', 'url', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($this->filled('chair_lecturer_id') && ! in_array((int) $this->chair_lecturer_id, array_map('intval', $this->input('examiner_ids', [])), true)) {
                $validator->errors()->add('chair_lecturer_id', 'Ketua sidang harus dipilih dari daftar penguji sidang.');
            }
        }];
    }
}
