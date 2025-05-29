<?php

namespace App\Filament\Resources;

use App\Exports\UserActivityExport;
use App\Filament\Resources\UserActivityResource\Pages;
use App\Filament\Resources\UserActivityResource\RelationManagers;
use App\Models\ActivityCategory;
use App\Models\Report;
use App\Models\UserActivity;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Str;

class UserActivityResource extends Resource
{
    protected static ?string $model = UserActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Kegiatan';

    protected static ?string $navigationLabel = 'Kegiatan Pegawai';

    protected static ?string $modelLabel = 'Kegiatan Pegawai';

    protected static ?string $pluralModelLabel = 'Kegiatan Pegawai';
    public static function getNavigationSort(): int
    {
        return 2; // Angka lebih kecil = lebih atas di sidebar
    }

    // public static function getTableQuery(): Builder
    // {
    //     return parent::getTableQuery()
    //         ->with('activity.categories'); // <-- eager load relasi kategori
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Peserta')
                    ->relationship('user', 'name')
                    //->unique(ignoreRecord: true)
                    ->required(),

                Forms\Components\Select::make('activity_id')
                    ->label('Kegiatan')
                    ->relationship('activity', 'title')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Pegawai')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.nip')
                    ->label('NIP'),
                Tables\Columns\TextColumn::make('user.title_complete')
                    ->label('Jabatan')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('activity.type')
                    ->label('Tipe')
                    ->searchable()
                    ->colors([
                        'primary' => 'inhouse',
                        'info' => 'exhouse',
                    ]),
                Tables\Columns\TextColumn::make('activity.title')
                    ->label('Kegiatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activity.categories.name')
                    ->label('Kategori Kegiatan')
                    ->searchable()
                    ->formatStateUsing(fn($state, $record) => $record->activity?->categories->pluck('name')->join(', ')),
                Tables\Columns\TextColumn::make('activity.location')
                    ->label('Lokasi')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Mulai Dari'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['start_date'] || $data['end_date'],
                            fn($q) => $q->whereHas('activity', function ($q) use ($data) {
                                if ($data['start_date']) {
                                    $q->whereDate('start_date', '>=', $data['start_date']);
                                }
                                if ($data['end_date']) {
                                    $q->whereDate('finish_date', '<=', $data['end_date']);
                                }
                            })
                        );
                    }),

                SelectFilter::make('activity.type')
                    ->label('Tipe Kegiatan')
                    ->options([
                        'exhouse' => 'Exhouse',
                        'inhouse' => 'Inhouse',
                    ]),
                    
                SelectFilter::make('categories.name')
                    ->label('Kategori Kegiatan'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('export_excel')
                    ->label('Export ke Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->modalSubmitActionLabel('Buat File')
                    ->form([
                        TextInput::make('filename')
                            ->label('Nama File')
                            ->default('Laporan kegiatan')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $filename = $data['filename'] ?? 'Laporan kegiatan';
                        $safeName = Str::slug($filename) . '-' . now()->format('YmdHis') . '.xlsx';

                        // 1. Buat file Excel ke memory (stream)
                        $export = new UserActivityExport($records);

                        // 2. Simpan ke storage lokal
                        Excel::store($export, 'reports/' . $safeName, 'public');

                        // 3. Simpan ke database
                        Report::create([
                            'name' => $filename,
                            'file_path' => 'reports/' . $safeName,
                            'generated_by' => auth()->id(),
                            'generated_at' => now(),
                        ]);

                        // 4. Download juga ke browser user
                        return Excel::download($export, $safeName);
                    })
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
            'index' => Pages\ListUserActivities::route('/'),
            'create' => Pages\CreateUserActivity::route('/create'),
            'edit' => Pages\EditUserActivity::route('/{record}/edit'),
        ];
    }
    
}
