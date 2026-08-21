<?php

namespace Database\Seeders;

use App\Models\FieldSupervisor;
use App\Models\KpAssessmentComponent;
use App\Models\KpAssignment;
use App\Models\KpDocument;
use App\Models\KpDocumentRequirement;
use App\Models\KpExam;
use App\Models\KpExaminer;
use App\Models\KpExamLog;
use App\Models\KpExamRequest;
use App\Models\KpFinalReport;
use App\Models\KpFinalReportFile;
use App\Models\KpFinalReportLog;
use App\Models\KpFinalScore;
use App\Models\KpLogbook;
use App\Models\KpLogbookLog;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpPlaceFieldSupervisor;
use App\Models\KpPlaceQuota;
use App\Models\KpPlaceSelection;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireQuestion;
use App\Models\KpQuestionnaireResponse;
use App\Models\KpRegistration;
use App\Models\KpReportGuidanceLog;
use App\Models\KpScore;
use App\Models\KpScoreLog;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\KpScoreCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class FarhamzahPresentationSeeder extends Seeder
{
    private const FARHAMZAH_EMAIL = 'farhamzah@ubpkarawang.ac.id';

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $farhamzah = $this->farhamzahUser();
        $lecturer = $this->lecturer($farhamzah);
        $fieldSupervisor = $this->fieldSupervisor($farhamzah);
        $period = $this->period($farhamzah);
        $place = $this->place($period, $farhamzah, $fieldSupervisor);
        $requirements = $this->requirements($period, $farhamzah);
        $components = $this->assessmentComponents($period, $farhamzah);
        [$studentQuestionnaire, $fieldQuestionnaire] = $this->questionnaires($period, $farhamzah);

        foreach ($this->studentRows() as $index => $row) {
            $studentUser = $this->studentUser($row);
            $student = $this->student($studentUser, $row);
            $registration = $this->registration($period, $student, $farhamzah, $index);
            $this->documents($registration, $requirements, $farhamzah);
            $selection = $this->selection($period, $registration, $student, $place['quota'], $studentUser);
            $assignment = $this->assignment($period, $registration, $selection, $student, $lecturer, $fieldSupervisor, $farhamzah, $index);

            $this->logbooks($assignment, $farhamzah);
            $this->guidanceLogs($assignment, $farhamzah, $index);
            $report = $this->finalReport($assignment, $farhamzah, $index);
            $exam = $this->exam($assignment, $report, $farhamzah, $lecturer, $index);
            $this->scores($assignment, $exam, $components, $farhamzah, $index);
            $this->studentQuestionnaireResponse($studentQuestionnaire, $assignment, $studentUser, $index);
        }

        $firstAssignment = KpAssignment::query()
            ->where('kp_period_id', $period->id)
            ->where('field_supervisor_id', $fieldSupervisor->id)
            ->orderBy('id')
            ->first();

        if ($firstAssignment) {
            $this->fieldQuestionnaireResponse($fieldQuestionnaire, $firstAssignment, $farhamzah);
        }
    }

    private function farhamzahUser(): User
    {
        $user = User::updateOrCreate(
            ['email' => self::FARHAMZAH_EMAIL],
            [
                'name' => 'Farhamzah',
                'password' => Hash::make($this->presentationPassword()),
                'status' => 'active',
                'must_change_password' => false,
                'profile_completed' => true,
            ],
        );

        $user->roles()->sync(Role::pluck('id'));

        return $user->refresh();
    }

    private function lecturer(User $user): Lecturer
    {
        return Lecturer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nidn_nip' => 'FARHAMZAH-UAT',
                'employee_number' => 'FARHAMZAH-UAT',
                'study_program' => 'Farmasi',
                'department' => 'Program Studi Farmasi',
                'expertise' => 'Pembimbing KP dan Evaluasi Praktik',
                'phone' => '081234567001',
                'address' => 'Universitas Buana Perjuangan Karawang',
                'status' => 'active',
                'profile_completed_at' => now(),
            ],
        );
    }

    private function fieldSupervisor(User $user): FieldSupervisor
    {
        return FieldSupervisor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'institution_name' => 'Apotek Buana Care Presentasi',
                'position' => 'Apoteker Pembimbing Lapangan',
                'phone' => '081234567002',
                'address' => 'Karawang',
                'status' => 'active',
                'profile_completed_at' => now(),
            ],
        );
    }

    private function period(User $actor): KpPeriod
    {
        return KpPeriod::updateOrCreate(
            ['name' => 'UAT Presentasi KP Farmasi 2026'],
            [
                'academic_year' => '2025/2026',
                'semester' => 'genap',
                'registration_start_at' => now()->subMonths(2),
                'registration_end_at' => now()->addMonth(),
                'document_verification_start_at' => now()->subMonths(2),
                'document_verification_end_at' => now()->addMonth(),
                'selection_start_at' => now()->subMonths(2),
                'selection_end_at' => now()->addMonth(),
                'kp_start_date' => now()->subDays(45)->toDateString(),
                'kp_end_date' => now()->addDays(15)->toDateString(),
                'status' => 'dibuka',
                'score_visible_to_students' => true,
                'description' => 'Periode dummy lengkap untuk presentasi dan test UAT.',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ],
        );
    }

    private function place(KpPeriod $period, User $actor, FieldSupervisor $fieldSupervisor): array
    {
        $place = KpPlace::updateOrCreate(
            ['name' => 'Apotek Buana Care Presentasi'],
            [
                'type' => 'apotek',
                'address' => 'Jl. Presentasi UAT No. 21, Karawang',
                'city' => 'Karawang',
                'province' => 'Jawa Barat',
                'contact_person' => 'Farhamzah',
                'phone' => '081234567002',
                'email' => 'apotek.presentasi@sikp.test',
                'description' => 'Tempat KP dummy untuk presentasi laporan, sidang, nilai, dan kuisioner.',
                'status' => 'aktif',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ],
        );

        KpPlaceFieldSupervisor::updateOrCreate(
            ['kp_place_id' => $place->id, 'field_supervisor_id' => $fieldSupervisor->id],
            ['status' => 'aktif', 'created_by' => $actor->id],
        );

        $quota = KpPlaceQuota::updateOrCreate(
            ['kp_period_id' => $period->id, 'kp_place_id' => $place->id],
            [
                'quota' => 10,
                'is_open' => true,
                'notes' => 'Kuota dummy presentasi.',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ],
        );

        return ['place' => $place, 'quota' => $quota];
    }

    private function requirements(KpPeriod $period, User $actor): array
    {
        return collect(['KRS', 'Transkrip sementara', 'Bukti pembayaran', 'Surat permohonan KP'])
            ->map(function (string $name, int $index) use ($period, $actor): KpDocumentRequirement {
                return KpDocumentRequirement::updateOrCreate(
                    ['kp_period_id' => $period->id, 'name' => $name],
                    [
                        'description' => 'Dokumen '.$name.' untuk simulasi presentasi.',
                        'is_required' => true,
                        'allowed_file_types' => 'pdf,jpg,jpeg,png',
                        'max_file_size_mb' => 5,
                        'sort_order' => $index + 1,
                        'status' => 'aktif',
                        'created_by' => $actor->id,
                        'updated_by' => $actor->id,
                    ],
                );
            })
            ->all();
    }

    private function studentRows(): array
    {
        return [
            [
                'name' => 'Nadia Putri Presentasi',
                'email' => 'presentasi.kp.nadia@ubpkarawang.ac.id',
                'nim' => '2499991001',
                'gender' => 'perempuan',
                'topic' => 'Evaluasi pelayanan resep dan edukasi pasien',
            ],
            [
                'name' => 'Raka Aditya Presentasi',
                'email' => 'presentasi.kp.raka@ubpkarawang.ac.id',
                'nim' => '2499991002',
                'gender' => 'laki-laki',
                'topic' => 'Pengelolaan stok obat fast moving',
            ],
            [
                'name' => 'Salsabila Rahma Presentasi',
                'email' => 'presentasi.kp.salsa@ubpkarawang.ac.id',
                'nim' => '2499991003',
                'gender' => 'perempuan',
                'topic' => 'Penerapan SOP pelayanan informasi obat',
            ],
        ];
    }

    private function studentUser(array $row): User
    {
        $user = User::updateOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'password' => Hash::make($this->presentationPassword()),
                'status' => 'active',
                'must_change_password' => false,
                'profile_completed' => true,
            ],
        );

        $user->roles()->sync(Role::where('name', 'mahasiswa')->pluck('id'));

        return $user->refresh();
    }

    private function student(User $user, array $row): Student
    {
        return Student::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nim' => $row['nim'],
                'study_program' => 'Farmasi',
                'semester' => 7,
                'class_name' => 'Farmasi UAT',
                'phone' => '08129999'.substr($row['nim'], -4),
                'address' => 'Karawang',
                'gender' => $row['gender'],
                'birth_place' => 'Karawang',
                'birth_date' => '2003-08-12',
                'status' => 'active',
                'profile_completed_at' => now(),
            ],
        );
    }

    private function registration(KpPeriod $period, Student $student, User $actor, int $index): KpRegistration
    {
        return KpRegistration::updateOrCreate(
            ['kp_period_id' => $period->id, 'student_id' => $student->id],
            [
                'registration_number' => 'KP-UAT-2026-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'status' => 'terverifikasi',
                'notes' => 'Pendaftaran dummy presentasi lengkap.',
                'submitted_at' => now()->subDays(42),
                'verified_by' => $actor->id,
                'verified_at' => now()->subDays(41),
                'verification_note' => 'Berkas lengkap dan siap ditempatkan.',
            ],
        );
    }

    private function documents(KpRegistration $registration, array $requirements, User $actor): void
    {
        foreach ($requirements as $requirement) {
            KpDocument::updateOrCreate(
                [
                    'kp_registration_id' => $registration->id,
                    'kp_document_requirement_id' => $requirement->id,
                ],
                [
                    'original_filename' => str($requirement->name)->slug()->append('-presentasi.pdf')->toString(),
                    'file_path' => 'demo/presentasi/berkas/'.$registration->id.'/'.str($requirement->name)->slug().'.pdf',
                    'file_disk' => 'local',
                    'file_mime' => 'application/pdf',
                    'file_size' => 256000,
                    'status' => 'disetujui',
                    'review_note' => 'Dokumen dummy disetujui untuk UAT.',
                    'uploaded_at' => now()->subDays(42),
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now()->subDays(41),
                ],
            );
        }
    }

    private function selection(KpPeriod $period, KpRegistration $registration, Student $student, KpPlaceQuota $quota, User $studentUser): KpPlaceSelection
    {
        return KpPlaceSelection::updateOrCreate(
            ['active_key' => $period->id.'-'.$student->id],
            [
                'kp_period_id' => $period->id,
                'kp_registration_id' => $registration->id,
                'student_id' => $student->id,
                'kp_place_id' => $quota->kp_place_id,
                'kp_place_quota_id' => $quota->id,
                'selected_at' => now()->subDays(40),
                'selected_by' => $studentUser->id,
                'status' => 'aktif',
                'note' => 'Pilihan tempat KP dummy presentasi.',
            ],
        );
    }

    private function assignment(KpPeriod $period, KpRegistration $registration, KpPlaceSelection $selection, Student $student, Lecturer $lecturer, FieldSupervisor $fieldSupervisor, User $actor, int $index): KpAssignment
    {
        return KpAssignment::updateOrCreate(
            ['active_key' => $period->id.'-'.$student->id],
            [
                'kp_period_id' => $period->id,
                'kp_registration_id' => $registration->id,
                'kp_place_selection_id' => $selection->id,
                'student_id' => $student->id,
                'kp_place_id' => $selection->kp_place_id,
                'internal_supervisor_id' => $lecturer->id,
                'field_supervisor_id' => $fieldSupervisor->id,
                'status' => 'berjalan',
                'assigned_by' => $actor->id,
                'assigned_at' => now()->subDays(39 - $index),
                'started_at' => now()->subDays(38)->toDateString(),
                'ended_at' => now()->addDays(15)->toDateString(),
                'workday_pattern' => KpAssignment::WORKDAY_SENIN_JUMAT,
                'note' => 'Farhamzah menjadi pembimbing dalam dan pembimbing lapangan pada data dummy ini.',
            ],
        );
    }

    private function logbooks(KpAssignment $assignment, User $validator): void
    {
        for ($day = 1; $day <= 12; $day++) {
            $activityDate = Carbon::today()->subDays(30 - $day);
            $logbook = KpLogbook::updateOrCreate(
                ['kp_assignment_id' => $assignment->id, 'activity_date' => $activityDate],
                [
                    'start_time' => '08:00',
                    'end_time' => '15:00',
                    'activity_title' => 'Kegiatan KP Presentasi Hari '.$day,
                    'activity_description' => 'Observasi pelayanan farmasi, pencatatan stok, dan diskusi kasus dengan pembimbing lapangan.',
                    'learning_outcome' => 'Mahasiswa memahami alur pelayanan, dokumentasi, dan komunikasi pasien.',
                    'obstacle' => $day % 4 === 0 ? 'Perlu penyesuaian pada format pencatatan.' : null,
                    'solution' => $day % 4 === 0 ? 'Format diperbaiki sesuai arahan pembimbing.' : null,
                    'status' => 'disetujui',
                    'submitted_at' => now()->subDays(30 - $day)->setTime(16, 0),
                    'validated_by' => $validator->id,
                    'validated_at' => now()->subDays(29 - $day)->setTime(9, 0),
                    'validation_note' => 'Logbook hari '.$day.' disetujui untuk dummy presentasi.',
                ],
            );

            KpLogbookLog::updateOrCreate(
                ['kp_logbook_id' => $logbook->id, 'action' => 'validated'],
                [
                    'user_id' => $validator->id,
                    'old_status' => 'menunggu_validasi',
                    'new_status' => 'disetujui',
                    'note' => 'Validasi dummy presentasi.',
                    'metadata' => ['source' => 'FarhamzahPresentationSeeder'],
                ],
            );
        }
    }

    private function guidanceLogs(KpAssignment $assignment, User $reviewer, int $studentIndex): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $status = $i === 3 && $studentIndex === 1 ? 'revisi' : 'disetujui';
            KpReportGuidanceLog::updateOrCreate(
                [
                    'kp_assignment_id' => $assignment->id,
                    'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL,
                    'guidance_date' => Carbon::today()->subDays(24 - $i),
                    'topic' => 'Bimbingan pembimbing dalam sesi '.$i,
                ],
                [
                    'student_note' => 'Mahasiswa mengajukan progres laporan sesi '.$i.' dan menindaklanjuti arahan sebelumnya.',
                    'document_url' => 'https://drive.google.com/file/d/dummy-internal-'.$assignment->id.'-'.$i.'/view',
                    'document_label' => 'Draft laporan pembimbing dalam sesi '.$i,
                    'status' => $status,
                    'submitted_at' => now()->subDays(24 - $i)->setTime(13, 0),
                    'validated_by' => $reviewer->id,
                    'validated_at' => now()->subDays(24 - $i)->setTime(15, 0),
                    'validation_note' => $status === 'revisi'
                        ? 'Bab pembahasan perlu ditambah referensi dan sudah tercatat sebagai bimbingan.'
                        : 'Progres sesuai arahan. Sesi disetujui.',
                ],
            );
        }

        foreach ([1, 2] as $i) {
            KpReportGuidanceLog::updateOrCreate(
                [
                    'kp_assignment_id' => $assignment->id,
                    'reviewer_type' => KpReportGuidanceLog::REVIEWER_FIELD,
                    'guidance_date' => Carbon::today()->subDays(13 - $i),
                    'topic' => 'Bimbingan pembimbing lapangan sesi '.$i,
                ],
                [
                    'student_note' => 'Mahasiswa mencatat tindak lanjut masukan lapangan terkait laporan KP.',
                    'document_url' => 'https://drive.google.com/file/d/dummy-field-'.$assignment->id.'-'.$i.'/view',
                    'document_label' => 'Draft laporan pembimbing lapangan sesi '.$i,
                    'status' => 'disetujui',
                    'submitted_at' => now()->subDays(13 - $i)->setTime(13, 0),
                    'validated_by' => $reviewer->id,
                    'validated_at' => now()->subDays(13 - $i)->setTime(15, 0),
                    'validation_note' => 'Catatan lapangan sudah ditindaklanjuti. Sesi disetujui.',
                ],
            );
        }
    }

    private function finalReport(KpAssignment $assignment, User $reviewer, int $index): KpFinalReport
    {
        $report = KpFinalReport::updateOrCreate(
            ['kp_assignment_id' => $assignment->id],
            [
                'current_version' => 1,
                'status' => 'disetujui',
                'submitted_at' => now()->subDays(9 - $index),
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now()->subDays(8 - $index),
                'review_note' => 'Laporan final dummy presentasi disetujui lengkap.',
                'final_document_url' => 'https://drive.google.com/file/d/final-presentasi-'.$assignment->id.'/view',
                'final_document_label' => 'Laporan Final KP Presentasi '.$assignment->student?->nim,
                'approved_at' => now()->subDays(8 - $index),
                'internal_review_status' => 'disetujui',
                'internal_reviewed_by' => $reviewer->id,
                'internal_reviewed_at' => now()->subDays(8 - $index),
                'internal_review_note' => 'Laporan sudah memenuhi arahan pembimbing dalam.',
                'internal_guidance_completed_by' => $reviewer->id,
                'internal_guidance_completed_at' => now()->subDays(10 - $index),
                'internal_guidance_completion_note' => 'Minimal 8 sesi bimbingan dalam sudah selesai.',
                'field_review_status' => 'disetujui',
                'field_reviewed_by' => $reviewer->id,
                'field_reviewed_at' => now()->subDays(8 - $index),
                'field_review_note' => 'Laporan sesuai masukan pembimbing lapangan.',
                'field_guidance_completed_by' => $reviewer->id,
                'field_guidance_completed_at' => now()->subDays(10 - $index),
                'field_guidance_completion_note' => 'Bimbingan lapangan sudah selesai.',
            ],
        );

        KpFinalReportFile::updateOrCreate(
            ['kp_final_report_id' => $report->id, 'version' => 1],
            [
                'original_filename' => 'laporan-final-presentasi-'.$assignment->student?->nim.'.pdf',
                'file_path' => 'demo/presentasi/laporan/'.$assignment->id.'/laporan-final.pdf',
                'file_disk' => 'local',
                'file_mime' => 'application/pdf',
                'file_size' => 1536000,
                'uploaded_by' => $assignment->student?->user_id,
                'uploaded_at' => now()->subDays(9 - $index),
                'note' => 'File dummy laporan final presentasi.',
            ],
        );

        foreach ([
            ['created', null, 'draft', 'Draft laporan dibuat.'],
            ['submitted', 'draft', 'menunggu_review', 'Laporan dikirim untuk review.'],
            ['internal_approved', 'menunggu_review', 'menunggu_review', 'Disetujui pembimbing dalam.'],
            ['field_approved', 'menunggu_review', 'disetujui', 'Disetujui pembimbing lapangan.'],
            ['internal_guidance_completed', 'belum_selesai', 'selesai', 'Bimbingan dalam selesai.'],
            ['field_guidance_completed', 'belum_selesai', 'selesai', 'Bimbingan lapangan selesai.'],
        ] as $row) {
            KpFinalReportLog::updateOrCreate(
                ['kp_final_report_id' => $report->id, 'action' => $row[0]],
                [
                    'user_id' => $reviewer->id,
                    'old_status' => $row[1],
                    'new_status' => $row[2],
                    'note' => $row[3],
                    'metadata' => ['source' => 'FarhamzahPresentationSeeder'],
                ],
            );
        }

        return $report;
    }

    private function exam(KpAssignment $assignment, KpFinalReport $report, User $actor, Lecturer $lecturer, int $index): KpExam
    {
        $request = KpExamRequest::updateOrCreate(
            ['kp_assignment_id' => $assignment->id],
            [
                'requested_by' => $assignment->student?->user_id,
                'status' => 'dijadwalkan',
                'request_note' => 'Pengajuan sidang dummy setelah laporan final lengkap.',
                'submitted_at' => now()->subDays(7 - $index),
                'reviewed_by' => $actor->id,
                'reviewed_at' => now()->subDays(7 - $index),
                'review_note' => 'Syarat sidang lengkap dan disetujui.',
            ],
        );

        $exam = KpExam::updateOrCreate(
            ['kp_assignment_id' => $assignment->id],
            [
                'kp_exam_request_id' => $request->id,
                'supervisor_id' => $lecturer->id,
                'examiner_id' => $lecturer->id,
                'exam_date' => now()->subDays(5 - $index)->toDateString(),
                'start_time' => sprintf('%02d:00', 9 + $index),
                'end_time' => sprintf('%02d:45', 9 + $index),
                'mode' => 'offline',
                'room' => 'Ruang Sidang UAT '.($index + 1),
                'meeting_link' => null,
                'status' => 'selesai',
                'scheduled_by' => $actor->id,
                'scheduled_at' => now()->subDays(6 - $index),
                'note' => 'Sidang dummy presentasi selesai.',
            ],
        );

        KpExaminer::updateOrCreate(
            ['kp_exam_id' => $exam->id, 'lecturer_id' => $lecturer->id],
            ['sort_order' => 1],
        );

        KpExamLog::updateOrCreate(
            ['kp_exam_request_id' => $request->id, 'kp_exam_id' => $exam->id, 'action' => 'exam_completed'],
            [
                'user_id' => $actor->id,
                'old_status' => 'dijadwalkan',
                'new_status' => 'selesai',
                'note' => 'Sidang dummy presentasi selesai.',
            ],
        );

        return $exam;
    }

    private function assessmentComponents(KpPeriod $period, User $actor): array
    {
        $rows = [
            ['pembimbing_dalam', 'Kualitas laporan akhir', 60, 1],
            ['pembimbing_dalam', 'Kedisiplinan bimbingan', 40, 2],
            ['pembimbing_lapangan', 'Kinerja di tempat KP', 50, 3],
            ['pembimbing_lapangan', 'Sikap profesional', 50, 4],
            ['penguji', 'Presentasi sidang', 50, 5],
            ['penguji', 'Penguasaan materi', 50, 6],
        ];

        return collect($rows)->map(function (array $row) use ($period, $actor): KpAssessmentComponent {
            return KpAssessmentComponent::updateOrCreate(
                ['kp_period_id' => $period->id, 'assessor_type' => $row[0], 'component_name' => $row[1]],
                [
                    'description' => 'Komponen penilaian dummy presentasi: '.$row[1].'.',
                    'weight' => $row[2],
                    'max_score' => 100,
                    'sort_order' => $row[3],
                    'is_required' => true,
                    'status' => 'aktif',
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ],
            );
        })->all();
    }

    private function scores(KpAssignment $assignment, KpExam $exam, array $components, User $assessor, int $index): void
    {
        $base = 88 + $index;
        $scoreMap = [
            'Kualitas laporan akhir' => $base + 1,
            'Kedisiplinan bimbingan' => $base + 2,
            'Kinerja di tempat KP' => $base + 3,
            'Sikap profesional' => $base + 1,
            'Presentasi sidang' => $base,
            'Penguasaan materi' => $base + 2,
        ];

        foreach ($components as $component) {
            $scoreValue = min(100, $scoreMap[$component->component_name] ?? $base);
            $score = KpScore::updateOrCreate(
                [
                    'kp_assignment_id' => $assignment->id,
                    'kp_assessment_component_id' => $component->id,
                    'assessor_user_id' => $assessor->id,
                ],
                [
                    'kp_exam_id' => $component->assessor_type === 'penguji' ? $exam->id : null,
                    'assessor_type' => $component->assessor_type,
                    'score' => $scoreValue,
                    'weighted_score' => round(($scoreValue * (float) $component->weight) / 100, 2),
                    'note' => 'Nilai dummy presentasi oleh Farhamzah.',
                    'status' => 'locked',
                    'submitted_at' => now()->subDays(3 - $index),
                    'locked_at' => now()->subDays(2 - $index),
                ],
            );

            KpScoreLog::updateOrCreate(
                ['kp_assignment_id' => $assignment->id, 'kp_score_id' => $score->id, 'action' => 'score_locked'],
                [
                    'user_id' => $assessor->id,
                    'old_status' => 'submitted',
                    'new_status' => 'locked',
                    'note' => 'Nilai dummy dikunci untuk presentasi.',
                    'metadata' => ['score' => $scoreValue],
                ],
            );
        }

        $finalScore = KpFinalScore::updateOrCreate(
            ['kp_assignment_id' => $assignment->id],
            [
                'attendance_score_override' => 100,
                'attendance_note' => 'Kehadiran dan logbook dummy lengkap.',
                'attendance_overridden_by' => $assessor->id,
                'attendance_overridden_at' => now()->subDays(2 - $index),
                'status' => 'published',
                'calculated_at' => now()->subDays(2 - $index),
                'finalized_by' => $assessor->id,
                'finalized_at' => now()->subDays(2 - $index),
                'published_at' => now()->subDays(1 - $index),
                'note' => 'Nilai akhir dummy sudah dipublish untuk presentasi.',
            ],
        );

        $final = app(KpScoreCalculator::class)->breakdown($assignment->fresh())['final_score'];
        $finalScore->update([
            'final_score' => $final,
            'final_grade' => $this->grade($final),
        ]);

        KpScoreLog::updateOrCreate(
            ['kp_assignment_id' => $assignment->id, 'kp_final_score_id' => $finalScore->id, 'action' => 'final_score_published'],
            [
                'user_id' => $assessor->id,
                'old_status' => 'locked',
                'new_status' => 'published',
                'note' => 'Final score dummy dipublikasikan.',
                'metadata' => ['final_score' => $final, 'grade' => $this->grade($final)],
            ],
        );
    }

    private function questionnaires(KpPeriod $period, User $actor): array
    {
        $student = KpQuestionnaire::updateOrCreate(
            ['kp_period_id' => $period->id, 'audience' => KpQuestionnaire::AUDIENCE_STUDENT, 'title' => 'Kuisioner Mahasiswa UAT Presentasi'],
            [
                'description' => 'Kuisioner dummy mahasiswa untuk memastikan syarat nilai dan report koordinator lengkap.',
                'status' => 'aktif',
                'starts_at' => now()->subMonth(),
                'ends_at' => now()->addMonth(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ],
        );

        $field = KpQuestionnaire::updateOrCreate(
            ['kp_period_id' => $period->id, 'audience' => KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR, 'title' => 'Kuisioner Tempat KP UAT Presentasi'],
            [
                'description' => 'Kuisioner dummy tempat KP untuk report koordinator.',
                'status' => 'aktif',
                'starts_at' => now()->subMonth(),
                'ends_at' => now()->addMonth(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ],
        );

        $this->question($student, 'Kualitas Pembimbingan', 'Pembimbing membantu proses KP dan laporan.', KpQuestionnaireQuestion::TYPE_SCALE, 1);
        $this->question($student, 'Kualitas Sistem', 'Aplikasi SI-KP mudah digunakan.', KpQuestionnaireQuestion::TYPE_SCALE, 2);
        $this->question($student, 'Umpan Balik', 'Catatan pengalaman mahasiswa selama KP.', KpQuestionnaireQuestion::TYPE_TEXTAREA, 3, false);

        $this->question($field, 'Kinerja Mahasiswa', 'Mahasiswa menunjukkan sikap profesional.', KpQuestionnaireQuestion::TYPE_SCALE, 1);
        $this->question($field, 'Kerja Sama', 'Tempat KP bersedia menerima mahasiswa berikutnya.', KpQuestionnaireQuestion::TYPE_CHOICE, 2, true, ['Ya', 'Tidak', 'Perlu dibicarakan']);
        $this->question($field, 'Rekomendasi Kuota', 'Perkiraan jumlah mahasiswa periode berikutnya.', KpQuestionnaireQuestion::TYPE_NUMBER, 3, false);
        $this->question($field, 'Saran', 'Saran untuk peningkatan kerja sama.', KpQuestionnaireQuestion::TYPE_TEXTAREA, 4, false);

        return [$student, $field];
    }

    private function question(KpQuestionnaire $questionnaire, string $section, string $text, string $type, int $sortOrder, bool $required = true, ?array $options = null): KpQuestionnaireQuestion
    {
        return KpQuestionnaireQuestion::updateOrCreate(
            ['kp_questionnaire_id' => $questionnaire->id, 'question_text' => $text],
            [
                'section' => $section,
                'answer_type' => $type,
                'options' => $options,
                'is_required' => $required,
                'sort_order' => $sortOrder,
                'status' => 'aktif',
            ],
        );
    }

    private function studentQuestionnaireResponse(KpQuestionnaire $questionnaire, KpAssignment $assignment, User $studentUser, int $index): void
    {
        $response = KpQuestionnaireResponse::updateOrCreate(
            [
                'kp_questionnaire_id' => $questionnaire->id,
                'kp_assignment_id' => $assignment->id,
                'respondent_user_id' => $studentUser->id,
            ],
            [
                'kp_place_id' => $assignment->kp_place_id,
                'kp_period_id' => $assignment->kp_period_id,
                'respondent_role' => 'mahasiswa',
                'status' => 'submitted',
                'submitted_at' => now()->subDays(1 - $index),
            ],
        );

        foreach ($questionnaire->activeQuestions as $question) {
            $answer = match ($question->answer_type) {
                KpQuestionnaireQuestion::TYPE_SCALE => (string) min(5, 4 + ($index % 2)),
                KpQuestionnaireQuestion::TYPE_TEXTAREA => 'Pengalaman KP dan bimbingan terekam jelas. Aplikasi memudahkan proses laporan sampai nilai.',
                default => 'Baik',
            };

            $response->answers()->updateOrCreate(
                ['kp_questionnaire_question_id' => $question->id],
                ['answer_value' => $answer],
            );
        }
    }

    private function fieldQuestionnaireResponse(KpQuestionnaire $questionnaire, KpAssignment $assignment, User $fieldUser): void
    {
        $response = KpQuestionnaireResponse::updateOrCreate(
            [
                'kp_questionnaire_id' => $questionnaire->id,
                'kp_place_id' => $assignment->kp_place_id,
                'kp_period_id' => $assignment->kp_period_id,
                'respondent_user_id' => $fieldUser->id,
            ],
            [
                'kp_assignment_id' => null,
                'respondent_role' => 'pembimbing_lapangan',
                'status' => 'submitted',
                'submitted_at' => now(),
            ],
        );

        foreach ($questionnaire->activeQuestions as $question) {
            $answer = match ($question->answer_type) {
                KpQuestionnaireQuestion::TYPE_SCALE => '5',
                KpQuestionnaireQuestion::TYPE_CHOICE => 'Ya',
                KpQuestionnaireQuestion::TYPE_NUMBER => '4',
                KpQuestionnaireQuestion::TYPE_TEXTAREA => 'Mahasiswa siap ditempatkan kembali. Koordinasi dan laporan sudah baik.',
                default => 'Baik',
            };

            $response->answers()->updateOrCreate(
                ['kp_questionnaire_question_id' => $question->id],
                ['answer_value' => $answer],
            );
        }
    }

    private function grade(float $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 75 => 'B',
            $score >= 65 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }

    private function presentationPassword(): string
    {
        $password = env('FARHAMZAH_PRESENTATION_PASSWORD');

        if (! is_string($password) || $password === '') {
            throw new \RuntimeException('Set FARHAMZAH_PRESENTATION_PASSWORD before running FarhamzahPresentationSeeder.');
        }

        return $password;
    }
}
