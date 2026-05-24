<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreUserAppAccess extends ReadOnlyCoreModel
{
    protected $table = 'user_app_accesses';

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CoreUser::class, 'user_id');
    }
}
