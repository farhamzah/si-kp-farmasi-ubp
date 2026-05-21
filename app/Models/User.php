<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'must_change_password',
        'profile_completed',
        'last_login_at',
        'avatar_path',
        'avatar_disk',
        'avatar_original_filename',
        'avatar_mime',
        'avatar_size',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'profile_completed' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function lecturer()
    {
        return $this->hasOne(Lecturer::class);
    }

    public function fieldSupervisor()
    {
        return $this->hasOne(FieldSupervisor::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName)
            || $this->roles()->where('name', $roleName)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty()
            || $this->roles()->whereIn('name', $roles)->exists();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function activeRoles()
    {
        return $this->roles()->orderBy('label')->get();
    }

    public function hasAvatar(): bool
    {
        return filled($this->avatar_path);
    }

    public function avatarUrl(): ?string
    {
        return $this->hasAvatar() ? route('profile.avatar.show') : null;
    }

    public function initials(): string
    {
        $words = str($this->name)
            ->replaceMatches('/[^A-Za-z0-9\s]/', ' ')
            ->squish()
            ->explode(' ')
            ->filter()
            ->reject(fn (string $word) => in_array(strtolower($word), ['dr', 'drs', 'dra', 'prof'], true))
            ->values();

        if ($words->isEmpty()) {
            return 'U';
        }

        $first = str($words->first())->substr(0, 1);
        $last = $words->count() > 1 ? str($words->last())->substr(0, 1) : str($words->first())->substr(1, 1);

        return str($first.$last)->upper()->toString();
    }

    public function displayRoleLabel(?string $role = null): string
    {
        $role ??= session('active_role');

        return match ($role) {
            'koordinator_kp' => 'Koordinator KP',
            'pembimbing_dalam' => 'Pembimbing Dalam',
            'pembimbing_lapangan' => 'Pembimbing Lapangan',
            'mahasiswa' => 'Mahasiswa',
            'admin' => 'Admin',
            'penguji' => 'Penguji',
            default => 'Belum memilih role',
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isProfileComplete(): bool
    {
        if ($this->profile_completed) {
            return true;
        }

        $type = $this->primaryProfileType();
        $profile = $this->profileModel();

        return match ($type) {
            'mahasiswa' => $profile && filled($profile->nim) && filled($profile->phone) && filled($profile->study_program) && filled($profile->semester),
            'dosen' => $profile && filled($profile->phone) && (filled($profile->study_program) || filled($profile->department)),
            'pembimbing_lapangan' => $profile && filled($profile->phone) && filled($profile->institution_name) && filled($profile->position),
            default => filled($this->name) && filled($this->email),
        };
    }

    public function profileModel(): ?Model
    {
        $this->loadMissing(['student', 'lecturer', 'fieldSupervisor', 'roles']);

        return match ($this->primaryProfileType()) {
            'mahasiswa' => $this->student,
            'dosen' => $this->lecturer,
            'pembimbing_lapangan' => $this->fieldSupervisor,
            default => null,
        };
    }

    public function primaryProfileType(): string
    {
        $this->loadMissing('roles');
        $roleNames = $this->roles->pluck('name');

        if ($this->student || $roleNames->contains('mahasiswa')) {
            return 'mahasiswa';
        }

        if ($this->fieldSupervisor || $roleNames->contains('pembimbing_lapangan')) {
            return 'pembimbing_lapangan';
        }

        if ($this->lecturer || $roleNames->intersect(['koordinator_kp', 'pembimbing_dalam', 'penguji'])->isNotEmpty()) {
            return 'dosen';
        }

        return 'admin';
    }
}
