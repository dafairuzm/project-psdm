<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Models\Attendance;
use App\Models\UserActivity;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;
use Filament\Notifications\Notification;


class ManageUserActivities extends ManageRelatedRecords
{
    protected static string $resource = ActivityResource::class;

    protected static string $relationship = 'userActivities';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $title = 'Peserta Kegiatan';

    public static function getNavigationLabel(): string
    {
        return 'Peserta & Daftar Hadir';
    }

    public function getTableModelLabel(): string
    {
        return 'Peserta & Daftar Hadir';
    }

    public function getTitle(): string | Htmlable
    {
        $recordTitle = $this->getRecordTitle();

        $recordTitle = $recordTitle instanceof Htmlable ? $recordTitle->toHtml() : $recordTitle;

        return "Peserta {$recordTitle}";
    }

    public function getTableRecordsPerPage(): int
    {
        return 20;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
    private function hasDateColumn(): bool
    {
        return Schema::hasColumn('attendances', 'date');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.nip')->label('NIP'),
                TextColumn::make('user.title_complete')->label('Jabatan'),
                TextColumn::make('attendance_summary')
                    ->label('Status Kehadiran')
                    ->getStateUsing(function ($record) {
                        return $record->attendances
                            ->sortBy('date')
                            ->map(function ($attendance) {
                                $formattedDate = Carbon::parse($attendance->date)->translatedFormat('d F');
                                return "{$formattedDate} ({$attendance->status})";
                            })
                            ->implode('<br>');
                    })
                    ->wrap()->html(), // Biar kalau kepanjangan dia pindah ke baris bawah, nggak satu baris panjang                
            ])
            ->filters([
                // Optional filters
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Peserta')
                    ->modalHeading('Tambah Peserta')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('user_id')
                            ->label('Nama Pegawai')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $user = \App\Models\User::find($state);
                                $set('nip', $user?->nip);
                                $set('jabatan', $user?->title_complete);
                            }),
            
                        TextInput::make('nip')
                            ->label('NIP')
                            ->disabled()
                            ->dehydrated(false),
            
                        TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->action(function (array $data) {
                        $activity = $this->getOwnerRecord();
            
                        // ❗ Cek apakah user sudah terdaftar di kegiatan ini
                        $exists = UserActivity::where('user_id', $data['user_id'])
                            ->where('activity_id', $activity->id)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Peserta ini sudah terdaftar')
                                ->danger()
                                ->send();
                            throw new Halt();
                        }
            
                        // Simpan user activity
                        $userActivity = UserActivity::create([
                            'activity_id' => $activity->id,
                            'user_id' => $data['user_id'],
                        ]);
            
                        // Buat daftar hadir otomatis
                        $period = CarbonPeriod::create($activity->start_date, $activity->finish_date);
                        foreach ($period as $date) {
                            Attendance::create([
                                'user_activity_id' => $userActivity->id,
                                'date' => $date->format('Y-m-d'),
                                'status' => 'Hadir',
                            ]);
                        }
            
                        Notification::make()
                            ->title('Peserta Ditambahkan')
                            ->success()
                            ->body('Peserta berhasil ditambahkan dan daftar hadir otomatis dibuat.')
                            ->send();
                    })            
            ])
            ->actions([
                Tables\Actions\Action::make('manage_attendance')
                    ->label('Atur Kehadiran')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->modalHeading(fn(UserActivity $record) => "Atur Kehadiran: {$record->user->name}")
                    ->modalWidth('lg')
                    ->form(function (UserActivity $record): array {
                        try {
                            // Get the activity
                            $activity = $record->activity;

                            // Cek apakah kolom tanggal ada
                            if (!$this->hasDateColumn()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Kolom date tidak ditemukan di tabel attendances.')
                                    ->send();

                                return [
                                    Forms\Components\Placeholder::make('error_message')
                                        ->content('Kolom date tidak ditemukan di tabel attendances. Silakan jalankan migrasi untuk menambahkan kolom tersebut.')
                                        ->columnSpan('full'),
                                ];
                            }

                            // Get period between start and end date
                            $period = CarbonPeriod::create(
                                $activity->start_date,
                                $activity->finish_date
                            );

                            // Generate form fields for each date
                            $fields = [];

                            foreach ($period as $date) {
                                $dateString = $date->format('Y-m-d');
                                $fieldId = md5($dateString); // Gunakan md5 untuk field ID yang aman
            
                                // Dapatkan attendance dengan query manual yang spesifik
                                $attendance = Attendance::where('user_activity_id', $record->id)
                                    ->where('date', $dateString)
                                    ->first();

                                // Jika belum ada attendance, buat baru
                                if (!$attendance) {
                                    $attendance = new Attendance([
                                        'user_activity_id' => $record->id,
                                        'date' => $dateString,
                                        'status' => 'Belum Diisi', // Default status - kode singkat untuk 'Absent'
                                    ]);
                                    $attendance->save();
                                }

                                // Gunakan cara konstruktor sederhana untuk Form Component
                                $fields[] = Forms\Components\Select::make("attendance.{$fieldId}")
                                    ->label($date->format('d M Y'))
                                    ->options([
                                        'Hadir' => 'Hadir',
                                        'Tidak Hadir' => 'Tidak Hadir',
                                        'Belum Diisi' => 'Belum Diisi',
                                    ])
                                    ->default($attendance->status ?? 'Belum Diisi');
                            }

                            return $fields;

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->send();

                            return [
                                Forms\Components\Placeholder::make('error_message')
                                    ->content('Terjadi kesalahan: ' . $e->getMessage())
                                    ->columnSpan('full'),
                            ];
                        }
                    })
                    ->action(function (array $data, UserActivity $record) {
                        try {
                            // Get the activity
                            $activity = $record->activity;

                            // Get period between start and end date
                            $period = CarbonPeriod::create(
                                $activity->start_date,
                                $activity->finish_date
                            );

                            // Cek apakah kolom tanggal ada
                            if (!$this->hasDateColumn()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Kolom date tidak ditemukan di tabel attendances.')
                                    ->send();
                                return;
                            }

                            // Update attendance status for each date
                            foreach ($period as $date) {
                                $dateString = $date->format('Y-m-d');
                                $fieldId = md5($dateString);
                                $status = $data['attendance'][$fieldId] ?? 'Belum Diisi';

                                // Gunakan metode updateOrCreate untuk menangani kasus insert/update
                                $attendance = Attendance::updateOrCreate(
                                    [
                                        'user_activity_id' => $record->id,
                                        'date' => $dateString,
                                    ],
                                    [
                                        'status' => $status
                                    ]
                                );
                            }

                            Notification::make()
                                ->success()
                                ->title('Berhasil')
                                ->body('Data kehadiran berhasil disimpan')
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn(UserActivity $record) => 'Daftar Kehadiran : ' . $record->user->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn(UserActivity $record) => view('filament.attendance-modal', [
                        'attendances' => $record->attendances()->with('userActivity')->get(),
                    ])),
            ])
            ->bulkActions([
                // Optional bulk actions
            ]);
    }

}
