<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpLogbookLog extends Model
{
    protected $fillable = [
        'kp_logbook_id',
        'user_id',
        'action',
        'old_status',
        'new_status',
        'note',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function logbook(): BelongsTo
    {
        return $this->belongsTo(KpLogbook::class, 'kp_logbook_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
