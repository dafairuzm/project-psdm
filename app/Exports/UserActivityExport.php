<?php

namespace App\Exports;

use App\Models\UserActivity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class UserActivityExport implements FromCollection, WithHeadings, WithEvents
{
    protected $records;
    protected int $counter = 1;
    protected $professionStats;

    public function __construct(Collection $records)
    {
        $this->records = $records;
        $this->rowCount = $records->count() + 4;
        $this->calculateUnitStats();
    }

    protected function calculateUnitStats()
    {
        // Hitung statistik per unit
        $this->professionStats = $this->records->load(['user.profession'])
            ->groupBy('user.profession.name')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc();
    }

    public function collection()
    {
        $this->counter = 1;
        
        return $this->records->load(['user.profession', 'activity.categories'])->map(function ($item) {

            $title = $item->activity->title ?? '';
            $organizer = $item->activity->organizer ?? '';
            $location = $item->activity->location ?? '';
            
            
            // Ambil kategori (jika ada multiple kategori, gabung dengan koma)
            $categories = $item->activity->categories->pluck('name')->implode(', ');

            return [
                'no' => $this->counter++,
                'nama' => $item->user->name ?? '',
                'nip' => $item->user->nip ?? '',
                'pangkat' => $item->user->employee_class ?? '',
                'jabatan' => $item->user->job_title ?? '',
                'nama_kegiatan' => $title,
                'kategori' => $categories,
                'waktu' => $item->activity->start_date->format('d') . ' s.d ' . $item->activity->finish_date->format('d F Y'),
                'penyelenggara' => $organizer,
                'tempat' => $location,
                'lama_jam' => $item->activity->duration ?? '',
            ];
        });
    }

    protected function limitText($text, $limit)
    {
        if (strlen($text) > $limit) {
            return substr($text, 0, $limit - 3) . '...';
        }
        return $text;
    }

    public function headings(): array
    {
        return [
            'NO',
            'NAMA',
            'NIP',
            'PANGKAT/GOL',
            'JABATAN',
            'JUDUL KEGIATAN',
            'KATEGORI',
            'WAKTU',
            'PENYELENGGARA',
            'TEMPAT',
            'LAMA JAM'
        ];
    }

    public function registerEvents(): array
    {
        $rowCount = count($this->collection()) + 4;

        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Judul utama
                $sheet->mergeCells('A1:K1');
                $sheet->setCellValue('A1', 'LAPORAN KEGIATAN PSDM RSUD dr.ISKAK TULUNGAGUNG');

                $sheet->mergeCells('A2:K2');
                $sheet->setCellValue('A2', 'DIKLAT / BIMTEK / WORKSHOP / SEMINAR EXHOUSE');

                $sheet->mergeCells('A3:K3');
                $sheet->setCellValue('A3', 'TAHUN -');

                // Center align semua judul (A1 sampai A3)
                $sheet->getStyle('A1:K3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:K3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1:K3')->getFont()->setBold(true);
            },

            AfterSheet::class => function (AfterSheet $event) use ($rowCount) {
                $sheet = $event->sheet->getDelegate();

                // Border seluruh tabel dari header sampai akhir data
                $sheet->getStyle("A4:K{$rowCount}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Bold dan background abu-abu untuk header
                $sheet->getStyle('A4:K4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'], // abu-abu muda
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Set text wrapping untuk kolom yang dibatasi karakternya
                $sheet->getStyle("F5:F{$rowCount}")->getAlignment()->setWrapText(true); // Judul Kegiatan
                $sheet->getStyle("G5:G{$rowCount}")->getAlignment()->setWrapText(true); // Kategori
                $sheet->getStyle("I5:I{$rowCount}")->getAlignment()->setWrapText(true); // Penyelenggara
                $sheet->getStyle("J5:J{$rowCount}")->getAlignment()->setWrapText(true); // Tempat

                // Set lebar kolom yang spesifik
                $sheet->getColumnDimension('F')->setWidth(40); // Judul Kegiatan
                $sheet->getColumnDimension('G')->setWidth(20); // Kategori
                $sheet->getColumnDimension('I')->setWidth(30); // Penyelenggara
                $sheet->getColumnDimension('J')->setWidth(30); // Tempat

                // Autosize kolom lainnya
                foreach (['A', 'B', 'C', 'D', 'E', 'H', 'K'] as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Set tinggi baris minimum untuk data
                for ($i = 5; $i <= $rowCount; $i++) {
                    $sheet->getRowDimension($i)->setRowHeight(30);
                }

                // Tambahkan tabel statistik unit di bawah data utama
                $statsStartRow = $rowCount + 3; // Berikan jarak 2 baris kosong
                
                // Judul statistik
                $sheet->mergeCells("A{$statsStartRow}:C{$statsStartRow}");
                $sheet->setCellValue("A{$statsStartRow}", 'STATISTIK KEGIATAN PER PROFESI');
                $sheet->getStyle("A{$statsStartRow}:C{$statsStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A{$statsStartRow}:C{$statsStartRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$statsStartRow}:C{$statsStartRow}")->getFont()->setSize(12);

                // Header tabel statistik
                $headerRow = $statsStartRow + 2;
                $sheet->setCellValue("A{$headerRow}", 'NO');
                $sheet->setCellValue("B{$headerRow}", 'UNIT');
                $sheet->setCellValue("C{$headerRow}", 'JUMLAH');
                
                $sheet->getStyle("A{$headerRow}:C{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E6E6FA'], // lavender
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Isi data statistik unit
                $dataRow = $headerRow + 1;
                $no = 1;
                $totalKegiatan = 0;
                
                foreach ($this->professionStats as $professionName => $count) {
                    $sheet->setCellValue("A{$dataRow}", $no++);
                    $sheet->setCellValue("B{$dataRow}", $professionName ?: 'Profesi lainnya');
                    $sheet->setCellValue("C{$dataRow}", $count);
                    $totalKegiatan += $count;
                    $dataRow++;
                }

                // Baris total
                $sheet->setCellValue("A{$dataRow}", '');
                $sheet->setCellValue("B{$dataRow}", 'TOTAL');
                $sheet->setCellValue("C{$dataRow}", $totalKegiatan);
                $sheet->getStyle("B{$dataRow}:C{$dataRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFE4B5'], // moccasin
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Border untuk tabel statistik
                $sheet->getStyle("A{$headerRow}:C{$dataRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Set alignment center untuk kolom nomor dan jumlah
                $sheet->getStyle("A" . ($headerRow + 1) . ":A{$dataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C" . ($headerRow + 1) . ":C{$dataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Autosize kolom statistik
                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setAutoSize(true);
            },
        ];
    }
}