<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function downloadSuratTugas(Activity $activity)
    {
        try {
            $documentService = new DocumentGeneratorService();
            $result = $documentService->generateSuratTugas($activity);

            if ($result['success']) {
                return response()->download($result['file_path'], $result['filename'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])->deleteFileAfterSend(true);
            } else {
                return back()->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}