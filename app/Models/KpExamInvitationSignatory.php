<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpExamInvitationSignatory extends Model
{
    protected $fillable = [
        'coordinator_name',
        'coordinator_nuptk',
        'head_program_name',
        'head_program_nuptk',
        'dean_name',
        'dean_nuptk',
        'effective_start_date',
        'effective_end_date',
        'is_active',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function active(): ?self
    {
        return self::query()->where('is_active', true)->latest('id')->first();
    }
}
