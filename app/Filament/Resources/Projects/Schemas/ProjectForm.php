<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nome progetto')->required()->maxLength(255),
                Select::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit'),
                Select::make('type')
                    ->label('Tipo')
                    ->options(ProjectType::class)
                    ->required()
                    ->disabledOn('edit')
                    ->helperText('Il tipo non si può cambiare dopo la creazione'),
                Select::make('owner_id')
                    ->label('Responsabile')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('members')
                    ->label('Membri')
                    ->relationship('members', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                DatePicker::make('due_date')
                    ->label('Scadenza')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->helperText('Impostata in automatico dal cambio di stato, modificabile a mano'),
            ]);
    }
}
