<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

abstract class ReadOnlyCoreModel extends Model
{
    protected $connection = 'core';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(fn (): bool => throw new RuntimeException('Core read models are read-only from KP.'));
        static::deleting(fn (): bool => throw new RuntimeException('Core read models are read-only from KP.'));
    }
}
