<?php

namespace App\Filament\Resources\ProfessionResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Profession;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfessionActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Kegiatan per Profesi';

    // protected int | string | array $columnSpan = 'full';
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'md' => 1,
        'xl' => 1,
    ];
    protected static ?string $height = '450px';
    
    protected static ?int $sort = 2;
    
    public ?string $filter = 'all';
    
    // Store data for description
    protected $chartData = null;
    
    protected function getFilters(): ?array
    {
        return [
            'all' => 'Semua Waktu',
            'today' => 'Hari ini',
            'yesterday' => 'Kemarin',
            'this_week' => 'Minggu Ini',
            'last_week' => 'Minggu Terakhir',
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Terakhir',
            'this_quarter' => 'Kuartal Ini',
            'this_year' => 'Tahun Ini',
            'last_30_days' => '30 Hari Terakhir',
            'last_90_days' => '90 Hari Terakhir',
        ];
    }
    
    protected function getData(): array
    {
        $activeFilter = $this->filter ?? 'all';
        
        // Base query untuk menghitung activity per job title
        $query = Profession::select('professions.name')
            ->leftJoin('users', 'users.profession_id', '=', 'professions.id')
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
        $data = $query->groupBy('professions.id', 'professions.name')
            ->select('professions.name', DB::raw('COUNT(user_activity.id) as activity_count'))
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
        
        // Create labels dengan auto truncation untuk nama panjang
        $labelsWithData = $data->map(function ($item) use ($total) {
            $percentage = $total > 0 ? round(($item->activity_count / $total) * 100, 1) : 0;
            
            // Truncate nama unit jika terlalu panjang untuk layout 2 kolom
            $unitName = $item->name;
            if (strlen($unitName) > 15) {
                $unitName = substr($unitName, 0, 12) . '...';
            }
            
            return "{$unitName} ({$percentage}%)";
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
    
    // public function getDescription(): ?string
    // {
    //     if (!$this->chartData || $this->chartData->isEmpty()) {
    //         return 'No data available for the selected period.';
    //     }
        
    //     $total = $this->chartData->sum('activity_count');
    //     return "Total Kegiatan: {$total}";
    // }
    
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
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 12,
                        'boxWidth' => 10,
                        'boxHeight' => 10,
                        'font' => [
                            'size' => 12,
                        ],
                        'textAlign' => 'left',
                        'maxWidth' => 120,
                    ]
                ],
                'tooltip' => [
                    'callbacks' => [
                        'title' => 'function(context) {
                            // Show full unit name in tooltip
                            const originalData = ' . json_encode($this->chartData?->mapWithKeys(function($item) { 
                                return [$item->name => $item->activity_count]; 
                            })->toArray() ?? []) . ';
                            
                            const labelText = context[0].label;
                            const shortName = labelText.split(" (")[0].replace("...", "");
                            
                            // Find full name from original data
                            for (const [fullName, count] of Object.entries(originalData)) {
                                if (fullName.toLowerCase().includes(shortName.toLowerCase()) || 
                                    shortName.toLowerCase().includes(fullName.toLowerCase().substring(0, 10))) {
                                    return fullName;
                                }
                            }
                            return shortName;
                        }',
                        'label' => 'function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed * 100) / total).toFixed(1);
                            return context.parsed + " kegiatan (" + percentage + "%)";
                        }'
                    ]
                ],
                // Plugin untuk data labels pada slice - disable untuk layout kecil
                'datalabels' => [
                    'display' => 'function(context) {
                        // Hanya tampilkan untuk slice besar (>= 10%) di layout 2 kolom
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = (context.parsed / total) * 100;
                        return percentage >= 10;
                    }',
                    'color' => 'white',
                    'font' => [
                        'weight' => 'bold',
                        'size' => 9
                    ],
                    'formatter' => 'function(value, context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(0);
                        return percentage + "%";
                    }',
                    'textAlign' => 'center',
                    'anchor' => 'center',
                    'align' => 'center',
                ]
            ],
            'cutout' => '50%', // Kurangi cutout untuk lebih banyak space
            'layout' => [
                'padding' => [
                    'left' => 10,
                    'right' => 10,
                    'top' => 10,
                    'bottom' => 20 // Space untuk legend di bottom
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