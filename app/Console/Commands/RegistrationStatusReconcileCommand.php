<?php

namespace App\Console\Commands;

use App\Models\KpRegistration;
use Illuminate\Console\Command;

class RegistrationStatusReconcileCommand extends Command
{
    protected $signature = 'kp:registration-status-reconcile
        {--execute : Apply KP registration status updates}
        {--confirm-execute : Confirm local KP write}
        {--registration-id= : Limit to one KP registration id}
        {--email= : Limit to student user email}
        {--show-rows : Show candidate rows}';

    protected $description = 'Dry-run or reconcile local KP registrations whose required documents are approved but registration is still draft/revisi';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        if ($execute && ! $this->option('confirm-execute')) {
            $this->error('Execute refused: missing --confirm-execute.');

            return self::FAILURE;
        }

        $registrations = KpRegistration::query()
            ->with(['period.documentRequirements', 'student.user', 'documents.requirement'])
            ->whereIn('status', ['draft', 'revisi'])
            ->when($this->option('registration-id'), fn ($query, $id) => $query->whereKey($id))
            ->when($this->option('email'), fn ($query, $email) => $query->whereHas('student.user', fn ($user) => $user->where('email', $email)))
            ->orderBy('id')
            ->get();

        $rows = [];
        $updated = 0;
        $skipped = 0;

        foreach ($registrations as $registration) {
            $requiredCount = $registration->period?->documentRequirements
                ->where('status', 'aktif')
                ->where('is_required', true)
                ->count() ?? 0;

            $allApproved = $requiredCount > 0 && $registration->allRequiredDocumentsApproved();
            $action = $allApproved ? 'promote_to_waiting_verification' : 'skip';

            $rows[] = [
                'id' => $registration->id,
                'student' => $registration->student?->user?->email ?? '-',
                'period' => $registration->period?->name ?? '-',
                'status' => $registration->status,
                'progress' => $registration->progressPercentage().'%',
                'required' => $requiredCount,
                'all_required_approved' => $allApproved ? 'yes' : 'no',
                'action' => $action,
            ];

            if (! $allApproved) {
                $skipped++;
                continue;
            }

            if ($execute) {
                $oldStatus = $registration->status;
                $registration->update([
                    'status' => 'menunggu_verifikasi',
                    'submitted_at' => $registration->submitted_at ?: now(),
                    'registration_number' => $registration->registration_number ?: $this->makeRegistrationNumber($registration),
                ]);

                $registration->logs()->create([
                    'user_id' => null,
                    'action' => 'registration_status_reconciled',
                    'old_status' => $oldStatus,
                    'new_status' => 'menunggu_verifikasi',
                    'note' => 'Rekonsiliasi status: semua dokumen wajib sudah disetujui, pendaftaran dinaikkan ke Menunggu Verifikasi.',
                ]);
            }

            $updated++;
        }

        $this->line('KP registration status reconcile');
        $this->line('Mode: '.($execute ? 'execute local KP updates' : 'dry-run only; no writes performed'));
        $this->line('Candidates scanned: '.$registrations->count());
        $this->line(($execute ? 'Updated' : 'Would update').': '.$updated);
        $this->line('Skipped: '.$skipped);
        $this->line('Write to Core/TU/SAFA: no/no/no');

        if ($this->option('show-rows')) {
            $this->table(['ID', 'Student', 'Period', 'Status', 'Progress', 'Required', 'All Required Approved', 'Action'], $rows);
        }

        return self::SUCCESS;
    }

    private function makeRegistrationNumber(KpRegistration $registration): string
    {
        return 'KP-'.now()->year.'-'.str_pad((string) $registration->id, 4, '0', STR_PAD_LEFT);
    }
}
