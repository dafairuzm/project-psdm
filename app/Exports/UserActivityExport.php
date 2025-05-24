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

    public function __construct(Collection $records)
    {
        $this->records = $records;
        $this->rowCount = $records->count() + 4;
    }
    public function collection()
    {

        $this->counter = 1;
        
        return $this->records->load(['user', 'activity'])->map(function ($item) {
                return [
                    'no' => $this->counter++, // nanti auto diisi di Excel manual atau pakai script
                    'nama' => $item->user->name ?? '',
                    'nip' => $item->user->nip ?? '',
                    'pangkat' => $item->user->employee_class ?? '',
                    'jabatan' => $item->user->title_complete ?? '',
                    'nama_kegiatan' => $item->activity->title ?? '',
                    'waktu' => $item->activity->start_date->format('d') . ' s.d ' . $item->activity->finish_date->format('d F Y'),
                    'penyelenggara' => $item->activity->organizer ?? '',
                    'tempat' => $item->activity->location ?? '',
                    'lama_jam' => $item->activity->duration ?? '', // misal durasi jam
                ];
            });
    }

    public function headings(): array
    {
        return [
            'NO',
            'NAMA',
            'NIP',
            'PANGKAT/GOL',
            'JABATAN',
            'JENIS KEGIATAN',
            'WAKTU',
            'PENYELENGGARA',
            'TEMPAT',
            'LAMA JAM'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // header bold
        ];
    }


    public function registerEvents(): array
    {
        $rowCount = count($this->collection()) + 4;

        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Judul utama
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', 'LAPORAN KEGIATAN PSDM RSUD dr.ISKAK TULUNGAGUNG');

                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue('A2', 'DIKLAT / BIMTEK / WORKSHOP / SEMINAR EXHOUSE');

                $sheet->mergeCells('A3:I3');
                $sheet->setCellValue('A3', 'TAHUN 2024');

                // Center align semua judul (A1 sampai A3)
                $sheet->getStyle('A1:I3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:I3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1:I3')->getFont()->setBold(true);
            },

            AfterSheet::class => function (AfterSheet $event) use ($rowCount) {
                $sheet = $event->sheet->getDelegate();

                // Border seluruh tabel dari header sampai akhir data
                $sheet->getStyle("A4:J{$rowCount}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Bold dan background abu-abu untuk header
                $sheet->getStyle('A4:J4')->applyFromArray([
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

                // Autosize semua kolom
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }



}
