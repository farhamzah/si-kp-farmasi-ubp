<?php

namespace App\Data;

class LecturerDisplayData
{
    public function __construct(
        public readonly string $source,
        public readonly ?int $legacyLecturerId,
        public readonly ?int $coreLecturerId,
        public readonly string $name,
        public readonly string $lecturerNumber,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $studyProgramName,
        public readonly ?string $departmentName,
        public readonly ?string $expertise,
        public readonly ?string $status,
        public readonly ?string $error = null,
    ) {
    }

    public function label(): string
    {
        return trim($this->lecturerNumber.' - '.$this->name);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
