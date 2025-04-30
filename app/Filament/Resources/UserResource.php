<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Pengguna';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->label('Peran')
                    ->options([
                        'admin' => 'Administrator',
                        'user' => 'Pengguna',
                    ])
                    ->required()
                    ->default('user'),
                Forms\Components\TextInput::make('nip')
                    ->label('NIP')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('employee_class')
                    ->label('Golongan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('job_title')
                    ->label('Jabatan')
                    ->options([
                        'dokter' => 'Dokter',
                        'perawat' => 'Perawat',
                        't.kes lain' => 'Tenaga Kesehatan Lain',
                        'manajemen' => 'Manajemen',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('title_complete')
                    ->label('Jabatan Lengkap')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'user' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrator',
                        'user' => 'Pengguna',
                    }),
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee_class')
                    ->label('Golongan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->label('Jabatan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'dokter' => 'Dokter',
                        'perawat' => 'Perawat',
                        't.kes lain' => 'Tenaga Kesehatan Lain',
                        'manajemen' => 'Manajemen',
                    }),
                Tables\Columns\TextColumn::make('title_complete')
                    ->label('Jabatan Lengkap')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
