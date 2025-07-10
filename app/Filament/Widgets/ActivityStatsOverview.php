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
            now()->subMonths(12);
            
        $endDate = ! is_null($this->filters['endDate'] ?? null) ?
            Carbon::parse($this->filters['endDate']) :
            now();

        // Menghitung jumlah periode berdasarkan range tanggal untuk chart
        $diffInDays = $startDate->diffInDays($endDate);
        $periods = max(1, min(12, (int) ceil($diffInDays / 30))); // Maksimal 12 periode

        // Cek apakah user adalah pegawai
        $isPegawai = auth()->user()->hasRole('Pegawai');
        
        // Fungsi untuk mengambil data chart berdasarkan periode yang dipilih
        $getChartData = function($type = null) use ($startDate, $endDate, $periods, $isPegawai) {
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
                    
                    // Kegiatan berdasarkan start_date saja
                    $query = Activity::whereBetween('start_date', [$weekStart, $weekEnd]);
                    
                    // Filter berdasarkan user jika role pegawai melalui tabel pivot
                    if ($isPegawai) {
                        $query->whereHas('users', function($q) {
                            $q->where('users.id', auth()->id());
                        });
                    }
                    
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
                    
                    // Kegiatan berdasarkan start_date saja
                    $query = Activity::whereBetween('start_date', [$monthStart, $monthEnd]);
                    
                    // Filter berdasarkan user jika role pegawai melalui tabel pivot
                    if ($isPegawai) {
                        $query->whereHas('users', function($q) {
                            $q->where('users.id', auth()->id());
                        });
                    }
                    
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
        $exhouseChart = $getChartData('dinas');
        $inhouseChart = $getChartData('mandiri');

        // Menentukan apakah menggunakan periode mingguan atau bulanan
        $isWeeklyPeriod = $periods <= 2;

        // Menghitung total untuk range yang dipilih berdasarkan start_date saja
        $totalActivitiesQuery = Activity::whereBetween('start_date', [$startDate, $endDate]);
        $totalExhouseQuery = Activity::where('type', 'dinas')
            ->whereBetween('start_date', [$startDate, $endDate]);
        $totalInhouseQuery = Activity::where('type', 'mandiri')
            ->whereBetween('start_date', [$startDate, $endDate]);

        // Filter berdasarkan user jika role pegawai melalui tabel pivot
        if ($isPegawai) {
            $totalActivitiesQuery->whereHas('users', function($q) {
                $q->where('users.id', auth()->id());
            });
            $totalExhouseQuery->whereHas('users', function($q) {
                $q->where('users.id', auth()->id());
            });
            $totalInhouseQuery->whereHas('users', function($q) {
                $q->where('users.id', auth()->id());
            });
        }

        $totalActivities = $totalActivitiesQuery->count();
        $totalExhouse = $totalExhouseQuery->count();
        $totalInhouse = $totalInhouseQuery->count();

        // Ubah label jika user adalah pegawai
        $labelPrefix = $isPegawai ? '' : '';

        return [
            Stat::make($labelPrefix . 'Semua Kegiatan', $totalActivities)
                ->chart($allActivitiesChart)
                ->description($this->getChartDescription($allActivitiesChart, $isWeeklyPeriod, $totalActivities))
                ->descriptionIcon($this->getChartTrendIcon($allActivitiesChart))
                ->color($this->getChartTrendColor($allActivitiesChart)),
                
            Stat::make($labelPrefix . 'Kegiatan Dinas/Ditugaskan', $totalExhouse)
                ->chart($exhouseChart)
                ->description($this->getChartDescription($exhouseChart, $isWeeklyPeriod, $totalExhouse))
                ->descriptionIcon($this->getChartTrendIcon($exhouseChart))
                ->color($this->getChartTrendColor($exhouseChart)),
                
            Stat::make($labelPrefix . 'Kegiatan Mandiri', $totalInhouse)
                ->chart($inhouseChart)
                ->description($this->getChartDescription($inhouseChart, $isWeeklyPeriod, $totalInhouse))
                ->descriptionIcon($this->getChartTrendIcon($inhouseChart))
                ->color($this->getChartTrendColor($inhouseChart)),
        ];
    }

    // Fungsi helper untuk menghitung persentase perubahan
    private function getChartDescription(array $chartData, bool $isWeeklyPeriod = false, int $totalActivities = 0): string
    {
        if (count($chartData) < 2) {
            // Jika hanya 1 periode dan tidak ada aktivitas sama sekali
            if ($totalActivities == 0) {
                return 'Belum ada aktivitas';
            }
            return 'Data periode tunggal';
        }
        
        $current = end($chartData);
        $previous = $chartData[count($chartData) - 2];
        
        // Jika kedua periode 0, tapi total ada aktivitas
        if ($previous == 0 && $current == 0) {
            $periodText = $isWeeklyPeriod ? 'minggu' : 'bulan';
            if ($totalActivities > 0) {
                return "Tidak ada aktivitas di 2 {$periodText} terakhir";
            } else {
                return "Tidak ada aktivitas di 2 {$periodText} terakhir";
            }
        }
        
        // Jika periode sebelumnya 0 tapi periode ini ada
        if ($previous == 0 && $current > 0) {
            $periodText = $isWeeklyPeriod ? 'minggu lalu' : 'bulan lalu';
            return "Meningkat dari 0 di {$periodText}";
        }
        
        // Jika periode sebelumnya ada tapi periode ini 0
        if ($previous > 0 && $current == 0) {
            $periodText = $isWeeklyPeriod ? 'minggu lalu' : 'bulan lalu';
            return "Menurun dari {$previous} di {$periodText}";
        }
        
        // Kalkulasi persentase normal
        $percentChange = round((($current - $previous) / $previous) * 100, 1);
        $periodText = $isWeeklyPeriod ? 'minggu lalu' : 'bulan lalu';
        
        if ($percentChange > 0) {
            return "{$percentChange}% peningkatan dari {$periodText}";
        } elseif ($percentChange < 0) {
            return abs($percentChange) . "% penurunan dari {$periodText}";
        } else {
            return "Tidak ada perubahan dari {$periodText}";
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