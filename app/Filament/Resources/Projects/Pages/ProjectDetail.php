<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Domain\Workflow\InvalidTransition;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\MaterialStatus;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Projects\Actions\ChangeStatusAction;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectStatusChange;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Models\Activity;

/**
 * Project detail (replaces the standard View page at `progetti/{record}`), ported from section #det of the demo.
 *
 * @property Project $record
 */
class ProjectDetail extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.project-detail';

    protected ?string $heading = '';

    /** aperti | completati */
    public string $tab = 'aperti';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->client->name.' — '.$this->getRecord()->name;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function changeStatusAction(): Action
    {
        return ChangeStatusAction::make()->color('primary');
    }

    /* Livewire actions: every id is resolved through the project's own relation. */

    public function changeStatus(string $to): void
    {
        $project = $this->project();

        try {
            app(ProjectStatusService::class)->change($project, $to, auth()->user());
        } catch (InvalidTransition $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Stato aggiornato: '.$project->statusLabel())->success()->send();
    }

    public function toggleRequirement(int $id): void
    {
        $project = $this->project();
        $requirement = $project->requirements()->findOrFail($id);
        $done = ! $requirement->is_done;

        $before = $project->status;

        // Through Eloquent: the observer re-evaluates the project's automatic transition.
        $requirement->update([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
            'done_by' => $done ? auth()->id() : null,
        ]);

        $this->notifyIfStatusChanged($before);
    }

    public function setMaterialStatus(int $id, string $status): void
    {
        $project = $this->project();
        $material = $project->materials()->findOrFail($id);

        $new = MaterialStatus::tryFrom($status);

        if ($new === null) {
            Notification::make()->title('Stato del materiale non valido')->danger()->send();

            return;
        }

        $before = $project->status;

        $material->update(['status' => $new]);

        $this->notifyIfStatusChanged($before);
    }

    public function toggleTask(int $id): void
    {
        $task = $this->project()->tasks()->findOrFail($id);

        $task->completed_at === null ? $task->complete() : $task->reopen();
    }

    public function addTask(string $title, ?int $assigneeId = null, ?string $dueDate = null): void
    {
        $project = $this->project();

        $validator = Validator::make(
            ['title' => trim($title), 'assignee' => $assigneeId, 'due' => filled($dueDate) ? $dueDate : null],
            ['title' => ['required', 'string', 'max:255'], 'assignee' => ['nullable', 'exists:users,id'], 'due' => ['nullable', 'date']],
            [],
            ['title' => 'titolo', 'assignee' => 'assegnatario', 'due' => 'scadenza'],
        );

        if ($validator->fails()) {
            Notification::make()->title($validator->errors()->first())->danger()->send();

            return;
        }

        $project->tasks()->create([
            'title' => trim($title),
            'assignee_id' => $assigneeId,
            'due_date' => filled($dueDate) ? $dueDate : null,
            'created_by' => auth()->id(),
            'sort' => (int) $project->tasks()->max('sort') + 1,
        ]);
    }

    public function addComment(string $body): void
    {
        $body = trim($body);

        if ($body === '') {
            return;
        }

        if (mb_strlen($body) > 5000) {
            Notification::make()->title('Il commento è troppo lungo (massimo 5000 caratteri)')->danger()->send();

            return;
        }

        $this->project()->comments()->create(['user_id' => auth()->id(), 'body' => $body]);
    }

    /* View */

    protected function getViewData(): array
    {
        $project = $this->project();
        $project->load([
            'client.contacts', 'owner', 'creator', 'members', 'primaryDomain',
            'requirements.doneBy',
            'materials.attachments', 'materials.requirement',
            'tasks.assignee', 'tasks.templateItem.template',
            'comments.user',
        ]);

        $workflow = $project->workflow();
        $trigger = $workflow->checklistTrigger();

        [$completed, $open] = $project->tasks->sortBy('sort')->partition(fn (Task $t) => $t->completed_at !== null);
        $shown = $this->tab === 'completati' ? $completed : $open;

        $isChecklist = fn (Task $t): bool => $trigger !== null && $t->templateItem?->template?->trigger_status === $trigger;

        $dueDays = $project->due_date !== null ? (int) today()->diffInDays($project->due_date, false) : null;
        $tasksTotal = $project->tasks->count();

        return [
            'project' => $project,
            'states' => $workflow->states(),
            'ready' => $workflow->ready(),
            'openCount' => $open->count(),
            'completedCount' => $completed->count(),
            'tasksTotal' => $tasksTotal,
            'tasksPct' => $tasksTotal > 0 ? (int) round($completed->count() / $tasksTotal * 100) : 0,
            'shown' => $shown->reject($isChecklist)->values(),
            'checklist' => $shown->filter($isChecklist)->values(),
            'people' => collect([$project->owner])->merge($project->members)->filter()->unique('id')->values(),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'contact' => $project->client->contacts->firstWhere('is_primary', true) ?? $project->client->contacts->first(),
            'alert' => $project->due_date !== null ? $workflow->deadlineMessage($project->status) : null,
            'dueDays' => $dueDays,
            'overdue' => $project->isOverdue(),
            'timeline' => $project->timeline()->take(12)->map(fn ($entry) => $this->describe($entry, $project)),
            'materialStatuses' => MaterialStatus::cases(),
            'clientUrl' => ClientResource::getUrl('edit', ['record' => $project->client]),
            'editUrl' => ProjectResource::getUrl('edit', ['record' => $project]),
        ];
    }

    /** The record, re-read so every action and render works on current data. */
    private function project(): Project
    {
        return $this->getRecord()->refresh();
    }

    private function notifyIfStatusChanged(string $before): void
    {
        $project = $this->project();

        if ($project->status !== $before) {
            Notification::make()->title('Stato aggiornato: '.$project->statusLabel())->success()->send();
        }
    }

    /**
     * One timeline line.
     *
     * @return array{html: HtmlString, at: string}
     */
    private function describe(Activity|ProjectStatusChange $entry, Project $project): array
    {
        $b = fn (string $s): string => '<b>'.e($s).'</b>';

        if ($entry instanceof ProjectStatusChange) {
            $label = $project->workflow()->states()[$entry->to_status] ?? $entry->to_status;
            $who = $entry->user?->name;
            $html = $who === null
                ? $b('Sistema').': stato impostato in '.$b($label)
                : $b($who).' ha cambiato lo stato in '.$b($label);

            return ['html' => new HtmlString($html), 'at' => $entry->changed_at->format('d/m/Y H:i')];
        }

        $who = $b($entry->causer?->name ?? 'Sistema');
        $subject = $entry->subject;
        $attributes = $entry->attribute_changes?->get('attributes') ?? [];
        $name = match (class_basename($entry->subject_type)) {
            'ProjectRequirement' => 'il requisito '.$b('«'.($subject?->label ?? '').'»'),
            'Material' => 'il materiale '.$b($subject?->kind?->label() ?? ''),
            'Task' => 'il to-do '.$b('«'.($subject?->title ?? '').'»'),
            default => 'il progetto',
        };

        $verb = match (true) {
            $entry->description === 'creato' => 'ha creato',
            $entry->description === 'eliminato' => 'ha eliminato',
            array_key_exists('is_done', $attributes) => $attributes['is_done'] ? 'ha completato' : 'ha riaperto',
            array_key_exists('completed_at', $attributes) => $attributes['completed_at'] ? 'ha completato' : 'ha riaperto',
            array_key_exists('status', $attributes) && $subject instanceof Material => 'ha segnato «'.e((MaterialStatus::tryFrom((string) $attributes['status']))?->label() ?? $attributes['status']).'»',
            default => 'ha modificato',
        };

        return ['html' => new HtmlString("{$who} {$verb} {$name}"), 'at' => $entry->created_at->format('d/m/Y H:i')];
    }
}
