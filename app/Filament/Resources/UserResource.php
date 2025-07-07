<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\JobTitle;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    // use HasPageShield;
    protected static ?string $model = User::class;
    protected static ?string $navigationGroup = 'Pengguna';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Pengguna';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';

    public static function getNavigationSort(): int
    {
        return 3; // Angka lebih kecil = lebih atas di sidebar
    }

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
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make('nip')
                    ->label('NIP')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('employee_type')
                    ->label('Jenis Pegawai')
                    ->required()
                    ->options([
                        'ASN PNS' => 'ASN PNS',
                        'ASN PPK' => 'ASN PPK',
                        'BLUD PHL' => 'BLUD PHL',
                        'BLUD PTT' => 'BLUD PTT',
                        'BLUD TETAP' => 'BLUD TETAP',
                        'KSO' => 'KSO',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('employee_class')
                    ->label('Golongan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('unit_id')
                    ->required()
                    ->relationship('Unit', 'name')
                    ->label('Unit')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('profession_id')
                    ->required()
                    ->relationship('profession', 'name')
                    ->label('Profesi')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('job_title')
                    ->label('Jabatan')
                    ->required()
                    ->maxLength(255),
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
                                        TextEntry::make('name')->label('Nama Pegawai'),
                                        TextEntry::make('nip')->label('NIP'),
                                        TextEntry::make('gender')->label('Jenis Kelamin'),
                                        TextEntry::make('employee_type')->label('Jenis Pegawai'),
                                        TextEntry::make('employee_class')->label('Pangkat/Gol'),
                                        TextEntry::make('roles.name')
                                            ->label('Role')
                                            ->badge()
                                            ->color(fn(string $state): string => match ($state) {
                                                'Admin' => 'danger',
                                                'Pegawai' => 'success',
                                                default => 'warning',
                                            }),
                                    ]),
                                    Group::make([
                                        TextEntry::make('unit.name')->label('Unit/Ruangan'),
                                        TextEntry::make('profession.name')->label('Profesi'),
                                        TextEntry::make('job_title')->label('Jabatan'),
                                    ]),
                                ]),
                        ])->from('md'),
                    ])
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
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('roles.name')
                    ->searchable()
                    ->color(fn(string $state): string => match ($state) {
                        'Admin' => 'danger',
                        'Pegawai' => 'success',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('employee_class')
                    ->label('Golongan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit/Ruangan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('profession.name')
                    ->label('Profesi')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('job_title')
                    ->label('Jabatan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail Pengguna')
                    ->modalActions([
                        Tables\Actions\Action::make('goToEdit')
                            ->label('Edit')
                            ->color('primary')
                            ->icon('heroicon-o-pencil')
                            ->url(fn($record) => static::getUrl('edit', ['record' => $record]))
                            ->openUrlInNewTab(false),
                    ]),
                Tables\Actions\EditAction::make()
                    ->hidden(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
            ])
            ->recordUrl(null)
            ->recordAction('view');
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
            'view' => Pages\EditUser::route('/{record}'),
        ];
    }

}
