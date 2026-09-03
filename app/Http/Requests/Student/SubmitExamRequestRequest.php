<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('mahasiswa') ?? false;
    }

    public function rules(): array
    {
        return [
            'request_note' => ['nullable', 'string', 'max:2000'],
            'payment_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120', 'required_without:payment_proof_url'],
            'payment_proof_url' => ['nullable', 'url:http,https', 'max:2048', 'required_without:payment_proof'],
            'payment_proof_label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_proof.required_without' => 'Upload bukti pembayaran KP atau tempel link Drive bukti pembayaran.',
            'payment_proof.mimes' => 'Bukti pembayaran harus PDF, JPG, JPEG, atau PNG.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5MB.',
            'payment_proof_url.required_without' => 'Upload bukti pembayaran KP atau tempel link Drive bukti pembayaran.',
            'payment_proof_url.url' => 'Link bukti pembayaran harus berupa URL http atau https.',
        ];
    }
}
