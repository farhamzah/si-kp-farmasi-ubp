<?php

namespace App\Http\Requests\Management;

use App\Models\KpExternalDocumentReference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExternalDocumentReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'external_document_id' => ['nullable', 'string', 'max:120'],
            'external_document_number' => ['nullable', 'string', 'max:120'],
            'external_status' => ['required', 'string', Rule::in(KpExternalDocumentReference::STATUSES)],
            'reference_url' => ['nullable', 'string', 'max:500', 'url:http,https'],
            'last_error' => ['nullable', 'string', 'max:2000'],
            'synced_at' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $referenceUrl = $this->input('reference_url');

                if ($referenceUrl && KpExternalDocumentReference::hasUnsafeReferenceUrl($referenceUrl)) {
                    $validator->errors()->add('reference_url', 'Reference URL tidak boleh berisi token, secret, signed URL, path storage/private, atau path file lokal.');
                }

                foreach (['external_document_id', 'external_document_number', 'last_error'] as $field) {
                    $value = $this->input($field);

                    if ($value && $this->containsSensitiveMarker((string) $value)) {
                        $validator->errors()->add($field, 'Field ini tidak boleh menyimpan token, password, secret, signed URL, path storage/private, atau path file lokal.');
                    }
                }
            },
        ];
    }

    public function sanitizedData(): array
    {
        return [
            'external_document_id' => $this->nullableString('external_document_id'),
            'external_document_number' => $this->nullableString('external_document_number'),
            'external_status' => $this->string('external_status')->toString(),
            'reference_url' => $this->nullableString('reference_url'),
            'last_error' => $this->nullableString('last_error'),
            'synced_at' => $this->input('synced_at'),
        ];
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }

    private function containsSensitiveMarker(string $value): bool
    {
        $value = strtolower(trim($value));

        foreach (KpExternalDocumentReference::SENSITIVE_REFERENCE_URL_MARKERS as $marker) {
            if (str_contains($value, $marker)) {
                return true;
            }
        }

        return (bool) preg_match('/^[a-z]:[\\\\\/]/i', $value);
    }
}
