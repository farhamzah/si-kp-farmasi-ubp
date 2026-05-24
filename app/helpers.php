<?php

use App\Models\Lecturer;
use App\Models\Student;
use App\Services\KpMasterDataReadService;

if (! function_exists('student_display_name')) {
    function student_display_name(Student $student): string
    {
        return app(KpMasterDataReadService::class)->getStudentDisplayData($student)->name;
    }
}

if (! function_exists('student_display_label')) {
    function student_display_label(Student $student): string
    {
        return app(KpMasterDataReadService::class)->getStudentDisplayData($student)->label();
    }
}

if (! function_exists('lecturer_display_name')) {
    function lecturer_display_name(Lecturer $lecturer): string
    {
        return app(KpMasterDataReadService::class)->getLecturerDisplayData($lecturer)->name;
    }
}

if (! function_exists('lecturer_display_label')) {
    function lecturer_display_label(Lecturer $lecturer): string
    {
        return app(KpMasterDataReadService::class)->getLecturerDisplayData($lecturer)->label();
    }
}
