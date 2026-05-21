<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpPlace extends Model
{
    public const TYPES = ['apotek', 'rumah_sakit', 'puskesmas', 'industri', 'klinik', 'distributor', 'lainnya'];

    protected $fillable = [
        'name',
        'type',
        'address',
        'city',
        'province',
        'contact_person',
        'phone',
        'email',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function quotas()
    {
        return $this->hasMany(KpPlaceQuota::class);
    }

    public function assignments()
    {
        return $this->hasMany(KpAssignment::class, 'kp_place_id');
    }

    public function fieldSupervisors()
    {
        return $this->belongsToMany(FieldSupervisor::class, 'kp_place_field_supervisors')->withPivot(['status'])->withTimestamps();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'rumah_sakit' => 'Rumah Sakit',
            'puskesmas' => 'Puskesmas',
            'industri' => 'Industri',
            'klinik' => 'Klinik',
            'distributor' => 'Distributor',
            'lainnya' => 'Lainnya',
            default => 'Apotek',
        };
    }

    public function statusLabel(): string
    {
        return $this->status === 'aktif' ? 'Aktif' : 'Nonaktif';
    }

    public function statusBadgeClass(): string
    {
        return $this->status === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700';
    }
}
