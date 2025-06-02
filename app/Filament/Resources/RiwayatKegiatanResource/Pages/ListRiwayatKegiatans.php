<?php

namespace App\Filament\Resources\RiwayatKegiatanResource\Pages;

use App\Filament\Resources\RiwayatKegiatanResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;

class ListRiwayatKegiatans extends ListRecords
{
    protected static string $resource = RiwayatKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tidak ada create action karena data kegiatan dikelola oleh admin
        ];
    }
    
    public function getTitle(): string
    {
        return 'Riwayat Kegiatan Saya';
    }
    
    public function getHeading(): string
    {
        return 'Riwayat Kegiatan Saya';
    }
    
    public function getSubheading(): ?string
    {
        $userName = Auth::user()->name;
        $totalActivities = Auth::user()->activities()->count();
        
        return "Menampilkan seluruh kegiatan yang pernah diikuti oleh {$userName}. Total: {$totalActivities} kegiatan.";
    }
}