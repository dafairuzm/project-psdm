<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentationResource\Pages;
use App\Filament\Resources\DocumentationResource\RelationManagers;
use App\Models\Documentation;
use DragonCode\Support\Facades\Helpers\Str;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class DocumentationResource extends Resource
{
    protected static ?string $model = Documentation::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Kegiatan';
    protected static ?string $navigationLabel = 'Dokumentasi';
    protected static ?string $modelLabel = 'Dokumentasi';
    protected static ?string $pluralModelLabel = 'Dokumentasi';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('activity_id')
                ->relationship('activity','title')
                ->preload()
                ->searchable()
                ->required()
                ->label('Kegiatan'),

                Hidden::make('user_id')
                ->default(fn() => auth()->id()),
                
                FileUpload::make('documentation')
                ->label('Dokumentasi')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png'])
                ->maxSize(15360) // 15 MB dalam KB
                ->directory('documentations')
                ->disk('public')
                ->preserveFilenames()
                ->visibility('public')
                ->required()
                ->helperText('Unggah file gambar dengan format JPG, JPEG, atau PNG. Maksimal ukuran file 15 MB.')
                ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Stack::make([
                ImageColumn::make('documentation')
                    ->extraImgAttributes([
                        'style' => 'aspect-ratio: 3/2; border-radius: 0.5rem; width: 100%; height: auto;',
                    ]),
    
                    
                // Spacer palsu biar ada jarak
                TextColumn::make('spacer')
                    ->label('')
                    ->formatStateUsing(fn () => ' ') // kasih space kosong
                    ->extraAttributes([
                        'style' => 'margin-bottom: 1rem;', // ini yang bikin efek <br>
                    ]),
    
                TextColumn::make('documentation')
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(fn (string $state) => Str::after($state, 'documentations/'))
                    ->wrap()
                    ->extraAttributes([
                        'style' => 'word-break: break-word;',
                    ]),
    
                TextColumn::make('activity.title'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(function ($state, $record) {
                        $createdBy = $record->user ? $record->user->name : 'Unknown';
                        $createdAt = $state ? $state->diffForHumans() : '';
                        return $createdBy . ', ' . $createdAt;
                    })
                    ->color('gray')
                    ->size(TextColumn\TextColumnSize::Small),
            ])->space(0), 
        ])
        ->contentGrid([
            'md' => 2,
            'xl' => 3,
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\ViewAction::make()
                ->form([])
                ->modalContent(function ($record) {
                    return new HtmlString('
                        <div class="space-y-6">
                            <div class="flex justify-center">
                                <img src="' . Storage::url($record->documentation) . '" 
                                     alt="Documentation Image"
                                     class="max-w-full h-auto rounded-lg shadow-lg"
                                     style="max-height: 50vh;">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">File Name</h3>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        ' . Str::after($record->documentation, 'documentations/') . '
                                    </p>
                                </div>
                                
                                ' . ($record->activity ? '
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Activity</h3>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        ' . $record->activity->title . '
                                    </p>
                                </div>
                                ' : '') . '
                                
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
                ->modalHeading(fn ($record) => 'Dokumentasi: ' . Str::after($record->documentation, 'documentations/'))
                ->modalWidth('7xl'),
                
                DeleteAction::make()// Ukuran modal besar untuk gambar
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
            'index' => Pages\ListDocumentations::route('/'),
            'create' => Pages\CreateDocumentation::route('/create'),
            // 'edit' => Pages\EditDocumentation::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }
}
