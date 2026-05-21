<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpDocumentRequirement extends Model
{
    protected $fillable = [
        'kp_period_id',
        'name',
        'description',
        'is_required',
        'allowed_file_types',
        'max_file_size_mb',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function period()
    {
        return $this->belongsTo(KpPeriod::class, 'kp_period_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documents()
    {
        return $this->hasMany(KpDocument::class, 'kp_document_requirement_id');
    }

    public function allowedFileTypesArray(): array
    {
        return collect(explode(',', $this->allowed_file_types ?: 'pdf,jpg,jpeg,png'))
            ->map(fn ($type) => trim(strtolower($type)))
            ->filter()
            ->values()
            ->all();
    }
}
