<?php

namespace App\Filament\Resources\ChecklistTemplates;

use App\Filament\Resources\ChecklistTemplates\Pages\CreateChecklistTemplate;
use App\Filament\Resources\ChecklistTemplates\Pages\EditChecklistTemplate;
use App\Filament\Resources\ChecklistTemplates\Pages\ListChecklistTemplates;
use App\Filament\Resources\RequirementTemplates\RequirementTemplateResource;
use App\Models\ChecklistTemplate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class ChecklistTemplateResource extends Resource
{
    protected static ?string $model = ChecklistTemplate::class;

    protected static ?string $slug = 'template-checklist';

    protected static ?string $modelLabel = 'Template checklist';

    protected static ?string $pluralModelLabel = 'Template checklist';

    protected static ?string $navigationLabel = 'Template checklist';

    protected static string|UnitEnum|null $navigationGroup = 'Impostazioni';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('flow')->label('Flusso')->options(RequirementTemplateResource::FLOWS)->required()->live(),
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('flow', $get('flow'))),
            Select::make('trigger_status')
                ->label('Si genera quando il progetto passa a')
                ->options(fn (Get $get): array => config('workflows.'.($get('flow') ?: 'sito').'.states', []))
                ->required(),
            Repeater::make('items')
                ->label('Voci')
                ->relationship()
                ->orderColumn('sort')
                ->addActionLabel('Aggiungi voce')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')->label('Voce')->required()->maxLength(255),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('flow')->label('Flusso')->formatStateUsing(fn ($state) => RequirementTemplateResource::FLOWS[$state] ?? $state)->badge(),
                TextColumn::make('trigger_status')->label('Stato di avvio'),
                TextColumn::make('items_count')->label('Voci')->counts('items'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChecklistTemplates::route('/'),
            'create' => CreateChecklistTemplate::route('/create'),
            'edit' => EditChecklistTemplate::route('/{record}/edit'),
        ];
    }
}
