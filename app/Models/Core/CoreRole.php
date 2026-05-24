<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CoreRole extends ReadOnlyCoreModel
{
    protected $table = 'roles';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(CoreUser::class, 'user_roles', 'role_id', 'user_id');
    }
}
