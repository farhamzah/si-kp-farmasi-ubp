<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpFinalReportFile extends Model
{
    protected $fillable = ['kp_final_report_id', 'version', 'original_filename', 'file_path', 'file_disk', 'file_mime', 'file_size', 'uploaded_by', 'uploaded_at', 'note'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }

    public function report() { return $this->belongsTo(KpFinalReport::class, 'kp_final_report_id'); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function humanFileSize(): string
    {
        if (! $this->file_size) {
            return '-';
        }

        $size = (float) $this->file_size;
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($size < 1024) {
                return round($size, 1).' '.$unit;
            }
            $size /= 1024;
        }

        return round($size, 1).' TB';
    }

    public function fileIconLabel(): string
    {
        return str_contains((string) $this->file_mime, 'pdf') ? 'PDF' : 'Dokumen';
    }
}
