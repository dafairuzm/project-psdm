<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Models\Certificate;
use Filament\Actions;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use DragonCode\Support\Facades\Helpers\Str;

class ManageCertificate extends ManageRelatedRecords
{
    protected static string $resource = ActivityResource::class;

    protected static string $relationship = 'certificates';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $title = 'Sertifikat';

    public function getTitle(): string
    {
        return 'Kelola Sertifikat - ' . $this->getRecord()->title;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('activity_id')
                    ->default(fn() => $this->getRecord()->id),
                    
                Hidden::make('user_id')
                    ->default(fn() => auth()->id()),
                    
                FileUpload::make('name')
                    ->label('Sertifikat')
                    ->required()
                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'])
                    ->maxSize(5120) // 5 MB dalam KB
                    ->directory('certificates')
                    ->disk('public')
                    ->preserveFilenames()
                    ->visibility('public')
                    ->helperText('Unggah file gambar dengan format JPG, JPEG, PNG, atau PDF. Maksimal ukuran file 5 MB.')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Stack::make([
                    // Preview sertifikat
                    TextColumn::make('name')
                        ->label('Preview')
                        ->searchable()              
                        ->formatStateUsing(function ($state) {
                            $ext = pathinfo($state, PATHINFO_EXTENSION);
                            $url = Storage::url($state);

                            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                return new HtmlString('
                                    <div style="width: 100%; height: 200px; overflow: hidden; border-radius: 0.5rem; background: #ffffff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center;">
                                        <img src="' . $url . '" 
                                             style="width: 100%; height: 100%; object-fit: cover; object-position: center;" 
                                             alt="Certificate preview" />
                                    </div>');
                            } elseif ($ext === 'pdf') {
                                return new HtmlString('
                                    <div style="width: 12rem; height: 200px; border-radius: 0.5rem; background: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                        <img src="' . asset('storage/images/icon-pdf.svg') . '" alt="PDF Icon" style="width: 80px; height: 80px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>');
                            } else {
                                return new HtmlString('
                                    <div style="width: 100%; height: 200px; border-radius: 0.5rem; background: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                        <div style="width: 4rem; height: 4rem; background: #e5e7eb; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                            <svg style="width: 2rem; height: 2rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <p style="font-size: 0.875rem; color: #6b7280;">File tidak dikenali</p>
                                    </div>');
                            }
                        }),

                    // Nama file
                    TextColumn::make('name')
                        ->label('File Name')
                        ->weight(FontWeight::Bold)
                        ->formatStateUsing(fn (string $state) => Str::afterLast($state, '/'))
  ->searchable(query: function ($query, $search) {
    return $query->whereRaw("LOWER(SUBSTRING_INDEX(name, '/', -1)) LIKE ?", ['%' . strtolower($search) . '%']);
})
                        ->formatStateUsing(fn (string $state) => Str::after($state, 'certificates/'))
                        ->wrap()
                        ->extraAttributes([
                            'style' => 'word-break: break-word; margin-top: 0.5rem;',
                        ]),

                    // Info pembuat dan tanggal
                    TextColumn::make('created_at')
                        ->label('Created')
                        ->sortable()
                        ->formatStateUsing(function ($state, $record) {
                            $createdBy = $record->user ? $record->user->name : 'Unknown';
                            $createdAt = $state ? $state->diffForHumans() : '';
                            return $createdBy . ', ' . $createdAt;
                        })
                        ->color('gray')
                        ->size(TextColumn\TextColumnSize::Small),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Sertifikat')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Tambah Sertifikat untuk ' . $this->getRecord()->title)
                    ->modalWidth('2xl')
                    ->successNotificationTitle('Sertifikat berhasil ditambahkan'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([])
                    ->modalContent(function ($record) {
                        $ext = pathinfo($record->name, PATHINFO_EXTENSION);
                        $url = Storage::url($record->name);
                        
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            $preview = '<div class="flex justify-center">
                                <img src="' . $url . '" 
                                     alt="Certificate Image"
                                     class="max-w-full h-auto rounded-lg shadow-lg"
                                     style="max-height: 70vh; object-fit: contain;">
                            </div>';
                        } elseif ($ext === 'pdf') {
                            $preview = '<div class="flex justify-center">
                                <iframe src="' . $url . '" 
                                        width="100%" 
                                        height="600px" 
                                        class="rounded-lg shadow-lg">
                                </iframe>
                            </div>';
                        } else {
                            $preview = '<div class="flex justify-center">
                                <div class="text-center p-8 bg-gray-50 rounded-lg">
                                    <p class="text-gray-500">File tidak dapat dipratinjau</p>
                                    <a href="' . $url . '" target="_blank" class="text-blue-600 underline">Unduh file</a>
                                </div>
                            </div>';
                        }

                        return new HtmlString('
                            <div class="space-y-6">
                                ' . $preview . '
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">File Name</h3>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            ' . Str::after($record->name, 'certificates/') . '
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Activity</h3>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            ' . $this->getRecord()->title . '
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</h3>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            ' . ($record->user ? $record->user->name : 'Unknown') . '
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</h3>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            ' . ($record->created_at ? $record->created_at->format('M d, Y H:i') : '') . '
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ');
                    })
                    ->modalHeading(fn ($record) => 'Sertifikat: ' . Str::after($record->name, 'certificates/'))
                    ->modalWidth('7xl'),
                // Tables\Actions\EditAction::make()
                //     ->modalHeading(fn ($record) => 'Edit Sertifikat')
                //     ->modalWidth('2xl'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Certificate $record) {
                        // Cek apakah file ada
                        if (!Storage::disk('public')->exists($record->name)) {
                            $this->notify('error', 'File tidak ditemukan');
                            return;
                        }

                        // Get file path
                        $filePath = Storage::disk('public')->path($record->name);
                        $fileName = basename($record->name);
                        
                        // Return download response
                        return response()->download($filePath, $fileName);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-x-mark')
            ->emptyStateHeading('Belum ada sertifikat')
            ->emptyStateDescription('Mulai dengan menambahkan sertifikat pertama untuk kegiatan ini.');
            // ->emptyStateActions([
            //     Tables\Actions\CreateAction::make()
            //         ->label('Tambah Sertifikat')
            //         ->icon('heroicon-o-plus'),
            // ]);
    }

    public function getRelationshipQuery(): Builder
    {
        return parent::getRelationshipQuery()->orderBy('created_at', 'desc');
    }
}