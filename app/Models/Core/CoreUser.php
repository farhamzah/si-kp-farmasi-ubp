<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreUser extends ReadOnlyCoreModel
{
    protected $table = 'users';

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'must_change_password' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(CoreStudent::class, 'user_id');
    }

    public function lecturer(): HasOne
    {
        return $this->hasOne(CoreLecturer::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(CoreRole::class, 'user_roles', 'user_id', 'role_id');
    }

    public function appAccesses(): HasMany
    {
        return $this->hasMany(CoreUserAppAccess::class, 'user_id');
    }
}
