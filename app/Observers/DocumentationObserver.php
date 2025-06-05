<?php

namespace App\Observers;

use App\Models\Documentation;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class DocumentationObserver
{
    public function created(Documentation $documentation)
    {
        // Ketika dokumentasi baru dibuat
        $this->sendNotification($documentation, 'ditambahkan');
    }

    public function updated(Documentation $documentation)
    {
        // Ketika dokumentasi diupdate
        if ($documentation->wasChanged('file_path')) {
            $this->sendNotification($documentation, 'diperbarui');
        }
    }

    private function sendNotification(Documentation $documentation, string $action)
    {
        if (Auth::check()) {
            $activity = $documentation->activity;
            $fileCount = is_array($documentation->file_path) 
                ? count($documentation->file_path) 
                : 1;

            // Notifikasi untuk user yang upload
            Notification::make()
                ->title('Dokumentasi Berhasil ' . ucfirst($action))
                ->body("Berhasil {$action} {$fileCount} file dokumentasi untuk kegiatan: {$activity->title}")
                ->icon('heroicon-o-document-arrow-up')
                ->success()
                ->sendToDatabase(Auth::user());

            // Kirim notifikasi ke admin menggunakan Spatie Permission
            try {
                // Cek apakah user bukan super_admin
                if (!Auth::user()->hasRole(['Admin'])) {
                    
                    // Ambil semua user dengan role super_admin
                    $admins = \App\Models\User::role('Admin')->get();
                    
                    if ($admins->count() > 0) {
                        $userName = Auth::user()->name;
                        
                        Notification::make()
                            ->title('Dokumentasi Baru dari ' . $userName)
                            ->body("{$userName} menambahkan dokumentasi untuk: {$activity->title}")
                            ->icon('heroicon-o-bell')
                            ->info()
                            ->sendToDatabase($admins);
                    }
                }
            } catch (\Exception $e) {
                // Jika ada error dengan role, skip notifikasi ke admin
                \Log::warning('Gagal mengirim notifikasi ke admin: ' . $e->getMessage());
            }
        }
    }
}