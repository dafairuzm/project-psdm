<?php

use App\Http\Controllers\DocumentController;
use App\Models\Documentation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/download-dokumentasi/{id}', function ($id) {
    $documentation = Documentation::findOrFail($id);
    return Storage::disk('public')->download($documentation->documentation);
})->name('download-dokumentasi');

Route::get('/download-surat-tugas/{activity}', [DocumentController::class, 'downloadSuratTugas'])
    ->name('download.surat-tugas');

// Route::get('/redirect-after-login', function () {
//         $user = auth()->user();
    
//         // Pegawai diarahkan ke resource pertama yang dia bisa akses
//         if ($user->hasRole('Pegawai')) {
//             return redirect()->route('filament.admin.resources.riwayat-kegiatans.index'); // Ganti sesuai resource yang bisa dia akses
//         }
    
//         // Role lain tetap ke dashboard
//         return redirect()->route('filament.admin.pages.dashboard');
//     });
