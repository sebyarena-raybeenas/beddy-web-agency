<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'To-do';

    protected static ?string $modelLabel = 'to-do';

    protected static ?string $pluralModelLabel = 'to-do';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Titolo')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->label('Descrizione')->columnSpanFull(),
            Select::make('assignee_id')
                ->label('Assegnato a')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            DatePicker::make('due_date')->label('Scadenza')->native(false)->displayFormat('d/m/Y'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                IconColumn::make('completed_at')->label('Fatto')->boolean(),
                TextColumn::make('title')->label('Titolo')->wrap()->searchable(),
                TextColumn::make('assignee.name')->label('Assegnato a')->placeholder('—'),
                TextColumn::make('due_date')->label('Scadenza')->date('d/m/Y')->placeholder('—'),
            ])
            ->defaultSort('sort')
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();
                    $data['sort'] = ((int) $this->getOwnerRecord()->tasks()->max('sort')) + 1;

                    return $data;
                }),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Completa')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record): bool => $record->completed_at === null)
                    ->action(fn ($record) => $record->complete()),
                Action::make('reopen')
                    ->label('Riapri')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn ($record): bool => $record->completed_at !== null)
                    ->action(fn ($record) => $record->reopen()),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
