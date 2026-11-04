<?php

namespace App\Domain\Reminders;

use App\Models\Project;
use App\Notifications\DeadlineReminder;
use App\Notifications\MaterialOverdueReminder;
use App\Notifications\ProjectReadyReminder;
use App\Notifications\ProjectReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReminderService
{
    private const TIMEZONE = 'Europe/Rome';

    /**
     * Sends the daily reminders to project owners; returns how many were sent.
     */
    public function run(CarbonImmutable $today): int
    {
        $sent = 0;

        Project::query()
            ->whereNotNull('owner_id')
            ->with(['owner', 'materials'])
            ->each(function (Project $project) use ($today, &$sent) {
                $workflow = $project->workflow();

                if (! $project->owner || $workflow->isFinal($project->status)) {
                    return;
                }

                if ($project->status === $workflow->ready()) {
                    $sent += $this->send($project, new ProjectReadyReminder($project), $today);
                }

                if ($workflow->deadlineDays($project->status) !== null
                    && $project->due_date !== null
                    && $today->format('Y-m-d') >= $project->due_date->format('Y-m-d')) {
                    $sent += $this->send($project, new DeadlineReminder($project), $today);
                }

                $kinds = $project->lateMaterials($today)
                    ->map(fn ($m) => mb_strtolower($m->kind->label()))
                    ->all();

                if ($kinds !== []) {
                    $sent += $this->send($project, new MaterialOverdueReminder($project, $kinds), $today);
                }
            });

        return $sent;
    }

    private function send(Project $project, ProjectReminder $notification, CarbonImmutable $today): int
    {
        if ($this->alreadySent($project, $notification, $today)) {
            return 0;
        }

        $project->owner->notify($notification);

        return 1;
    }

    private function alreadySent(Project $project, ProjectReminder $notification, CarbonImmutable $today): bool
    {
        // The day is the calendar date in Rome, converted to the storage timezone.
        $start = CarbonImmutable::parse($today->format('Y-m-d'), self::TIMEZONE)->startOfDay();
        $appTz = config('app.timezone');

        return DB::table('notifications')
            ->where('type', $notification::class)
            ->where('data->project_id', $project->id)
            ->where('created_at', '>=', $start->setTimezone($appTz)->format('Y-m-d H:i:s'))
            ->where('created_at', '<=', $start->endOfDay()->setTimezone($appTz)->format('Y-m-d H:i:s'))
            ->exists();
    }
}
