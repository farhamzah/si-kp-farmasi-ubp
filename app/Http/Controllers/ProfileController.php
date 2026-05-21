<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load(['roles', 'student', 'lecturer', 'fieldSupervisor']);

        return view('profile.show', [
            'user' => $user,
            'profileType' => $user->primaryProfileType(),
            'profile' => $user->profileModel(),
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $request->user()->load(['roles', 'student', 'lecturer', 'fieldSupervisor']);

        return view('profile.edit', [
            'user' => $user,
            'profileType' => $user->primaryProfileType(),
            'profile' => $user->profileModel(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user()->load(['roles', 'student', 'lecturer', 'fieldSupervisor']);
        $type = $user->primaryProfileType();

        $validated = match ($type) {
            'mahasiswa' => $request->validate([
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:1000'],
                'gender' => ['nullable', 'string', 'max:30'],
                'birth_place' => ['nullable', 'string', 'max:255'],
                'birth_date' => ['nullable', 'date'],
                'study_program' => ['nullable', 'string', 'max:255'],
                'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
                'class_name' => ['nullable', 'string', 'max:50'],
            ]),
            'dosen' => $request->validate([
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:1000'],
                'study_program' => ['nullable', 'string', 'max:255'],
                'department' => ['nullable', 'string', 'max:255'],
                'expertise' => ['nullable', 'string', 'max:255'],
            ]),
            'pembimbing_lapangan' => $request->validate([
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:1000'],
                'institution_name' => ['nullable', 'string', 'max:255'],
                'position' => ['nullable', 'string', 'max:255'],
            ]),
            default => $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            ]),
        };

        if ($type === 'admin') {
            $user->update($validated);
        } else {
            $profile = $user->profileModel();

            if (! $profile) {
                $profile = match ($type) {
                    'mahasiswa' => $user->student()->create([]),
                    'dosen' => $user->lecturer()->create([]),
                    'pembimbing_lapangan' => $user->fieldSupervisor()->create([]),
                };
            }

            $profile->update($validated);
        }

        $user->refresh()->load(['roles', 'student', 'lecturer', 'fieldSupervisor']);
        $complete = $user->isProfileComplete();
        $profile = $user->profileModel();

        if ($profile && $complete && blank($profile->profile_completed_at)) {
            $profile->update(['profile_completed_at' => now()]);
        }

        $user->update(['profile_completed' => $complete]);

        return redirect()->route('profile.show')->with('status', 'Profil berhasil diperbarui.');
    }

    public function avatar(Request $request): StreamedResponse
    {
        $user = $request->user();

        abort_if(! $user->avatar_path || ! Storage::disk($user->avatar_disk ?? 'local')->exists($user->avatar_path), 404);

        return Storage::disk($user->avatar_disk ?? 'local')->response(
            $user->avatar_path,
            $user->avatar_original_filename,
            [
                'Content-Type' => $user->avatar_mime ?: 'image/jpeg',
                'Cache-Control' => 'private, max-age=300',
            ]
        );
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2048),
            ],
        ], [
            'avatar.required' => 'Pilih file foto profil terlebih dahulu.',
            'avatar.image' => 'Foto profil harus berupa gambar JPG, PNG, atau WebP.',
            'avatar.mimes' => 'Foto profil harus berupa JPG, PNG, atau WebP.',
            'avatar.max' => 'Ukuran foto profil maksimal 2MB.',
        ]);

        $user = $request->user();
        $file = $validated['avatar'];
        $disk = 'local';
        $directory = 'avatars/'.$user->id;
        $filename = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs($directory, $filename, $disk);

        $oldDisk = $user->avatar_disk ?: $disk;
        $oldPath = $user->avatar_path;

        $user->update([
            'avatar_path' => $path,
            'avatar_disk' => $disk,
            'avatar_original_filename' => $file->getClientOriginalName(),
            'avatar_mime' => $file->getMimeType(),
            'avatar_size' => $file->getSize(),
        ]);

        if ($oldPath) {
            rescue(fn () => Storage::disk($oldDisk)->delete($oldPath), report: false);
        }

        return back()->with('status', 'Foto profil berhasil diperbarui.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        $oldDisk = $user->avatar_disk ?: 'local';
        $oldPath = $user->avatar_path;

        $user->update([
            'avatar_path' => null,
            'avatar_disk' => 'local',
            'avatar_original_filename' => null,
            'avatar_mime' => null,
            'avatar_size' => null,
        ]);

        if ($oldPath) {
            rescue(fn () => Storage::disk($oldDisk)->delete($oldPath), report: false);
        }

        return back()->with('status', 'Foto profil berhasil dihapus.');
    }
}
