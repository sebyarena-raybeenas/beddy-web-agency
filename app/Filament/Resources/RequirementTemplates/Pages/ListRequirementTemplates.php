<?php

namespace App\Filament\Resources\RequirementTemplates\Pages;

use App\Filament\Resources\RequirementTemplates\RequirementTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRequirementTemplates extends ListRecords
{
    protected static string $resource = RequirementTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
