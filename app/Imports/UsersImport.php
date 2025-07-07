<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Unit;
use App\Models\Profession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    private array $importResults = [
        'total_processed' => 0,
        'success_count' => 0,
        'skip_count' => 0,
        'errors' => []
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $this->importResults['total_processed']++;
            $rowNumber = $index + 2; // +2 karena index dimulai dari 0 dan ada header
            
            try {
                // Handle email kosong dengan placeholder (termasuk jika berisi "-")
                $email = (!empty($row['email']) && $row['email'] !== '-') ? $row['email'] : $this->generatePlaceholderEmail($row['nama_pegawai']);
                
                // Validasi data
                $validator = Validator::make(array_merge($row->toArray(), ['email' => $email]), [
                    'nama_pegawai' => 'required|string|max:255',
                    'email' => 'required|email',
                    'nip' => 'required|string|unique:users,nip',
                    'lp' => 'required|in:L,P,LAKI-LAKI,PEREMPUAN',
                    'jnspegawai' => 'required|in:ASN PNS,ASN PPPK,BLUD PHL,BLUD PTT,BLUD TETAP,KSO',
                    'pangkatgol' => 'nullable|string',
                    'jabatan' => 'nullable|string',
                ], [
                    'nama_pegawai.required' => 'Kolom Nama Pegawai wajib diisi',
                    'email.required' => 'Kolom Email wajib diisi',
                    'email.email' => 'Format email tidak valid',
                    'nip.required' => 'Kolom NIP wajib diisi',
                    'nip.unique' => 'NIP sudah terdaftar',
                    'lp.required' => 'Kolom L/P wajib diisi',
                    'lp.in' => 'Kolom L/P harus berisi L atau P',
                    'jnspegawai.required' => 'Kolom Jns.Pegawai wajib diisi',
                    'jnspegawai.in' => 'Jenis Pegawai tidak valid',
                ]);

                if ($validator->fails()) {
                    $this->importResults['errors'][] = "Baris {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Cek jika user sudah ada berdasarkan NIP
                $existingUser = User::where('nip', $row['nip'])->first();

                if ($existingUser) {
                    $this->importResults['skip_count']++;
                    $this->importResults['errors'][] = "Baris {$rowNumber}: User dengan NIP sudah ada - dilewati";
                    continue;
                }

                // Cari atau buat Unit berdasarkan nama
                $unit = Unit::firstOrCreate(
                    ['name' => $row['unit_kerja']]
                );

                // Tentukan profession berdasarkan kata pertama dari jabatan
                $professionName = $this->determineProfession($row['jabatan']);
                $profession = Profession::firstOrCreate(
                    ['name' => $professionName],
                    ['description' => 'deskripsi belum dibuat']
                );

                // Konversi gender
                $gender = $this->convertGender($row['lp']);

                // Buat user baru
                $user = User::create([
                    'name' => $row['nama_pegawai'],
                    'email' => $email,
                    'nip' => $row['nip'],
                    'gender' => $gender,
                    'employee_type' => $row['jnspegawai'],
                    'employee_class' => $row['pangkatgol'] ?? null,
                    'job_title' => $row['jabatan'] ?? null,
                    'unit_id' => $unit->id,
                    'profession_id' => $profession->id,
                    'password' => Hash::make($email), // Password sama dengan email (atau placeholder)
                ]);

                // Assign default role "Pegawai" menggunakan Spatie Permission
                try {
                    // Cek apakah role "Pegawai" ada
                    $pegawaiRole = \Spatie\Permission\Models\Role::where('name', 'Pegawai')->first();
                    
                    if ($pegawaiRole) {
                        $user->assignRole('Pegawai');
                        $this->importResults['success_count']++;
                    } else {
                        // Jika role Pegawai tidak ada, coba buat atau gunakan role default lain
                        $this->importResults['errors'][] = "Baris {$rowNumber}: Role 'Pegawai' tidak ditemukan, user dibuat tanpa role";
                        $this->importResults['success_count']++;
                    }
                    
                } catch (\Exception $roleException) {
                    // User sudah dibuat, tapi gagal assign role
                    $this->importResults['errors'][] = "Baris {$rowNumber}: User berhasil dibuat tapi gagal assign role - " . $roleException->getMessage();
                    $this->importResults['success_count']++;
                }

            } catch (\Exception $e) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: Error - " . $e->getMessage();
            }
        }
    }

    /**
     * Generate placeholder email berdasarkan nama pegawai
     */
    private function generatePlaceholderEmail(string $namaPegawai): string
    {
        // Ambil 2 kata pertama dari nama pegawai
        $words = explode(' ', trim($namaPegawai));
        $firstTwoWords = array_slice($words, 0, 2);
        
        // Gabungkan dengan 'placeholder' dan buat lowercase
        $emailName = strtolower(implode('', $firstTwoWords) . 'placeholder');
        
        // Hapus karakter yang tidak diinginkan untuk email
        $emailName = preg_replace('/[^a-z0-9]/', '', $emailName);
        
        return $emailName . '@gmail.com';
    }

    /**
     * Menentukan profession berdasarkan kata pertama dari jabatan
     */
    private function determineProfession(?string $jabatan): string
    {
        if (empty($jabatan)) {
            return 'Lain-lain';
        }

        // Ambil kata pertama dari jabatan dan ubah ke lowercase untuk perbandingan
        $firstWord = strtolower(trim(explode(' ', $jabatan)[0]));

        // Cek apakah kata pertama sesuai dengan profesi yang didukung
        switch ($firstWord) {
            case 'dokter':
                return 'Dokter';
            case 'perawat':
                return 'Perawat';
            case 'bidan':
                return 'Bidan';
            default:
                return 'Lain-lain';
        }
    }

    /**
     * Konversi gender dari format L/P ke laki-laki/perempuan
     */
    private function convertGender(string $gender): string
    {
        $gender = strtoupper(trim($gender));
        
        switch ($gender) {
            case 'L':
                return 'laki-laki';
            case 'P':
                return 'perempuan';
            case 'LAKI-LAKI':
                return 'laki-laki';
            case 'PEREMPUAN':
                return 'perempuan';
            default:
                return 'laki-laki'; // default jika tidak sesuai
        }
    }

    public function getImportResults(): array
    {
        return $this->importResults;
    }
}