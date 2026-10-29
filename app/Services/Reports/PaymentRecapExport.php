<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel Rekap Pembayaran dengan tata letak template keuangan: baris 1 bulan (digabung
 * di atas minggunya), baris 2 judul kolom/minggu ke-n, data mulai baris 3, baris total di bawah.
 */
class PaymentRecapExport
{
    private const FILL_MONTH = 'FFF2CC';

    private const FILL_HEADER = 'F3F3F3';

    private const MONEY = '#,##0';

    public static function download(array $report, string $filename): StreamedResponse
    {
        $spreadsheet = self::spreadsheet($report);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function spreadsheet(array $report): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Pembayaran');

        $col = fn (int $index) => Coordinate::stringFromColumnIndex($index);
        $fixed = ['No', 'Gelombang', 'Nama Lengkap', 'Prodi', 'Kelas'];
        $weekStart = count($fixed) + 1;
        $componentStart = $weekStart + $report['weeks']->count();
        $totalStart = $componentStart + $report['components']->count();
        $pathColumn = $totalStart + 2;
        $lastRow = 2 + $report['rows']->count() + 1;

        // Baris 1: bulan di atas minggu-minggunya, kelompok komponen, dan total.
        $column = $weekStart;
        foreach ($report['months'] as $month) {
            $sheet->setCellValue($col($column) . '1', $month['label']);
            if ($month['span'] > 1) {
                $sheet->mergeCells($col($column) . '1:' . $col($column + $month['span'] - 1) . '1');
            }
            $column += $month['span'];
        }

        $sheet->setCellValue($col($componentStart) . '1', 'Per Komponen');
        if ($report['components']->count() > 1) {
            $sheet->mergeCells($col($componentStart) . '1:' . $col($totalStart - 1) . '1');
        }
        $sheet->setCellValue($col($totalStart) . '1', 'Total');
        $sheet->mergeCells($col($totalStart) . '1:' . $col($totalStart + 1) . '1');

        // Baris 2: judul kolom.
        $headers = [...$fixed, ...$report['weeks']->pluck('week')->all(), ...$report['components']->all(), 'Pembayaran', 'Outstanding', 'Jalur Daftar', 'Keterangan'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue($col($index + 1) . '2', $header);
        }

        // Data camaba.
        $row = 3;
        foreach ($report['rows'] as $index => $item) {
            $applicant = $item['applicant'];
            $program = $applicant->enrolled_study_program;
            $waveNumber = preg_replace('/\D/', '', (string) $applicant->admissionWave?->code);

            $values = [
                $index + 1,
                $waveNumber !== '' ? (int) $waveNumber : $applicant->admissionWave?->name,
                $applicant->full_name,
                $program ? $program->degree . ' - ' . $program->name : null,
                $applicant->classType?->name,
            ];

            foreach ($values as $offset => $value) {
                $sheet->setCellValue($col($offset + 1) . $row, $value);
            }

            foreach ($report['weeks'] as $offset => $week) {
                $amount = $item['weekly'][$week['key']];
                if ($amount > 0) {
                    $sheet->setCellValue($col($weekStart + $offset) . $row, $amount);
                }
            }

            foreach ($report['components'] as $offset => $name) {
                $amount = $item['components'][$name] ?? 0;
                // Formulir gratis ditulis 0 (bukan kosong) agar terlihat sudah tercatat.
                if ($amount > 0 || ($name === PaymentRecap::FORM_COMPONENT && $item['note'])) {
                    $sheet->setCellValue($col($componentStart + $offset) . $row, $amount);
                }
            }

            $sheet->setCellValue($col($totalStart) . $row, $item['paid']);
            $sheet->setCellValue($col($totalStart + 1) . $row, $item['outstanding']);
            $sheet->setCellValue($col($pathColumn) . $row, $applicant->registration_path_label);
            $sheet->setCellValue($col($pathColumn + 1) . $row, $item['note']);
            $row++;
        }

        // Baris total.
        $sheet->setCellValue('C' . $lastRow, 'TOTAL');
        for ($column = $weekStart; $column <= $totalStart + 1; $column++) {
            $letter = $col($column);
            $sheet->setCellValue($letter . $lastRow, $report['rows']->isEmpty() ? 0 : "=SUM({$letter}3:{$letter}" . ($lastRow - 1) . ')');
        }

        // Gaya mengikuti template: header berwarna, angka ribuan, garis tipis, baris 1-2 & kolom A-E dibekukan.
        $lastColumn = $col($pathColumn + 1);
        $sheet->getStyle("A1:{$lastColumn}2")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("{$col($weekStart)}1:{$col($totalStart + 1)}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::FILL_MONTH);
        $sheet->getStyle("A2:{$lastColumn}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::FILL_HEADER);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D9D9D9');
        $sheet->getStyle("{$col($weekStart)}3:{$col($totalStart + 1)}{$lastRow}")->getNumberFormat()->setFormatCode(self::MONEY);
        $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::FILL_MONTH);
        $sheet->freezePane('F3');

        foreach (['A' => 5, 'B' => 11, 'C' => 32, 'D' => 40, 'E' => 11] as $letter => $width) {
            $sheet->getColumnDimension($letter)->setWidth($width);
        }
        for ($column = $weekStart; $column <= $totalStart + 1; $column++) {
            $sheet->getColumnDimension($col($column))->setWidth($column >= $componentStart ? 15 : 12);
        }
        $sheet->getColumnDimension($col($pathColumn))->setWidth(13);
        $sheet->getColumnDimension($lastColumn)->setWidth(30);

        return $spreadsheet;
    }
}
