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
use Filament\Pages\SubNavigationPosition;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
// use Filament\Forms\Components\Section;
use Filament\Infolists\Components\Split;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';
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
                Forms\Components\Section::make('Dasar Surat')
                    ->schema([
                        Forms\Components\Repeater::make('reference')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Textarea::make('title')
                                    ->label('Judul Surat')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(1000),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Dasar Surat')
                    ])->collapsible(),
                Forms\Components\Section::make('Detail Kegiatan')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Kegiatan')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Tipe Kegiatan')
                            ->options([
                                'dinas' => 'Dinas',
                                'mandiri' => 'Mandiri',
                            ])
                            ->required(),
                        Forms\Components\MultiSelect::make('categories')
                            ->relationship('categories', 'name')
                            ->columns(2)
                            ->preload()
                            ->label('Kategori'),
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
                    ])->collapsible()->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Kegiatan')
                    ->searchable()
                    ->extraAttributes([
                        'style' => 'width: 400px; max-width: 600px;'
                    ])
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->searchable()
                    ->colors([
                        'primary' => 'dinas',
                        'warning' => 'mandiri',
                    ]),
                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Kategori')
                    ->sortable()
                    ->badge()
                    ->separator(',')
                    ->limit(30)
                    ->color('gray')
                    ->placeholder('not set'),
                Tables\Columns\TextColumn::make('organizer')
                    ->label('Penyelenggara')
                    ->searchable()
                    ->extraAttributes([
                        'style' => 'width: 300px; max-width: 300px;'
                    ])
                    ->limit(60)
                    ->wrap()
                    ->placeholder('not set'),
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable()
                    ->extraAttributes([
                        'style' => 'width: 300px; max-width: 300px;'
                    ])
                    ->limit(60)
                    ->wrap()
                    ->placeholder('belum di set'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->date('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finish_date')
                    ->label('Tanggal Selesai')
                    ->date('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
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
                        return $query
                            ->when($data['start_date'], fn($q) => $q->whereDate('start_date', '>=', $data['start_date']))
                            ->when($data['end_date'], fn($q) => $q->whereDate('finish_date', '<=', $data['end_date']));
                    }),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Kegiatan')
                    ->options([
                        'dinas' => 'Dinas',
                        'mandiri' => 'Mandiri',
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
                Section::make('Dasar')
                    ->schema([
                        TextEntry::make('reference')
                            ->label('Dasar Surat')
                            ->getStateUsing(function ($record) {
                                $references = $record->reference;

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
                            ->html(), // Tambahkan ini untuk render HTML
                    ]),
                Section::make('Detail Kegiatan')
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
                                            ->color(fn(string $state): string => $state === 'dinas' ? 'primary' : 'warning'),
                                        TextEntry::make('categories.name')
                                            ->label('Kategori')
                                            ->badge()
                                            ->separator(',')
                                            ->limit(30)
                                            ->color('gray')
                                            ->placeholder('not set'),
                                        TextEntry::make('organizer')
                                            ->label('Penyelenggara')
                                            ->placeholder('not set'),
                                    ]),
                                    Components\Group::make([
                                        TextEntry::make('location')
                                            ->label('Lokasi')
                                            ->placeholder('not set'),
                                        TextEntry::make('start_date')
                                            ->label('Tanggal Mulai')
                                            ->date('d F Y'),
                                        TextEntry::make('finish_date')
                                            ->label('Tanggal Selesai')
                                            ->date('d F Y'),
                                        TextEntry::make('duration')
                                            ->label('Durasi')
                                            ->suffix('Jam Pelajaran')
                                            ->placeholder('not set'),
                                    ]),
                                ]),
                        ])->from('md'),
                    ]),

                Section::make('Sertifikat')
                    ->schema([
                        TextEntry::make('custom_certificates_grid')
                            ->label('')
                            ->state(function ($record) {
                                $certificates = $record->certificates()->orderBy('created_at', 'desc')->get();


                                if ($certificates->isEmpty()) {
                                    return '<div style="text-align: center; padding: 32px; color: #6b7280; background-color: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db;">
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 16px; color: #9ca3af;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                    </svg>
                                    <p style="margin: 0; font-size: 16px;">Belum ada sertifikat yang diupload</p>
                                    <p style="margin: 4px 0 0; font-size: 14px; color: #9ca3af;">Ke halaman sertifikat untuk menambah template</p>
                                </div>';
                                }

                                $formattedCertificates = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 12px;">';

                                foreach ($certificates as $cert) {
                                    $fileName = basename($cert->name);
                                    $shortFileName = strlen($fileName) > 25 ? substr($fileName, 0, 22) . '...' : $fileName;
                                    $url = \Illuminate\Support\Facades\Storage::url($cert->name);
                                    $date = $cert->created_at->format('d M Y, H:i');
                                    $extension = strtolower(pathinfo($cert->name, PATHINFO_EXTENSION));

                                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isPdf = $extension === 'pdf';

                                    if ($isImage) {
                                        $preview = '<div style="width: 100%; height: 200px; overflow: hidden; border-radius: 8px 8px 0 0; background-color: #f9fafb; position: relative;">
                            <img src="' . $url . '" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">
                            <div style="display: none; width: 100%; height: 100%; background-color: #f3f4f6; align-items: center; justify-content: center; color: #6b7280; font-size: 14px;">
                                Gambar tidak dapat dimuat
                            </div>
                        </div>';
                                        $fileType = 'Gambar';
                                    } elseif ($isPdf) {
                                        $preview = '<div style="width: 100%; height: 200px; background-color: #f3f4f6; border-radius: 8px 8px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #374151;">
                                            <img src="' . asset('storage/images/icon-pdf.svg') . '" alt="PDF Icon" style="width: 80px; height: 80px; margin-bottom: 8px;">
                                        </div>';
                                        $fileType = 'PDF';
                                    } else {
                                        $preview = '<div style="width: 100%; height: 200px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 8px 8px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; position: relative;">
                                            <svg style="width: 64px; height: 64px; margin-bottom: 8px;" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span style="font-size: 12px; font-weight: 500; opacity: 0.9;">DOC</span>
                                        </div>';
                                        $fileType = 'Dokumen';
                                    }

                                    $formattedCertificates .= '<div style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.06); transition: all 0.3s ease;" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 8px 25px rgba(0,0,0,0.12)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 4px rgba(0,0,0,0.06)\'">
                                        ' . $preview . '
                                        <div style="padding: 16px;">
                                            <div style="margin-bottom: 12px;">
                                                <a href="' . $url . '" target="_blank" style="color: #1f2937; font-weight: 600; font-size: 14px; line-height: 1.4; text-decoration: none; transition: color 0.2s ease;" onmouseover="this.style.color=\'#2563eb\'" onmouseout="this.style.color=\'#1f2937\'" title="' . $fileName . '">' . $shortFileName . '</a>
                                                <div style="color: #6b7280; font-size: 12px; font-weight: 500; margin-top: 4px;">' . $fileType . '</div>
                                            </div>
                                            <div style="color: #6b7280; font-size: 11px; display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid #f3f4f6;">
                                                <div style="display: flex; align-items: center;">
                                                    <svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    ' . $date . '
                                                </div>
                                                <a href="' . $url . '" target="_blank" style="color: #3b82f6; font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px; transition: color 0.2s ease;" onmouseover="this.style.color=\'#1d4ed8\'" onmouseout="this.style.color=\'#3b82f6\'">
                                                    <svg style="width: 12px; height: 12px;" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Lihat
                                                </a>
                                            </div>
                                        </div>
                                    </div>';
                                }

                                $formattedCertificates .= '</div>';
                                return $formattedCertificates;
                            })
                            ->html()
                            ->columnSpanFull()
                    ])
                    ->collapsible(),

                Section::make('Dokumentasi')
                    ->schema([
                        TextEntry::make('custom_docs_grid')
                            ->label('')
                            ->state(function ($record) {
                                $docs = $record->activitydocs;

                                if ($docs->isEmpty()) {
                                    return '<div style="text-align: center; padding: 32px; color: #6b7280; background-color: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db;">
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 16px; color: #9ca3af;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                    </svg>
                                    <p style="margin: 0; font-size: 16px;">Belum ada dokumentasi yang diupload</p>
                                    <p style="margin: 4px 0 0; font-size: 14px; color: #9ca3af;">ke halaman dokumentasi untuk menambah file</p>
                                </div>';
                                }

                                $formattedDocs = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 12px;">';

                                foreach ($docs as $doc) {
                                    $fileName = basename($doc->documentation);
                                    $shortFileName = strlen($fileName) > 25 ? substr($fileName, 0, 22) . '...' : $fileName;
                                    $url = asset('storage/' . $doc->documentation);
                                    $date = $doc->created_at->format('d M Y, H:i');
                                    $extension = strtolower(pathinfo($doc->documentation, PATHINFO_EXTENSION));

                                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

                                    if ($isImage) {
                                        $preview = '<div style="width: 100%; height: 200px; overflow: hidden; border-radius: 8px 8px 0 0; background-color: #f9fafb; position: relative;">
                                        <img src="' . $url . '" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>';
                                        $fileType = 'Gambar';
                                    } else {
                                        $preview = '<div style="width: 100%; height: 200px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 8px 8px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; position: relative;">
                                        <svg style="width: 64px; height: 64px; margin-bottom: 8px;" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span style="font-size: 12px; font-weight: 500; opacity: 0.9;">DOC</span>
                                    </div>';
                                        $fileType = 'Dokumen';
                                    }

                                    $formattedDocs .= '<div style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.06); transition: all 0.3s ease;" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 8px 25px rgba(0,0,0,0.12)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 4px rgba(0,0,0,0.06)\'">
                                    ' . $preview . '
                                    <div style="padding: 16px;">
                                        <div style="margin-bottom: 12px;">
                                            <a href="' . $url . '" target="_blank" style="color: #1f2937; font-weight: 600; font-size: 14px; line-height: 1.4; text-decoration: none; transition: color 0.2s ease;" onmouseover="this.style.color=\'#2563eb\'" onmouseout="this.style.color=\'#1f2937\'" title="' . $fileName . '">' . $shortFileName . '</a>
                                            <div style="color: #6b7280; font-size: 12px; font-weight: 500; margin-top: 4px;">' . $fileType . '</div>
                                        </div>
                                        <div style="color: #6b7280; font-size: 11px; display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid #f3f4f6;">
                                            <div style="display: flex; align-items: center;">
                                                <svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                </svg>
                                                ' . $date . '
                                            </div>
                                            <a href="' . $url . '" target="_blank" style="color: #3b82f6; font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px; transition: color 0.2s ease;" onmouseover="this.style.color=\'#1d4ed8\'" onmouseout="this.style.color=\'#3b82f6\'">
                                                <svg style="width: 12px; height: 12px;" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Lihat
                                            </a>
                                        </div>
                                    </div>
                                </div>';
                                }

                                $formattedDocs .= '</div>';
                                return $formattedDocs;
                            })
                            ->html()
                            ->columnSpanFull()
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
                                    ->map(function ($note) {
                                        return "<div class='border border-gray-200 rounded-lg p-4 bg-white shadow-sm mb-3'>
                                                <div class='text-gray-800 mb-2'>" . trim($note) . "</div>
                                                <div class='text-gray-500 text-sm'>
                                                    <span>Dibuat oleh: Admin</span>
                                                    <span class='mx-2'>•</span>
                                                    <span>" . now()->format('d F Y, H:i') . "</span>
                                                </div>
                                            </div>";
                                    })
                                    ->implode('');

                                return $notes;
                            })
                            ->html()
                            ->columnSpanFull(),
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
            Pages\ManageCertificate::class,
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
            'certificate' => Pages\ManageCertificate::route('/{record}/certificate'),
            'note' => Pages\ManageNote::route('/{record}/note'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // UserActivityRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }

}