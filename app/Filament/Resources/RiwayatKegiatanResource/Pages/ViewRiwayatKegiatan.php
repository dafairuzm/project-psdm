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

class ViewRiwayatKegiatan extends ViewRecord
{
    protected static string $resource = RiwayatKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_documentation')
                ->label('Tambah Dokumentasi')
                ->icon('heroicon-o-camera')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('documentation')
                        ->label('File Dokumentasi')
                        ->directory('documentations')
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->multiple()
                        ->required()
                        ->helperText('Upload file gambar atau PDF sebagai dokumentasi kegiatan'),
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
                                    ->color(fn(string $state): string => match ($state) {
                                        'seminar' => 'success',
                                        'workshop' => 'info',
                                        'pelatihan' => 'warning',
                                        'rapat' => 'gray',
                                        default => 'primary',
                                    }),

                                TextEntry::make('activity.categories.name')
                                    ->label('Kategori')
                                    ->badge()
                                    ->separator(', '),

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
                                    ->suffix(' hari'),
                            ])
                    ]),

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

                                $formattedNotes = $notes->map(function ($note) {
                                    $date = $note->created_at->format('d F Y, H:i');
                                    return $note->note . ' <span class="text-gray-500 text-sm">(' . $date . ')</span>';
                                })->join('<br><br>');

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