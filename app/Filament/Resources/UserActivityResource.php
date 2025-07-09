<?php

namespace App\Filament\Resources;

use App\Exports\UserActivityExport;
use App\Filament\Resources\UserActivityResource\Pages;
use App\Models\ActivityCategory;
use App\Models\Report;
use App\Models\UserActivity;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
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

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Kegiatan';

    protected static ?string $navigationLabel = 'Kegiatan Pegawai';

    protected static ?string $modelLabel = 'Kegiatan Pegawai';

    protected static ?string $pluralModelLabel = 'Kegiatan Pegawai';
    public static function getNavigationSort(): int
    {
        return 2; // Angka lebih kecil = lebih atas di sidebar
    }

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
                Tables\Columns\TextColumn::make('user.employee_class')
                    ->label('Pangkat/Gol')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('user.job_title')
                    ->label('Jabatan')
                    ->searchable()
                    ->placeholder('not set'),
                Tables\Columns\TextColumn::make('user.unit.name')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->placeholder('not set')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\BadgeColumn::make('activity.type')
                    ->label('Tipe')
                    ->searchable()
                    ->colors([
                        'primary' => 'dinas',
                        'warning' => 'mandiri',
                    ]),
                Tables\Columns\TextColumn::make('activity.title')
                    ->label('Kegiatan')
                    ->searchable()
                    ->extraAttributes([
                        'style' => 'width: 400px; max-width: 300px;'
                    ])
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('activity.categories.name')
                    ->label('Kategori')
                    ->searchable()
                    ->badge()
                    ->separator(',')
                    ->limit(30)
                    ->color('gray')
                    ->placeholder('not set'),
                Tables\Columns\TextColumn::make('activity.organizer')
                    ->label('Penyelenggara')
                    ->extraAttributes([
                        'style' => 'width: 300px; max-width: 300px;'
                    ])
                    ->limit(60)
                    ->wrap()
                    ->placeholder('not set'),
                Tables\Columns\TextColumn::make('activity.location')
                    ->label('Lokasi')
                    ->searchable()
                    ->extraAttributes([
                        'style' => 'width: 300px; max-width: 300px;'
                    ])
                    ->limit(60)
                    ->placeholder('not set')
                    ->wrap(),
                Tables\Columns\TextColumn::make('activity.duration')
                    ->label('Durasi')
                    ->numeric()
                    ->sortable()
                    ->suffix(' JPL')
                    ->placeholder('not set'),
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

                    SelectFilter::make('activity_type')
                    ->label('Tipe Kegiatan')
                    ->options([
                        'dinas' => 'Dinas',
                        'mandiri' => 'Mandiri',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['value'],
                            fn($q) => $q->whereHas('activity', function ($q) use ($data) {
                                $q->where('type', $data['value']);
                            })
                        );
                    }),

                    SelectFilter::make('activity_categories')
                    ->label('Kategori Kegiatan')
                    ->options(function () {
                        return ActivityCategory::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        // Ambil activity_id yang memiliki kategori tertentu
                        $activityIds = \DB::table('activity_activity_category')
                            ->where('activity_category_id', $data['value'])
                            ->pluck('activity_id');
                            
                        return $query->whereIn('activity_id', $activityIds);
                    }),

                    SelectFilter::make('user_unit')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->options(function () {
                        return \App\Models\Unit::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['value'],
                            fn($q) => $q->whereHas('user', function ($userQuery) use ($data) {
                                $userQuery->where('unit_id', $data['value']);
                            })
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->recordAction('view')
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
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        Split::make([
                            Grid::make(2)
                                ->schema([
                                    Group::make([
                                        TextEntry::make('user.name')
                                            ->label('Nama Pegawai'),
                                        TextEntry::make('user.nip')
                                            ->label('NIP')
                                            ->placeholder('not set'),
                                        TextEntry::make('user.employee_class')
                                            ->label('Pangkat/Gol')
                                            ->placeholder('not set'),
                                        TextEntry::make('user.job_title')
                                            ->label('Jabatan'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('activity.reference')
                                            ->label('Dasar Surat')
                                            ->getStateUsing(function ($record) {
                                                $references = $record->activity->reference;

                                                if (!$references || !is_array($references)) {
                                                    return 'Tidak ada dasar surat';
                                                }

                                                $formattedList = [];
                                                foreach ($references as $index => $item) {
                                                    $title = is_array($item) ? ($item['title'] ?? 'Tanpa judul') : $item;
                                                    $formattedList[] = ($index + 1) . '. ' . $title;
                                                }

                                                return implode('<br>', $formattedList);
                                            })
                                            ->html(),
                                        TextEntry::make('activity.type')
                                            ->label('Tipe')
                                            ->badge()
                                            ->colors([
                                                'primary' => 'dinas',
                                                'warning' => 'mandiri',
                                            ]),
                                        TextEntry::make('activity.title')
                                            ->label('Kegiatan'),
                                        TextEntry::make('activity.categories.name')
                                            ->label('Kategori Kegiatan')
                                            ->badge()
                                            ->separator(',')
                                            ->limit(30)
                                            ->color('gray')
                                            ->placeholder('not set'),
                                        TextEntry::make('activity.organizer')
                                            ->label('Penyelenggara')
                                            ->placeholder('not set'),
                                        TextEntry::make('activity.location')
                                            ->label('Lokasi')
                                            ->placeholder('not set'),
                                        TextEntry::make('activity.start_date')
                                            ->label('Tanggal Mulai')
                                            ->date('d F Y'),
                                        TextEntry::make('activity.finish_date')
                                            ->label('Tanggal Selesai')
                                            ->date('d F Y'),
                                        TextEntry::make('activity.duration')
                                            ->label('Durasi')
                                            ->suffix(' Jam Pelajaran')
                                            ->placeholder('not set'),
                                    ]),
                                ]),
                        ])->from('md'),
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
            'index' => Pages\ListUserActivities::route('/'),
            'create' => Pages\CreateUserActivity::route('/create'),
            // 'edit' => Pages\EditUserActivity::route('/{record}/edit'),
            // 'view' => Pages\ViewUserActivity::route('/{record}/view'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }

}
