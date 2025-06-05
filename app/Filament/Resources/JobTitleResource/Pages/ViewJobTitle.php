<?php

namespace App\Filament\Resources\JobTitleResource\Pages;

use App\Filament\Resources\JobTitleResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewJobTitle extends ViewRecord
{
    protected static string $resource = JobTitleResource::class;

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
