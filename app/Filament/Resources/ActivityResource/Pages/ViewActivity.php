<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;
    public function getTitle(): string | Htmlable
    {
        /** @var \App\Models\Activity */
        $record = $this->getRecord();

        return $record->title;
    }

    protected function getActions(): array
    {
        return [];
    }
}
