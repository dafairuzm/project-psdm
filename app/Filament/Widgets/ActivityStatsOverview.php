<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class ActivityStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;
    
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        // Mendapatkan filter dari dashboard
        $startDate = ! is_null($this->filters['startDate'] ?? null) ?
            Carbon::parse($this->filters['startDate']) :
            now()->subMonths(6);
            
        $endDate = ! is_null($this->filters['endDate'] ?? null) ?
            Carbon::parse($this->filters['endDate']) :
            now();

        // Menghitung jumlah periode berdasarkan range tanggal untuk chart
        $diffInDays = $startDate->diffInDays($endDate);
        $periods = max(1, min(12, (int) ceil($diffInDays / 30))); // Maksimal 12 periode

        // Fungsi untuk mengambil data chart berdasarkan periode yang dipilih
        $getChartData = function($type = null) use ($startDate, $endDate, $periods) {
            $data = [];
            
            // Jika periode kurang dari 2 bulan, gunakan data mingguan
            if ($periods <= 2) {
                $weeks = max(1, (int) ceil($startDate->diffInWeeks($endDate)));
                for ($i = $weeks - 1; $i >= 0; $i--) {
                    $weekStart = $startDate->copy()->addWeeks($i);
                    $weekEnd = $startDate->copy()->addWeeks($i + 1)->subDay();
                    
                    if ($weekEnd->gt($endDate)) {
                        $weekEnd = $endDate->copy();
                    }
                    
                    $query = Activity::whereBetween('created_at', [$weekStart, $weekEnd]);
                    if ($type) {
                        $query->where('type', $type);
                    }
                    $data[] = $query->count();
                }
            } else {
                // Gunakan data bulanan
                for ($i = $periods - 1; $i >= 0; $i--) {
                    $monthStart = $startDate->copy()->addMonths($i)->startOfMonth();
                    $monthEnd = $startDate->copy()->addMonths($i)->endOfMonth();
                    
                    // Pastikan tidak keluar dari range
                    if ($monthStart->lt($startDate)) {
                        $monthStart = $startDate->copy();
                    }
                    if ($monthEnd->gt($endDate)) {
                        $monthEnd = $endDate->copy();
                    }
                    
                    $query = Activity::whereBetween('created_at', [$monthStart, $monthEnd]);
                    if ($type) {
                        $query->where('type', $type);
                    }
                    $data[] = $query->count();
                }
            }
            
            return $data;
        };

        // Mengambil data chart
        $allActivitiesChart = $getChartData();
        $exhouseChart = $getChartData('exhouse');
        $inhouseChart = $getChartData('inhouse');

        // Menghitung total untuk range yang dipilih
        $totalActivities = Activity::whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $totalExhouse = Activity::where('type', 'exhouse')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $totalInhouse = Activity::where('type', 'inhouse')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return [
            Stat::make('Semua Kegiatan', $totalActivities)
                ->chart($allActivitiesChart)
                ->description($this->getChartDescription($allActivitiesChart))
                ->descriptionIcon($this->getChartTrendIcon($allActivitiesChart))
                ->color($this->getChartTrendColor($allActivitiesChart)),
                
            Stat::make('Kegiatan Exhouse', $totalExhouse)
                ->chart($exhouseChart)
                ->description($this->getChartDescription($exhouseChart))
                ->descriptionIcon($this->getChartTrendIcon($exhouseChart))
                ->color($this->getChartTrendColor($exhouseChart)),
                
            Stat::make('Kegiatan Inhouse', $totalInhouse)
                ->chart($inhouseChart)
                ->description($this->getChartDescription($inhouseChart))
                ->descriptionIcon($this->getChartTrendIcon($inhouseChart))
                ->color($this->getChartTrendColor($inhouseChart)),
        ];
    }

    // Fungsi helper untuk menghitung persentase perubahan
    private function getChartDescription(array $chartData): string
    {
        if (count($chartData) < 2) {
            return 'Data tidak cukup';
        }
        
        $current = end($chartData);
        $previous = $chartData[count($chartData) - 2];
        
        if ($previous == 0 && $current == 0) {
            return 'Tidak ada aktivitas';
        }
        
        if ($previous == 0) {
            return 'Periode pertama';
        }
        
        $percentChange = round((($current - $previous) / $previous) * 100, 1);
        
        if ($percentChange > 0) {
            return "{$percentChange}% peningkatan";
        } elseif ($percentChange < 0) {
            return abs($percentChange) . "% penurunan";
        } else {
            return "Tidak ada perubahan";
        }
    }

    // Fungsi helper untuk menentukan icon trend
    private function getChartTrendIcon(array $chartData): string
    {
        if (count($chartData) < 2) {
            return 'heroicon-m-calendar-days';
        }
        
        $current = end($chartData);
        $previous = $chartData[count($chartData) - 2];
        
        if ($current > $previous) {
            return 'heroicon-m-arrow-trending-up';
        } elseif ($current < $previous) {
            return 'heroicon-m-arrow-trending-down';
        } else {
            return 'heroicon-m-minus';
        }
    }

    // Fungsi helper untuk menentukan warna berdasarkan trend
    private function getChartTrendColor(array $chartData): string
    {
        if (count($chartData) < 2) {
            return 'gray';
        }
        
        $current = end($chartData);
        $previous = $chartData[count($chartData) - 2];
        
        if ($current > $previous) {
            return 'success';
        } elseif ($current < $previous) {
            return 'danger';
        } else {
            return 'gray';
        }
    }
}