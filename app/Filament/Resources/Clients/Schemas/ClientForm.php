<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Enums\DomainOwnership;
use App\Enums\HostingStatus;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Ragione sociale')->required()->maxLength(255),
                TextInput::make('vat_number')->label('Partita IVA')->maxLength(255),
                TextInput::make('address')->label('Indirizzo')->maxLength(255),
                Select::make('hosting_status')->label('Hosting')->options(HostingStatus::class),
                Toggle::make('has_assistance')->label('Assistenza')->live(),
                TextInput::make('assistance_type')
                    ->label('Tipo di assistenza')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (bool) $get('has_assistance')),

                // On creation contacts and domains are entered inline; afterwards the relation managers take over.
                Repeater::make('contacts')
                    ->label('Contatti')
                    ->relationship()
                    ->addActionLabel('Aggiungi contatto')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visibleOn('create')
                    ->schema([
                        TextInput::make('name')->label('Nome')->required()->maxLength(255),
                        TextInput::make('role')->label('Ruolo')->maxLength(255),
                        TextInput::make('phone')->label('Telefono')->tel()->maxLength(255),
                        TextInput::make('email')->label('Email')->email()->maxLength(255),
                        Toggle::make('is_primary')->label('Contatto principale'),
                    ]),
                Repeater::make('domains')
                    ->label('Domini')
                    ->relationship()
                    ->addActionLabel('Aggiungi dominio')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visibleOn('create')
                    ->schema([
                        TextInput::make('name')->label('Dominio')->required()->maxLength(255),
                        Select::make('ownership')->label('Intestazione')->options(DomainOwnership::class),
                    ]),
            ]);
    }
}
