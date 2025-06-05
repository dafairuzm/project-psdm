<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class DocumentationUploadedNotification extends Notification
{
    use Queueable;

    protected $riwayatKegiatan;
    protected $fileCount;

    public function __construct($riwayatKegiatan, $fileCount)
    {
        $this->riwayatKegiatan = $riwayatKegiatan;
        $this->fileCount = $fileCount;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return FilamentNotification::make()
            ->title('Dokumentasi Berhasil Diupload')
            ->body("Berhasil mengupload {$this->fileCount} file dokumentasi untuk kegiatan: {$this->riwayatKegiatan->nama_kegiatan}")
            ->icon('heroicon-o-document-arrow-up')
            ->iconColor('success')
            ->getDatabaseMessage();
    }
}
