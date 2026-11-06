<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/** The contact fields, shared by the client creation form and the contacts relation manager. */
class ContactFields
{
    /** @return array<int, mixed> */
    public static function schema(): array
    {
        return [
            TextInput::make('name')->label('Nome')->required()->maxLength(255),
            TextInput::make('role')->label('Ruolo')->maxLength(255),
            Repeater::make('phones')
                ->label('Telefoni')
                ->simple(TextInput::make('value')->label('Telefono')->tel()->maxLength(255))
                ->addActionLabel('Aggiungi telefono')
                ->defaultItems(1)
                ->reorderable(false)
                ->mutateDehydratedStateUsing(fn (?array $state): array => self::values($state)),
            Repeater::make('emails')
                ->label('Email')
                ->simple(TextInput::make('value')->label('Email')->email()->maxLength(255))
                ->addActionLabel('Aggiungi email')
                ->defaultItems(1)
                ->reorderable(false)
                ->mutateDehydratedStateUsing(fn (?array $state): array => self::values($state)),
            Toggle::make('is_primary')->label('Contatto principale')->columnSpanFull(),
        ];
    }

    /**
     * Flattens the simple repeater items to a plain list, dropping the empty rows.
     *
     * @param  array<int|string, mixed>|null  $state
     * @return list<string>
     */
    private static function values(?array $state): array
    {
        return collect($state ?? [])
            ->map(fn (mixed $item): mixed => is_array($item) ? ($item['value'] ?? null) : $item)
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->values()
            ->all();
    }
}
