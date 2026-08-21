<?php

namespace App\Services;

use App\Models\KpExam;
use App\Models\KpExamInvitation;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class KpExamInvitationService
{
    public function createOrUpdate(KpExam $exam, array $data, User $actor): KpExamInvitation
    {
        $invitation = KpExamInvitation::firstOrNew(['kp_exam_id' => $exam->id]);

        if (! $invitation->exists) {
            $invitation->letter_number = $this->nextLetterNumber($exam);
            $invitation->verification_code = Str::upper(Str::random(12));
        }

        $invitation->fill([
            'coordinator_name' => $data['coordinator_name'],
            'coordinator_nuptk' => $data['coordinator_nuptk'] ?? null,
            'head_program_name' => $data['head_program_name'],
            'head_program_nuptk' => $data['head_program_nuptk'] ?? null,
            'dean_name' => $data['dean_name'],
            'dean_nuptk' => $data['dean_nuptk'] ?? null,
            'status' => 'published',
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        return $invitation->fresh(['exam.assignment.student.user', 'exam.assignment.period', 'exam.assignment.place', 'exam.supervisor.user', 'exam.examiners.user', 'exam.examiner.user']);
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
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function pdfResponse(KpExamInvitation $invitation): Response
    {
        $lines = $this->plainTextLines($invitation);
        $pdf = $this->simplePdf($lines);

        return response($pdf, 200, [
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

    private function plainTextLines(KpExamInvitation $invitation): array
    {
        $exam = $invitation->exam;
        $assignment = $exam->assignment;
        $student = $assignment?->student;

        return [
            'FAKULTAS FARMASI UNIVERSITAS BUANA PERJUANGAN KARAWANG',
            'UNDANGAN SIDANG KERJA PRAKTIK',
            'Nomor: '.$invitation->letter_number,
            '',
            'Yth. Bapak/Ibu Penguji dan Pembimbing Kerja Praktik',
            'di tempat',
            '',
            'Dengan hormat, sehubungan dengan pelaksanaan Sidang Kerja Praktik Program Studi Farmasi, kami mengundang Bapak/Ibu untuk hadir pada:',
            'Nama Mahasiswa: '.($student?->user?->name ?: '-'),
            'NIM: '.($student?->nim ?: '-'),
            'Tempat KP: '.($assignment?->place?->name ?: '-'),
            'Hari/Tanggal: '.($exam->exam_date?->translatedFormat('l, d F Y') ?: '-'),
            'Waktu: '.substr((string) $exam->start_time, 0, 5).' - '.substr((string) $exam->end_time, 0, 5).' WIB',
            'Lokasi/Media: '.($exam->room ?: $exam->meeting_link ?: '-'),
            'Pembimbing: '.($exam->supervisor ? lecturer_display_name($exam->supervisor) : '-'),
            'Penguji: '.$exam->examinerNamesLabel(),
            '',
            'Demikian undangan ini disampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.',
            '',
            'Koordinator Sidang: '.$invitation->coordinator_name.' / '.($invitation->coordinator_nuptk ?: '-'),
            'Kaprodi: '.$invitation->head_program_name.' / '.($invitation->head_program_nuptk ?: '-'),
            'Dekan: '.$invitation->dean_name.' / '.($invitation->dean_nuptk ?: '-'),
            'Kode verifikasi: '.$invitation->verification_code,
            'URL verifikasi: '.$this->verificationUrl($invitation),
        ];
    }

    private function simplePdf(array $lines): string
    {
        $content = "BT\n/F1 11 Tf\n50 790 Td\n14 TL\n";
        foreach ($lines as $line) {
            $content .= '('.$this->pdfEscape($line).") Tj\nT*\n";
        }
        $content .= "ET";

        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], Str::ascii($text));
    }

    private function roman(int $month): string
    {
        return [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month] ?? 'I';
    }
}
