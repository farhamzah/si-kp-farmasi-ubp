<?php

namespace App\Console\Commands;

use App\Models\KpAssignment;
use App\Services\KpPlaceSelectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignmentCancelReconcileCommand extends Command
{
    protected $signature = 'kp:assignment-cancel-reconcile
        {--execute : Apply local KP cleanup}
        {--confirm-execute : Confirm local KP write}
        {--assignment-id= : Limit to one assignment id}
        {--email= : Limit to student user email}
        {--show-rows : Show candidate rows}';

    protected $description = 'Dry-run or clean cancelled KP assignments that still keep supervisors or active place selections';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        if ($execute && ! $this->option('confirm-execute')) {
            $this->error('Execute refused: missing --confirm-execute.');

            return self::FAILURE;
        }

        $assignments = KpAssignment::query()
            ->with(['selection.registration', 'selection.quota', 'student.user', 'period', 'place', 'internalSupervisor.user', 'fieldSupervisor.user'])
            ->where('status', 'dibatalkan')
            ->where(function ($query): void {
                $query
                    ->whereNotNull('internal_supervisor_id')
                    ->orWhereNotNull('field_supervisor_id')
                    ->orWhereNotNull('active_key')
                    ->orWhereHas('selection', fn ($selection) => $selection->where('status', 'aktif'));
            })
            ->when($this->option('assignment-id'), fn ($query, $id) => $query->whereKey($id))
            ->when($this->option('email'), fn ($query, $email) => $query->whereHas('student.user', fn ($user) => $user->where('email', $email)))
            ->orderBy('id')
            ->get();

        $rows = [];
        $updated = 0;
        $supervisorsCleared = 0;
        $selectionsCancelled = 0;

        foreach ($assignments as $assignment) {
            $willClearSupervisors = filled($assignment->internal_supervisor_id) || filled($assignment->field_supervisor_id) || filled($assignment->active_key);
            $willCancelSelection = $assignment->selection?->status === 'aktif';

            $rows[] = [
                'id' => $assignment->id,
                'student' => $assignment->student?->user?->email ?? '-',
                'period' => $assignment->period?->name ?? '-',
                'place' => $assignment->place?->name ?? '-',
                'internal' => $assignment->internalSupervisor?->user?->name ?? '-',
                'field' => $assignment->fieldSupervisor?->user?->name ?? '-',
                'selection' => $assignment->selection?->status ?? '-',
                'action' => collect([
                    $willClearSupervisors ? 'clear_supervisors' : null,
                    $willCancelSelection ? 'cancel_selection' : null,
                ])->filter()->implode(', ') ?: 'none',
            ];

            if (! $execute) {
                if ($willClearSupervisors) {
                    $supervisorsCleared++;
                }
                if ($willCancelSelection) {
                    $selectionsCancelled++;
                }
                $updated++;
                continue;
            }

            DB::transaction(function () use ($assignment, &$updated, &$supervisorsCleared, &$selectionsCancelled): void {
                $locked = KpAssignment::query()
                    ->with(['selection.registration', 'selection.quota'])
                    ->lockForUpdate()
                    ->findOrFail($assignment->id);

                $oldInternal = $locked->internal_supervisor_id;
                $oldField = $locked->field_supervisor_id;
                $oldSelectionStatus = $locked->selection?->status;

                $locked->update([
                    'internal_supervisor_id' => null,
                    'field_supervisor_id' => null,
                    'active_key' => null,
                    'note' => trim(((string) $locked->note)."\nRekonsiliasi: pembimbing dilepas karena penempatan sudah dibatalkan."),
                ]);

                if ($oldInternal || $oldField) {
                    $supervisorsCleared++;
                }

                $locked->logs()->create([
                    'user_id' => null,
                    'action' => 'assignment_cancel_reconciled',
                    'old_status' => 'dibatalkan',
                    'new_status' => 'dibatalkan',
                    'old_internal_supervisor_id' => $oldInternal,
                    'new_internal_supervisor_id' => null,
                    'old_field_supervisor_id' => $oldField,
                    'new_field_supervisor_id' => null,
                    'note' => 'Rekonsiliasi: penempatan dibatalkan dilepas dari pembimbing dan selection aktif terkait dibatalkan bila ada.',
                ]);

                if ($locked->selection && $locked->selection->status === 'aktif') {
                    $locked->selection->update([
                        'status' => 'dibatalkan',
                        'cancelled_by' => null,
                        'cancelled_at' => now(),
                        'cancellation_reason' => 'Rekonsiliasi: penempatan terkait sudah dibatalkan.',
                        'active_key' => null,
                    ]);

                    if ($locked->selection->registration) {
                        app(KpPlaceSelectionService::class)->logSelection(
                            $locked->selection->registration,
                            $locked->selection->quota,
                            null,
                            'selection_cancel_reconciled_from_assignment',
                            'info',
                            'Rekonsiliasi: pilihan tempat dibatalkan karena penempatan terkait sudah dibatalkan.',
                            null,
                            null,
                            ['assignment_id' => $locked->id, 'old_selection_status' => $oldSelectionStatus]
                        );
                    }

                    $selectionsCancelled++;
                }

                $updated++;
            });
        }

        $this->line('KP assignment cancel reconcile');
        $this->line('Mode: '.($execute ? 'execute local KP updates' : 'dry-run only; no writes performed'));
        $this->line('Candidates scanned: '.$assignments->count());
        $this->line(($execute ? 'Updated' : 'Would update').': '.$updated);
        $this->line('Supervisors cleared: '.$supervisorsCleared);
        $this->line('Active selections cancelled: '.$selectionsCancelled);
        $this->line('Write to Core/TU/SAFA: no/no/no');

        if ($this->option('show-rows')) {
            $this->table(['ID', 'Student', 'Period', 'Place', 'Internal', 'Field', 'Selection', 'Action'], $rows);
        }

        return self::SUCCESS;
    }
}
