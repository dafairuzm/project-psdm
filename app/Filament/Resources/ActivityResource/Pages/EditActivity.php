<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected static ?string $title = 'Edit Kegiatan';

    public static function getNavigationLabel(): string
    {
        return 'Edit Kegiatan';
    }

    public function getTableModelLabel(): string
    {
        return 'Edit Kegiatan';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
