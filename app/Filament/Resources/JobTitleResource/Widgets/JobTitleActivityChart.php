<?php

namespace App\Filament\Resources\JobTitleResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\JobTitle;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JobTitleActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Total Kegiatan per Jabatan';

    // protected int | string | array $columnSpan = 'full';
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];
    protected static ?string $height = '400px';
    
    protected static ?int $sort = 2;
    
    public ?string $filter = 'all';
    
    // Store data for description
    protected $chartData = null;
    
    protected function getFilters(): ?array
    {
        return [
            'all' => 'All Time',
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'this_year' => 'This Year',
            'last_30_days' => 'Last 30 Days',
            'last_90_days' => 'Last 90 Days',
        ];
    }
    
    protected function getData(): array
    {
        $activeFilter = $this->filter ?? 'all';
        
        // Base query untuk menghitung activity per job title
        $query = JobTitle::select('job_titles.name')
            ->leftJoin('users', 'users.job_title_id', '=', 'job_titles.id')
            ->leftJoin('user_activity', 'user_activity.user_id', '=', 'users.id')
            ->leftJoin('activities', 'activities.id', '=', 'user_activity.activity_id');
        
        // Apply date filter berdasarkan timestamp activity
        if ($activeFilter !== 'all') {
            $dateRange = $this->getDateRange($activeFilter);
            if ($dateRange) {
                $query->whereBetween('activities.created_at', $dateRange);
            }
        }
        
        // Group by job title dan hitung jumlah activity
        $data = $query->groupBy('job_titles.id', 'job_titles.name')
            ->select('job_titles.name', DB::raw('COUNT(user_activity.id) as activity_count'))
            ->having('activity_count', '>', 0)
            ->orderBy('activity_count', 'desc')
            ->get();
        
        // Store data for description
        $this->chartData = $data;
        
        // Jika tidak ada data
        if ($data->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#E5E7EB'],
                        'borderWidth' => 0,
                    ],
                ],
                'labels' => ['No Data Available'],
            ];
        }
        
        // Color palette untuk chart
        $colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
            '#9966FF', '#FF9F40', '#EF4444', '#10B981',
            '#F59E0B', '#8B5CF6', '#06B6D4', '#84CC16'
        ];
        
        // Calculate total for percentage
        $total = $data->sum('activity_count');
        
        // Create labels with percentage and count
        $labelsWithData = $data->map(function ($item) use ($total) {
            $percentage = $total > 0 ? round(($item->activity_count / $total) * 100, 1) : 0;
            return "{$item->name} ({$percentage}% - {$item->activity_count})";
        })->toArray();
        
        return [
            'datasets' => [
                [
                    'data' => $data->pluck('activity_count')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $labelsWithData,
        ];
    }
    
    public function getDescription(): ?string
    {
        if (!$this->chartData || $this->chartData->isEmpty()) {
            return 'No data available for the selected period.';
        }
        
        $total = $this->chartData->sum('activity_count');
        return "Total Kegiatan: {$total}";
    }
    
    protected function getDateRange(string $filter): ?array
    {
        $now = Carbon::now();
        
        return match ($filter) {
            'today' => [$now->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay()
            ],
            'this_week' => [$now->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek()
            ],
            'this_month' => [$now->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth()
            ],
            'this_quarter' => [$now->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year' => [$now->startOfYear(), $now->copy()->endOfYear()],
            'last_30_days' => [$now->copy()->subDays(30), $now],
            'last_90_days' => [$now->copy()->subDays(90), $now],
            default => null,
        };
    }
    
    protected function getType(): string
    {
        return 'doughnut';
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
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ]
                    ]
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed * 100) / total).toFixed(1);
                            const originalLabel = context.label.split(" (")[0];
                            return originalLabel + ": " + context.parsed + " activities (" + percentage + "%)";
                        }'
                    ]
                ],
                // Plugin untuk data labels pada slice
                'datalabels' => [
                    'display' => 'function(context) {
                        // Hanya tampilkan label jika persentase >= 5% untuk menghindari tumpang tindih
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = (context.parsed / total) * 100;
                        return percentage >= 5;
                    }',
                    'color' => 'white',
                    'font' => [
                        'weight' => 'bold',
                        'size' => 11
                    ],
                    'formatter' => 'function(value, context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return percentage + "%\\n(" + value + ")";
                    }',
                    'textAlign' => 'center',
                    'anchor' => 'center', // Posisi di tengah slice
                    'align' => 'center',
                    'offset' => 0,
                    'borderRadius' => 4,
                    'backgroundColor' => 'function(context) {
                        // Background semi-transparan untuk readability
                        return "rgba(0, 0, 0, 0.7)";
                    }',
                    'borderColor' => 'white',
                    'borderWidth' => 1,
                    'padding' => 4
                ]
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '60%',
            'layout' => [
                'padding' => [
                    'left' => 20,
                    'right' => 20,
                    'top' => 20,
                    'bottom' => 20
                ]
            ]
        ];
    }
    
    // Method untuk register plugin yang diperlukan
    protected function getExtraJsConfig(): ?string
    {
        return '
        // Import Chart.js datalabels plugin
        const script = document.createElement("script");
        script.src = "https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js";
        script.onload = function() {
            if (typeof Chart !== "undefined") {
                Chart.register(ChartDataLabels);
            }
        };
        document.head.appendChild(script);
        ';
    }
}