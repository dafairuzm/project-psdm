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
            Stat::make('Kegiatan Exhouse', Activity::where('type', 'exhouse')->count())
                ->description('Total kegiatan exhouse')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Kegiatan Inhouse', Activity::where('type', 'inhouse')->count())
                ->description('Total kegiatan inhouse')
                ->color('info')
                ->chart([3, 5, 3, 4, 5, 6, 3, 7]),
        ];
    }
} 