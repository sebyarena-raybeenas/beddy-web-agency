<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Commenti';

    protected static ?string $modelLabel = 'commento';

    protected static ?string $pluralModelLabel = 'commenti';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->label('Commento')->required()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('user.name')->label('Autore')->placeholder('—'),
                TextColumn::make('body')->label('Commento')->wrap(),
                TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();

                    return $data;
                }),
            ])
            ->recordActions([DeleteAction::make()]);
    }
}
