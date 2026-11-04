<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Contatti';

    protected static ?string $modelLabel = 'contatto';

    protected static ?string $pluralModelLabel = 'contatti';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome')->required()->maxLength(255),
            TextInput::make('role')->label('Ruolo')->maxLength(255),
            TextInput::make('phone')->label('Telefono')->tel()->maxLength(255),
            TextInput::make('email')->label('Email')->email()->maxLength(255),
            Toggle::make('is_primary')->label('Contatto principale'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('role')->label('Ruolo'),
                TextColumn::make('phone')->label('Telefono'),
                TextColumn::make('email')->label('Email'),
                IconColumn::make('is_primary')->label('Principale')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
