<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Enums\DomainOwnership;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = 'Domini';

    protected static ?string $modelLabel = 'dominio';

    protected static ?string $pluralModelLabel = 'domini';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Dominio')->required()->maxLength(255),
            Select::make('ownership')->label('Intestazione')->options(DomainOwnership::class),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Dominio')->searchable(),
                TextColumn::make('ownership')->label('Intestazione')->badge(),
                TextColumn::make('role')->label('Ruolo')->badge(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
