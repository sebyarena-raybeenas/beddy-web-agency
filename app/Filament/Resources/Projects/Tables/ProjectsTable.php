<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Domain\Workflow\WorkflowRegistry;
use App\Enums\ProjectType;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Progetto')->searchable()->sortable(),
                TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record): string => $record->statusLabel())
                    ->sortable(),
                TextColumn::make('owner.name')->label('Responsabile')->placeholder('—'),
                TextColumn::make('due_date')->label('Scadenza')->date('d/m/Y')->placeholder('—')->sortable(),
            ])
            ->defaultSort('due_date')
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(ProjectType::class),
                SelectFilter::make('client_id')->label('Cliente')->relationship('client', 'name')->searchable()->preload(),
                SelectFilter::make('status')->label('Stato')->options(fn (): array => self::statusOptions()),
                SelectFilter::make('owner_id')->label('Responsabile')->relationship('owner', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        $registry = app(WorkflowRegistry::class);
        $options = [];

        foreach (ProjectType::cases() as $type) {
            $options += $registry->for($type)->states();
        }

        return $options;
    }
}
