<?php

namespace App\Services;

use App\Models\KpExam;
use App\Models\KpExamMinute;
use App\Models\KpDocumentSignature;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KpExamMinuteService
{
    public function __construct(
        private readonly KpExamService $examService,
        private readonly KpDocumentSignatureService $signatureService,
        private readonly QrCodeService $qrCodeService,
    ) {}

    public function close(KpExam $exam, User $actor, array $data): KpExamMinute
    {
        if ((int) $exam->chair_lecturer_id !== (int) ($actor->lecturer?->id ?: 0)) {
            abort(403, 'Hanya Ketua Sidang yang dapat menutup sidang dan membuat berita acara.');
        }
        if ($exam->minutes()->exists()) {
            throw ValidationException::withMessages(['minutes' => 'Berita acara sidang ini sudah dibuat.']);
        }
        if ($exam->status === 'dibatalkan') {
            throw ValidationException::withMessages(['minutes' => 'Sidang yang dibatalkan tidak dapat ditutup.']);
        }
        if ($exam->exam_date?->isFuture()) {
            throw ValidationException::withMessages(['minutes' => 'Berita acara baru dapat dibuat pada atau setelah tanggal sidang.']);
        }

        $minute = DB::transaction(function () use ($exam, $actor, $data): KpExamMinute {
            $exam = KpExam::query()->lockForUpdate()->findOrFail($exam->id);
            $ready = $exam->assignment->isAllRequiredScoresSubmitted();

            return KpExamMinute::create([
                'kp_exam_id' => $exam->id,
                'minutes_number' => $exam->minutes_number,
                'chair_lecturer_id' => $exam->chair_lecturer_id,
                'status' => $ready ? 'siap_terbit' : 'menunggu_nilai',
                'result' => $data['result'],
                'actual_start_time' => $data['actual_start_time'],
                'actual_end_time' => $data['actual_end_time'],
                'revision_deadline' => $data['revision_deadline'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attendance' => $data['attendance'] ?? [],
                'verification_code' => Str::upper(Str::random(16)),
                'closed_by' => $actor->id,
                'closed_at' => now(),
            ]);
        });

        if ($exam->status !== 'selesai') {
            $this->examService->completeExam($actor, $exam, $data['notes'] ?? null);
        }

        return $minute->fresh();
    }

    public function syncReadiness(KpExamMinute $minute): KpExamMinute
    {
        if ($minute->status !== 'terbit') {
            $ready = $minute->exam->assignment->isAllRequiredScoresSubmitted();
            $minute->update(['status' => $ready ? 'siap_terbit' : 'menunggu_nilai']);
        }

        return $minute->fresh();
    }

    public function publish(KpExamMinute $minute, User $actor): KpExamMinute
    {
        $minute = $this->syncReadiness($minute);
        if ($minute->status !== 'siap_terbit') {
            throw ValidationException::withMessages(['minutes' => 'Berita acara belum dapat diterbitkan karena masih ada nilai wajib yang belum disubmit.']);
        }

        $minute->exam->load($this->relations());
        $minute->update([
            'status' => 'terbit',
            'published_by' => $actor->id,
            'published_at' => now(),
            'snapshot' => $this->snapshot($minute),
        ]);

        $this->replaceSignatures($minute, $actor);

        return $minute->fresh();
    }

    public function rebuild(KpExamMinute $minute, User $actor, string $reason): KpExamMinute
    {
        if ($minute->status !== 'terbit') {
            throw ValidationException::withMessages(['minutes' => 'Hanya berita acara yang sudah terbit yang dapat dibangun ulang.']);
        }

        $minute->exam->load($this->relations());
        $minute->update([
            'verification_code' => Str::upper(Str::random(16)),
            'document_version' => ((int) ($minute->document_version ?: 1)) + 1,
            'last_rebuild_reason' => $reason,
            'rebuilt_by' => $actor->id,
            'rebuilt_at' => now(),
            'published_by' => $actor->id,
            'published_at' => now(),
            'snapshot' => $this->snapshot($minute),
        ]);
        $this->replaceSignatures($minute, $actor, $reason);

        return $minute->fresh(['signatures']);
    }

    public function verificationUrl(KpExamMinute $minute): string
    {
        return URL::route('exam-minutes.verify', $minute->verification_code);
    }

    public function pdfResponse(KpExamMinute $minute): Response
    {
        $minute->loadMissing(array_merge(['chair.user'], array_map(fn (string $relation): string => 'exam.'.$relation, $this->relations())));
        $pdf = Pdf::loadView('exam-minutes.document-pdf', [
            'minute' => $minute,
            'verificationUrl' => $this->verificationUrl($minute),
            'logoSrc' => $this->fileDataUri(public_path('images/logo-ubp-karawang.png'), 'image/png'),
            'qrSrc' => $this->qrCodeService->pngDataUri($this->verificationUrl($minute)),
            'signatureQrSrcs' => $this->signatureQrSources($minute),
        ])->setPaper('a4', 'portrait')->setOption(['defaultFont' => 'DejaVu Sans', 'dpi' => 120, 'isRemoteEnabled' => false]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="berita-acara-sidang-kp-'.$minute->kp_exam_id.'.pdf"',
        ]);
    }

    public function qrSvg(KpExamMinute $minute): string
    {
        return $this->qrCodeService->svg($this->verificationUrl($minute));
    }

    public function relations(): array
    {
        return ['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.fieldSupervisor.user', 'assignment.scores.component', 'supervisor.user', 'examiner.user', 'examiners.user', 'chair.user'];
    }

    private function snapshot(KpExamMinute $minute): array
    {
        $exam = $minute->exam;
        return [
            'student' => $exam->assignment->student->user->name,
            'nim' => $exam->assignment->student->nim,
            'place' => $exam->assignment->place->name,
            'period' => $exam->assignment->period->name,
            'schedule' => $exam->scheduleLabel(),
            'chair' => lecturer_display_name($exam->chair),
            'examiners' => $exam->examinerNamesLabel(),
            'result' => $minute->resultLabel(),
        ];
    }

    private function replaceSignatures(KpExamMinute $minute, User $actor, ?string $reason = null): void
    {
        $exam = $minute->exam;
        $lecturers = $exam->examiners
            ->when($exam->chair, fn ($items) => $items->prepend($exam->chair))
            ->unique('id')
            ->values();
        $metadata = [
            'document_number' => $minute->minutes_number,
            'student_name' => $exam->assignment?->student?->user?->name,
            'student_nim' => $exam->assignment?->student?->nim,
        ];
        $signers = $lecturers->map(fn ($lecturer) => [
            'key' => 'lecturer_'.$lecturer->id,
            'user_id' => $lecturer->user_id,
            'name' => lecturer_display_name($lecturer),
            'identifier' => $lecturer->nidn_nip ?: $lecturer->employee_number,
            'role' => (int) $lecturer->id === (int) $exam->chair_lecturer_id ? 'Ketua Sidang' : 'Anggota Penguji',
            'metadata' => $metadata,
        ])->all();

        $this->signatureService->replace(
            KpDocumentSignature::DOCUMENT_MINUTE,
            $minute->id,
            (int) $minute->document_version,
            $signers,
            $actor,
            $reason,
        );
    }

    private function signatureQrSources(KpExamMinute $minute): array
    {
        $minute->loadMissing('signatures');

        return $minute->signatures
            ->where('status', 'active')
            ->where('version', (int) $minute->document_version)
            ->mapWithKeys(fn ($signature) => [$signature->id => $this->signatureService->dataUri($signature)])
            ->all();
    }

    private function fileDataUri(string $path, string $mime): string
    {
        return is_file($path) ? 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path)) : '';
    }
}
