<?php

namespace App\Filament\Resources\ActivityDocResource\Pages;

use App\Filament\Resources\ActivityDocResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityDocs extends ListRecords
{
    protected static string $resource = ActivityDocResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
