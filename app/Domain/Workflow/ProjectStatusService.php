<?php

namespace App\Domain\Workflow;

use App\Events\ProjectStatusChanged;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectStatusService
{
    /**
     * Manual path: any state of the flow except the automatic "ready" one.
     *
     * @throws InvalidTransition
     */
    public function change(Project $project, string $to, ?User $by): void
    {
        $workflow = $project->workflow();

        $this->guardKnownAndNew($project, $to);

        if ($to === $workflow->ready()) {
            throw new InvalidTransition('Lo stato "'.$workflow->label($to).'" viene impostato solo in automatico');
        }

        $this->apply($project, $to, $by);
    }

    /**
     * Automatic path: may enter the "ready" state, recorded without a user.
     *
     * @throws InvalidTransition
     */
    public function applySystem(Project $project, string $to): void
    {
        $this->guardKnownAndNew($project, $to);

        $this->apply($project, $to, null);
    }

    private function guardKnownAndNew(Project $project, string $to): void
    {
        if (! $project->workflow()->has($to)) {
            throw new InvalidTransition("Lo stato \"{$to}\" non esiste nel flusso di questo progetto");
        }

        if ($to === $project->status) {
            throw new InvalidTransition('Il progetto è già in questo stato');
        }
    }

    private function apply(Project $project, string $to, ?User $by): void
    {
        $from = $project->status;
        $days = $project->workflow()->deadlineDays($to);

        DB::transaction(function () use ($project, $from, $to, $by, $days) {
            $project->forceFill([
                'status' => $to,
                'status_changed_at' => now(),
                'due_date' => $days === null ? null : today()->addDays($days),
            ])->save();

            $project->statusHistory()->create([
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $by?->id,
                'changed_at' => now(),
            ]);
        });

        ProjectStatusChanged::dispatch($project, $from, $to);
    }
}
