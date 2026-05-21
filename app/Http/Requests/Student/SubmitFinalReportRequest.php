<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFinalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('mahasiswa') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
