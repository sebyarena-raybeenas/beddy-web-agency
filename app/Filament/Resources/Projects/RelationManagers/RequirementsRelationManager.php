<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class RequirementsRelationManager extends RelationManager
{
    protected static string $relationship = 'requirements';

    protected static ?string $title = 'Requisiti';

    protected static ?string $modelLabel = 'requisito';

    protected static ?string $pluralModelLabel = 'requisiti';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Requisito')->required()->maxLength(255)->columnSpanFull(),
            Toggle::make('is_mandatory')->label('Obbligatorio')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')->label('Requisito')->wrap(),
                IconColumn::make('is_mandatory')->label('Obbligatorio')->boolean(),
                ToggleColumn::make('is_done')
                    ->label('Fatto')
                    ->updateStateUsing(function ($record, bool $state): bool {
                        // Saved through Eloquent: the observer evaluates the automatic "ready" state.
                        $record->update([
                            'is_done' => $state,
                            'done_at' => $state ? now() : null,
                            'done_by' => $state ? auth()->id() : null,
                        ]);

                        return $state;
                    }),
            ])
            ->defaultSort('sort')
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    $data['sort'] = ((int) $this->getOwnerRecord()->requirements()->max('sort')) + 1;

                    return $data;
                }),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
