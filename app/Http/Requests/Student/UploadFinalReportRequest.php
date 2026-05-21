<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UploadFinalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('mahasiswa') ?? false;
    }

    public function rules(): array
    {
        return [
            'report_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
