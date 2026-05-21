<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class SaveScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['pembimbing_dalam', 'pembimbing_lapangan', 'penguji']) ?? false;
    }

    public function rules(): array
    {
        return [
            'scores' => ['required', 'array'],
            'scores.*.component_id' => ['required', 'exists:kp_assessment_components,id'],
            'scores.*.score' => ['required', 'numeric', 'min:0', 'max:100'],
            'scores.*.note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
