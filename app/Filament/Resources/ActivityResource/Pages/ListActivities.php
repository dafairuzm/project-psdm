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
            Actions\CreateAction::make(),
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
                    ->label('Semua Kegiatan')
                    ->icon('heroicon-o-list-bullet'),
                'exhouse' => Tab::make()
                    ->label('Kegiatan Exhouse')
                    ->icon('heroicon-o-building-office')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'exhouse')),
                'inhouse' => Tab::make()
                    ->label('Kegiatan Inhouse')
                    ->icon('heroicon-o-home')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'inhouse')),
    ];
}

}
