<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Users\DeactivationGuard;
use App\Models\User;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->label('Attivo')
                    ->default(true)
                    ->rules([fn (?User $record): Closure => DeactivationGuard::rule($record ?? new User)])
                    ->visibleOn('edit'),
            ]);
    }
}
