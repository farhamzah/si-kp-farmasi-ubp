<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
