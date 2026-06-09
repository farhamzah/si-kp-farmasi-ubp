<?php

namespace App\Services;

use App\Models\Core\CoreUser;
use App\Models\Role;
use App\Models\User;
use App\Support\CoreRoleTranslator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class CoreBridgeAuthService
{
    private ?string $failureReason = null;

    public function attempt(string $email, string $password, bool $remember = false): array
    {
        $this->failureReason = null;
        $mode = config('kp_auth.mode', 'legacy');

        if ($mode === 'legacy') {
            return $this->fallbackLegacyAttempt($email, $password, $remember);
        }

        $coreResult = $this->attemptCoreBridge($email, $password, $remember);
        if ($coreResult['ok']) {
            return $coreResult;
        }

        if ($mode === 'core_bridge_with_legacy_fallback' && in_array($coreResult['reason'], ['core_unavailable', 'invalid_credentials'], true)) {
            Log::warning('KP auth legacy fallback attempted after Core bridge failure.', [
                'email' => $this->normalize($email),
                'reason' => $coreResult['reason'],
            ]);

            return $this->fallbackLegacyAttempt($email, $password, $remember, 'legacy_fallback');
        }

        return $coreResult;
    }

    public function validateCoreUser(string $email, string $password): ?CoreUser
    {
        try {
            $coreUser = CoreUser::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$this->normalize($email)])
                ->first();
        } catch (Throwable $exception) {
            $this->failureReason = 'core_unavailable';
            Log::warning('KP auth Core lookup failed.', [
                'email' => $this->normalize($email),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $coreUser) {
            $this->failureReason = 'core_user_missing';
            Log::warning('KP auth Core user missing.', ['email' => $this->normalize($email)]);

            return null;
        }

        if (! $coreUser->active) {
            $this->failureReason = 'core_user_inactive';
            Log::warning('KP auth Core user inactive.', ['email' => $this->normalize($email)]);

            return null;
        }

        if ($coreUser->must_change_password) {
            $this->failureReason = 'core_password_must_change';
            Log::warning('KP auth Core user must change password before KP login.', ['email' => $this->normalize($email)]);

            return null;
        }

        if (! Hash::check($password, $coreUser->password)) {
            $this->failureReason = 'invalid_credentials';

            return null;
        }

        return $coreUser;
    }

    public function validateKpAppAccess(int $coreUserId): bool
    {
        $allowedRoles = config('kp_auth.core_bridge_allowed_roles', []);
        $coreUser = CoreUser::query()
            ->with(['roles', 'appAccesses'])
            ->find($coreUserId);
        $accesses = $coreUser?->appAccesses
            ->where('app_code', 'kp-farmasi')
            ->where('is_active', true) ?? collect();
        $coreRoles = $coreUser?->roles->pluck('name')->all() ?? [];
        $roleCandidates = $accesses
            ->pluck('role_slug')
            ->merge($coreRoles)
            ->filter()
            ->values()
            ->all();

        if ($accesses->isEmpty()) {
            $this->failureReason = 'core_app_access_denied';
            Log::warning('KP auth Core app access denied.', ['core_user_id' => $coreUserId]);

            return false;
        }

        if (in_array('admin-core', $roleCandidates, true) && collect($roleCandidates)->intersect($allowedRoles)->isEmpty()) {
            $this->failureReason = 'core_app_access_denied';
            Log::warning('KP auth denied admin-core-only Core app access.', ['core_user_id' => $coreUserId]);

            return false;
        }

        if (CoreRoleTranslator::coreRolesToKp($roleCandidates) === []) {
            $this->failureReason = 'core_app_access_denied';
            Log::warning('KP auth Core app access denied.', ['core_user_id' => $coreUserId]);

            return false;
        }

        return true;
    }

    public function resolveLegacyKpUser(int $coreUserId): ?User
    {
        $legacyUser = User::query()->where('core_user_id', $coreUserId)->first();

        if (! $legacyUser) {
            $this->failureReason = 'legacy_bridge_user_missing';
            Log::warning('KP auth legacy bridge user missing.', ['core_user_id' => $coreUserId]);

            return null;
        }

        if (method_exists($legacyUser, 'isActive') && ! $legacyUser->isActive()) {
            $this->failureReason = 'legacy_user_inactive';
            Log::warning('KP auth legacy bridge user inactive.', ['core_user_id' => $coreUserId]);

            return null;
        }

        return $legacyUser;
    }

    public function fallbackLegacyAttempt(string $email, string $password, bool $remember = false, string $via = 'legacy'): array
    {
        $ok = Auth::attempt(['email' => $email, 'password' => $password], $remember);
        if (! $ok) {
            $this->failureReason = 'invalid_credentials';

            return $this->result(false, null, $this->failureReason, $via);
        }

        Log::info('KP auth legacy login success.', [
            'email' => $this->normalize($email),
            'via' => $via,
        ]);

        return $this->result(true, Auth::user(), null, $via);
    }

    public function explainFailureReason(): ?string
    {
        return $this->failureReason;
    }

    private function attemptCoreBridge(string $email, string $password, bool $remember): array
    {
        $coreUser = $this->validateCoreUser($email, $password);
        if (! $coreUser) {
            return $this->result(false, null, $this->failureReason, 'core_bridge');
        }

        if (! $this->validateKpAppAccess($coreUser->id)) {
            return $this->result(false, null, $this->failureReason, 'core_bridge');
        }

        $legacyUser = $this->resolveLegacyKpUser($coreUser->id);
        if (! $legacyUser) {
            return $this->result(false, null, $this->failureReason, 'core_bridge');
        }

        $this->syncLegacyRolesFromCore($legacyUser, $coreUser);

        Auth::login($legacyUser, $remember);
        Log::info('KP auth Core bridge login success.', [
            'email' => $this->normalize($email),
            'core_user_id' => $coreUser->id,
            'legacy_user_id' => $legacyUser->id,
        ]);

        return $this->result(true, $legacyUser, null, 'core_bridge');
    }

    private function result(bool $ok, ?User $legacyUser, ?string $reason, string $via): array
    {
        return [
            'ok' => $ok,
            'legacy_user' => $legacyUser,
            'reason' => $reason,
            'via' => $via,
        ];
    }

    private function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    private function syncLegacyRolesFromCore(User $legacyUser, CoreUser $coreUser): void
    {
        $coreRoles = $coreUser->roles->pluck('name');
        $appAccessRoles = $coreUser->appAccesses
            ->where('app_code', 'kp-farmasi')
            ->where('is_active', true)
            ->pluck('role_slug');
        $kpRoles = CoreRoleTranslator::coreRolesToKp($appAccessRoles->merge($coreRoles));

        if ($kpRoles === []) {
            return;
        }

        $roleIds = Role::query()
            ->whereIn('name', $kpRoles)
            ->pluck('id')
            ->all();

        $legacyUser->roles()->sync($roleIds);
        $legacyUser->load('roles');
    }
}
