<?php

namespace App\Support;

class CoreAcademicUnitMapper
{
    public const HIERARCHY = [
        'faculty',
        'study_program',
        'department',
    ];

    public const STUDY_PROGRAM_ALIASES = [
        'farmasi' => 'Farmasi S1',
        's1 farmasi' => 'Farmasi S1',
        'farmasi s1' => 'Farmasi S1',
        'profesi apoteker' => 'Profesi Apoteker',
    ];

    public const DEPARTMENT_ALIASES = [
        'farmakologi dan farmasi klinik' => 'Farmakologi dan Farmasi Klinik',
        'farmasi klinis' => 'Farmakologi dan Farmasi Klinik',
        'biologi farmasi' => 'Biologi Farmasi',
        'farmakokimia' => 'Farmakokimia',
        'teknologi sediaan farmasi' => 'Teknologi Sediaan Farmasi',
    ];

    public const FACULTY_ALIASES = [
        'fakultas farmasi' => 'Fakultas Farmasi',
        'farmasi fakultas' => 'Fakultas Farmasi',
    ];

    public static function hierarchy(): array
    {
        return self::HIERARCHY;
    }

    public static function mapStudyProgram(?string $value): ?string
    {
        $normalized = self::normalize($value);

        return self::STUDY_PROGRAM_ALIASES[$normalized] ?? null;
    }

    public static function mapDepartment(?string $value): ?string
    {
        $normalized = self::normalize($value);

        if (self::isFacultyLabel($value)) {
            return null;
        }

        return self::DEPARTMENT_ALIASES[$normalized] ?? null;
    }

    public static function mapFaculty(?string $value): ?string
    {
        $normalized = self::normalize($value);

        return self::FACULTY_ALIASES[$normalized] ?? null;
    }

    public static function isFacultyLabel(?string $value): bool
    {
        return array_key_exists(self::normalize($value), self::FACULTY_ALIASES);
    }

    public static function normalize(?string $value): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim((string) $value)) ?? '');
    }
}
