<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;


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

        return "Kelola Dokumentasi {$recordTitle}";
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
                ->directory('activity-docs')
                ->disk('public')
                ->preserveFilenames()
                ->visibility('public')
                ->required(),
            // Placeholder::make('existing_docs_info')
            //     ->content(fn($record) => '🛈 Terdapat ' . $record->activitydocs->count() . ' dokumentasi yang sudah diunggah. Anda masih bisa menambahkan dokumentasi baru jika diperlukan.')
            //     ->visible(fn($record) => $record && $record->activitydocs->isNotEmpty())
            //     ->dehydrated(false),
            // // View::make('filament.component.activity-docs')
            //     ->label('Dokumentasi Sebelumnya')
            //     ->visible(fn($record) => $record && $record->activitydocs->isNotEmpty())
            //     ->viewData([
            //         'docs' => fn($record) => $record->activitydocs,
            //     ])
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns(
            [
                ImageColumn::make('documentation')
                    ->label('Gambar')
                    ->disk('public') // atau sesuai disk yang kamu pakai
                    ->height('80px')// atur ukuran tinggi gambar
                    ->width('auto')  // atau bisa juga 100%
                    ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 8px;']),
                TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    //->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                //ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);

    }
}
