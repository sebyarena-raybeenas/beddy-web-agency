<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ragione sociale')->searchable()->sortable(),
                TextColumn::make('vat_number')->label('Partita IVA')->searchable(),
                TextColumn::make('hosting_status')->label('Hosting')->badge(),
                IconColumn::make('has_assistance')->label('Assistenza')->boolean(),
                TextColumn::make('projects_count')->label('Progetti')->counts('projects'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
