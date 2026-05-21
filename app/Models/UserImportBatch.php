<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserImportBatch extends Model
{
    protected $fillable = [
        'imported_by',
        'import_type',
        'original_filename',
        'total_rows',
        'success_rows',
        'failed_rows',
        'status',
        'notes',
    ];

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function errors()
    {
        return $this->hasMany(UserImportError::class, 'import_batch_id');
    }
}
