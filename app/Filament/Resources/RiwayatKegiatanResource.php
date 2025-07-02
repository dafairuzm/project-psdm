<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiwayatKegiatanResource\Pages;
use App\Models\UserActivity; // Kembali menggunakan UserActivity
use App\Models\Documentation;
use App\Models\Note;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Pages\SubNavigationPosition;

class RiwayatKegiatanResource extends Resource
{
    protected static ?string $model = UserActivity::class; // Gunakan UserActivity

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Riwayat Kegiatan';
    
    protected static ?string $modelLabel = 'Riwayat Kegiatan';
    
    protected static ?string $pluralModelLabel = 'Riwayat Kegiatan';

    // Override permission checking untuk navigation
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_riwayat::kegiatan');
    }

    // Override permission checking untuk resource
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_riwayat::kegiatan');
    }
    
    public static function canView($record): bool
    {
        return auth()->user()->can('view_riwayat::kegiatan');
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()->can('create_riwayat::kegiatan');
    }
    
    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_riwayat::kegiatan');
    }
    
    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_riwayat::kegiatan');
    }

    // Override getEloquentQuery untuk additional security
    public static function getEloquentQuery(): Builder
    {
        // Cek permission dulu
        if (!auth()->user()->can('view_any_riwayat::kegiatan')) {
            abort(403, 'Unauthorized access to Riwayat Kegiatan');
        }
        
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form untuk dokumentasi dan catatan
                Forms\Components\Section::make('Tambah Dokumentasi')
                ->schema([
                    Forms\Components\FileUpload::make('documentation_files')
                        ->label('File Dokumentasi')
                        ->directory('documentations')
                        ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png'])
                        ->maxSize(15360) // 15MB dalam KB
                        ->multiple()
                        ->columnSpanFull()
                        ->helperText('Unggah file gambar dengan format JPG, JPEG, atau PNG. Maksimal ukuran file 15 MB.')
                        ->afterStateUpdated(function ($state, $record, $set) {
                            // Simpan ke model Documentation ketika file diupload
                            if ($state && $record && $record->activity_id) {
                                Documentation::create([
                                    'activity_id' => $record->activity_id,
                                    'file_path' => $state,
                                    'uploaded_by' => Auth::id(),
                                ]);
                            }
                        })
                        ->dehydrated(false), // Tidak disimpan ke UserActivity
                ])
                ->collapsible()
                ->visible(fn ($record) => $record && $record->activity_id),
                    
                Forms\Components\Section::make('Tambah Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', Auth::id())->with(['activity', 'activity.categories']))
            ->columns([
                TextColumn::make('activity.title')
                    ->label('Judul Kegiatan')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->extraAttributes([
                        'style' => 'width: 200px; max-width: 200px;'
                    ]),
                    
                TextColumn::make('activity.type')
                    ->label('Jenis')
                    ->badge()
                    ->colors([
                        'primary' => 'inhouse',
                        'warning' => 'exhouse',
                    ]),
                    
                TextColumn::make('activity.categories.name')
                    ->label('Kategori')
                    ->badge()
                    ->separator(', ')
                    ->placeholder('not set')
                    ->wrap(),
                    
                TextColumn::make('activity.speaker')
                    ->label('Pemateri')
                    ->searchable()
                    ->wrap()
                    ->extraAttributes([
                        'style' => 'width: 100px; max-width: 100px;'
                    ])
                    ->limit(30),
                    
                TextColumn::make('activity.organizer')
                    ->label('Penyelenggara')
                    ->searchable()
                    ->wrap()
                    ->limit(30),
                    
                TextColumn::make('activity.location')
                    ->label('Lokasi')
                    ->searchable()
                    ->wrap()
                    ->extraAttributes([
                        'style' => 'width: 200px; max-width: 200px;'
                    ])
                    ->limit(30),
                    
                TextColumn::make('activity.start_date')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->sortable(),
                    
                TextColumn::make('activity.finish_date')
                    ->label('Tanggal Selesai')
                    ->date('d M Y')
                    ->sortable(),
                    
                TextColumn::make('activity.duration')
                    ->label('Durasi')
                    ->suffix(' JPL')
                    ->alignCenter(),
                    
                TextColumn::make('attendances_count')
                    ->label('Kehadiran')
                    ->counts('attendances')
                    ->suffix(' hari')
                    ->alignCenter(),
                    
                TextColumn::make('documentation_status')
                    ->label('Dokumentasi')
                    ->state(function (UserActivity $record): string {
                        $docCount = Documentation::where('activity_id', $record->activity_id)
                            ->where('user_id', Auth::id())
                            ->count();
                        return $docCount > 0 ? "Ada ({$docCount})" : 'Belum ada';
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, 'Ada') ? 'success' : 'danger'),
                    
                TextColumn::make('notes_status')
                    ->label('Catatan')
                    ->state(function (UserActivity $record): string {
                        $noteCount = Note::where('activity_id', $record->activity_id)
                            ->where('user_id', Auth::id())
                            ->count();
                        return $noteCount > 0 ? "Ada ({$noteCount})" : 'Belum ada';
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, 'Ada') ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('activity.type')
                    ->label('Jenis Kegiatan')
                    ->relationship('activity', 'type')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereHas('activity', fn ($query) => $query->whereDate('start_date', '>=', $date)),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereHas('activity', fn ($query) => $query->whereDate('start_date', '<=', $date)),
                            );
                    })
            ])
            ->actions([
                ViewAction::make()
                    ->label('Lihat Detail')
                    ->color('info')
                    ->visible(fn() => auth()->user()->can('view_riwayat::kegiatan')),
                    
                Action::make('add_documentation')
                    ->label('Tambah Dokumentasi')
                    ->icon('heroicon-o-camera')
                    ->color('success')
                    ->visible(fn() => auth()->user()->can('update_riwayat::kegiatan'))
                    ->form([
                        Forms\Components\FileUpload::make('documentation')
                            ->label('File Dokumentasi')
                            ->directory('documentations')
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(15360) // 15MB dalam KB
                            ->multiple()
                            ->required()
                            ->helperText('Unggah file gambar dengan format JPG, JPEG, atau PNG. Maksimal ukuran file 15 MB.'),
                    ])
                    ->action(function (UserActivity $record, array $data): void {
                        foreach ($data['documentation'] as $file) {
                            Documentation::create([
                                'activity_id' => $record->activity_id,
                                'documentation' => $file,
                                'user_id' => Auth::id(),
                            ]);
                            Notification::make()
                        ->title('Upload Berhasil')
                        ->body('Dokumentasi berhasil diupload!')
                        ->success()
                        ->send();
                        }
                    })
                    ->successNotificationTitle('Dokumentasi berhasil ditambahkan')
                    ->modalHeading('Tambah Dokumentasi Kegiatan'),
                    
                Action::make('add_note')
                    ->label('Tambah Catatan')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn() => auth()->user()->can('update_riwayat::kegiatan'))
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->required()
                            ->rows(5)
                            ->helperText('Tambahkan catatan atau refleksi mengenai kegiatan ini'),
                    ])
                    ->action(function (UserActivity $record, array $data): void {
                        Note::create([
                            'activity_id' => $record->activity_id,
                            'note' => $data['note'],
                            'user_id' => Auth::id(),
                        ]);
                    })
                    ->successNotificationTitle('Catatan berhasil ditambahkan')
                    ->modalHeading('Tambah Catatan Kegiatan'),
            ])
            ->bulkActions([
                // Tidak ada bulk actions
            ])
            ->defaultSort('activity.start_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
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
            'index' => Pages\ListRiwayatKegiatans::route('/'),
            'view' => Pages\ViewRiwayatKegiatan::route('/{record}'),
            // 'edit' => Pages\EditRiwayatKegiatan::route('/{record}/edit'),
        ];
    }
}