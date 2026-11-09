<?php

namespace App\Filament\Resources\SalesReps;

use App\Filament\Resources\SalesReps\Pages\CreateSalesRep;
use App\Filament\Resources\SalesReps\Pages\EditSalesRep;
use App\Filament\Resources\SalesReps\Pages\ListSalesReps;
use App\Models\SalesRep;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesRepResource extends Resource
{
    protected static ?string $model = SalesRep::class;

    protected static ?string $slug = 'commerciali';

    protected static ?string $modelLabel = 'commerciale';

    protected static ?string $pluralModelLabel = 'commerciali';

    protected static ?string $navigationLabel = 'Commerciali';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    /** Shared with the "create on the fly" option of the project form. */
    public static function fields(): array
    {
        return [
            TextInput::make('name')->label('Nome e cognome')->required()->maxLength(255)->unique(SalesRep::class, 'name', ignoreRecord: true),
            TextInput::make('email')->label('Email')->email()->maxLength(255),
            TextInput::make('phone')->label('Telefono')->tel()->maxLength(255),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::fields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->placeholder('—'),
                TextColumn::make('phone')->label('Telefono')->placeholder('—'),
                TextColumn::make('projects_count')->label('Progetti')->counts('projects')->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesReps::route('/'),
            'create' => CreateSalesRep::route('/create'),
            'edit' => EditSalesRep::route('/{record}/edit'),
        ];
    }
}
