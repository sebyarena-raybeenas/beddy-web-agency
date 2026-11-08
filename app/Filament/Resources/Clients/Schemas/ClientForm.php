<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Enums\DomainOwnership;
use App\Enums\DomainRole;
use App\Filament\Support\UniqueDomainName;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Cliente (nome e cognome del referente)')->required()->maxLength(255),
                TextInput::make('legal_name')->label('Ragione sociale')->maxLength(255),
                TextInput::make('vat_number')->label('Partita IVA')->maxLength(255),
                TextInput::make('address')->label('Indirizzo')->maxLength(255),

                // On creation contacts and domains are entered inline; afterwards the relation managers take over.
                Repeater::make('contacts')
                    ->label('Contatti')
                    ->relationship()
                    ->addActionLabel('Aggiungi contatto')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visibleOn('create')
                    ->schema(ContactFields::schema()),
                Repeater::make('domains')
                    ->label('Domini')
                    ->relationship()
                    ->addActionLabel('Aggiungi dominio')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visibleOn('create')
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $primaries = collect((array) $value)
                            ->filter(fn ($item): bool => ($item['role'] ?? null) === DomainRole::Principale->value);

                        if ($primaries->count() > 1) {
                            $fail('Un solo dominio può essere principale.');
                        }
                    })
                    ->schema([
                        TextInput::make('name')->label('Dominio')->required()->maxLength(255)->rule(UniqueDomainName::rule()),
                        Select::make('ownership')->label('Intestazione')->options(DomainOwnership::class),
                        Select::make('role')
                            ->label('Ruolo')
                            ->options(DomainRole::class)
                            ->default(DomainRole::Secondario->value)
                            ->selectablePlaceholder(false)
                            ->required(),
                    ]),
            ]);
    }
}
