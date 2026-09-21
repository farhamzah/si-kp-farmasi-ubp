<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kp_final_reports')
            ->where(function ($query): void {
                $query->whereNull('internal_guidance_completed_at')
                    ->orWhereNull('field_guidance_completed_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($reports): void {
                foreach ($reports as $report) {
                    $updates = [];

                    if ($report->internal_guidance_completed_at === null
                        && $report->internal_review_status === 'disetujui'
                        && $this->reviewedGuidanceCount((int) $report->kp_assignment_id, 'internal') >= 8
                        && $this->pendingGuidanceCount((int) $report->kp_assignment_id, 'internal') === 0) {
                        $updates += [
                            'internal_guidance_completed_by' => $report->internal_reviewed_by,
                            'internal_guidance_completed_at' => $report->internal_reviewed_at ?? $report->approved_at ?? now(),
                            'internal_guidance_completion_note' => 'Otomatis selesai karena laporan disetujui dan minimal 8 sesi bimbingan telah direview.',
                        ];
                    }

                    if ($report->field_guidance_completed_at === null
                        && $report->field_review_status === 'disetujui'
                        && $this->reviewedGuidanceCount((int) $report->kp_assignment_id, 'field') >= 1
                        && $this->pendingGuidanceCount((int) $report->kp_assignment_id, 'field') === 0) {
                        $updates += [
                            'field_guidance_completed_by' => $report->field_reviewed_by,
                            'field_guidance_completed_at' => $report->field_reviewed_at ?? $report->approved_at ?? now(),
                            'field_guidance_completion_note' => 'Otomatis selesai karena laporan disetujui dan minimal satu sesi bimbingan lapangan telah direview.',
                        ];
                    }

                    if ($updates !== []) {
                        DB::table('kp_final_reports')->where('id', $report->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        // Data completion is intentionally retained because it reflects validated guidance history.
    }

    private function reviewedGuidanceCount(int $assignmentId, string $type): int
    {
        return $this->guidanceQuery($assignmentId, $type)
            ->whereIn('status', ['disetujui', 'revisi'])
            ->count();
    }

    private function pendingGuidanceCount(int $assignmentId, string $type): int
    {
        return $this->guidanceQuery($assignmentId, $type)
            ->where('status', 'menunggu_validasi')
            ->count();
    }

    private function guidanceQuery(int $assignmentId, string $type)
    {
        return DB::table('kp_report_guidance_logs')
            ->where('kp_assignment_id', $assignmentId)
            ->where(function ($query) use ($type): void {
                if ($type === 'internal') {
                    $query->where('reviewer_type', 'internal')->orWhereNull('reviewer_type');
                } else {
                    $query->where('reviewer_type', 'field');
                }
            });
    }
};
