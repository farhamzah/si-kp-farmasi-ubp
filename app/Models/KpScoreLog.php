<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpScoreLog extends Model
{
    protected $fillable = ['kp_assignment_id', 'kp_score_id', 'kp_final_score_id', 'user_id', 'action', 'old_status', 'new_status', 'note', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function assignment() { return $this->belongsTo(KpAssignment::class, 'kp_assignment_id'); }
    public function score() { return $this->belongsTo(KpScore::class, 'kp_score_id'); }
    public function finalScore() { return $this->belongsTo(KpFinalScore::class, 'kp_final_score_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
