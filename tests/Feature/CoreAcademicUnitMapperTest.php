<?php

namespace Tests\Feature;

use App\Support\CoreAcademicUnitMapper;
use Tests\TestCase;

class CoreAcademicUnitMapperTest extends TestCase
{
    public function test_study_program_alias_follows_core_label(): void
    {
        $this->assertSame('Farmasi S1', CoreAcademicUnitMapper::mapStudyProgram('Farmasi'));
        $this->assertSame('Farmasi S1', CoreAcademicUnitMapper::mapStudyProgram('S1 Farmasi'));
        $this->assertSame('Farmasi S1', CoreAcademicUnitMapper::mapStudyProgram('Farmasi S1'));
    }

    public function test_department_alias_follows_core_label(): void
    {
        $this->assertSame('Farmakologi dan Farmasi Klinik', CoreAcademicUnitMapper::mapDepartment('Farmasi Klinis'));
        $this->assertSame('Teknologi Sediaan Farmasi', CoreAcademicUnitMapper::mapDepartment('Teknologi Sediaan Farmasi'));
    }

    public function test_faculty_label_is_not_mapped_as_department(): void
    {
        $this->assertTrue(CoreAcademicUnitMapper::isFacultyLabel('Fakultas Farmasi'));
        $this->assertSame('Fakultas Farmasi', CoreAcademicUnitMapper::mapFaculty('Fakultas Farmasi'));
        $this->assertNull(CoreAcademicUnitMapper::mapDepartment('Fakultas Farmasi'));
    }

    public function test_hierarchy_uses_core_order(): void
    {
        $this->assertSame(['faculty', 'study_program', 'department'], CoreAcademicUnitMapper::hierarchy());
    }
}
