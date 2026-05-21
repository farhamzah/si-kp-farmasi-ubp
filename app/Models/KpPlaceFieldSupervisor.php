<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpPlaceFieldSupervisor extends Model
{
    protected $fillable = ['kp_place_id', 'field_supervisor_id', 'status', 'created_by'];

    public function place() { return $this->belongsTo(KpPlace::class, 'kp_place_id'); }
    public function fieldSupervisor() { return $this->belongsTo(FieldSupervisor::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
