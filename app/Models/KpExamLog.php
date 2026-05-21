<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpExamLog extends Model
{
    protected $fillable = ['kp_exam_request_id', 'kp_exam_id', 'user_id', 'action', 'old_status', 'new_status', 'note', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function request() { return $this->belongsTo(KpExamRequest::class, 'kp_exam_request_id'); }
    public function exam() { return $this->belongsTo(KpExam::class, 'kp_exam_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
