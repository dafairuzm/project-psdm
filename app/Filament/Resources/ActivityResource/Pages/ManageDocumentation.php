<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Models\Documentation;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Storage;


class ManageDocumentation extends ManageRelatedRecords
{
    protected static string $resource = ActivityResource::class;
    protected static string $relationship = 'activitydocs';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Kegiatan';

    public function getTitle(): string|Htmlable
    {
        $recordTitle = $this->getRecordTitle();
        $recordTitle = $recordTitle instanceof Htmlable ? $recordTitle->toHtml() : $recordTitle;

        return "Dokumentasi {$recordTitle}";
    }

    public static function getNavigationLabel(): string
    {
        return 'Dokumentasi';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('documentation')
                ->label('Dokumentasi')
                ->image()
                ->directory('documentations')
                ->disk('public')
                ->preserveFilenames()
                ->visibility('public')
                ->required(),
            Hidden::make('user_id')
                ->default(fn() => auth()->id()),
            Hidden::make('activity_id')
                ->default(fn() => $this->getOwnerRecord()->id),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns(
            [
                ImageColumn::make('documentation')
                    ->label('Gambar')
                    ->disk('public') // atau sesuai disk yang kamu pakai
                    ->height('100px')// atur ukuran tinggi gambar
                    ->width('auto')  // atau bisa juga 100%
                    ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 8px;']),
                TextColumn::make('user.name')
                    ->label('Dibuat oleh')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat pada')//->searchable()
                    ->sortable(),
            ]
        )
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make()
                ->label('Upload Dokumentasi')
                ->icon('heroicon-o-arrow-up'),
            ])
            ->actions([
                ViewAction::make()
                ->modalHeading('Preview'),
                DeleteAction::make(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('download-dokumentasi', ['id' => $record->id])),
            ])
            ->BulkActions([
                DeleteBulkAction::make(),
            ]);

    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['activity_id'] = $this->getOwnerRecord()->id; // tambahkan ini juga
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!isset($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }
        return $data;
    }


}