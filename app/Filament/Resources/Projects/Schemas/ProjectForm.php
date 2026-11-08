<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\HostingStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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
                Select::make('payment_status')
                    ->label('Pagamento')
                    ->options(PaymentStatus::class)
                    ->default(PaymentStatus::NonPagato->value)
                    ->selectablePlaceholder(false)
                    ->required()
                    ->helperText('Acconto per partire con lo sviluppo, saldo per pubblicare'),
                Select::make('hosting_status')
                    ->label('Hosting')
                    ->options(HostingStatus::class),
                Toggle::make('has_assistance')
                    ->label('Assistenza')
                    ->live()
                    ->inline(false),
                TextInput::make('assistance_type')
                    ->label('Tipo di assistenza')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (bool) $get('has_assistance')),
            ]);
    }
}
