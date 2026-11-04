<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Clients\DomainService;
use App\Enums\DomainOwnership;
use App\Enums\DomainRole;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

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
                TextColumn::make('role')->label('Ruolo')->badge(),
                TextColumn::make('ownership')->label('Intestazione')->badge(),
            ])
            ->headerActions([
                // New domains belong to the project's client.
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    $data['client_id'] = $this->getOwnerRecord()->client_id;

                    return $data;
                }),
                Action::make('associate')
                    ->label('Associa dominio del cliente')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Select::make('domain_id')
                            ->label('Dominio')
                            ->options(fn (): array => Domain::query()
                                ->where('client_id', $this->getOwnerRecord()->client_id)
                                ->whereNull('project_id')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        Domain::findOrFail($data['domain_id'])
                            ->forceFill(['project_id' => $this->getOwnerRecord()->getKey()])
                            ->save();
                    }),
            ])
            ->recordActions([
                Action::make('makePrimary')
                    ->label('Rendi principale')
                    ->icon('heroicon-o-star')
                    ->visible(fn (Domain $record): bool => $record->role !== DomainRole::Principale)
                    ->requiresConfirmation()
                    ->action(function (Domain $record): void {
                        try {
                            app(DomainService::class)->setPrimary($this->getOwnerRecord(), $record);
                        } catch (ValidationException $e) {
                            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Dominio principale aggiornato')->success()->send();
                    }),
            ]);
    }
}
