<?php

namespace App\Filament\Resources\InternalActivities;

use App\Filament\Resources\InternalActivities\Pages\ActivityDetail;
use App\Filament\Resources\InternalActivities\Pages\CreateInternalActivity;
use App\Filament\Resources\InternalActivities\Pages\EditInternalActivity;
use App\Filament\Resources\InternalActivities\Pages\ListInternalActivities;
use App\Models\InternalActivity;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InternalActivityResource extends Resource
{
    protected static ?string $model = InternalActivity::class;

    protected static ?string $slug = 'attivita-interne';

    protected static ?string $modelLabel = 'attività interna';

    protected static ?string $pluralModelLabel = 'attività interne';

    protected static ?string $navigationLabel = 'Attività interne';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome attività')->required()->maxLength(255)->columnSpanFull(),
            Select::make('owner_id')
                ->label('Responsabile')
                ->options(fn (): array => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            DatePicker::make('due_date')->label('Scadenza')->native(false)->displayFormat('d/m/Y'),
            Textarea::make('description')->label('Descrizione')->rows(5)->maxLength(10000)->columnSpanFull(),
            // On creation the to-dos can be listed right away; afterwards they are managed in the detail page.
            Repeater::make('todos')
                ->label('To-do')
                ->relationship()
                ->schema([TextInput::make('title')->hiddenLabel()->placeholder('Cosa c’è da fare')->required()->maxLength(255)])
                ->orderColumn('sort')
                ->addActionLabel('Aggiungi to-do')
                ->defaultItems(0)
                ->columnSpanFull()
                ->visibleOn('create'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Attività')->searchable()->sortable()->weight('bold'),
                TextColumn::make('owner.name')->label('Responsabile')->placeholder('Nessun responsabile'),
                TextColumn::make('due_date')->label('Scadenza')->date('d/m/Y')->sortable()->placeholder('—')
                    ->color(fn (InternalActivity $record): ?string => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('todos_progress')
                    ->label('To-do')
                    ->state(fn (InternalActivity $record): string => $record->todos_done_count.' / '.$record->todos_count),
                TextColumn::make('status')->label('Stato')->badge()
                    ->state(fn (InternalActivity $record): string => $record->isCompleted() ? 'Completata' : 'Aperta')
                    ->color(fn (InternalActivity $record): string => $record->isCompleted() ? 'success' : 'warning'),
            ])
            ->filters([
                TernaryFilter::make('completed')
                    ->label('Stato')
                    ->placeholder('Tutte')
                    ->trueLabel('Completate')
                    ->falseLabel('Aperte')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('completed_at'),
                        false: fn (Builder $query) => $query->whereNull('completed_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                SelectFilter::make('owner_id')->label('Responsabile')->relationship('owner', 'name'),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('name'))
            ->recordUrl(fn (InternalActivity $record): string => $record->detailUrl())
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('owner')->withCount([
            'todos',
            'todos as todos_done_count' => fn (Builder $t) => $t->whereNotNull('completed_at'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInternalActivities::route('/'),
            'create' => CreateInternalActivity::route('/create'),
            'view' => ActivityDetail::route('/{record}'),
            'edit' => EditInternalActivity::route('/{record}/edit'),
        ];
    }
}
