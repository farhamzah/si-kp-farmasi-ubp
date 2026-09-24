<?php

namespace App\Services;

use App\Models\Core\CoreLecturer;
use App\Models\Lecturer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class KpOfficialIdentityResolver
{
    private array $coreCache = [];

    public function resolve(?int $lecturerId, string $fallbackName, ?string $fallbackNuptk = null): array
    {
        $lecturer = $lecturerId ? Lecturer::query()->with('user')->find($lecturerId) : null;
        $lecturer ??= $this->findLegacyLecturer($fallbackName);

        if (! $lecturer) {
            return [
                'lecturer_id' => null,
                'name' => $fallbackName,
                'nuptk' => $fallbackNuptk,
                'source' => 'manual',
            ];
        }

        $core = $this->coreLecturerFor($lecturer);
        if (! $core) {
            return [
                'lecturer_id' => $lecturer->id,
                'name' => $lecturer->user?->name ?: $fallbackName,
                'nuptk' => $fallbackNuptk,
                'source' => 'legacy',
            ];
        }

        return [
            'lecturer_id' => $lecturer->id,
            'name' => $this->coreDisplayName($core) ?: $lecturer->user?->name ?: $fallbackName,
            'nuptk' => filled($core->nuptk) ? trim((string) $core->nuptk) : null,
            'source' => 'core',
        ];
    }

    public function options(): Collection
    {
        return Lecturer::query()
            ->with('user')
            ->whereHas('user')
            ->get()
            ->map(function (Lecturer $lecturer): array {
                $identity = $this->resolve($lecturer->id, (string) $lecturer->user?->name);

                return [
                    'id' => $lecturer->id,
                    'name' => $identity['name'],
                    'nuptk' => $identity['nuptk'],
                    'source' => $identity['source'],
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function findLegacyLecturer(string $name): ?Lecturer
    {
        $needle = $this->nameKey($name);
        if ($needle === '') {
            return null;
        }

        return Lecturer::query()
            ->with('user')
            ->whereHas('user')
            ->get()
            ->first(function (Lecturer $lecturer) use ($needle): bool {
                $candidate = $this->nameKey((string) $lecturer->user?->name);

                return $candidate === $needle
                    || ($candidate !== '' && (str_contains($needle, $candidate) || str_contains($candidate, $needle)));
            });
    }

    private function coreLecturerFor(Lecturer $lecturer): ?CoreLecturer
    {
        $cacheKey = $lecturer->core_lecturer_id
            ? 'lecturer:'.$lecturer->core_lecturer_id
            : ($lecturer->user?->core_user_id ? 'user:'.$lecturer->user->core_user_id : null);

        if ($cacheKey === null) {
            return null;
        }

        if (array_key_exists($cacheKey, $this->coreCache)) {
            return $this->coreCache[$cacheKey];
        }

        try {
            if ($lecturer->core_lecturer_id) {
                return $this->coreCache[$cacheKey] = CoreLecturer::query()->with('user')->find($lecturer->core_lecturer_id);
            }

            if ($lecturer->user?->core_user_id) {
                $core = CoreLecturer::query()
                    ->with('user')
                    ->where('user_id', $lecturer->user->core_user_id)
                    ->first();
                if ($core) {
                    return $this->coreCache[$cacheKey] = $core;
                }
            }
        } catch (Throwable) {
            // Core may be unavailable during a deployment or local test; keep the saved fallback identity usable.
        }

        return $this->coreCache[$cacheKey] = null;
    }

    private function coreDisplayName(CoreLecturer $lecturer): ?string
    {
        $name = $lecturer->display_name_with_title
            ?: $lecturer->formal_name
            ?: $this->composeTitledName($lecturer->front_title, $lecturer->name, $lecturer->back_title)
            ?: $lecturer->user?->display_name_with_title
            ?: $lecturer->user?->formal_name
            ?: $lecturer->user?->name
            ?: $lecturer->name;

        return filled($name) ? trim((string) $name) : null;
    }

    private function composeTitledName(?string $frontTitle, ?string $name, ?string $backTitle): ?string
    {
        if (blank($name)) {
            return null;
        }

        $display = trim(collect([$frontTitle, $name])->filter(fn ($value) => filled($value))->implode(' '));

        return filled($backTitle) ? $display.', '.trim($backTitle) : $display;
    }

    private function nameKey(string $name): string
    {
        $ignored = ['apt', 'dr', 'dra', 'drs', 'prof', 'ssi', 'sfarm', 'mfarm', 'msi', 'mm', 'mhum', 'spd', 'mpd'];

        return collect(preg_split('/\s+/', Str::lower(Str::ascii(preg_replace('/[^\pL\pN]+/u', ' ', $name)))) ?: [])
            ->filter(fn (string $part) => $part !== '' && ! in_array($part, $ignored, true))
            ->implode(' ');
    }
}
