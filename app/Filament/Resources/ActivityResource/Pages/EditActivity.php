<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Models\ActivityDoc;
use Doctrine\DBAL\Schema\View;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\EditRecord;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;
    protected static ?string $navigationLabel = 'Edit Kegiatan';
    

    public static function getNavigationLabel(): string
    {
        return 'Edit Kegiatan';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // if (isset($data['documentation'])) {
        //     $data['documentation'] = str_replace('public/', '', $data['documentation']);
        // }

        return $data;
    }

    // protected function afterSave(): void
    // {
    //     $this->handleDocumentationUpload();
    // }

    // protected function handleDocumentationUpload(): void
    // {
    //     $paths = $this->form->getState()['temp_documentation'] ?? [];

    //     if ($this->record && is_array($paths)) {
    //         foreach ($paths as $path) {
    //             ActivityDoc::firstOrCreate([
    //                 'activity_id' => $this->record->id,
    //                 'documentation' => $path, // sudah string path
    //             ]);
    //         }

    //         // Kosongkan field agar tidak tersimpan ulang
    //         //$this->form->fill(['temp_documentation' => []]);
    //     }
    // }
    // protected function beforeSave(): void
    // {
    //     $this->handleDocumentationUpload();
    // }
    
}
