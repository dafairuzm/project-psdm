<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\JobTitle;

class UserActivitiesImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $errors = [];
    protected $successCount = 0;
    protected $skipCount = 0;

    public function collection(Collection $rows)
    {
        Log::info("Total rows to process: " . $rows->count());
        Log::info("First row headers: ", $rows->first() ? array_keys($rows->first()->toArray()) : []);

        foreach ($rows as $index => $row) {
            try {
                Log::info("Processing row " . ($index + 1), $row->toArray());

                // Akses dengan nama kolom yang sudah dikonversi Laravel Excel
                $nama = isset($row['nama']) ? trim($row['nama']) : null;
                $judulPelatihan = isset($row['judul_pelatihan']) ? trim($row['judul_pelatihan']) : null;

                Log::info("Row " . ($index + 1) . " - Nama: '$nama', Judul: '$judulPelatihan'");

                // Skip jika row kosong atau tidak lengkap
                if (empty($nama) || empty($judulPelatihan)) {
                    $this->errors[] = "Baris " . ($index + 2) . ": Nama atau judul pelatihan kosong. Nama: '$nama', Judul: '$judulPelatihan'";
                    $this->skipCount++;
                    continue;
                }

                // Cari atau buat user
                $user = $this->findOrCreateUser($row);
                Log::info("User found/created: " . $user->name . " (ID: " . $user->id . ")");

                // Cari atau buat activity beserta kategorinya
                $activity = $this->findOrCreateActivity($row);
                Log::info("Activity found/created: " . $activity->title . " (ID: " . $activity->id . ")");

                // Attach user ke activity jika belum ada
                if (!$user->activities()->where('activity_id', $activity->id)->exists()) {
                    $user->activities()->attach($activity->id, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);                    
                    $this->successCount++;
                    Log::info("User attached to activity successfully");
                } else {
                    $this->skipCount++;
                    Log::info("User already attached to this activity, skipped");
                }

            } catch (\Exception $e) {
                $this->errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                Log::error("Import error pada baris " . ($index + 2), [
                    'error' => $e->getMessage(),
                    'data' => $row->toArray(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info("Import completed. Success: {$this->successCount}, Skip: {$this->skipCount}, Errors: " . count($this->errors));
    }

    protected function findOrCreateUser($row)
    {
        $nama = trim($row['nama']);
        $jobTitle = trim($row['jabatan']);

        // Cek user berdasarkan nama dan job_title
        $user = User::where('name', $nama)
            ->where('job_title', $jobTitle)
            ->first();

        if (!$user) {
            // Ambil nilai pangkat/golongan dari kolom 'pangkatgol' atau beri default '-'
            $employeeClass = !empty($row['pangkatgol']) ? trim($row['pangkatgol']) : '-';

            try {
                // Mulai database transaction
                \DB::beginTransaction();

                // Buat user baru
                $user = User::create([
                    'name' => $nama,
                    'email' => $this->generateEmail($nama),
                    'password' => bcrypt('password123'), // Default password
                    'nip' => null, // Biarkan kosong
                    'employee_class' => $employeeClass,
                    'job_title' => $jobTitle,
                    'unit_id' => null, // Biarkan kosong sesuai permintaan
                ]);

                // Assign default role "Pegawai" menggunakan Spatie Permission
                $pegawaiRole = \Spatie\Permission\Models\Role::where('name', 'Pegawai')->first();
                
                if ($pegawaiRole) {
                    $user->assignRole('Pegawai');
                } else {
                    // Log warning jika role tidak ada, tapi tetap lanjutkan
                    \Log::warning("Role 'Pegawai' tidak ditemukan saat membuat user", [
                        'user_name' => $nama,
                        'user_email' => $user->email
                    ]);
                }

                // Commit transaction
                \DB::commit();

            } catch (\Exception $e) {
                // Rollback jika ada error
                \DB::rollback();
                
                // Log error
                \Log::error("Gagal membuat user baru", [
                    'user_name' => $nama,
                    'job_title' => $jobTitle,
                    'error' => $e->getMessage()
                ]);
                
                // Re-throw exception agar proses import bisa handle error
                throw $e;
            }
        }

        return $user;
    }

    protected function findOrCreateActivity($row)
    {
        $title = trim($row['judul_pelatihan']);
        $startDate = $this->parseDate($row['tanggal_mulai']);

        // Cari activity berdasarkan judul dan tanggal mulai
        $activity = Activity::where('title', $title)
            ->where('start_date', $startDate)
            ->first();

        if (!$activity) {
            $finishDate = $this->parseDate($row['tanggal_selesai']);

            // Parse lama_jam untuk duration (sesuai nama kolom di database)
            $lamaJam = null;
            if (!empty($row['lama_jam'])) {
                $lamaJam = is_numeric($row['lama_jam']) ? (int) $row['lama_jam'] : null;
            }

            // Buat activity baru
            $activity = Activity::create([
                'title' => $title,
                'type' => $row['jenis'] ?? null,
                'organizer' => $row['penyelenggara'] ?? null,
                'location' => $row['tempat'] ?? null,
                'start_date' => $startDate,
                'finish_date' => $finishDate,
                'duration' => $lamaJam // Menggunakan lama_jam dari Excel untuk kolom duration
            ]);

            Log::info("Activity created with ID: " . $activity->id);
        }

        // Handle kategori kegiatan
        $this->attachCategoryToActivity($activity, $row);

        return $activity;
    }

    protected function attachCategoryToActivity($activity, $row)
    {
        // Ambil nama kategori dari kolom Excel (setelah type/jenis)
        $categoryName = isset($row['kategori']) ? trim($row['kategori']) : null;

        if (!empty($categoryName)) {
            try {
                // Cari atau buat kategori
                $category = ActivityCategory::firstOrCreate(
                    ['name' => $categoryName],
                    [
                        'name' => $categoryName,
                        'description' => null, // atau bisa ditambahkan kolom description di Excel jika diperlukan
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                Log::info("Category found/created: " . $category->name . " (ID: " . $category->id . ")");

                // Attach kategori ke activity jika belum ada
                if (!$activity->categories()->where('activity_category_id', $category->id)->exists()) {
                    $activity->categories()->attach($category->id);
                    Log::info("Category attached to activity successfully");
                } else {
                    Log::info("Category already attached to this activity");
                }

            } catch (\Exception $e) {
                Log::error("Error processing category for activity", [
                    'activity_id' => $activity->id,
                    'category_name' => $categoryName,
                    'error' => $e->getMessage()
                ]);
                
                // Log error tapi jangan stop proses import
                $this->errors[] = "Warning: Gagal memproses kategori '$categoryName' untuk activity '{$activity->title}': " . $e->getMessage();
            }
        } else {
            Log::info("No category specified for activity: " . $activity->title);
        }
    }

    protected function generateEmail($name)
    {
        // Generate email sederhana dari nama
        $email = strtolower(str_replace(' ', '.', trim($name))) . '@company.com';

        // Remove special characters from email
        $email = preg_replace('/[^a-z0-9.@]/', '', $email);

        // Cek jika email sudah ada, tambahkan angka
        $counter = 1;
        $originalEmail = $email;
        while (User::where('email', $email)->exists()) {
            $email = str_replace('@company.com', $counter . '@company.com', $originalEmail);
            $counter++;
        }

        return $email;
    }

    protected function parseDate($dateString)
    {
        if (empty($dateString))
            return null;

        try {
            // Handle Excel date format atau string
            if (is_numeric($dateString)) {
                // Excel serial date
                return Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateString)->format('Y-m-d'));
            }

            // Coba parse format dd/mm/yyyy
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateString)) {
                return Carbon::createFromFormat('d/m/Y', $dateString);
            }

            // Coba parse format lain
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: " . $dateString, ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string',
            'judul_pelatihan' => 'required|string',
            // Tambahkan validasi untuk kolom yang required di database
            'pangkatgol' => 'nullable|string', // Sesuaikan dengan nama kolom di Excel
            'kategori' => 'nullable|string', // Validasi untuk kategori kegiatan
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama.required' => 'Kolom nama tidak boleh kosong',
            'judul_pelatihan.required' => 'Kolom judul pelatihan tidak boleh kosong',
        ];
    }

    // Method untuk mendapatkan hasil import
    public function getImportResults()
    {
        return [
            'success_count' => $this->successCount,
            'skip_count' => $this->skipCount,
            'errors' => $this->errors,
            'total_processed' => $this->successCount + $this->skipCount + count($this->errors)
        ];
    }
}