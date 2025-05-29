<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageNote extends ManageRelatedRecords
{
    protected static string $resource = ActivityResource::class;
    protected static string $relationship = 'notes';
    protected static ?string $navigationLabel = 'Catatan';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';


    public function getTitle(): string|Htmlable
    {
        $recordTitle = $this->getRecordTitle();
        $recordTitle = $recordTitle instanceof Htmlable ? $recordTitle->toHtml() : $recordTitle;

        return "Kelola Catatan {$recordTitle}";
    }

    public static function getNavigationLabel(): string
    {
        return 'Catatan';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('note')
                    ->label('Catatan')
                    ->required(),

                Hidden::make('user_id')
                    ->default(fn() => auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('note')->limit(255),
                TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')->dateTime(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
               CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}