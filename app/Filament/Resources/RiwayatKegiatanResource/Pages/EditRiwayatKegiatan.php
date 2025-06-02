<?php

namespace App\Filament\Resources\RiwayatKegiatanResource\Pages;

use App\Filament\Resources\RiwayatKegiatanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRiwayatKegiatan extends EditRecord
{
    protected static string $resource = RiwayatKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
