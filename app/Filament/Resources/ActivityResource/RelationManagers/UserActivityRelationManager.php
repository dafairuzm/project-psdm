<?php

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Builder;



class UserActivityRelationManager extends RelationManager
{
    protected static string $relationship = 'userActivities';
    protected static ?string $title = 'Peserta Kegiatan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Nama Pegawai')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $user = User::find($state);

                        if ($user) {
                            $set('nip', $user->nip);
                            $set('jabatan', $user->title_complete);
                        } else {
                            $set('nip', null);
                            $set('jabatan', null);
                        }
                    })
                    ->afterStateHydrated(function ($state, callable $set, ?Model $record) {
                        // Ini akan dipanggil saat View action digunakan
                        if ($record && $record->user) {
                            $set('nip', $record->user->nip);
                            $set('jabatan', $record->user->title_complete);
                        }
                    }),

                TextInput::make('nip')
                    ->label('NIP')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('jabatan')
                    ->label('Jabatan')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Nama Pegawai'),
                TextColumn::make('user.nip')->label('NIP'),
                TextColumn::make('user.title_complete')->label('Jabatan'),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->with('user'))
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Peserta')
                    ->modalHeading('Tambah Peserta')
                    ->failureNotificationTitle('Gagal menambahkan peserta')
                    ->using(function (array $data, RelationManager $livewire): Model {
                        $activity = $livewire->ownerRecord;

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

                        return UserActivity::create([
                            'user_id' => $data['user_id'],
                            'activity_id' => $activity->id,
                        ]);
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Lihat')
                    ->mutateRecordDataUsing(function (array $data): array {
                        // Load user data untuk view action
                        $userActivity = UserActivity::with('user')->find($data['id']);
                        if ($userActivity && $userActivity->user) {
                            $data['nip'] = $userActivity->user->nip;
                            $data['jabatan'] = $userActivity->user->title_complete;
                        }
                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }
}