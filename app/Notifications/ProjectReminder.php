<?php

namespace App\Notifications;

use App\Models\Project;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

/**
 * Base for the daily in-app reminders: Filament database format plus the project id.
 */
abstract class ProjectReminder extends Notification
{
    public function __construct(public Project $project) {}

    abstract public function message(): string;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $data = FilamentNotification::make()
            ->title($this->project->name)
            ->body($this->message())
            ->actions([
                Action::make('apri')
                    ->label('Apri progetto')
                    ->url($this->project->detailUrl()),
            ])
            ->getDatabaseMessage();

        $data['project_id'] = $this->project->id;

        return $data;
    }
}
