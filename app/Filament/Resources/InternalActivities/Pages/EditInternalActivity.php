<?php

namespace App\Filament\Resources\InternalActivities\Pages;

use App\Filament\Resources\InternalActivities\InternalActivityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInternalActivity extends EditRecord
{
    protected static string $resource = InternalActivityResource::class;

    protected static ?string $title = 'Modifica attività interna';

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getRecord()->detailUrl();
    }
}
