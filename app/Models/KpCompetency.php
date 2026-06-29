<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpCompetency extends Model
{
    protected $fillable = [
        'kp_period_id',
        'place_type',
        'place_types',
        'title',
        'description',
        'sort_order',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'place_types' => 'array',
        ];
    }

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
        $types = $this->selectedPlaceTypes();

        if ($types->isEmpty()) {
            return 'Semua tipe tempat';
        }

        return $types
            ->map(fn ($type) => self::typeLabel($type))
            ->join(', ');
    }

    public function selectedPlaceTypes()
    {
        $types = collect($this->place_types ?? [])
            ->filter()
            ->values();

        if ($types->isEmpty() && filled($this->place_type)) {
            $types = collect([$this->place_type]);
        }

        return $types;
    }

    public function appliesToPlaceType(?string $placeType): bool
    {
        $types = $this->selectedPlaceTypes();

        return $types->isEmpty() || $types->contains($placeType);
    }

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
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
