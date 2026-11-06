<?php

namespace App\Filament\Resources\Users\Tables;

use App\Domain\Users\DeactivationGuard;
use App\Domain\Users\InviteUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Attivo')
                    ->rules(fn (User $record): array => ['boolean', DeactivationGuard::rule($record)]),
                TextColumn::make('last_login_at')
                    ->label('Ultimo accesso')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Mai')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('resendInvitation')
                    ->label('Reinvia invito')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->modalHeading('Reinvia invito')
                    ->modalDescription('Verrà inviata una nuova email con un nuovo link per impostare la password.')
                    ->action(function ($record): void {
                        app(InviteUser::class)->resend($record);

                        Notification::make()->title('Invito reinviato')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }
}
