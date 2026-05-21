<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpFinalReportLog extends Model
{
    protected $fillable = ['kp_final_report_id', 'user_id', 'action', 'old_status', 'new_status', 'note', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function report() { return $this->belongsTo(KpFinalReport::class, 'kp_final_report_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
