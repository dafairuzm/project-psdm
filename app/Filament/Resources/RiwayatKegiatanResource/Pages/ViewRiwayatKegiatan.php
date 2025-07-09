<?php

namespace App\Filament\Resources\RiwayatKegiatanResource\Pages;

use App\Filament\Resources\RiwayatKegiatanResource;
use App\Models\Documentation;
use App\Models\Note;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;

class ViewRiwayatKegiatan extends ViewRecord
{
    protected static string $resource = RiwayatKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_certificate')
                ->label('Tambah Sertifikat')
                ->icon('heroicon-o-document-arrow-up')
                ->color('primary')
                ->visible(fn () => auth()->user()->can('create_certificate'))
                ->form([
                    Forms\Components\FileUpload::make('certificate')
                        ->label('File Sertifikat')
                        ->directory('certificates')
                        ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'])
                        ->maxSize(5120) // 5MB = 5120 KB
                        ->required()
                        ->helperText('Unggah file JPG, PNG, atau PDF. Maks 5 MB.')
                        ->preserveFilenames(), // Preserve original filename
                ])
                ->action(function (array $data): void {
                    // Ambil file yang diupload
                    $uploadedFile = $data['certificate'];
                    
                    // Tentukan nama asli file
                    $originalName = '';
                    if ($uploadedFile instanceof UploadedFile) {
                        $originalName = $uploadedFile->getClientOriginalName();
                    } elseif (is_string($uploadedFile)) {
                        // Jika sudah berupa string path, ambil nama file dari path
                        $originalName = basename($uploadedFile);
                    }

                    \App\Models\Certificate::create([
                        'activity_id' => $this->record->activity_id,
                        'user_id' => Auth::id(),
                        'name' => $uploadedFile, // Path file di storage
                        'original_name' => $originalName, // Nama asli file
                    ]);

                    Notification::make()
                        ->title('Berhasil')
                        ->body('Sertifikat berhasil ditambahkan.')
                        ->success()
                        ->send();
                })
                ->modalHeading('Upload Sertifikat'),

            Action::make('add_documentation')
                ->label('Tambah Dokumentasi')
                ->icon('heroicon-o-camera')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('documentation')
                            ->label('File Dokumentasi')
                            ->directory('documentations')
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(5120) 
                            ->multiple()
                            ->required()
                            ->helperText('Unggah file gambar dengan format JPG, JPEG, atau PNG. Maksimal ukuran file 5 MB.'),
                ])
                ->action(function (array $data): void {
                    foreach ($data['documentation'] as $file) {
                        Documentation::create([
                            'activity_id' => $this->record->activity_id,
                            'documentation' => $file,
                            'user_id' => Auth::id(),
                        ]);
                    }
                })
                ->successNotificationTitle('Dokumentasi berhasil ditambahkan')
                ->modalHeading('Tambah Dokumentasi Kegiatan'),

            Action::make('add_note')
                ->label('Tambah Catatan')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->form([
                    Forms\Components\Textarea::make('note')
                        ->label('Catatan')
                        ->required()
                        ->rows(5)
                        ->helperText('Tambahkan catatan atau refleksi mengenai kegiatan ini'),
                ])
                ->action(function (array $data): void {
                    Note::create([
                        'activity_id' => $this->record->activity_id,
                        'note' => $data['note'],
                        'user_id' => Auth::id(),
                    ]);
                })
                ->successNotificationTitle('Catatan berhasil ditambahkan')
                ->modalHeading('Tambah Catatan Kegiatan'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Kegiatan')
                    ->description('Detail lengkap kegiatan yang diikuti')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('activity.title')
                                    ->label('Judul Kegiatan')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->columnSpanFull(),

                                TextEntry::make('activity.type')
                                    ->label('Jenis Kegiatan')
                                    ->badge()
                                    ->colors([
                                        'primary' => 'inhouse',
                                        'warning' => 'exhouse',
                                    ]),

                                TextEntry::make('activity.categories.name')
                                    ->label('Kategori')
                                    ->badge()
                                    ->separator(', ')
                                    ->placeholder('not set'),

                                TextEntry::make('activity.speaker')
                                    ->label('Pemateri/Narasumber')
                                    ->placeholder('Tidak ada pemateri'),

                                TextEntry::make('activity.organizer')
                                    ->label('Penyelenggara')
                                    ->placeholder('Tidak disebutkan'),

                                TextEntry::make('activity.location')
                                    ->label('Lokasi')
                                    ->placeholder('Tidak disebutkan'),

                                TextEntry::make('activity.start_date')
                                    ->label('Tanggal Mulai')
                                    ->date('d F Y'),

                                TextEntry::make('activity.finish_date')
                                    ->label('Tanggal Selesai')
                                    ->date('d F Y'),

                                TextEntry::make('activity.duration')
                                    ->label('Durasi')
                                    ->suffix(' JPL'),
                            ])
                    ]),

                Section::make('Sertifikat Saya')
                    ->description('Sertifikat yang telah saya upload untuk kegiatan ini')
                    ->headerActions([
                        InfolistAction::make('delete_certificates')
                            ->label('Hapus Sertifikat')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->form([
                                Forms\Components\CheckboxList::make('selected_certs')
                                    ->label('Pilih sertifikat yang ingin dihapus')
                                    ->options(function () {
                                        return \App\Models\Certificate::where('activity_id', $this->record->activity_id)
                                            ->where('user_id', Auth::id())
                                            ->get()
                                            ->mapWithKeys(function ($cert) {
                                                // Prioritas untuk menampilkan nama file
                                                $displayName = $cert->original_name ?? basename($cert->name);
                                                $date = $cert->created_at->format('d M Y, H:i');
                                                return [$cert->id => "{$displayName} ({$date})"];
                                            });
                                    })
                                    ->required()
                                    ->columns(1)
                                    ->helperText('Pilih satu atau lebih sertifikat yang ingin dihapus'),
                            ])
                            ->action(function (array $data): void {
                                $certs = \App\Models\Certificate::whereIn('id', $data['selected_certs'])
                                    ->where('user_id', Auth::id())
                                    ->get();

                                foreach ($certs as $cert) {
                                    if (Storage::exists($cert->name)) {
                                        Storage::delete($cert->name);
                                    }
                                    $cert->delete();
                                }
                            })
                            ->successNotificationTitle('Sertifikat berhasil dihapus')
                            ->modalHeading('Hapus Sertifikat')
                            ->requiresConfirmation()
                            ->modalDescription('Apakah Anda yakin ingin menghapus sertifikat yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                            ->visible(fn () => \App\Models\Certificate::where('activity_id', $this->record->activity_id)
                                ->where('user_id', Auth::id())
                                ->exists()),
                    ])
                    ->schema([
                        // Di bagian TextEntry untuk certificate_grid, ganti dengan kode ini:
// Di bagian TextEntry untuk certificate_grid, ganti dengan kode ini:

TextEntry::make('certificate_grid')
    ->label('')
    ->state(function () {
        $certs = \App\Models\Certificate::where('activity_id', $this->record->activity_id)
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        if ($certs->isEmpty()) {
            return '<div style="text-align: center; padding: 32px; color: #6b7280; background-color: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db;">
                <svg style="width: 48px; height: 48px; margin: 0 auto 16px; color: #9ca3af;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                </svg>
                <p style="margin: 0; font-size: 16px;">Belum ada sertifikat yang diupload</p>
                <p style="margin: 4px 0 0; font-size: 14px; color: #9ca3af;">Gunakan tombol "Tambah Sertifikat" untuk mengunggah</p>
            </div>';
        }

        $html = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">';

        foreach ($certs as $cert) {
            // Prioritas untuk menampilkan nama file yang benar
            $displayName = $cert->original_name ?? basename($cert->name);
            
            // Pastikan path file benar - hapus 'certificates/' jika sudah ada di $cert->name
            $filePath = $cert->name;
            if (!str_starts_with($filePath, 'certificates/')) {
                $filePath = 'certificates/' . $filePath;
            }
            
            $url = asset('storage/' . $filePath);
            $date = $cert->created_at->format('d M Y, H:i');
            $extension = strtolower(pathinfo($displayName, PATHINFO_EXTENSION));
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp']);
            
            // Debug URL (hapus setelah berhasil)
            // dd($cert->name, $filePath, $url);

            if ($isImage) {
    $preview = "<img src='{$url}' alt='Preview Sertifikat' style='width: 100%; height: 180px; object-fit: cover; border-radius: 8px 8px 0 0;' onerror='this.style.display=\"none\"; this.nextElementSibling.style.display=\"flex\";'>
        <div style='width: 100%; height: 180px; background: linear-gradient(135deg, #6b7280, #4b5563); border-radius: 8px 8px 0 0; display: none; align-items: center; justify-content: center; color: white; font-weight: bold;'>
            <div style='text-align: center;'>
                <svg style='width: 48px; height: 48px; margin-bottom: 8px;' fill='currentColor' viewBox='0 0 20 20'>
                    <path fill-rule='evenodd' d='M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z' clip-rule='evenodd'></path>
                </svg>
                <div>IMAGE</div>
            </div>
        </div>";
} else {
    $preview = '<a href="' . $url . '" target="_blank" style="text-decoration: none;">
        <div style="width: 100%; height: 180px; background-color: #f8f9fa; border-radius: 8px 8px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #6b7280; border: 1px solid #e5e7eb; cursor: pointer; transition: background-color 0.2s;">
            <img src="' . asset('storage/images/icon-pdf.svg') . '" alt="PDF Icon" style="width: 80px; height: 80px; margin-bottom: 8px;">
        </div>
    </a>';
    $fileType = 'PDF';
}

            $html .= "<div style='border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.06);'>
                {$preview}
                <div style='padding: 12px;'>
                    <a href='{$url}' target='_blank' style='font-weight: 600; font-size: 14px; color: #1f2937; text-decoration: none;' title='{$displayName}'>{$displayName}</a>
                    <div style='color: #6b7280; font-size: 12px; margin-top: 4px;'>Diupload pada {$date}</div>
                </div>
            </div>";
        }

        $html .= '</div>';
        return $html;
    })
    ->html()
    ->columnSpanFull(),
                    ])
                    ->visible(fn () => \App\Models\Certificate::where('activity_id', $this->record->activity_id)
                        ->where('user_id', Auth::id())
                        ->exists())
                    ->collapsible(),

                Section::make('Dokumentasi Saya')
                    ->description('Dokumentasi yang telah saya upload untuk kegiatan ini')
                    ->headerActions([
                        InfolistAction::make('delete_documentation')
                            ->label('Hapus Dokumentasi')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->form([
                                Forms\Components\CheckboxList::make('selected_docs')
                                    ->label('Pilih dokumentasi yang ingin dihapus')
                                    ->options(function () {
                                        return Documentation::where('activity_id', $this->record->activity_id)
                                            ->where('user_id', Auth::id())
                                            ->get()
                                            ->mapWithKeys(function ($doc) {
                                                $fileName = basename($doc->documentation);
                                                $date = $doc->created_at->format('d M Y, H:i');
                                                $extension = strtolower(pathinfo($doc->documentation, PATHINFO_EXTENSION));
                                                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                $type = $isImage ? 'Gambar' : 'Dokumen';
                                                
                                                return [$doc->id => "{$fileName} ({$type} - {$date})"];
                                            });
                                    })
                                    ->required()
                                    ->columns(1)
                                    ->helperText('Pilih satu atau lebih dokumentasi yang ingin dihapus'),
                            ])
                            ->action(function (array $data): void {
                                $docs = Documentation::whereIn('id', $data['selected_docs'])
                                    ->where('user_id', Auth::id())
                                    ->get();
                                
                                foreach ($docs as $doc) {
                                    // Hapus file dari storage
                                    if (Storage::exists($doc->documentation)) {
                                        Storage::delete($doc->documentation);
                                    }
                                    // Hapus record dari database
                                    $doc->delete();
                                }
                            })
                            ->successNotificationTitle('Dokumentasi berhasil dihapus')
                            ->modalHeading('Hapus Dokumentasi')
                            ->requiresConfirmation()
                            ->modalDescription('Apakah Anda yakin ingin menghapus dokumentasi yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                            ->visible(fn() => Documentation::where('activity_id', $this->record->activity_id)
                                ->where('user_id', Auth::id())
                                ->exists()),
                    ])  
                    ->schema([
                        TextEntry::make('documentation_responsive_grid')
                            ->label('')
                            ->state(function () {
                                $docs = Documentation::where('activity_id', $this->record->activity_id)
                                    ->where('user_id', Auth::id())
                                    ->latest()
                                    ->get();

                                if ($docs->isEmpty()) {
                                    return '<div style="text-align: center; padding: 32px; color: #6b7280; background-color: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db;">
                                        <svg style="width: 48px; height: 48px; margin: 0 auto 16px; color: #9ca3af;" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <p style="margin: 0; font-size: 16px;">Belum ada dokumentasi yang diupload</p>
                                        <p style="margin: 4px 0 0; font-size: 14px; color: #9ca3af;">Gunakan tombol "Tambah Dokumentasi" untuk menambah file</p>
                                    </div>';
                                }

                                $formattedDocs = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 12px;">';

                                foreach ($docs as $doc) {
                                    $fileName = basename($doc->documentation);
                                    $shortFileName = strlen($fileName) > 25 ? substr($fileName, 0, 22) . '...' : $fileName;
                                    $url = asset('storage/' . $doc->documentation);
                                    $date = $doc->created_at->format('d M Y, H:i');
                                    $extension = strtolower(pathinfo($doc->documentation, PATHINFO_EXTENSION));

                                    // Preview section untuk gambar saja
                                    $fileType = '';
                                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

                                    if ($isImage) {
                                        $fileType = 'Gambar';
                                        $preview = '<div style="width: 100%; height: 200px; overflow: hidden; border-radius: 8px 8px 0 0; background-color: #f9fafb; position: relative;">
                                            <img src="' . $url . '" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>';
                                    } else {
                                        $fileType = 'Dokumen';
                                        $preview = '<div style="width: 100%; height: 200px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 8px 8px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; position: relative;">
                                            <svg style="width: 64px; height: 64px; margin-bottom: 8px;" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span style="font-size: 12px; font-weight: 500; opacity: 0.9;">DOC</span>
                                        </div>';
                                    }

                                    $formattedDocs .= '<div style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.06); transition: all 0.3s ease;" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 8px 25px rgba(0,0,0,0.12)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 4px rgba(0,0,0,0.06)\'">
                                        
                                        <!-- Preview -->
                                        ' . $preview . '
                                        
                                        <!-- File Info -->
                                        <div style="padding: 16px;">
                                            <div style="margin-bottom: 12px;">
                                                <a href="' . $url . '" target="_blank" style="color: #1f2937; font-weight: 600; font-size: 14px; line-height: 1.4; text-decoration: none; transition: color 0.2s ease;" onmouseover="this.style.color=\'#2563eb\'" onmouseout="this.style.color=\'#1f2937\'" title="' . $fileName . '">' . $shortFileName . '</a>
                                                <div style="color: #6b7280; font-size: 12px; font-weight: 500; margin-top: 4px;">' . $fileType . '</div>
                                            </div>
                                            <div style="color: #6b7280; font-size: 11px; display: flex; align-items: center; padding-top: 12px; border-top: 1px solid #f3f4f6;">
                                                <svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                </svg>
                                                ' . $date . '
                                            </div>
                                        </div>
                                    </div>';
                                }

                                $formattedDocs .= '</div>';

                                return $formattedDocs;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn() => Documentation::where('activity_id', $this->record->activity_id)
                        ->where('user_id', Auth::id())
                        ->exists())
                    ->collapsible(),

                Section::make('Catatan Saya')
                    ->description('Catatan dan refleksi yang telah saya buat untuk kegiatan ini')
                    ->headerActions([
                        InfolistAction::make('delete_notes')
                            ->label('Hapus Catatan')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->form([
                                Forms\Components\CheckboxList::make('selected_notes')
                                    ->label('Pilih catatan yang ingin dihapus')
                                    ->options(function () {
                                        return Note::where('activity_id', $this->record->activity_id)
                                            ->where('user_id', Auth::id())
                                            ->orderBy('created_at', 'desc')
                                            ->get()
                                            ->mapWithKeys(function ($note) {
                                                $date = $note->created_at->format('d M Y, H:i');
                                                $preview = strlen($note->note) > 50 
                                                    ? substr($note->note, 0, 50) . '...' 
                                                    : $note->note;
                                                
                                                return [$note->id => "{$preview} ({$date})"];
                                            });
                                    })
                                    ->required()
                                    ->columns(1)
                                    ->helperText('Pilih satu atau lebih catatan yang ingin dihapus'),
                            ])
                            ->action(function (array $data): void {
                                Note::whereIn('id', $data['selected_notes'])
                                    ->where('user_id', Auth::id())
                                    ->delete();
                            })
                            ->successNotificationTitle('Catatan berhasil dihapus')
                            ->modalHeading('Hapus Catatan')
                            ->requiresConfirmation()
                            ->modalDescription('Apakah Anda yakin ingin menghapus catatan yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                            ->visible(fn() => Note::where('activity_id', $this->record->activity_id)
                                ->where('user_id', Auth::id())
                                ->exists()),
                    ])
                    ->schema([
                        TextEntry::make('formatted_notes')
                            ->label('')
                            ->state(function () {
                                $notes = Note::where('activity_id', $this->record->activity_id)
                                    ->where('user_id', Auth::id())
                                    ->orderBy('created_at', 'desc')
                                    ->get();
                    
                                if ($notes->isEmpty()) {
                                    return "<div class='border border-gray-200 rounded-lg p-4 bg-gray-50'>
                                                <p class='text-gray-500 italic'>Tidak ada catatan tersedia</p>
                                            </div>";
                                }
                    
                                $formattedNotes = $notes->map(function ($note) {
                                    $date = $note->created_at->format('d F Y, H:i');
                                    $userName = $note->user->name ?? 'Unknown User'; // Ambil nama user
                                    
                                    return "<div class='border border-gray-200 rounded-lg p-4 bg-white shadow-sm mb-3'>
                                                <div class='text-gray-800 mb-2'>" . $note->note . "</div>
                                                <div class='text-gray-500 text-sm'>
                                                    <span>Dibuat oleh: " . $userName . "</span>
                                                    <span class='mx-2'>•</span>
                                                    <span>" . $date . "</span>
                                                </div>
                                            </div>";
                                })->join('');
                    
                                return $formattedNotes;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn() => Note::where('activity_id', $this->record->activity_id)
                        ->where('user_id', Auth::id())
                        ->exists())
                    ->collapsible(),
            ]);
    }
}