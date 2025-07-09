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
