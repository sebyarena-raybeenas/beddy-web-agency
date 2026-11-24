<?php

namespace App\Filament\Resources\InternalActivities\Pages;

use App\Filament\Resources\InternalActivities\InternalActivityResource;
use App\Models\InternalActivity;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

/**
 * Detail of an internal activity: description, custom to-dos and notes.
 *
 * @property InternalActivity $record
 */
class ActivityDetail extends Page
{
    use InteractsWithRecord;

    protected static string $resource = InternalActivityResource::class;

    protected string $view = 'filament.pages.activity-detail';

    protected ?string $heading = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function toggleCompleted(): void
    {
        $activity = $this->activity();
        $activity->update(['completed_at' => $activity->isCompleted() ? null : now()]);

        Notification::make()
            ->title($activity->isCompleted() ? 'Attività completata' : 'Attività riaperta')
            ->success()
            ->send();
    }

    public function addTodo(string $title): void
    {
        $title = trim($title);

        if ($title === '') {
            return;
        }

        if (mb_strlen($title) > 255) {
            Notification::make()->title('Il to-do è troppo lungo (massimo 255 caratteri)')->danger()->send();

            return;
        }

        $activity = $this->activity();
        $activity->todos()->create([
            'title' => $title,
            'sort' => (int) $activity->todos()->max('sort') + 1,
            'created_by' => auth()->id(),
        ]);
    }

    public function toggleTodo(int $id): void
    {
        $this->activity()->todos()->findOrFail($id)->toggle();
    }

    public function deleteTodo(int $id): void
    {
        $this->activity()->todos()->findOrFail($id)->delete();
    }

    public function addNote(string $body): void
    {
        $body = trim($body);

        if ($body === '') {
            return;
        }

        if (mb_strlen($body) > 5000) {
            Notification::make()->title('La nota è troppo lunga (massimo 5000 caratteri)')->danger()->send();

            return;
        }

        $this->activity()->notes()->create(['user_id' => auth()->id(), 'body' => $body]);
    }

    public function deleteNote(int $id): void
    {
        $note = $this->activity()->notes()->findOrFail($id);

        if ($note->user_id !== auth()->id()) {
            Notification::make()->title('Puoi eliminare solo le tue note')->danger()->send();

            return;
        }

        $note->delete();
    }

    protected function getViewData(): array
    {
        $activity = $this->activity()->load(['owner', 'creator', 'todos', 'notes.user']);
        $total = $activity->todos->count();
        $done = $activity->todos->whereNotNull('completed_at')->count();

        return [
            'activity' => $activity,
            'todosTotal' => $total,
            'todosDone' => $done,
            'todosPct' => $total > 0 ? (int) round($done / $total * 100) : 0,
            'dueDays' => $activity->due_date !== null ? (int) today()->diffInDays($activity->due_date, false) : null,
            'indexUrl' => InternalActivityResource::getUrl('index'),
            'editUrl' => InternalActivityResource::getUrl('edit', ['record' => $activity]),
        ];
    }

    /** The record, re-read so every action and render works on current data. */
    private function activity(): InternalActivity
    {
        return $this->getRecord()->refresh();
    }
}
