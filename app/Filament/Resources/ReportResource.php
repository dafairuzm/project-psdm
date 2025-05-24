<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Filament\Resources\ReportResource\RelationManagers;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Storage;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $modelLabel = 'Laporan';

    protected static ?string $pluralModelLabel = 'Laporan';
    public static function getNavigationSort(): int
    {
        return 4; // Angka lebih kecil = lebih atas di sidebar
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('file_path')->disabled(),
            Forms\Components\DateTimePicker::make('generated_at')->disabled(),
            Forms\Components\TextInput::make('generated_by')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama File')->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                ->label('Dibuat Oleh')
                ->searchable(),
                Tables\Columns\TextColumn::make('generated_at')
                ->label('Tanggal Dibuat')
                ->formatStateUsing(fn ($state) =>
                    \Carbon\Carbon::parse($state)
                        ->locale('id')
                        ->translatedFormat('d F Y H:i')
                ),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(Report $record) => Storage::url($record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                ->label('Hapus')
                ->using(function (Report $record) {
                    // Hapus file dari storage
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($record->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($record->file_path);
                    }
            
                    // Hapus data dari database
                    $record->delete();
            
                    return $record;
                }),
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
