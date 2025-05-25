<?php

namespace App\Imports;

use App\Models\User;
use App\Models\JobTitle;
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
                // Validasi data
                $validator = Validator::make($row->toArray(), [
                    'nama' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'nip' => 'nullable',
                    'golongan' => 'nullable|string',
                    'jabatan_lengkap' => 'nullable|string',
                    'jabatan' => 'required|string',
                ], [
                    'nama.required' => 'Kolom Nama wajib diisi',
                    'email.required' => 'Kolom Email wajib diisi',
                    'email.email' => 'Format email tidak valid',
                    'email.unique' => 'Email sudah terdaftar',
                    'jabatan.required' => 'Kolom Jabatan wajib diisi',
                ]);

                if ($validator->fails()) {
                    $this->importResults['errors'][] = "Baris {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Cek jika user sudah ada berdasarkan email saja
                $existingUser = User::where('email', $row['email'])->first();

                if ($existingUser) {
                    $this->importResults['skip_count']++;
                    $this->importResults['errors'][] = "Baris {$rowNumber}: User dengan email sudah ada - dilewati";
                    continue;
                }

                // Cari atau buat job title berdasarkan nama
                $jobTitle = JobTitle::firstOrCreate(
                    ['name' => $row['jabatan']],
                    ['description' => 'Auto created from import']
                );

                // Buat user baru
                User::create([
                    'name' => $row['nama'],
                    'email' => $row['email'],
                    'nip' => $row['nip'],
                    'employee_class' => $row['golongan'] ?? null,
                    'title_complete' => $row['jabatan_lengkap'] ?? null,
                    'job_title_id' => $jobTitle->id,
                    'password' => Hash::make($row['email']), // Password sama dengan email
                    'role' => 'user', // Default role user
                ]);

                $this->importResults['success_count']++;

            } catch (\Exception $e) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: Error - " . $e->getMessage();
            }
        }
    }

    public function getImportResults(): array
    {
        return $this->importResults;
    }
}