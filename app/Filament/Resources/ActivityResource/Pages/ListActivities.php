<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Filament\Widgets\ActivityStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kegiatan')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActivityStatsOverview::class,
        ];
    }
    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('Semua'),
            'exhouse' => Tab::make()
                ->label('Exhouse')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'exhouse')),
            'inhouse' => Tab::make()
                ->label('Inhouse')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'inhouse')),
        ];
    }

}
