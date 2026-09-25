<?php

namespace App\Support;

use App\Models\KpAssignment;

final class KpReportFilename
{
    public static function final(KpAssignment $assignment): string
    {
        return self::build($assignment, 'LAPORAN AKHIR KP');
    }

    public static function postExam(KpAssignment $assignment): string
    {
        return self::build($assignment, 'LAPORAN FINAL PASCASIDANG KP');
    }

    private static function build(KpAssignment $assignment, string $documentType): string
    {
        $assignment->loadMissing(['student.user', 'period']);

        $filename = collect([
            $assignment->student?->nim,
            $assignment->student?->user?->name,
            $documentType,
            $assignment->period?->name,
        ])->filter()
            ->map(fn (mixed $part): string => preg_replace('/[^A-Za-z0-9]+/', '_', strtoupper(trim((string) $part))) ?: '')
            ->filter()
            ->implode('_');

        return $filename.'.pdf';
    }
}
