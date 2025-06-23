<?php

namespace App\Filament\Resources\UserActivityResource\Widgets;

use App\Models\Activity;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Components\Select;
use Illuminate\Support\Carbon;

class UserActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Kegiatan';

    // protected int | string | array $columnSpan = 'full';
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];
    protected static ?string $height = '400px';

    public ?string $filter = null;

    protected function getData(): array
    {
        $year = $this->filter ?? Carbon::now()->year;
        
        // Ambil data kegiatan berdasarkan start_date dan tahun yang dipilih
        $activities = Activity::whereYear('start_date', $year)
            ->selectRaw('MONTH(start_date) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        // Buat array untuk 12 bulan (Jan-Des)
        $monthlyData = [];
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[] = $activities[$month] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Kegiatan',
                    'data' => $monthlyData,
                    'borderColor' => 'rgb(59, 130, 246)', // Primary color (blue)
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointBorderColor' => 'rgb(59, 130, 246)',
                    'pointHoverBackgroundColor' => 'rgb(37, 99, 235)',
                    'pointHoverBorderColor' => 'rgb(37, 99, 235)',
                ],
            ],
            'labels' => array_values($monthNames),
        ];
    }

    protected function getFilters(): ?array
    {
        // Ambil tahun-tahun yang tersedia dari data kegiatan
        $years = Activity::selectRaw('YEAR(start_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->mapWithKeys(fn($year) => [$year => $year])
            ->toArray();

        // Jika tidak ada data, tambahkan tahun sekarang
        if (empty($years)) {
            $years[Carbon::now()->year] = Carbon::now()->year;
        }

        return $years;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
        'aspectRatio' => 1, // Sesuaikan ratio yang diinginkan
        'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Kegiatan',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Bulan',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}