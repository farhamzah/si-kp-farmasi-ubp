<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UploadKpDocumentRequest;
use App\Models\KpDocument;
use App\Models\KpDocumentRequirement;
use App\Models\KpRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class KpDocumentUploadController extends Controller
{
    public function store(UploadKpDocumentRequest $request, KpRegistration $registration, KpDocumentRequirement $requirement): RedirectResponse
    {
        abort_unless($request->user()->student?->id === $registration->student_id, 403);

        $document = KpDocument::firstOrCreate([
            'kp_registration_id' => $registration->id,
            'kp_document_requirement_id' => $requirement->id,
        ]);

        if ($document->file_path) {
            Storage::disk($document->file_disk)->delete($document->file_path);
        }

        $file = $request->file('document');
        $path = $file->store('kp-documents/'.$registration->id, 'local');

        $oldStatus = $document->status;
        $document->update([
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_disk' => 'local',
            'file_mime' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'menunggu',
            'review_note' => null,
            'uploaded_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $registration->logs()->create([
            'user_id' => $request->user()->id,
            'action' => 'document_uploaded',
            'old_status' => $oldStatus,
            'new_status' => 'menunggu',
            'note' => 'Upload dokumen: '.$requirement->name,
        ]);

        if ($registration->status === 'revisi') {
            $registration->update(['status' => 'draft']);
        }

        return back()->with('status', 'Dokumen berhasil diupload.');
    }
}
