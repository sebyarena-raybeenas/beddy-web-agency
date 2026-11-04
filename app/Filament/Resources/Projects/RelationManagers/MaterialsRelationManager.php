<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\MaterialKind;
use App\Enums\MaterialStatus;
use App\Models\Attachment;
use App\Models\Material;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    protected static ?string $title = 'Materiale';

    protected static ?string $modelLabel = 'materiale';

    protected static ?string $pluralModelLabel = 'materiale';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kind')->label('Tipo')->options(MaterialKind::class)->required(),
            Select::make('status')
                ->label('Stato')
                ->options(MaterialStatus::class)
                ->default(MaterialStatus::Atteso->value)
                ->required(),
            FileUpload::make('files')
                ->label('File')
                ->multiple()
                ->disk(Attachment::DISK)
                ->visibility('private')
                ->directory(fn (): string => Attachment::directoryFor($this->getOwnerRecord()->getKey()))
                ->storeFileNamesIn('file_names')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('kind')
            ->columns([
                TextColumn::make('kind')->label('Tipo')->badge(),
                TextColumn::make('requirement.label')->label('Requisito')->wrap()->placeholder('—'),
                SelectColumn::make('status')
                    ->label('Stato')
                    ->options(MaterialStatus::class)
                    ->selectablePlaceholder(false),
                TextColumn::make('attachments_count')->label('File')->counts('attachments'),
                TextColumn::make('received_at')->label('Ricevuto il')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->using(function (array $data): Model {
                    $material = $this->getOwnerRecord()->materials()->create([
                        'kind' => $data['kind'],
                        'status' => $data['status'],
                        'requested_at' => now(),
                    ]);

                    $this->storeAttachments($material, $data);

                    return $material;
                }),
            ])
            ->recordActions([
                EditAction::make()->using(function (Model $record, array $data): Model {
                    $record->update([
                        'kind' => $data['kind'],
                        'status' => $data['status'],
                    ]);

                    $this->storeAttachments($record, $data);

                    return $record;
                }),
                DeleteAction::make(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeAttachments(Material $material, array $data): void
    {
        $names = $data['file_names'] ?? [];

        foreach ((array) ($data['files'] ?? []) as $key => $path) {
            $material->attachments()->create([
                'path' => $path,
                'filename' => $names[$path] ?? $names[$key] ?? basename($path),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
