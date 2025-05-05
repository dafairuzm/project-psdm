<?php

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserActivityRelationManager extends RelationManager
{
    protected static string $relationship = 'userActivities';

    protected static ?string $title = 'Peserta Kegiatan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('attendance_status')
                    ->label('Status Kehadiran')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Tidak Hadir' => 'Tidak Hadir',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nama Pegawai'),
                Tables\Columns\TextColumn::make('user.title_complete')->label('Jabatan'),
                Tables\Columns\TextColumn::make('attendance_status')->label('Kehadiran')
                ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Tidak Hadir' => 'danger',
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->label('Tambah Peserta')
                ->modalHeading('Tambah Peserta'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
