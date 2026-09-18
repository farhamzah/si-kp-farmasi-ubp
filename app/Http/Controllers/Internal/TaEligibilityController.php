<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\KpAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaEligibilityController extends Controller
{
    public function __invoke(Request $request, string $nim): JsonResponse
    {
        $expected = (string) config('ta_integration.status_token');
        $provided = (string) $request->bearerToken();
        abort_if($expected === '' || $provided === '' || ! hash_equals($expected, $provided), 401);

        $assignment = KpAssignment::query()
            ->with(['student.user', 'exam.minutes', 'postExamReport'])
            ->whereHas('student', fn ($student) => $student->where('nim', $nim))
            ->latest('assigned_at')
            ->first();

        if (! $assignment) {
            return response()->json(['data' => ['student_identifier' => $nim, 'eligible' => false, 'reason' => 'kp_assignment_not_found']]);
        }

        $examCompleted = (bool) ($assignment->exam?->minutes && $assignment->exam->minutes->result !== 'belum_lulus');
        $reportApproved = (bool) $assignment->postExamReport?->isApproved();

        return response()->json(['data' => [
            'student_identifier' => $nim,
            'student_name' => $assignment->student?->user?->name,
            'eligible' => $examCompleted && $reportApproved,
            'exam_completed' => $examCompleted,
            'post_exam_report_status' => $assignment->postExamReport?->status ?? 'belum_upload',
            'post_exam_report_approved_at' => $assignment->postExamReport?->approved_at?->toIso8601String(),
        ]]);
    }
}
