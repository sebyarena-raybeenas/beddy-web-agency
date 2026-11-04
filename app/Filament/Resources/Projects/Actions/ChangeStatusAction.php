<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Domain\Workflow\InvalidTransition;
use App\Domain\Workflow\ProjectStatusService;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ChangeStatusAction
{
    public static function make(): Action
    {
        return Action::make('changeStatus')
            ->label('Cambia stato')
            ->icon('heroicon-o-arrow-path')
            ->modalHeading('Cambia stato')
            ->schema(fn (Page $livewire): array => [
                Select::make('status')
                    ->label('Nuovo stato')
                    ->options(fn (): array => self::options($livewire->getRecord()))
                    ->required(),
            ])
            ->action(function (array $data, Page $livewire): void {
                /** @var Project $project */
                $project = $livewire->getRecord();

                try {
                    app(ProjectStatusService::class)->change($project, $data['status'], auth()->user());
                } catch (InvalidTransition $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                $project->refresh();

                if (method_exists($livewire, 'refreshFormData')) {
                    $livewire->refreshFormData(['status', 'due_date']);
                }

                Notification::make()->title('Stato aggiornato: '.$project->statusLabel())->success()->send();
            });
    }

    /**
     * Manual states: every state of the flow except the automatic "ready" one and the current one.
     *
     * @return array<string, string>
     */
    private static function options(Project $project): array
    {
        $workflow = $project->workflow();

        return collect($workflow->states())
            ->except([$workflow->ready(), $project->status])
            ->all();
    }
}
