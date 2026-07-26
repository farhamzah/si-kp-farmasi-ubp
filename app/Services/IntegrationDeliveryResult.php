<?php

namespace App\Services;

class IntegrationDeliveryResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?int $httpStatus,
        public readonly ?string $errorCode,
        public readonly ?string $message,
    ) {}

    public static function sent(?int $httpStatus, ?string $message = null): self
    {
        return new self('sent', $httpStatus, null, $message);
    }

    public static function retryable(?int $httpStatus, string $errorCode, ?string $message = null): self
    {
        return new self('retryable', $httpStatus, $errorCode, $message);
    }

    public static function permanent(?int $httpStatus, string $errorCode, ?string $message = null): self
    {
        return new self('permanent', $httpStatus, $errorCode, $message);
    }

    public function sentSuccessfully(): bool
    {
        return $this->status === 'sent';
    }

    public function retryableFailure(): bool
    {
        return $this->status === 'retryable';
    }
}
