<?php

namespace App\Services;

use App\Models\Activity;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DocumentGeneratorService
{
    private $templatePath;
    private $outputPath;

    public function __construct()
    {
        $this->templatePath = storage_path('app/public/templates/');
        $this->outputPath = storage_path('app/generated/');
        
        // Ensure directories exist
        if (!file_exists($this->templatePath)) {
            mkdir($this->templatePath, 0755, true);
        }
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }

    public function generateSuratTugas(Activity $activity)
    {
        try {
            // Load template
            $templateFile = $this->templatePath . 'surat_tugas_template.docx';
            
            if (!file_exists($templateFile)) {
                throw new \Exception('Template surat tugas tidak ditemukan. Pastikan file template ada di storage/app/templates/surat_tugas_template.docx');
            }

            $templateProcessor = new TemplateProcessor($templateFile);
            
            // Get participants with their details
            $participants = $activity->users()->get();
            
            if ($participants->isEmpty()) {
                throw new \Exception('Tidak ada peserta yang terdaftar untuk aktivitas ini.');
            }

            // Replace basic activity data
            $this->replaceReferences($templateProcessor, $activity);
            $templateProcessor->setValue('title', $activity->title);
            $templateProcessor->setValue('start_date', $this->formatDate($activity->start_date));
            $templateProcessor->setValue('finish_date', $this->formatDate($activity->finish_date));
            $templateProcessor->setValue('location', $activity->location);

            // Handle multiple participants
            $this->replaceParticipants($templateProcessor, $participants);

            // Generate filename
            $filename = $this->generateFilename($activity);
            $outputFile = $this->outputPath . $filename;

            // Save the document
            $templateProcessor->saveAs($outputFile);

            return [
                'success' => true,
                'file_path' => $outputFile,
                'filename' => $filename,
                'message' => 'Surat tugas berhasil dibuat'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal membuat surat tugas: ' . $e->getMessage()
            ];
        }
    }

    private function replaceReferences($templateProcessor, $activity)
    {
        try {
            $references = $activity->reference;
            
            \Log::info('Processing references: ', ['references' => $references]);
            
            // Handle null or empty references
            if (empty($references)) {
                \Log::warning('No references found!');
                // Set empty block if no references
                $templateProcessor->cloneBlock('reference_block', 0);
                return;
            }
            
            // Convert everything to JSON first, then back to array to handle complex structures
            $jsonReferences = json_encode($references);
            $decodedReferences = json_decode($jsonReferences, true);
            
            // Flatten and clean the array
            $cleanReferences = $this->extractStringsFromArray($decodedReferences);
            
            if (empty($cleanReferences)) {
                \Log::warning('No clean references found after extraction!');
                $templateProcessor->cloneBlock('reference_block', 0);
                return;
            }
            
            \Log::info('Clean references count: ' . count($cleanReferences));
            
            // Prepare data for cloning
            $referenceData = [];
            foreach ($cleanReferences as $index => $reference) {
                $referenceData[] = [
                    'reference_number' => $index + 1, // Nomor urut reference
                    'reference_text' => $reference,
                    // Conditional untuk "Dasar" - hanya tampil di index 0
                    'show_dasar' => $index === 0 ? 'Dasar' : '',
                    'show_colon' => $index === 0 ? ':' : '',
                    // Nomor otomatis
                    'auto_number' => $index + 1
                ];
                
                \Log::info("Reference {$index}: ", $referenceData[$index]);
            }
            
            // Clone the reference block for each reference
            $templateProcessor->cloneBlock('reference_block', count($referenceData), true, true);
            
            // Replace values for each reference
            foreach ($referenceData as $index => $reference) {
                $blockIndex = $index + 1; // PhpWord uses 1-based indexing for cloned blocks
                
                $templateProcessor->setValue("reference_number#{$blockIndex}", $reference['reference_number']);
                $templateProcessor->setValue("reference_text#{$blockIndex}", $reference['reference_text']);
                
                // Set conditional values
                $templateProcessor->setValue("show_dasar#{$blockIndex}", $reference['show_dasar']);
                $templateProcessor->setValue("show_colon#{$blockIndex}", $reference['show_colon']);
                
                // Set auto number
                $templateProcessor->setValue("auto_number#{$blockIndex}", $reference['auto_number']);
                
                \Log::info("Replaced reference block {$blockIndex} with: ", $reference);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error processing references: ' . $e->getMessage());
            // Fallback if all else fails
            $templateProcessor->cloneBlock('reference_block', 0);
        }
    }
    
    private function extractStringsFromArray($data)
    {
        $result = [];
        
        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $nested = $this->extractStringsFromArray($item);
                    $result = array_merge($result, $nested);
                } else {
                    $stringItem = trim((string) $item);
                    if (!empty($stringItem)) {
                        $result[] = $stringItem;
                    }
                }
            }
        } else {
            $stringItem = trim((string) $data);
            if (!empty($stringItem)) {
                $result[] = $stringItem;
            }
        }
        
        return $result;
    }

    private function replaceParticipants($templateProcessor, $participants)
    {
        \Log::info('Processing participants count: ' . $participants->count());
        
        if ($participants->isEmpty()) {
            \Log::warning('No participants found!');
            return;
        }
        
        // Prepare data for cloning
        $participantData = [];
        foreach ($participants as $index => $participant) {
            $participantData[] = [
                'participant_number' => $index + 1, // Nomor urut participant
                'name' => $participant->name ?? '-',
                'nip' => $participant->nip ?? '-',
                'employee_class' => $participant->employee_class ?? '-',
                'job_title' => $participant->job_title ?? '-',
                // Conditional untuk "Kepada" - hanya tampil di index 0
                'show_kepada' => $index === 0 ? 'Kepada :' : '',
                'show_kepada_cell' => $index === 0 ? 'Kepada' : '',
                'show_colon' => $index === 0 ? ':' : '',
                // Nomor otomatis setelah colon
                'auto_number' => $index + 1
            ];
            
            \Log::info("Participant {$index}: ", $participantData[$index]);
        }
        
        // Clone the participant block for each participant
        $templateProcessor->cloneBlock('participant_block', count($participantData), true, true);
        
        // Replace values for each participant
        foreach ($participantData as $index => $participant) {
            $blockIndex = $index + 1; // PhpWord uses 1-based indexing for cloned blocks
            
            $templateProcessor->setValue("participant_number#{$blockIndex}", $participant['participant_number']);
            $templateProcessor->setValue("name#{$blockIndex}", $participant['name']);
            $templateProcessor->setValue("nip#{$blockIndex}", $participant['nip']);
            $templateProcessor->setValue("employee_class#{$blockIndex}", $participant['employee_class']);
            $templateProcessor->setValue("job_title#{$blockIndex}", $participant['job_title']);
            
            // Set conditional values
            $templateProcessor->setValue("show_kepada#{$blockIndex}", $participant['show_kepada']);
            $templateProcessor->setValue("show_kepada_cell#{$blockIndex}", $participant['show_kepada_cell']);
            $templateProcessor->setValue("show_colon#{$blockIndex}", $participant['show_colon']);
            
            // Set auto number
            $templateProcessor->setValue("auto_number#{$blockIndex}", $participant['auto_number']);
            
            \Log::info("Replaced participant block {$blockIndex} with: ", $participant);
        }
    }

    private function formatDate($date)
    {
        if (!$date) return '-';
        
        return Carbon::parse($date)->locale('id')->isoFormat('DD MMMM YYYY');
    }

    private function generateFilename(Activity $activity)
    {
        $title = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $activity->title);
        $date = date('Y-m-d_H-i-s');
        
        return "Surat_Tugas_{$title}_{$date}.docx";
    }

    public function cleanupFile($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function createTemplate()
    {
        // Method untuk membuat template dasar jika belum ada
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        // Header with logo placeholder
        $header = $section->addHeader();
        $header->addImage(public_path('images/logo_pemda.png'), [
            'width' => 70,
            'height' => 90,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
        ]);
        
        // Title
        $section->addText('SURAT PERINTAH TUGAS', [
            'bold' => true,
            'size' => 14,
            'underline' => 'single'
        ], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        
        $section->addTextBreak();
        
        $section->addText('Nomor : [onshow.nomersurat]', ['bold' => true]);
        
        $section->addTextBreak();
        
        // Reference section with block structure
        $section->addText('${reference_block}');
        $section->addText('${show_dasar} ${show_colon} ${auto_number}. ${reference_text}');
        $section->addText('${/reference_block}');
        
        $section->addTextBreak();
        
        $section->addText('MEMERINTAHKAN', ['bold' => true, 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        
        $section->addTextBreak();
        
        // Participant section with block structure
        $section->addText('${participant_block}');
        $section->addText('${show_kepada_cell} ${show_colon} ${auto_number}. Nama: ${name}');
        $section->addText('NIP: ${nip}');
        $section->addText('Pangkat: ${employee_class}');
        $section->addText('Jabatan: ${job_title}');
        $section->addTextBreak();
        $section->addText('${/participant_block}');
        
        $section->addTextBreak();
        
        $section->addText('Untuk : ${title} Tanggal ${start_date} s/d ${finish_date} di ${location}', ['bold' => true]);
        
        // Save template
        $templateFile = $this->templatePath . 'surat_tugas_template.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($templateFile);
        
        return $templateFile;
    }
}