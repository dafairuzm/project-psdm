<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Services\DocumentGeneratorService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Response;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    protected static ?string $title = 'Lihat Detail';
    public function getTitle(): string|Htmlable
    {
        /** @var \App\Models\Activity */
        $record = $this->getRecord();

        return $record->title;
    }

    protected function getActions(): array
    {
        return [];
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('generateSuratTugas')
                ->label('Generate Surat Tugas')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->action(function () {
                    $this->redirect(route('download.surat-tugas', $this->record->id));
                })
                ->requiresConfirmation()
                ->modalHeading('Generate Surat Perintah Tugas')
                ->modalDescription('Apakah Anda yakin ingin membuat surat perintah tugas untuk aktivitas ini?')
                ->modalSubmitActionLabel('Ya, Generate')
                ->visible(fn() => $this->record->users()->exists()),

        ];
    }
    protected function generateSuratTugas()
    {
        try {
            $documentService = new DocumentGeneratorService();
            $result = $documentService->generateSuratTugas($this->record);

            if ($result['success']) {
                // Send file as download response
                return Response::download($result['file_path'], $result['filename'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])->deleteFileAfterSend(true);

            } else {
                Notification::make()
                    ->title('Gagal membuat surat tugas')
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
