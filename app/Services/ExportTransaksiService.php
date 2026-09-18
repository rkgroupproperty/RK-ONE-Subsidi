<?php

namespace App\Services;

use App\Models\Akad;
use App\Models\AkadDetail;
use App\Models\BAST;
use App\Models\GantiNama;
use App\Models\PembelianCancel;
use App\Models\PengajuanHold;
use App\Models\PindahUnit;
use App\Models\PPJB;
use App\Models\SPPR;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportTransaksiService
{
    private Spreadsheet $spreadsheet;
    private Worksheet $sheet;

    private array $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];

    private array $cellStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
    }

    private function styleHeader(int $colCount): void
    {
        $lastCol = chr(64 + $colCount);
        $this->sheet->getStyle("A1:{$lastCol}1")->applyFromArray($this->headerStyle);
        $this->sheet->getRowDimension(1)->setRowHeight(25);
    }

    private function styleCells(int $colCount, int $rowCount): void
    {
        $lastCol = chr(64 + $colCount);
        $this->sheet->getStyle("A2:{$lastCol}{$rowCount}")->applyFromArray($this->cellStyle);
    }

    private function autoWidth(int $colCount): void
    {
        $highestRow = $this->sheet->getHighestRow();
        $highestCol = $this->sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($i = 1; $i <= $highestColIndex; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $maxLen = 0;

            for ($row = 1; $row <= $highestRow; $row++) {
                $cell = $this->sheet->getCell("{$colLetter}{$row}");
                $val = $cell->getValue();
                if ($val !== null && $val !== '') {
                    $formatted = $cell->getFormattedValue();
                    $lines = explode("\n", (string) $formatted);
                    foreach ($lines as $line) {
                        $len = mb_strlen(trim($line));
                        if ($len > $maxLen) {
                            $maxLen = $len;
                        }
                    }
                }
            }

            $width = min(max($maxLen + 3, 10), 50);
            $this->sheet->getColumnDimension($colLetter)->setWidth($width);
        }
    }

    private function output(string $filename)
    {
        $writer = new Xlsx($this->spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer->save($tempFile);

        $contents = file_get_contents($tempFile);
        @unlink($tempFile);

        return response($contents)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Cache-Control', 'max-age=0');
    }

    public function exportBooking()
    {
        $data = PengajuanHold::with(['marketing', 'lokasi', 'kavling'])
            ->where('stt_reg', '!=', 2)
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Tanggal', 'Nama Customer', 'Marketing', 'Lokasi', 'Kavling', 'Status', 'Booking Fee', 'Besaran DP', 'Diskon'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $status = match ($item->stt_reg) {
                1 => 'Pending',
                2 => 'Disetujui',
                3 => 'Ditolak',
                default => 'Unknown',
            };

            $marketing = ((int) $item->id_marketing === 0) ? 'Non Marketing' : ($item->marketing->nama_marketing ?? '-');

            $this->sheet->fromArray([
                $no++,
                $item->tgl_booking ? \Carbon\Carbon::parse($item->tgl_booking)->format('d/m/Y') : '-',
                $item->nama_lengkap ?? '-',
                $marketing,
                $item->lokasi->nama_kavling ?? '-',
                $item->kavling->kode_kavling ?? '-',
                $status,
                $item->booking_fee ?? 0,
                $item->besaran_dp ?? 0,
                $item->diskon ?? 0,
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_booking.xlsx');
    }

    public function exportSPPR()
    {
        $data = SPPR::with(['customer', 'customer.lokasi', 'customer.kavling'])
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Nama', 'Blok', 'No', 'Total', 'Cicilan/bln'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $this->sheet->fromArray([
                $no++,
                $item->nama ?? $item->customer->nama_lengkap ?? '-',
                $item->blok ?? ($item->customer->kavling->kode_kavling ?? '-'),
                $item->no ?? '-',
                $item->total_yang_harus_dibayar ?? 0,
                $item->cicilan_per_bulan ?? 0,
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_proses_admin.xlsx');
    }

    public function exportWawancara()
    {
        $data = Wawancara::with(['customer', 'customer.lokasi', 'customer.kavling', 'bankKPR'])
            ->where('status', 1)
            ->whereHas('customer', fn ($q) => $q->where('stt_arsip', 0))
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Customer', 'Lokasi Rumah', 'Tgl Wawancara', 'Bank KPR', 'Catatan'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $lokasi = ($item->customer->kavling->kode_kavling ?? '-') . ' - ' . ($item->customer->lokasi->nama_kavling ?? '-');
            $catatan = strip_tags($item->catatan_wawancara ?? '-');

            $this->sheet->fromArray([
                $no++,
                $item->customer->nama_lengkap ?? '-',
                $lokasi,
                $item->tgl_wawancara ? \Carbon\Carbon::parse($item->tgl_wawancara)->format('d/m/Y') : '-',
                $item->bankKPR->nama ?? '-',
                $catatan,
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_proses_bank.xlsx');
    }

    public function exportAccBank()
    {
        $data = WawancaraSp3k::with(['wawancara.customer', 'wawancara.customer.lokasi', 'wawancara.customer.kavling', 'bankKPR'])
            ->where('status', 1)
            ->whereHas('wawancara.customer', fn ($q) => $q->where('stt_arsip', 0))
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Customer', 'Lokasi Rumah', 'Bank KPR', 'Harga Jual', 'ACC Plafon', 'Tgl SP3K', 'Tgl Expired', 'Sisa Hari'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $customer = $item->wawancara->customer ?? null;
            $lokasi = ($customer->kavling->kode_kavling ?? '-') . ' - ' . ($customer->lokasi->nama_kavling ?? '-');
            $hargaJual = $customer->kavling->rincianBiaya->firstWhere('nama', 'Harga Rumah')->nilai ?? $customer->total_harga ?? 0;

            $tglExpired = $item->tgl_expired ? \Carbon\Carbon::parse($item->tgl_expired) : null;
            $sisaHari = '-';
            if ($tglExpired) {
                $selisih = \Carbon\Carbon::now()->diffInDays($tglExpired, false);
                $sisaHari = $selisih < 0 ? 'Kadaluarsa' : (int) $selisih . ' hari';
            }

            $this->sheet->fromArray([
                $no++,
                $customer->nama_lengkap ?? '-',
                $lokasi,
                $item->bankKPR->nama ?? '-',
                $hargaJual,
                $item->acc_plafon ?? 0,
                $item->tgl_terbit_sp3k ? \Carbon\Carbon::parse($item->tgl_terbit_sp3k)->format('d/m/Y') : '-',
                $item->tgl_expired ? $tglExpired->format('d/m/Y') : '-',
                $sisaHari,
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_acc_bank.xlsx');
    }

    public function exportPPJB()
    {
        $data = PPJB::with(['customer', 'customer.lokasi', 'customer.kavling'])
            ->whereHas('customer', fn ($q) => $q->where('stt_arsip', 0))
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Tanggal PPJB', 'No. PPJB', 'Customer', 'Lokasi Rumah'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $lokasi = ($item->customer->kavling->kode_kavling ?? '-') . ' - ' . ($item->customer->lokasi->nama_kavling ?? '-');

            $this->sheet->fromArray([
                $no++,
                $item->tgl_ppjb ? \Carbon\Carbon::parse($item->tgl_ppjb)->format('d/m/Y') : '-',
                $item->no_ppjb ?? '-',
                $item->customer->nama_lengkap ?? '-',
                $lokasi,
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_ppjb.xlsx');
    }

    public function exportAkad()
    {
        $data = Akad::with([])
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Jadwal Akad', 'Total Akad', 'Keterangan'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $totalAkad = AkadDetail::where('id_akad', $item->id)->count();

            $this->sheet->fromArray([
                $no++,
                $item->tgl_akad ? \Carbon\Carbon::parse($item->tgl_akad)->format('d/m/Y') : '-',
                $totalAkad,
                $item->keterangan ?? '-',
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_akad.xlsx');
    }

    public function exportBAST()
    {
        $data = BAST::with(['customer', 'customer.lokasi', 'customer.kavling'])
            ->whereHas('customer', fn ($q) => $q->where('stt_arsip', 0))
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Tanggal BAST', 'No. BAST', 'Customer', 'Lokasi Rumah'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $lokasi = ($item->customer->kavling->kode_kavling ?? '-') . ' - ' . ($item->customer->lokasi->nama_kavling ?? '-');

            $this->sheet->fromArray([
                $no++,
                $item->tgl_bast ? \Carbon\Carbon::parse($item->tgl_bast)->format('d/m/Y') : '-',
                $item->no_bast ?? '-',
                $item->customer->nama_lengkap ?? '-',
                $lokasi,
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_bast.xlsx');
    }

    public function exportPindahUnit()
    {
        $data = PindahUnit::with(['customer', 'kavlingLama.lokasi', 'kavlingBaru.lokasi'])
            ->whereHas('customer', fn ($q) => $q->where('stt_arsip', 0))
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Tanggal Pindah', 'Customer', 'Lokasi Lama', 'Lokasi Baru', 'Keterangan'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $lokasiLama = ($item->kavlingLama->kode_kavling ?? '-') . ' - ' . ($item->kavlingLama->lokasi->nama_kavling ?? '-');
            $lokasiBaru = ($item->kavlingBaru->kode_kavling ?? '-') . ' - ' . ($item->kavlingBaru->lokasi->nama_kavling ?? '-');

            $this->sheet->fromArray([
                $no++,
                $item->tgl_pindah ? \Carbon\Carbon::parse($item->tgl_pindah)->format('d/m/Y') : '-',
                $item->customer->nama_lengkap ?? '-',
                $lokasiLama,
                $lokasiBaru,
                $item->keterangan ?? '-',
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_pindah_unit.xlsx');
    }

    public function exportGantiNama()
    {
        $data = GantiNama::with(['customerLama', 'customerBaru', 'customerBaru.lokasi', 'customerBaru.kavling'])
            ->whereHas('customerBaru', fn ($q) => $q->where('stt_arsip', 0))
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Tanggal', 'Customer Lama', 'Customer Baru', 'Lokasi', 'Biaya', 'Keterangan'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $lokasi = ($item->customerBaru->kavling->kode_kavling ?? '-') . ' - ' . ($item->customerBaru->lokasi->nama_kavling ?? '-');

            $this->sheet->fromArray([
                $no++,
                $item->tgl_ganti ? \Carbon\Carbon::parse($item->tgl_ganti)->format('d/m/Y') : '-',
                $item->customerLama->nama_lengkap ?? '-',
                $item->customerBaru->nama_lengkap ?? '-',
                $lokasi,
                $item->biaya_ganti_nama ?? 0,
                $item->keterangan ?? '-',
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_ganti_nama.xlsx');
    }

    public function exportPembelianCancel()
    {
        $data = PembelianCancel::with(['customer', 'customer.kavling'])
            ->orderByDesc('id')
            ->get();

        $headers = ['No', 'Tanggal Pembatalan', 'Customer', 'No. Telp', 'Keterangan'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $this->sheet->fromArray([
                $no++,
                $item->tgl_batal ? \Carbon\Carbon::parse($item->tgl_batal)->format('d/m/Y') : '-',
                $item->customer->nama_lengkap ?? '-',
                $item->customer->no_telp ?? '-',
                $item->keterangan ?? '-',
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_pembelian_cancel.xlsx');
    }
}
