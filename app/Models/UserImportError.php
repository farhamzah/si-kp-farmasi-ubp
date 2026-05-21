<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserImportError extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'identifier',
        'error_message',
        'row_data',
    ];

    protected function casts(): array
    {
        return [
            'row_data' => 'array',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(UserImportBatch::class, 'import_batch_id');
    }
}
