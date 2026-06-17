<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpCompetency extends Model
{
    protected $fillable = [
        'kp_period_id',
        'place_type',
        'title',
        'description',
        'sort_order',
        'status',
        'created_by',
    ];

    public function period()
    {
        return $this->belongsTo(KpPeriod::class, 'kp_period_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function achievements()
    {
        return $this->hasMany(KpCompetencyAchievement::class, 'kp_competency_id');
    }

    public function statusLabel(): string
    {
        return $this->status === 'aktif' ? 'Aktif' : 'Nonaktif';
    }

    public function statusBadgeClass(): string
    {
        return $this->status === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700';
    }

    public function placeTypeLabel(): string
    {
        return match ($this->place_type) {
            'rumah_sakit' => 'Rumah Sakit',
            'puskesmas' => 'Puskesmas',
            'industri' => 'Industri',
            'klinik' => 'Klinik',
            'distributor' => 'Distributor',
            'lainnya' => 'Lainnya',
            'apotek' => 'Apotek',
            default => 'Semua tipe tempat',
        };
    }
}
