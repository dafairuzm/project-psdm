<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActivityStatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Semua', Activity::count())
            ->chart([7, 2, 10, 3, 15, 4, 17]),
                
            Stat::make('Kegiatan Exhouse', Activity::where('type', 'exhouse')->count())
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Kegiatan Inhouse', Activity::where('type', 'inhouse')->count())
                ->color('info')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
        ];
    }
} 