<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpQuotaLog extends Model
{
    protected $fillable = [
        'kp_place_quota_id',
        'user_id',
        'old_quota',
        'new_quota',
        'old_is_open',
        'new_is_open',
        'action',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'old_is_open' => 'boolean',
            'new_is_open' => 'boolean',
        ];
    }

    public function quota()
    {
        return $this->belongsTo(KpPlaceQuota::class, 'kp_place_quota_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
