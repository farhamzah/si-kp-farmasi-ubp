<?php

namespace App\Data;

class StudentDisplayData
{
    public function __construct(
        public readonly string $source,
        public readonly ?int $legacyStudentId,
        public readonly ?int $coreStudentId,
        public readonly string $name,
        public readonly string $studentNumber,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $studyProgramName,
        public readonly ?string $className,
        public readonly ?int $semester,
        public readonly ?string $status,
        public readonly ?string $error = null,
    ) {
    }

    public function label(): string
    {
        return trim($this->studentNumber.' - '.$this->name);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
