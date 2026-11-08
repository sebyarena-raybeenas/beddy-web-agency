<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('legal_name')->label('Ragione sociale')->searchable()->placeholder('—'),
                TextColumn::make('vat_number')->label('Partita IVA')->searchable(),
                TextColumn::make('projects_count')->label('Progetti')->counts('projects'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
