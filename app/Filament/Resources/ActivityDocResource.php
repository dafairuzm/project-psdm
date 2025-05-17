<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityDocResource\Pages;
use App\Filament\Resources\ActivityDocResource\RelationManagers;
use App\Models\ActivityDoc;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActivityDocResource extends Resource
{
    protected static ?string $model = ActivityDoc::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Dokumentasi Tambahan';
    protected static ?string $modelLabel = 'Dokumentasi';
    protected static ?string $pluralModelLabel = 'Dokumentasi';
    protected static ?string $navigationLabel = 'Dokumentasi';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('activity_id')
                ->label('Nama Kegiatan')
                ->relationship('activity', 'title')
                ->required(),
            FileUpload::make('documentation')
                ->label('Dokumentasi')
                ->image()
                ->directory('activity-docs')
                ->disk('public')
                ->preserveFilenames()
                ->visibility('public')
                ->required(),
            Hidden::make('user_id')
                ->default(fn() => auth()->id()),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('documentation')
                    ->label('Dokumentasi')
                    ->height('80px')// atur ukuran tinggi gambar
                    ->width('auto'),  // atau bisa juga 100%
                    //->searchable(),
                Tables\Columns\TextColumn::make('activity.title')
                    ->label('Nama Kegiatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(), 

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListActivityDocs::route('/'),
            'create' => Pages\CreateActivityDoc::route('/create'),
            'edit' => Pages\EditActivityDoc::route('/{record}/edit'),
        ];
    }
}
