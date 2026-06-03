<?php

namespace App\Console\Commands;

use App\Support\RoleDashboard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UiReadinessCheckCommand extends Command
{
    protected $signature = 'kp:ui-readiness-check';

    protected $description = 'Read-only UI/UX production readiness checks for KP Farmasi views';

    public function handle(): int
    {
        $checks = $this->checks();
        $blockers = collect($checks)->where('level', 'blocker')->where('passed', false)->values();
        $warnings = collect($checks)->where('level', 'warning')->where('passed', false)->values();

        $this->info('KP UI readiness check');
        $this->line('Read-only: yes');
        $this->line('Checks: '.count($checks));
        $this->line('Blockers: '.$blockers->count());
        $this->line('Warnings: '.$warnings->count());
        $this->line('Ready for UI/UX UAT: '.($blockers->isEmpty() ? 'yes' : 'no'));

        $failed = collect($checks)->where('passed', false)->values();

        if ($failed->isNotEmpty()) {
            $this->newLine();
            $this->line('Open items:');
            foreach ($failed as $item) {
                $prefix = $item['level'] === 'blocker' ? 'BLOCKER' : 'WARNING';
                $this->line("  [{$prefix}] {$item['key']}: {$item['message']}");
            }
        }

        return $blockers->isEmpty() ? self::SUCCESS : self::FAILURE;
    }

    private function checks(): array
    {
        $layout = $this->contents(resource_path('views/layouts/app.blade.php'));
        $login = $this->contents(resource_path('views/auth/login.blade.php'));
        $css = $this->contents(resource_path('css/app.css'));

        return [
            $this->check('main_layout_exists', $layout !== '', 'Layout utama wajib tersedia.'),
            $this->check('login_view_exists', $login !== '', 'Halaman login wajib tersedia.'),
            $this->check('viewport_meta_exists', str_contains($layout, 'name="viewport"'), 'Layout wajib punya viewport meta untuk mobile.'),
            $this->check('page_overflow_guard_exists', str_contains($layout, 'overflow-x-hidden'), 'Layout wajib punya guard horizontal overflow.'),
            $this->check('sidebar_scroll_guard_exists', str_contains($layout, 'si-sidebar-scroll'), 'Sidebar panjang wajib punya scroll guard.'),
            $this->check('topbar_truncation_exists', str_contains($layout, 'truncate'), 'Nama user, role, dan judul wajib ditruncate agar tidak overlap.'),
            $this->check('responsive_table_css_exists', str_contains($css, 'min-width: max(100%, 48rem)'), 'Tabel besar wajib aman pada layar kecil.'),
            $this->check('focus_visible_exists', str_contains($css, ':focus-visible'), 'Focus state keyboard wajib tersedia.'),
            $this->check('login_error_state_exists', str_contains($login, '$errors->any()'), 'Login wajib punya error state jelas.'),
            $this->check('login_mobile_safe_height', str_contains($login, 'min-h-screen'), 'Login wajib aman pada tinggi layar mobile.'),
            $this->check('role_menus_do_not_ship_placeholders', $this->roleMenusHaveNoKnownPlaceholders(), 'Menu role production tidak boleh menampilkan placeholder Segera.'),
            $this->warning('visual_browser_screenshot_required', false, 'Screenshot desktop/mobile tetap perlu dilakukan di browser normal sebelum go-live.'),
        ];
    }

    private function roleMenusHaveNoKnownPlaceholders(): bool
    {
        $unsupported = ['Catatan Lapangan', 'Detail Mahasiswa'];

        foreach (RoleDashboard::ROLES as $role) {
            if (collect($role['menu'] ?? [])->intersect($unsupported)->isNotEmpty()) {
                return false;
            }
        }

        return true;
    }

    private function check(string $key, bool $passed, string $message): array
    {
        return [
            'key' => $key,
            'level' => 'blocker',
            'passed' => $passed,
            'message' => $message,
        ];
    }

    private function warning(string $key, bool $passed, string $message): array
    {
        return [
            'key' => $key,
            'level' => 'warning',
            'passed' => $passed,
            'message' => $message,
        ];
    }

    private function contents(string $path): string
    {
        if (! File::exists($path)) {
            return '';
        }

        return (string) File::get($path);
    }
}
