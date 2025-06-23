<?php

namespace App\Filament\Resources\UserActivityResource\Pages;

use App\Filament\Imports\UserActivityImporter;
use App\Filament\Resources\UserActivityResource;
use App\Filament\Widgets\ActivityStatsOverview;
use App\Imports\UserActivitiesImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;

class ListUserActivities extends ListRecords
{
    protected static string $resource = UserActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import dari Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    Placeholder::make('template_info')
                        ->disableLabel()
                        ->content(new HtmlString('
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-md">
                                <a href="' . asset('storage/templates/Data Kegiatan Example .xlsx') . '" 
                                download="Data Kegiatan Example .xlsx"
                                class="text-emerald-600 hover:text-grey-800 underline text-md font-medium">
                                    Download Template
                                </a>
                            </div>
    ')),

                    FileUpload::make('file')
                        ->label('File Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv'
                        ])
                        ->maxSize(10240) // 10MB
                        ->required()
                        ->helperText('Upload file Excel (.xlsx, .xls) dengan format yang sesuai'),
                ])
                ->action(function (array $data) {
                    try {
                        // Get the uploaded file path
                        $uploadedFile = $data['file'];
                        $filePath = storage_path('app/public/' . $uploadedFile);

                        // Debug: Log file path
                        \Illuminate\Support\Facades\Log::info('Import file path: ' . $filePath);
                        \Illuminate\Support\Facades\Log::info('File exists: ' . (file_exists($filePath) ? 'Yes' : 'No'));

                        $import = new UserActivitiesImport();

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
                ->modalHeading('Import Data Kegiatan Pegawai')
                ->modalDescription('
            Pastikan format file Excel sesuai dengan template.
            File harus memiliki header di baris pertama. Data yang sudah ada akan dilewati')
                ->modalSubmitActionLabel('Import'),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            ActivityStatsOverview::class,
        ];
    }
    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('Semua Kegiatan'),
            'exhouse' => Tab::make()
                ->label('Kegiatan Exhouse')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereHas('activity', function ($q) {
                        $q->where('type', 'exhouse');
                    });
                }),
            'inhouse' => Tab::make()
                ->label('Kegiatan Inhouse')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereHas('activity', function ($q) {
                        $q->where('type', 'inhouse');
                    });
                }),
        ];
    }
    protected function getTableRecordAction(): ?string
    {
        return 'view';
    }

    public function getSubheading(): ?string
    {

        return "Halaman ini menampilkan kegiatan berdasarkan nama pegawai. Anda dapat membuat laporan melalui halaman ini";
    }
}
