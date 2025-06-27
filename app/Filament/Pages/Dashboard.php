<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ActivityResource\Widgets\LatestActivities;
use App\Filament\Resources\JobTitleResource\Widgets\JobTitleActivityChart;
use App\Filament\Resources\UserActivityResource\Widgets\UserActivityChart;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Carbon\Carbon;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;
    public static function canAccess(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if (auth()->user()->hasRole('Pegawai')) {
            return false;
        }

        return true;
    }

    public function getTitle(): string
    {
        return 'Dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public function getWidgets(): array
    {
        return [
            ...Filament::getWidgets(), // Ganti dengan nama widget stats kamu
            UserActivityChart::class,
            JobTitleActivityChart::class,
            LatestActivities::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }

    public function filtersForm(Form $form): Form
{
    return $form
        ->schema([
            Section::make('')
                ->key('filters_section')
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Tanggal Mulai')
                        ->default(now()->subMonths(12))
                        ->maxDate(fn (callable $get) => $get('endDate') ?: now())
                        ->reactive(),
                    
                    DatePicker::make('endDate')
                        ->label('Tanggal Akhir')
                        ->default(now())
                        ->minDate(fn (callable $get) => $get('startDate'))
                        ->maxDate(now())
                        ->reactive(),
                ])
                ->columns(2)
                ->headerActions([
                    Action::make('resetFilters')
                        ->label('Reset ke Default')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->action(function () {
                            $this->filters['startDate'] = now()->subMonths(12)->format('Y-m-d');
                            $this->filters['endDate'] = now()->format('Y-m-d');
                        })
                ])
        ])
        ->statePath('filters');
}

// Method untuk mendapatkan default filters (opsional)
public function getDefaultFilters(): array
{
    return [
        'startDate' => now()->subMonths(6)->format('Y-m-d'),
        'endDate' => now()->format('Y-m-d'),
    ];
}
}