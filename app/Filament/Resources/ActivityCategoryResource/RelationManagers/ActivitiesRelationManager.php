<?php

namespace App\Filament\Resources\ActivityCategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Daftar Kegiatan';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul Kegiatan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Tipe Kegiatan')
                    ->options([
                        'exhouse' => 'Exhouse',
                        'inhouse' => 'Inhouse',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('speaker')
                    ->label('Pembicara')
                    ->maxLength(255),
                Forms\Components\TextInput::make('organizer')
                    ->label('Penyelenggara')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('location')
                    ->label('Lokasi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->format('Y-m-d'),
                Forms\Components\DatePicker::make('finish_date')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->after('start_date')
                    ->format('Y-m-d'),
                Forms\Components\TextInput::make('duration')
                    ->label('Durasi (Jam pelajaran)')
                    ->numeric()
                    ->required()
                    ->minValue(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Kegiatan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->searchable(),
                Tables\Columns\TextColumn::make('speaker')
                    ->label('Pembicara')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organizer')
                    ->label('Penyelenggara')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->date('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finish_date')
                    ->label('Tanggal Selesai')
                    ->date('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Durasi (Jam pelajaran)')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Kegiatan')
                    ->options([
                        'exhouse' => 'Exhouse',
                        'inhouse' => 'Inhouse',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
} 