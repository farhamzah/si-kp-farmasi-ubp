<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KpExternalDocumentReference extends Model
{
    protected $fillable = [
        'uuid',
        'source_app',
        'external_app',
        'document_type',
        'service_code',
        'source_module',
        'source_reference_type',
        'source_reference_id',
        'external_document_id',
        'external_document_number',
        'external_status',
        'reference_url',
        'file_hash',
        'metadata',
        'last_payload_snapshot',
        'last_error',
        'synced_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_payload_snapshot' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $reference): void {
            $reference->uuid ??= (string) Str::uuid();
            $reference->source_app ??= 'kp-farmasi';
            $reference->external_app ??= 'tu-farmasi';
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isSafeReferenceUrl(): bool
    {
        if (! $this->reference_url) {
            return true;
        }

        $url = strtolower($this->reference_url);

        if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://')) {
            return false;
        }

        return ! preg_match('/token|signature|signed|password|secret|storage\/app|private|temporary/', $url);
    }
}
