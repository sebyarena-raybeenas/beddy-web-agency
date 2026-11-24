<?php

namespace App\Filament\Resources\InternalActivities\Pages;

use App\Filament\Resources\InternalActivities\InternalActivityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInternalActivity extends CreateRecord
{
    protected static string $resource = InternalActivityResource::class;

    protected static ?string $title = 'Nuova attività interna';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getRecord()->detailUrl();
    }
}
