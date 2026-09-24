<?php

namespace App\Services;

use App\Models\KpExam;
use App\Models\KpExamInvitation;
use App\Models\KpExamInvitationSignatory;
use App\Models\KpDocumentSignature;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class KpExamInvitationService
{
    public function __construct(
        private readonly KpDocumentSignatureService $signatureService,
        private readonly KpOfficialIdentityResolver $identityResolver,
    ) {}

    public function createOrUpdate(KpExam $exam, User $actor, ?KpExamInvitationSignatory $signatory = null): KpExamInvitation
    {
        $signatory ??= $this->activeSignatory();
        $invitation = KpExamInvitation::firstOrNew(['kp_exam_id' => $exam->id]);

        if (! $invitation->exists) {
            $invitation->letter_number = $this->nextLetterNumber($exam);
            $invitation->verification_code = Str::upper(Str::random(12));
        }

        $invitation->fill([
            ...$this->resolvedSignatorySnapshot($signatory),
            'status' => 'published',
            'document_version' => (int) ($invitation->document_version ?: 1),
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        $this->signatureService->replace(
            KpDocumentSignature::DOCUMENT_INVITATION,
            $invitation->id,
            (int) $invitation->document_version,
            $this->signers($invitation, $actor),
            $actor,
        );

        return $invitation->fresh(['exam.assignment.student.user', 'exam.assignment.period', 'exam.assignment.place', 'exam.supervisor.user', 'exam.examiners.user', 'exam.examiner.user', 'signatures']);
    }

    public function rebuild(KpExamInvitation $invitation, User $actor, string $reason): KpExamInvitation
    {
        $signatory = $this->activeSignatory();
        $invitation->fill([
            'verification_code' => Str::upper(Str::random(12)),
            ...$this->resolvedSignatorySnapshot($signatory),
            'document_version' => ((int) ($invitation->document_version ?: 1)) + 1,
            'last_rebuild_reason' => $reason,
            'rebuilt_by' => $actor->id,
            'rebuilt_at' => now(),
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        $this->signatureService->replace(
            KpDocumentSignature::DOCUMENT_INVITATION,
            $invitation->id,
            (int) $invitation->document_version,
            $this->signers($invitation, $actor),
            $actor,
            $reason,
        );

        return $invitation->fresh(['signatures']);
    }

    public function activeSignatory(): KpExamInvitationSignatory
    {
        $signatory = KpExamInvitationSignatory::active();

        if (! $signatory) {
            throw new \RuntimeException('Pejabat penandatangan undangan sidang belum diatur.');
        }

        return $signatory;
    }

    public function saveActiveSignatory(array $data, User $actor): KpExamInvitationSignatory
    {
        $coordinator = $this->identityResolver->resolve($data['coordinator_lecturer_id'] ?? null, $data['coordinator_name'], $data['coordinator_nuptk'] ?? null);
        $headProgram = $this->identityResolver->resolve($data['head_program_lecturer_id'] ?? null, $data['head_program_name'], $data['head_program_nuptk'] ?? null);
        $dean = $this->identityResolver->resolve($data['dean_lecturer_id'] ?? null, $data['dean_name'], $data['dean_nuptk'] ?? null);

        KpExamInvitationSignatory::query()->where('is_active', true)->update([
            'is_active' => false,
            'effective_end_date' => now()->toDateString(),
        ]);

        return KpExamInvitationSignatory::create([
            'coordinator_lecturer_id' => $coordinator['lecturer_id'],
            'coordinator_name' => $coordinator['name'],
            'coordinator_nuptk' => $coordinator['nuptk'],
            'head_program_lecturer_id' => $headProgram['lecturer_id'],
            'head_program_name' => $headProgram['name'],
            'head_program_nuptk' => $headProgram['nuptk'],
            'dean_lecturer_id' => $dean['lecturer_id'],
            'dean_name' => $dean['name'],
            'dean_nuptk' => $dean['nuptk'],
            'effective_start_date' => $data['effective_start_date'] ?? now()->toDateString(),
            'is_active' => true,
            'updated_by' => $actor->id,
        ]);
    }

    private function resolvedSignatorySnapshot(KpExamInvitationSignatory $signatory): array
    {
        $coordinator = $this->identityResolver->resolve($signatory->coordinator_lecturer_id, $signatory->coordinator_name, $signatory->coordinator_nuptk);
        $headProgram = $this->identityResolver->resolve($signatory->head_program_lecturer_id, $signatory->head_program_name, $signatory->head_program_nuptk);
        $dean = $this->identityResolver->resolve($signatory->dean_lecturer_id, $signatory->dean_name, $signatory->dean_nuptk);

        $signatory->forceFill([
            'coordinator_lecturer_id' => $coordinator['lecturer_id'],
            'coordinator_name' => $coordinator['name'],
            'coordinator_nuptk' => $coordinator['nuptk'],
            'head_program_lecturer_id' => $headProgram['lecturer_id'],
            'head_program_name' => $headProgram['name'],
            'head_program_nuptk' => $headProgram['nuptk'],
            'dean_lecturer_id' => $dean['lecturer_id'],
            'dean_name' => $dean['name'],
            'dean_nuptk' => $dean['nuptk'],
        ]);
        if ($signatory->isDirty()) {
            $signatory->save();
        }

        return [
            'coordinator_name' => $coordinator['name'],
            'coordinator_nuptk' => $coordinator['nuptk'],
            'head_program_name' => $headProgram['name'],
            'head_program_nuptk' => $headProgram['nuptk'],
            'dean_name' => $dean['name'],
            'dean_nuptk' => $dean['nuptk'],
        ];
    }

    public function nextLetterNumber(KpExam $exam): string
    {
        $year = $exam->exam_date?->format('Y') ?: now()->format('Y');
        $month = $this->roman((int) ($exam->exam_date?->format('n') ?: now()->format('n')));
        $next = KpExamInvitation::whereYear('created_at', $year)->count() + 1;

        return str_pad((string) $next, 3, '0', STR_PAD_LEFT).'/UND-KP/FF-UBP/'.$month.'/'.$year;
    }

    public function verificationUrl(KpExamInvitation $invitation): string
    {
        return URL::route('exam-invitations.verify', $invitation->verification_code);
    }

    public function wordResponse(KpExamInvitation $invitation): Response
    {
        $filename = 'undangan-sidang-kp-'.$invitation->kp_exam_id.'.doc';
        $html = view('exam-invitations.letter-word', [
            'invitation' => $invitation,
            'verificationUrl' => $this->verificationUrl($invitation),
            'signatureQrSrcs' => $this->signatureQrSources($invitation),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function pdfResponse(KpExamInvitation $invitation): Response
    {
        $pdf = Pdf::loadView('exam-invitations.letter-pdf', [
            'invitation' => $invitation,
            'verificationUrl' => $this->verificationUrl($invitation),
            'logoSrc' => $this->fileDataUri(public_path('images/logo-ubp-karawang.png'), 'image/png'),
            'qrSrc' => 'data:image/svg+xml;base64,'.base64_encode($this->qrSvg($invitation)),
            'signatureQrSrcs' => $this->signatureQrSources($invitation),
        ])->setPaper('a4', 'portrait')->setOption([
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 120,
            'isRemoteEnabled' => false,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="undangan-sidang-kp-'.$invitation->kp_exam_id.'.pdf"',
        ]);
    }

    public function qrSvg(KpExamInvitation $invitation): string
    {
        $payload = sha1($this->verificationUrl($invitation));
        $size = 29;
        $cell = 6;
        $pad = 4;
        $svgSize = ($size + ($pad * 2)) * $cell;
        $rects = [];

        $finder = function (int $x, int $y) use (&$rects, $cell, $pad): void {
            for ($row = 0; $row < 7; $row++) {
                for ($col = 0; $col < 7; $col++) {
                    $edge = $row === 0 || $row === 6 || $col === 0 || $col === 6;
                    $inner = $row >= 2 && $row <= 4 && $col >= 2 && $col <= 4;
                    if ($edge || $inner) {
                        $rects[] = '<rect x="'.(($x + $col + $pad) * $cell).'" y="'.(($y + $row + $pad) * $cell).'" width="'.$cell.'" height="'.$cell.'"/>';
                    }
                }
            }
        };

        $finder(0, 0);
        $finder($size - 7, 0);
        $finder(0, $size - 7);

        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if (($row < 8 && $col < 8) || ($row < 8 && $col > $size - 9) || ($row > $size - 9 && $col < 8)) {
                    continue;
                }

                $index = ($row * $size + $col) % strlen($payload);
                $value = hexdec($payload[$index]);
                if ((($value + $row + ($col * 3)) % 5) < 2) {
                    $rects[] = '<rect x="'.(($col + $pad) * $cell).'" y="'.(($row + $pad) * $cell).'" width="'.$cell.'" height="'.$cell.'"/>';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$svgSize.' '.$svgSize.'" width="'.$svgSize.'" height="'.$svgSize.'"><rect width="100%" height="100%" fill="#fff"/><g fill="#0f172a">'.implode('', $rects).'</g></svg>';
    }

    private function fileDataUri(string $path, string $mime): string
    {
        if (! is_file($path)) {
            return '';
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    private function signers(KpExamInvitation $invitation, User $actor): array
    {
        $invitation->loadMissing('exam.assignment.student.user');
        $metadata = [
            'document_number' => $invitation->letter_number,
            'student_name' => $invitation->exam?->assignment?->student?->user?->name,
            'student_nim' => $invitation->exam?->assignment?->student?->nim,
        ];

        return [
            ['key' => 'coordinator', 'user_id' => $actor->id, 'name' => $invitation->coordinator_name, 'identifier' => $invitation->coordinator_nuptk, 'role' => 'Koordinator Sidang', 'metadata' => $metadata],
            ['key' => 'head_program', 'name' => $invitation->head_program_name, 'identifier' => $invitation->head_program_nuptk, 'role' => 'Ketua Program Studi Farmasi', 'metadata' => $metadata],
            ['key' => 'dean', 'name' => $invitation->dean_name, 'identifier' => $invitation->dean_nuptk, 'role' => 'Dekan Fakultas Farmasi', 'metadata' => $metadata],
        ];
    }

    private function signatureQrSources(KpExamInvitation $invitation): array
    {
        $invitation->loadMissing('signatures');

        return $invitation->signatures
            ->where('status', 'active')
            ->where('version', (int) $invitation->document_version)
            ->mapWithKeys(fn ($signature) => [$signature->id => $this->signatureService->dataUri($signature)])
            ->all();
    }

    private function roman(int $month): string
    {
        return [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month] ?? 'I';
    }
}
