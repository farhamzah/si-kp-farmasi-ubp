<?php

namespace App\Http\Requests\Student;

use App\Models\KpPeriod;
use Illuminate\Foundation\Http\FormRequest;

class StoreKpRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('mahasiswa') ?? false;
    }

    public function rules(): array
    {
        return [
            'kp_period_id' => ['required', 'exists:kp_periods,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $period = KpPeriod::find($this->input('kp_period_id'));

            if (! $period || ! $period->isRegistrationOpen()) {
                $validator->errors()->add('kp_period_id', 'Periode KP belum dibuka untuk pendaftaran.');
            }

            if (! $this->user()?->student || ! $this->user()->isProfileComplete()) {
                $validator->errors()->add('profile', 'Lengkapi profil terlebih dahulu sebelum mendaftar KP.');
            }
        });
    }
}
