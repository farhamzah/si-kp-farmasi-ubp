<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationOutboxEvent extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_SENT = 'SENT';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'event_id',
        'destination_app',
        'event_type',
        'event_version',
        'source_app',
        'source_record_id',
        'source_revision',
        'correlation_id',
        'payload',
        'status',
        'attempt_count',
        'available_at',
        'locked_at',
        'last_attempted_at',
        'sent_at',
        'last_http_status',
        'last_error_code',
        'last_error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'available_at' => 'datetime',
            'locked_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function envelope(): array
    {
        return [
            'event_id' => $this->event_id,
            'event_type' => $this->event_type,
            'event_version' => $this->event_version,
            'source_app' => $this->source_app,
            'source_record_id' => $this->source_record_id,
            'source_revision' => $this->source_revision,
            'correlation_id' => $this->correlation_id,
            'occurred_at' => $this->created_at?->toIso8601String(),
            'payload' => $this->payload ?: [],
        ];
    }
}
