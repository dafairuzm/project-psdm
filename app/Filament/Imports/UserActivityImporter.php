<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\Activity;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UserActivityImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('NO')
                ->label('No'),
            ImportColumn::make('NAMA')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->label('Nama Pegawai'),
            ImportColumn::make('PANGKAT/GOL')
                ->label('Pangkat/Gol'),
            ImportColumn::make('JABATAN')
                ->label('Jabatan'),
            ImportColumn::make('JUDUL PELATIHAN')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->label('Nama Kegiatan'),
            ImportColumn::make('TANGGAL MULAI')
                ->requiredMapping()
                ->rules(['required'])
                ->label('Tanggal Mulai'),
            ImportColumn::make('TANGGAL SELESAI')
                ->requiredMapping()
                ->rules(['required'])
                ->label('Tanggal Selesai'),
            ImportColumn::make('PENYELENGGARA')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->label('Penyelenggara'),
            ImportColumn::make('TEMPAT')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->label('Tempat'),
            ImportColumn::make('LAMA JAM')
                ->numeric()
                ->label('Lama jam'),
        ];
    }

    public function resolveRecord(): ?User
    {
        // Log semua data yang masuk
        \Log::info('=== IMPORT START ===', [
            'raw_data' => $this->data,
            'timestamp' => now()
        ]);

        \DB::beginTransaction();
        try {
            // Validasi data dasar
            if (empty($this->data) || !is_array($this->data)) {
                \Log::error('Data is empty or not array', ['data' => $this->data]);
                throw new \Exception('Data tidak valid');
            }

            // Validasi dan format data
            $nama = isset($this->data['NAMA']) ? trim($this->data['NAMA']) : null;
            $jabatan = isset($this->data['JABATAN']) ? trim($this->data['JABATAN']) : null;
            $pangkat = isset($this->data['PANGKAT/GOL']) ? trim($this->data['PANGKAT/GOL']) : null;
            
            \Log::info('Parsed basic data', [
                'nama' => $nama,
                'jabatan' => $jabatan,
                'pangkat' => $pangkat
            ]);
            
            if (empty($nama)) {
                throw new \Exception('Nama tidak boleh kosong');
            }

            // Format tanggal dengan error handling
            $tanggalMulai = isset($this->data['TANGGAL MULAI']) ? trim($this->data['TANGGAL MULAI']) : null;
            $tanggalSelesai = isset($this->data['TANGGAL SELESAI']) ? trim($this->data['TANGGAL SELESAI']) : null;
            
            \Log::info('Date strings', [
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai
            ]);

            if (empty($tanggalMulai) || empty($tanggalSelesai)) {
                throw new \Exception('Tanggal mulai dan selesai harus diisi');
            }

            try {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $tanggalMulai)->startOfDay();
                $finishDate = \Carbon\Carbon::createFromFormat('d/m/Y', $tanggalSelesai)->endOfDay();
                
                \Log::info('Parsed dates', [
                    'start_date' => $startDate->toDateTimeString(),
                    'finish_date' => $finishDate->toDateTimeString()
                ]);
            } catch (\Exception $e) {
                \Log::error('Date parsing error', [
                    'error' => $e->getMessage(),
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai
                ]);
                throw new \Exception('Format tanggal tidak valid. Gunakan format dd/mm/yyyy. Error: ' . $e->getMessage());
            }

            // Bersihkan judul pelatihan
            $judulPelatihan = isset($this->data['JUDUL PELATIHAN']) ? trim($this->data['JUDUL PELATIHAN']) : null;
            $judulPelatihan = preg_replace('/[^\x20-\x7E]/', '', $judulPelatihan);
            $judulPelatihan = trim($judulPelatihan);
            
            \Log::info('Cleaned training title', ['judul' => $judulPelatihan]);
            
            if (empty($judulPelatihan)) {
                throw new \Exception('Judul pelatihan tidak boleh kosong');
            }

            // Step 1: Proses User
            \Log::info('=== PROCESSING USER ===');
            
            // Cek user existing
            $userQuery = User::where('name', $nama);
            if ($jabatan) {
                $userQuery->where('title_complete', $jabatan);
            }
            
            $user = $userQuery->first();
            \Log::info('User lookup result', [
                'found' => $user ? true : false,
                'user_id' => $user ? $user->id : null
            ]);
            
            if (!$user) {
                // Generate email yang unique
                $baseEmail = strtolower(str_replace(' ', '.', $nama)) . '@example.com';
                $email = $baseEmail;
                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = strtolower(str_replace(' ', '.', $nama)) . $counter . '@example.com';
                    $counter++;
                }
                
                // Buat user baru
                $userData = [
                    'name' => $nama,
                    'email' => $email,
                    'password' => bcrypt('psdm123'),
                ];
                
                // Hanya tambahkan jika ada data
                if ($jabatan) {
                    $userData['title_complete'] = $jabatan;
                }
                if ($pangkat) {
                    $userData['employee_class'] = $pangkat;
                }
                
                \Log::info('Creating user with data', $userData);
                
                $user = User::create($userData);
                
                \Log::info('User created successfully', [
                    'id' => $user->id, 
                    'name' => $user->name,
                    'email' => $user->email
                ]);
            } else {
                \Log::info('Using existing user', ['id' => $user->id, 'name' => $user->name]);
            }

            // Step 2: Proses Activity
            \Log::info('=== PROCESSING ACTIVITY ===');
            
            $penyelenggara = isset($this->data['PENYELENGGARA']) ? trim($this->data['PENYELENGGARA']) : '';
            $tempat = isset($this->data['TEMPAT']) ? trim($this->data['TEMPAT']) : '';
            $duration = isset($this->data['LAMA JAM']) ? (int)$this->data['LAMA JAM'] : 0;
            
            \Log::info('Activity data', [
                'title' => $judulPelatihan,
                'organizer' => $penyelenggara,
                'location' => $tempat,
                'duration' => $duration
            ]);
            
            $activity = Activity::where('title', $judulPelatihan)
                ->where('start_date', $startDate)
                ->where('finish_date', $finishDate)
                ->where('organizer', $penyelenggara)
                ->first();

            \Log::info('Activity lookup result', [
                'found' => $activity ? true : false,
                'activity_id' => $activity ? $activity->id : null
            ]);

            if (!$activity) {
                $activityData = [
                    'title' => $judulPelatihan,
                    'type' => 'inhouse',
                    'start_date' => $startDate,
                    'finish_date' => $finishDate,
                    'organizer' => $penyelenggara,
                    'location' => $tempat,
                    'duration' => $duration,
                ];
                
                \Log::info('Creating activity with data', $activityData);

                $activity = Activity::create($activityData);

                \Log::info('Activity created successfully', [
                    'id' => $activity->id, 
                    'title' => $activity->title
                ]);
            } else {
                \Log::info('Using existing activity', [
                    'id' => $activity->id, 
                    'title' => $activity->title
                ]);
            }

            // Step 3: Attach user ke activity
            \Log::info('=== PROCESSING ATTACHMENT ===');
            
            $isAttached = $user->activities()->where('activity_id', $activity->id)->exists();
            \Log::info('Checking attachment', [
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'already_attached' => $isAttached
            ]);
            
            if (!$isAttached) {
                $user->activities()->attach($activity->id);
                \Log::info('User attached to activity successfully', [
                    'user_id' => $user->id, 
                    'activity_id' => $activity->id,
                    'user_name' => $user->name,
                    'activity_title' => $activity->title
                ]);
            } else {
                \Log::info('User already attached to activity', [
                    'user_id' => $user->id, 
                    'activity_id' => $activity->id
                ]);
            }

            // Verify attachment
            $attachmentExists = $user->activities()->where('activity_id', $activity->id)->exists();
            \Log::info('Final attachment verification', [
                'attachment_exists' => $attachmentExists,
                'user_activities_count' => $user->activities()->count()
            ]);

            \DB::commit();
            \Log::info('=== IMPORT SUCCESS ===', [
                'user_id' => $user->id,
                'activity_id' => $activity->id
            ]);
            
            return $user;
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('=== IMPORT FAILED ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data
            ]);
            
            // Return null agar Filament tahu ada error
            return null;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor data peserta selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diimpor.';
        }

        return $body;
    }
}