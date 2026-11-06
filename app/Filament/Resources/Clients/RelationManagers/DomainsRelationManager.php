<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Domain\Clients\DomainService;
use App\Enums\DomainOwnership;
use App\Enums\DomainRole;
use App\Filament\Support\UniqueDomainName;
use App\Models\Domain;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            TextInput::make('name')->label('Dominio')->required()->maxLength(255)->rule(UniqueDomainName::rule()),
            Select::make('ownership')->label('Intestazione')->options(DomainOwnership::class),
            Select::make('role')
                ->label('Ruolo')
                ->options(DomainRole::class)
                ->default(DomainRole::Secondario->value)
                ->selectablePlaceholder(false)
                ->required()
                ->live(),
            // Shown only when another principale exists: ticking it is the confirmation.
            Checkbox::make('confirm_primary')
                ->label(fn (?Domain $record): string => 'Esiste già il dominio principale «'
                    .$this->existingPrimary($record)?->name
                    .'». Confermo: questo diventa principale e tutti gli altri diventano secondari.')
                ->visible(fn (Get $get, ?Domain $record): bool => $this->needsConfirmation($get('role'), $record))
                ->accepted()
                ->validationMessages(['accepted' => 'Conferma per rendere principale questo dominio.'])
                ->dehydrated(false)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Dominio')->searchable(),
                TextColumn::make('ownership')->label('Intestazione')->badge(),
                TextColumn::make('role')->label('Ruolo')->badge()
                    ->color(fn (DomainRole $state): string => $state === DomainRole::Principale ? 'primary' : 'gray'),
            ])
            ->headerActions([
                CreateAction::make()->using(fn (array $data): Model => DB::transaction(function () use ($data) {
                    $domain = $this->getOwnerRecord()->domains()->create([
                        'name' => $data['name'],
                        'ownership' => $data['ownership'] ?? null,
                        'role' => DomainRole::Secondario,
                    ]);

                    if ($this->wantsPrimary($data)) {
                        app(DomainService::class)->makeClientPrimary($domain);
                    }

                    return $domain;
                })),
            ])
            ->recordActions([
                EditAction::make()->using(fn (Domain $record, array $data): Model => DB::transaction(function () use ($record, $data) {
                    $record->update([
                        'name' => $data['name'],
                        'ownership' => $data['ownership'] ?? null,
                    ]);

                    $service = app(DomainService::class);
                    $isPrimary = $record->role === DomainRole::Principale;

                    if ($this->wantsPrimary($data) && ! $isPrimary) {
                        $service->makeClientPrimary($record);
                    } elseif (! $this->wantsPrimary($data) && $isPrimary) {
                        $service->makeSecondary($record);
                    }

                    return $record;
                })),
                DeleteAction::make(),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    private function wantsPrimary(array $data): bool
    {
        $role = $data['role'] ?? null;

        return ($role instanceof DomainRole ? $role->value : $role) === DomainRole::Principale->value;
    }

    private function existingPrimary(?Domain $record): ?Domain
    {
        return app(DomainService::class)->existingClientPrimary($this->getOwnerRecord()->getKey(), $record?->getKey());
    }

    private function needsConfirmation(mixed $role, ?Domain $record): bool
    {
        $role = $role instanceof DomainRole ? $role->value : $role;

        return $role === DomainRole::Principale->value && $this->existingPrimary($record) !== null;
    }
}
