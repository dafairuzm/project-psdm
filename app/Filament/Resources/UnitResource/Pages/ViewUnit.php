<?php

namespace App\Filament\Resources\UnitResource\Pages;


use App\Filament\Resources\UnitResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewUnit extends ViewRecord
{
    protected static string $resource = UnitResource::class;

    protected static ?string $title = 'Lihat Detail';
    public function getTitle(): string | Htmlable
    {
        /** @var \App\Models\Activity */
        $record = $this->getRecord();

        return $record->name;
    }

    protected function getActions(): array
    {
        return [];
    }

}
