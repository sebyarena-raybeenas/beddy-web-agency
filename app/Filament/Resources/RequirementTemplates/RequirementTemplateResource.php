<?php

namespace App\Filament\Resources\RequirementTemplates;

use App\Enums\MaterialKind;
use App\Filament\Resources\RequirementTemplates\Pages\CreateRequirementTemplate;
use App\Filament\Resources\RequirementTemplates\Pages\EditRequirementTemplate;
use App\Filament\Resources\RequirementTemplates\Pages\ListRequirementTemplates;
use App\Models\RequirementTemplate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class RequirementTemplateResource extends Resource
{
    public const FLOWS = ['sito' => 'Sito', 'modifica' => 'Modifica'];

    protected static ?string $model = RequirementTemplate::class;

    protected static ?string $slug = 'template-requisiti';

    protected static ?string $modelLabel = 'Template requisiti';

    protected static ?string $pluralModelLabel = 'Template requisiti';

    protected static ?string $navigationLabel = 'Template requisiti';

    protected static string|UnitEnum|null $navigationGroup = 'Impostazioni';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('flow')->label('Flusso')->options(self::FLOWS)->required()->live(),
            TextInput::make('label')
                ->label('Requisito')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('flow', $get('flow'))),
            Toggle::make('is_mandatory')->label('Obbligatorio')->default(true),
            Select::make('material_kind')
                ->label('Materiale collegato')
                ->options(MaterialKind::class)
                ->helperText('Se impostato, il progetto avrà una riga di materiale collegata al requisito'),
            TextInput::make('sort')->label('Ordine')->numeric()->default(0)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flow')->label('Flusso')->formatStateUsing(fn ($state) => self::FLOWS[$state] ?? $state)->badge()->sortable(),
                TextColumn::make('label')->label('Requisito')->wrap()->searchable(),
                IconColumn::make('is_mandatory')->label('Obbligatorio')->boolean(),
                TextColumn::make('material_kind')->label('Materiale')->badge()->placeholder('—'),
                TextColumn::make('sort')->label('Ordine')->sortable(),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequirementTemplates::route('/'),
            'create' => CreateRequirementTemplate::route('/create'),
            'edit' => EditRequirementTemplate::route('/{record}/edit'),
        ];
    }
}
