<?php

namespace App\Filament\Resources\InternalActivities\Pages;

use App\Filament\Resources\InternalActivities\InternalActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInternalActivities extends ListRecords
{
    protected static string $resource = InternalActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuova attività')];
    }
}
