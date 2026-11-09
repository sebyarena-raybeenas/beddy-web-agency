<?php

namespace App\Filament\Resources\SalesReps\Pages;

use App\Filament\Resources\SalesReps\SalesRepResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesReps extends ListRecords
{
    protected static string $resource = SalesRepResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
