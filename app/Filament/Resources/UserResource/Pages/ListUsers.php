<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Imports\UserImport;
use App\Imports\UsersImport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [        
            Actions\Action::make('import')
                ->label('Import Pegawai')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv'
                        ])
                        ->maxSize(10240) // 10MB
                        ->required()
                        ->helperText('Upload file Excel (.xlsx, .xls) dengan format: Nama, Email, NIP, Golongan, Jabatan Lengkap, Jabatan'),
                ])
                ->action(function (array $data) {
                    try {
                        // Get the uploaded file path
                        $uploadedFile = $data['file'];
                        $filePath = storage_path('app/public/' . $uploadedFile);

                        // Debug: Log file path
                        \Illuminate\Support\Facades\Log::info('Import file path: ' . $filePath);
                        \Illuminate\Support\Facades\Log::info('File exists: ' . (file_exists($filePath) ? 'Yes' : 'No'));

                        $import = new UsersImport();

                        // Import menggunakan Laravel Excel dengan WithHeadingRow
                        Excel::import($import, $filePath);

                        $results = $import->getImportResults();

                        // Hapus file setelah import
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }

                        // Notifikasi sukses
                        Notification::make()
                            ->title('Import Berhasil!')
                            ->body("Total diproses: {$results['total_processed']} | Berhasil: {$results['success_count']} | Dilewati: {$results['skip_count']} | Error: " . count($results['errors']))
                            ->success()
                            ->duration(5000)
                            ->send();

                        // Jika ada error, tampilkan juga
                        if (!empty($results['errors'])) {
                            $errorMessage = "Terdapat " . count($results['errors']) . " error:\n";
                            $errorMessage .= implode("\n", array_slice($results['errors'], 0, 5)); // Tampilkan 5 error pertama
                            if (count($results['errors']) > 5) {
                                $errorMessage .= "\n... dan " . (count($results['errors']) - 5) . " error lainnya";
                            }

                            Notification::make()
                                ->title('Perhatian - Ada Error')
                                ->body($errorMessage)
                                ->warning()
                                ->duration(10000)
                                ->send();
                        }

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Import error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString()
                        ]);

                        Notification::make()
                            ->title('Import Gagal!')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->duration(8000)
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Import Data Pegawai')
                ->modalDescription('
                Pastikan format file Excel sesuai dengan template.
                File harus memiliki header: Nama, Email, NIP, Golongan, Jabatan Lengkap, Jabatan.
                Password otomatis akan menggunakan email, role default user.')
                ->modalSubmitActionLabel('Import'),
                Actions\CreateAction::make()
                ->label('Tambah Pegawai')
                ->icon('heroicon-o-plus'),
        ];
    }
}