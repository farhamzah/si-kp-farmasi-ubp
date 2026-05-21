<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UploadKpDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('mahasiswa') ?? false;
    }

    public function rules(): array
    {
        $requirement = $this->route('requirement');
        $types = $requirement?->allowedFileTypesArray() ?: ['pdf', 'jpg', 'jpeg', 'png'];
        $maxKb = ($requirement?->max_file_size_mb ?: 5) * 1024;

        return [
            'document' => ['required', 'file', 'mimes:'.implode(',', $types), 'max:'.$maxKb],
        ];
    }
}
