<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use App\Models\ActivityDoc;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Pages\SubNavigationPosition;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Resources\ActivityResource\RelationManagers\UserActivityRelationManager;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Kegiatan';

    protected static ?string $navigationLabel = 'Kegiatan';

    protected static ?string $modelLabel = 'Kegiatan';

    protected static ?string $pluralModelLabel = 'Kegiatan';

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
                Forms\Components\Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
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
                    ->rule('after_or_equal:start_date')
                    ->format('Y-m-d'),
                Forms\Components\TextInput::make('duration')
                    ->label('Durasi (Jam pelajaran)')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                // Forms\Components\Grid::make(1)
                //     ->schema([
                //         FileUpload::make('temp_documentation')
                //             ->label('Upload Dokumentasi')
                //             ->image()
                //             ->multiple()
                //             ->directory('activity-docs')
                //             ->disk('public')
                //             ->preserveFilenames()
                //             ->visibility('public'),
                //         // // ->afterStateUpdated(function ($state, $set, $get, $livewire, $component) {
                //         // //     if ($livewire->record && is_array($state)) {
                //         // //         foreach ($state as $uploadedFile) {
                //         // //             $storedPath = $uploadedFile->storeAs(
                //         // //                 'activity-docs',
                //         // //                 $uploadedFile->getClientOriginalName(),
                //         // //                 'public'
                //         // //             );

                //         // //             if (
                //         // //                 !ActivityDoc::where('activity_id', $livewire->record->id)
                //         // //                     ->where('documentation', $storedPath)
                //         // //                     ->exists()
                //         // //             ) {
                //         // //                 ActivityDoc::create([
                //         // //                     'activity_id' => $livewire->record->id,
                //         // //                     'documentation' => $storedPath,
                //         // //                 ]);
                //         // //             }
                //         // //         }

                //         //         $set('temp_documentation', []);
                //         //     }
                //         // }),

                //         Placeholder::make('existing_docs_info')
                //             ->content(fn($record) => '🛈 Terdapat ' . $record->activitydocs->count() . ' dokumentasi yang sudah diunggah. Anda masih bisa menambahkan dokumentasi baru jika diperlukan.')
                //             ->visible(fn($record) => $record && $record->activitydocs->isNotEmpty())
                //             ->dehydrated(false),
                //     ]),

                //->dehydrated(false),
                // Tampilkan dokumentasi yang sudah tersimpan
                // Forms\Components\Placeholder::make('Dokumentasi Tersimpan')
                //     ->content(function ($record) {
                //         return view('filament.component.activity-docs', [
                //             'docs' => $record?-> activitydocs ?? [],
                //         ]);
                //     })
                // ->dehydrated(false)
                // ->columnSpanFull(),
                // Forms\Components\View::make('filament.component.activity-docs')
                //     ->label('Dokumentasi Sebelumnya')
                //     ->visible(fn($record) => $record && $record->activitydocs->isNotEmpty())
                //     ->viewData([
                //         'docs' => fn($record) => $record->activitydocs,
                //     ])
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
                Tables\Columns\TextColumn::make('category.name')
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
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Peserta Hadir')
                    ->getStateUsing(function ($record) {
                        return $record->userActivities()
                            ->where('attendance_status', 'Hadir')
                            ->count();
                        //->count('attendance_status');
                    }),
            ])
            ->filters([], layout: FiltersLayout::AboveContent) // tidak pakai filter tambahan
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
            'activitydocs' => Pages\ManageDocumentation::route('/{record}/activitydocs'),
            'notes' => Pages\ManageNote::route('/{record}/notes')
        ];
    }

    public static function getRelations(): array
    {
        return [
            UserActivityRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('activitydocs');
    }

    public static function getRecordSubnavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditActivity::class,
            Pages\ManageDocumentation::class,
            Pages\ManageNote::class
        ]);
    }

}
