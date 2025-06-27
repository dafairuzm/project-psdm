<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class NoteUploadedNotification extends Notification
{
    use Queueable;

    protected $activity;
    protected $noteContent;

    public function __construct($activity, $noteContent)
    {
        $this->activity = $activity;
        $this->noteContent = $noteContent;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        // Potong note jika terlalu panjang untuk preview
        $notePreview = strlen($this->noteContent) > 100 
            ? substr($this->noteContent, 0, 100) . '...' 
            : $this->noteContent;

        return FilamentNotification::make()
            ->title('Catatan Berhasil Diupload')
            ->body("Berhasil menambahkan catatan untuk kegiatan: {$this->activity->title}. Catatan: {$notePreview}")
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->getDatabaseMessage();
    }
}