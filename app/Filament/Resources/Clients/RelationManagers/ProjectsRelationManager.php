<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    protected static ?string $title = 'Progetti';

    protected static ?string $modelLabel = 'progetto';

    protected static ?string $pluralModelLabel = 'progetti';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Progetto')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('status')->label('Stato')->formatStateUsing(fn ($state, $record) => $record->statusLabel()),
                TextColumn::make('due_date')->label('Scadenza')->date('d/m/Y'),
            ])
            ->recordUrl(fn ($record): string => ProjectResource::getUrl('view', ['record' => $record]));
    }
}
