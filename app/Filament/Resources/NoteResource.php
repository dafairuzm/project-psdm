<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NoteResource\Pages;
use App\Filament\Resources\NoteResource\RelationManagers;
use App\Models\Note;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationGroup = 'Kegiatan';
    protected static ?string $navigationLabel = 'Catatan';
    protected static ?string $modelLabel = 'Catatan';
    protected static ?string $pluralModelLabel = 'Catatan';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('note')
                ->label('Catatan')
                ->columnSpanFull(),
                Select::make('activity_id')
                ->relationship('activity','title')
                ->label('Kegiatan')
                ->searchable()
                ->preload(),
                Hidden::make('user_id')
                ->default(fn() => auth()->id()),
            ]);
    }
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('note')
                        ->label('Catatan'),
                        TextEntry::make('activity.title')
                        ->label('Kegiatan'),
                        TextEntry::make('user.name')
                        ->label('Dibuat oleh'),
                        TextEntry::make('created_at')
                        ->label('Dibuat di'),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('note')
                ->label('Catatan')
                ->wrap()
                ->searchable(),
                TextColumn::make('activity.title')
                ->label('Kegiatan')
                ->extraAttributes([
                    'style' => 'width: 400px; max-width: 600px;'
                ])
                ->limit(60)
                ->wrap()
                ->searchable(),
                TextColumn::make('user.name')
                ->label('Dibuat oleh')
                ->searchable(),
                TextColumn::make('created_at')
                ->since()
                ->label('Dibuat'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotes::route('/'),
            // 'create' => Pages\CreateNote::route('/create'),
            // 'edit' => Pages\EditNote::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }
}
