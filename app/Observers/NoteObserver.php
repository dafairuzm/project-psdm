<?php

namespace App\Observers;

use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class NoteObserver
{
    public function created(Note $note)
    {
        // Ketika catatan baru dibuat
        $this->sendNotification($note, 'ditambahkan');
    }

    public function updated(Note $note)
    {
        // Ketika catatan diupdate
        if ($note->wasChanged('note')) {
            $this->sendNotification($note, 'diperbarui');
        }
    }

    private function sendNotification(Note $note, string $action)
    {
        if (Auth::check()) {
            $activity = $note->activity;
            
            // Potong note jika terlalu panjang untuk preview
            $notePreview = strlen($note->note) > 80 
                ? substr($note->note, 0, 80) . '...' 
                : $note->note;

            // Notifikasi untuk user yang upload
            Notification::make()
                ->title('Catatan Berhasil ' . ucfirst($action))
                ->body("Berhasil {$action} catatan untuk kegiatan: {$activity->title}. Catatan: {$notePreview}")
                ->icon('heroicon-o-document-text')
                ->success()
                ->sendToDatabase(Auth::user());

            // Kirim notifikasi ke admin menggunakan Spatie Permission
            try {
                // Cek apakah user bukan Admin
                if (!Auth::user()->hasRole(['Admin'])) {
                    
                    // Ambil semua user dengan role Admin
                    $admins = \App\Models\User::role('Admin')->get();
                    
                    if ($admins->count() > 0) {
                        $userName = Auth::user()->name;
                        
                        Notification::make()
                            ->title('Catatan Baru dari ' . $userName)
                            ->body("{$userName} menambahkan catatan untuk: {$activity->title}")
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