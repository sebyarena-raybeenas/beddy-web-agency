<?php

namespace App\Notifications;

use App\Models\InternalActivity;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

/**
 * Daily in-app reminder for an internal activity close to (or past) its due date.
 */
class ActivityDeadlineReminder extends Notification
{
    public function __construct(public InternalActivity $activity, public CarbonImmutable $today) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function message(): string
    {
        $days = (int) $this->today->startOfDay()->diffInDays($this->activity->due_date->copy()->startOfDay(), false);

        $when = match (true) {
            $days > 1 => "scade tra {$days} giorni",
            $days === 1 => 'scade domani',
            $days === 0 => 'scade oggi',
            $days === -1 => 'scaduta da 1 giorno',
            default => 'scaduta da '.abs($days).' giorni',
        };

        return 'Attività interna '.$when.' ('.$this->activity->due_date->format('d/m/Y').'): completala o aggiorna la scadenza.';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $data = FilamentNotification::make()
            ->title($this->activity->name)
            ->body($this->message())
            ->actions([
                Action::make('apri')
                    ->label('Apri attività')
                    ->url($this->activity->detailUrl()),
            ])
            ->getDatabaseMessage();

        $data['internal_activity_id'] = $this->activity->id;

        return $data;
    }
}
