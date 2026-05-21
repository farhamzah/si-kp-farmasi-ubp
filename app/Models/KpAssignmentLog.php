<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpAssignmentLog extends Model
{
    protected $fillable = [
        'kp_assignment_id', 'user_id', 'action', 'old_status', 'new_status',
        'old_internal_supervisor_id', 'new_internal_supervisor_id',
        'old_field_supervisor_id', 'new_field_supervisor_id', 'note',
    ];

    public function assignment() { return $this->belongsTo(KpAssignment::class, 'kp_assignment_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function oldInternalSupervisor() { return $this->belongsTo(Lecturer::class, 'old_internal_supervisor_id'); }
    public function newInternalSupervisor() { return $this->belongsTo(Lecturer::class, 'new_internal_supervisor_id'); }
    public function oldFieldSupervisor() { return $this->belongsTo(FieldSupervisor::class, 'old_field_supervisor_id'); }
    public function newFieldSupervisor() { return $this->belongsTo(FieldSupervisor::class, 'new_field_supervisor_id'); }
}
