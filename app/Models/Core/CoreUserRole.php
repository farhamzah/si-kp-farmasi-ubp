<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreUserRole extends ReadOnlyCoreModel
{
    protected $table = 'user_roles';

    public function user(): BelongsTo
    {
        return $this->belongsTo(CoreUser::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(CoreRole::class, 'role_id');
    }
}
