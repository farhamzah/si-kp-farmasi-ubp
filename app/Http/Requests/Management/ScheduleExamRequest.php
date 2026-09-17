<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ScheduleExamRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('chair_lecturer_id')) {
            return;
        }

        $examRequest = $this->route('examRequest');
        $exam = $this->route('exam');
        $internalSupervisorId = $examRequest?->assignment?->internal_supervisor_id
            ?? $exam?->assignment?->internal_supervisor_id
            ?? $exam?->supervisor_id;

        if ($internalSupervisorId) {
            $this->merge(['chair_lecturer_id' => $internalSupervisorId]);
        }
    }

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
            'allow_backdate' => ['nullable', 'boolean'],
            'backdate_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $examRequest = $this->route('examRequest');
            $exam = $this->route('exam');
            $internalSupervisorId = (int) ($examRequest?->assignment?->internal_supervisor_id
                ?? $exam?->assignment?->internal_supervisor_id
                ?? $exam?->supervisor_id);
            $chairId = (int) $this->chair_lecturer_id;
            $examinerIds = array_map('intval', $this->input('examiner_ids', []));

            if ($chairId && $chairId !== $internalSupervisorId && ! in_array($chairId, $examinerIds, true)) {
                $validator->errors()->add('chair_lecturer_id', 'Ketua sidang pengganti harus dipilih dari daftar penguji sidang.');
            }

            if (! $validator->errors()->has('exam_date') && $this->filled('exam_date')) {
                $isBackdated = Carbon::parse($this->input('exam_date'))->startOfDay()->lt(today());

                if ($isBackdated && ! $this->boolean('allow_backdate')) {
                    $validator->errors()->add('allow_backdate', 'Konfirmasi penjadwalan tanggal sebelumnya wajib dicentang.');
                }

                if ($isBackdated && mb_strlen(trim((string) $this->input('backdate_reason'))) < 10) {
                    $validator->errors()->add('backdate_reason', 'Jelaskan alasan penjadwalan tanggal sebelumnya minimal 10 karakter.');
                }
            }
        }];
    }
}
