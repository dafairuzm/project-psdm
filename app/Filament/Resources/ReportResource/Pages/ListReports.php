<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Filament\Resources\UserActivityResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
{
    return [
        Action::make('toUserActivity')
            ->label('Buat Laporan')
            ->icon('heroicon-o-document-text') // optional
            ->color('primary')
            ->url(fn () => UserActivityResource::getUrl())
            ->openUrlInNewTab(false), // true kalau mau buka tab baru
    ];
}
}
