<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Resources\ActivityResource\RelationManagers\UserActivityRelationManager;
use Filament\Pages\SubNavigationPosition;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Kegiatan';
    protected static ?string $navigationLabel = 'Kegiatan';
    protected static ?string $modelLabel = 'Kegiatan';
    protected static ?string $pluralModelLabel = 'Kegiatan';
    public static function getNavigationSort(): int
    {
        return 1; // Angka lebih kecil = lebih atas di sidebar
    }
    protected static ?string $recordTitleAttribute = 'title';
    protected static ?int $navigationSort = 3;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Form $form): Form
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
                Forms\Components\MultiSelect::make('categories')
                    ->relationship('categories', 'name')
                    ->columns(2)
                    ->preload()
                    ->label('Kategori'),
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
                    ->rule('after_or_equal:start_date')
                    ->format('Y-m-d'),
                Forms\Components\TextInput::make('duration')
                    ->label('Durasi (Jam pelajaran)')
                    ->numeric()
                    ->required()
                    ->minValue(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Kegiatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->searchable(),
                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Kategori')
                    ->sortable(),
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
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Mulai Dari'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['start_date'], fn($q) => $q->whereDate('start_date', '>=', $data['start_date']))
                            ->when($data['end_date'], fn($q) => $q->whereDate('finish_date', '<=', $data['end_date']));
                    }),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Kegiatan')
                    ->options([
                        'exhouse' => 'Exhouse',
                        'inhouse' => 'Inhouse',
                    ]),

                Tables\Filters\SelectFilter::make('categories')
                    ->label('Kategori')
                    ->relationship('categories', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Utama')
                    ->schema([
                        Split::make([
                            Components\Grid::make(2)
                                ->schema([
                                    Components\Group::make([
                                        TextEntry::make('title')
                                            ->label('Judul Kegiatan'),
                                        TextEntry::make('type')
                                            ->label('Tipe Kegiatan')
                                            ->badge()
                                            ->color(fn(string $state): string => $state === 'inhouse' ? 'success' : 'info'),
                                        TextEntry::make('categories.name')
                                            ->label('Kategori'),
                                        TextEntry::make('organizer')
                                            ->label('Penyelenggara'),
                                    ]),
                                    Components\Group::make([
                                        TextEntry::make('speaker')
                                            ->label('Pembicara'),
                                        TextEntry::make('location')
                                            ->label('Lokasi'),
                                        TextEntry::make('start_date')
                                            ->label('Tanggal Mulai')
                                            ->date('d F Y'),
                                        TextEntry::make('finish_date')
                                            ->label('Tanggal Selesai')
                                            ->date('d F Y'),
                                        TextEntry::make('duration')
                                            ->label('Durasi')
                                            ->suffix('Jam Pelajaran'),
                                    ]),
                                ]),
                        ])->from('md'),
                    ]),

                Section::make('Dokumentasi')
                    ->schema([
                        ImageEntry::make('activitydocs.documentation')
                            ->label('')
                            ->grow(false)
                            ->columnSpanFull()
                            ->columns(2)
                    ])
                    ->collapsible(),

                Section::make('Catatan Kegiatan')
                    ->schema([
                        TextEntry::make('notes.note')
                            ->prose()
                            ->markdown()
                            ->hiddenLabel()
                            ->formatStateUsing(function ($state) {
                                $notes = collect(explode(',', $state))
                                    ->map(fn($note) => '<li>' . trim($note) . '</li>')
                                    ->implode('');

                                return "<ul class='list-disc list-inside pl-4'>" . $notes . "</ul>";
                            })
                            ->html(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewActivity::class,
            Pages\EditActivity::class,
            Pages\ManageUserActivities::class,
            Pages\ManageDocumentation::class,
            Pages\ManageNote::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
            'attendances' => Pages\ManageUserActivities::route('/{record}/attendances'),
            'documentation' => Pages\ManageDocumentation::route('/{record}/documentation'),
            'note' => Pages\ManageNote::route('/{record}/note'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            UserActivityRelationManager::class,
        ];
    }
}
