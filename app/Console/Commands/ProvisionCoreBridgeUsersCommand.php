<?php

namespace App\Console\Commands;

use App\Models\Core\CoreUser;
use App\Services\KpCoreBridgeProvisioningService;
use Illuminate\Console\Command;
use Throwable;

class ProvisionCoreBridgeUsersCommand extends Command
{
    protected $signature = 'kp:provision-core-bridge-users
        {--execute : Create/link KP legacy bridge users and roles}
        {--confirm-execute : Confirm write to KP users/user_roles/profile tables}
        {--limit=0 : Limit number of Core users processed; 0 means no limit}';

    protected $description = 'Bulk dry-run or sync KP legacy bridge users from Core kp-farmasi app access';

    public function handle(KpCoreBridgeProvisioningService $service): int
    {
        $execute = (bool) $this->option('execute');

        if ($execute && ! $this->option('confirm-execute')) {
            $this->error('Execute refused: missing --confirm-execute.');

            return self::FAILURE;
        }

        try {
            $emails = $this->emailsToProcess();
        } catch (Throwable $exception) {
            $this->error('Core lookup failed safely: '.$exception->getMessage());

            return self::FAILURE;
        }

        $summary = [
            'mode' => $execute ? 'execute KP bridge write' : 'dry-run only; no writes performed',
            'total' => count($emails),
            'created' => 0,
            'synced' => 0,
            'skipped' => 0,
            'blocked' => 0,
        ];

        $this->info('KP Core bridge bulk provisioning');
        $this->line('Mode: '.$summary['mode']);
        $this->line('Core users with active kp-farmasi access: '.$summary['total']);

        foreach ($emails as $email) {
            $report = $execute ? $service->execute($email) : $service->plan($email);
            $action = (string) $report['action'];

            if ($report['blockers'] !== []) {
                $summary['blocked']++;
                $this->warn("  - {$email}: blocked");
                foreach ($report['blockers'] as $blocker) {
                    $this->warn("    {$blocker}");
                }

                continue;
            }

            match ($action) {
                'create', 'created' => $summary['created']++,
                'link', 'update', 'synced' => $summary['synced']++,
                default => $summary['skipped']++,
            };

            $this->line("  - {$email}: {$action}; roles=".implode(',', $report['kp_roles']));
        }

        $this->newLine();
        $this->line('Summary:');
        $this->line('  total: '.$summary['total']);
        $this->line('  created: '.$summary['created']);
        $this->line('  synced: '.$summary['synced']);
        $this->line('  skipped: '.$summary['skipped']);
        $this->line('  blocked: '.$summary['blocked']);

        return $summary['blocked'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function emailsToProcess(): array
    {
        $query = CoreUser::query()
            ->whereHas('appAccesses', function ($query): void {
                $query
                    ->where('app_code', 'kp-farmasi')
                    ->where('is_active', true);
            })
            ->whereNotNull('email')
            ->orderBy('id');

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->values()
            ->all();
    }
}
