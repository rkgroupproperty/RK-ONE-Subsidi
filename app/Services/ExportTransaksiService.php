<?php

namespace App\Services;

use App\Models\Akad;
use App\Models\AkadDetail;
use App\Models\BAST;
use App\Models\Customer;
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
        $query = SPPR::with(['customer.lokasi', 'customer.kavling', 'customer.marketing', 'customer.progres', 'customer.adminPemberkasan', 'marketing'])
            ->whereHas('customer', function ($q) {
                $q->where('stt_arsip', 0)->where('id_status_progres', 10);
                if (request()->filled('id_lokasi')) {
                    $q->where('id_lokasi', request('id_lokasi'));
                }
                if (request()->filled('id_marketing')) {
                    $q->where('id_marketing', request('id_marketing'));
                }
                if (request()->filled('id_admin_pemberkasan')) {
                    $q->where('id_admin_pemberkasan', request('id_admin_pemberkasan'));
                }
            })
            ->orderByDesc('id');

        $data = $query->get();

        $headers = ['No', 'Tanggal Proses', 'Nama Nasabah', 'No Telp', 'Marketing', 'Admin Pemberkasan', 'Lokasi / Perumahan', 'Blok / Unit', 'Status Progres'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $customer = $item->customer;
            $marketing = $customer->marketing->nama_marketing ?? $item->marketing->nama_marketing ?? 'Non Marketing';
            $admin = $customer->adminPemberkasan->nama_lengkap ?? '-';
            $tglRaw = ($customer && $customer->tanggal_verif) ? $customer->tanggal_verif : $item->created_at;
            $tgl = $tglRaw ? \Carbon\Carbon::parse($tglRaw)->format('d/m/Y') : '-';

            $this->sheet->fromArray([
                $no++,
                $tgl,
                $item->nama ?? $customer->nama_lengkap ?? '-',
                $item->no_telp ?? $customer->no_telp ?? '-',
                $marketing,
                $admin,
                $customer->lokasi->nama_kavling ?? '-',
                ($item->blok ? $item->blok . ' ' . $item->no : ($customer->kavling->kode_kavling ?? '-')),
                'Proses Admin',
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

    public function exportProsesMarketing()
    {
        $query = Customer::with(['lokasi', 'kavling', 'marketing', 'progres'])
            ->where('stt_arsip', 0)
            ->where('id_status_progres', 11);

        if (request()->filled('id_lokasi')) {
            $query->where('id_lokasi', request('id_lokasi'));
        }
        if (request()->filled('id_marketing')) {
            $query->where('id_marketing', request('id_marketing'));
        }

        $data = $query->orderByDesc('id')->get();

        $headers = ['No', 'Tanggal Verif', 'Nama Nasabah', 'No Telp', 'Marketing', 'Lokasi / Perumahan', 'Blok / Unit', 'Status Progres'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $marketing = $item->marketing->nama_marketing ?? 'Non Marketing';
            $tgl = $item->tanggal_verif ? \Carbon\Carbon::parse($item->tanggal_verif)->format('d/m/Y') : '-';

            $this->sheet->fromArray([
                $no++,
                $tgl,
                $item->nama_lengkap ?? '-',
                $item->no_telp ?? '-',
                $marketing,
                $item->lokasi->nama_kavling ?? '-',
                $item->kavling->kode_kavling ?? '-',
                $item->progres->status_progres ?? 'Pemberkasan Marketing',
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_pemberkasan_marketing.xlsx');
    }

    public function exportWawancara()
    {
        $query = Wawancara::with(['customer.lokasi', 'customer.kavling', 'customer.marketing', 'customer.progres', 'bankKPR'])
            ->where('status', 1)
            ->whereHas('customer', function ($q) {
                $q->where('stt_arsip', 0)->where('id_status_progres', 7);
                if (request()->filled('id_lokasi')) {
                    $q->where('id_lokasi', request('id_lokasi'));
                }
                if (request()->filled('id_marketing')) {
                    $q->where('id_marketing', request('id_marketing'));
                }
            });

        if (request()->filled('id_bank_kpr')) {
            $query->where('id_bank_kpr', request('id_bank_kpr'));
        }

        $data = $query->orderByDesc('id')->get();

        $headers = ['No', 'Tanggal Wawancara', 'Nama Nasabah', 'No Telp', 'Marketing', 'Lokasi / Perumahan', 'Blok / Unit', 'Bank KPR', 'Status Progres', 'Catatan'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $customer = $item->customer;
            $marketing = $customer->marketing->nama_marketing ?? 'Non Marketing';
            $catatan = strip_tags($item->catatan_wawancara ?? '-');

            $this->sheet->fromArray([
                $no++,
                $item->tgl_wawancara ? \Carbon\Carbon::parse($item->tgl_wawancara)->format('d/m/Y') : '-',
                $customer->nama_lengkap ?? '-',
                $customer->no_telp ?? '-',
                $marketing,
                $customer->lokasi->nama_kavling ?? '-',
                $customer->kavling->kode_kavling ?? '-',
                $item->bankKPR->nama ?? '-',
                'Proses Bank',
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
        $query = WawancaraSp3k::with(['wawancara.customer.lokasi', 'wawancara.customer.kavling', 'wawancara.customer.marketing', 'wawancara.customer.progres', 'bankKPR'])
            ->where('status', 1)
            ->whereHas('wawancara.customer', function ($q) {
                $q->where('stt_arsip', 0)->where('id_status_progres', 4);
                if (request()->filled('id_lokasi')) {
                    $q->where('id_lokasi', request('id_lokasi'));
                }
                if (request()->filled('id_marketing')) {
                    $q->where('id_marketing', request('id_marketing'));
                }
            });

        if (request()->filled('id_bank_kpr')) {
            $query->where('id_bank_kpr', request('id_bank_kpr'));
        }

        $data = $query->orderByDesc('id')->get();

        $headers = ['No', 'Tgl Terbit SP3K', 'Nama Nasabah', 'No Telp', 'Marketing', 'Lokasi / Perumahan', 'Blok / Unit', 'Bank KPR', 'ACC Plafon', 'Tgl Expired', 'Sisa Hari', 'Status Progres'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $customer = $item->wawancara->customer ?? null;
            $marketing = $customer->marketing->nama_marketing ?? 'Non Marketing';
            $tglExpired = $item->tgl_expired ? \Carbon\Carbon::parse($item->tgl_expired) : null;
            $sisaHari = '-';
            if ($tglExpired) {
                $selisih = \Carbon\Carbon::now()->diffInDays($tglExpired, false);
                $sisaHari = $selisih < 0 ? 'Kadaluarsa' : (int) $selisih . ' hari';
            }

            $this->sheet->fromArray([
                $no++,
                $item->tgl_terbit_sp3k ? \Carbon\Carbon::parse($item->tgl_terbit_sp3k)->format('d/m/Y') : '-',
                $customer->nama_lengkap ?? '-',
                $customer->no_telp ?? '-',
                $marketing,
                $customer->lokasi->nama_kavling ?? '-',
                $customer->kavling->kode_kavling ?? '-',
                $item->bankKPR->nama ?? '-',
                $item->acc_plafon ?? 0,
                $item->tgl_expired ? $tglExpired->format('d/m/Y') : '-',
                $sisaHari,
                'SP3K Terbit',
            ], null, "A{$row}");
            $row++;
        }

        $colCount = count($headers);
        $this->styleHeader($colCount);
        if ($row > 2) {
            $this->styleCells($colCount, $row - 1);
        }
        $this->autoWidth($colCount);

        return $this->output('data_sp3k.xlsx');
    }

    public function exportPPJB()
    {
        $query = PPJB::with(['customer.lokasi', 'customer.kavling', 'customer.marketing', 'customer.progres'])
            ->whereHas('customer', function ($q) {
                $q->where('stt_arsip', 0)->where('id_status_progres', 6);
                if (request()->filled('id_lokasi')) {
                    $q->where('id_lokasi', request('id_lokasi'));
                }
                if (request()->filled('id_marketing')) {
                    $q->where('id_marketing', request('id_marketing'));
                }
            })
            ->orderByDesc('id');

        $data = $query->get();

        $headers = ['No', 'Tanggal PPJB', 'No. PPJB', 'Nama Nasabah', 'No Telp', 'Marketing', 'Lokasi / Perumahan', 'Blok / Unit', 'Termin', 'Status Progres'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $customer = $item->customer;
            $marketing = $customer->marketing->nama_marketing ?? 'Non Marketing';

            $this->sheet->fromArray([
                $no++,
                $item->tanggal_ppjb ? \Carbon\Carbon::parse($item->tanggal_ppjb)->format('d/m/Y') : '-',
                $item->no_ppjb ?? '-',
                $customer->nama_lengkap ?? '-',
                $customer->no_telp ?? '-',
                $marketing,
                $customer->lokasi->nama_kavling ?? '-',
                $customer->kavling->kode_kavling ?? '-',
                $item->termin ?? 0,
                'PPJB',
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
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $filter = request()->get('filter', 'bulan_ini');

        $query = AkadDetail::with(['customer.lokasi', 'customer.kavling', 'customer.marketing', 'customer.progres', 'akad'])
            ->whereHas('customer', function ($q) {
                $q->where('stt_arsip', 0);
                if (request()->filled('id_lokasi')) {
                    $q->where('id_lokasi', request('id_lokasi'));
                }
                if (request()->filled('id_marketing')) {
                    $q->where('id_marketing', request('id_marketing'));
                }
            });

        if ($filter === 'semua') {
            // all
        } elseif ($filter === 'bulan_kemarin') {
            $lastMonth = $now->copy()->subMonth();
            $query->whereHas('akad', function ($q) use ($lastMonth) {
                $q->whereYear('tgl_akad', $lastMonth->year)->whereMonth('tgl_akad', $lastMonth->month);
            });
        } elseif ($filter === 'pilih_bulan' && request()->filled('bulan') && request()->filled('tahun')) {
            $b = (int) request('bulan');
            $t = (int) request('tahun');
            $query->whereHas('akad', function ($q) use ($b, $t) {
                $q->whereYear('tgl_akad', $t)->whereMonth('tgl_akad', $b);
            });
        } elseif ($filter === 'custom' && request()->filled('start_date') && request()->filled('end_date')) {
            $s = \Carbon\Carbon::parse(request('start_date'))->startOfDay()->toDateString();
            $e = \Carbon\Carbon::parse(request('end_date'))->endOfDay()->toDateString();
            $query->whereHas('akad', function ($q) use ($s, $e) {
                $q->whereBetween('tgl_akad', [$s, $e]);
            });
        } else {
            $query->whereHas('akad', function ($q) use ($now) {
                $q->whereYear('tgl_akad', $now->year)->whereMonth('tgl_akad', $now->month);
            });
        }

        $query->orderByDesc('id');
        $data = $query->get();

        if ($data->isEmpty()) {
            $akadQuery = Akad::query();
            if ($filter === 'semua') {
                // all
            } elseif ($filter === 'bulan_kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $akadQuery->whereYear('tgl_akad', $lastMonth->year)->whereMonth('tgl_akad', $lastMonth->month);
            } elseif ($filter === 'pilih_bulan' && request()->filled('bulan') && request()->filled('tahun')) {
                $b = (int) request('bulan');
                $t = (int) request('tahun');
                $akadQuery->whereYear('tgl_akad', $t)->whereMonth('tgl_akad', $b);
            } elseif ($filter === 'custom' && request()->filled('start_date') && request()->filled('end_date')) {
                $s = \Carbon\Carbon::parse(request('start_date'))->startOfDay()->toDateString();
                $e = \Carbon\Carbon::parse(request('end_date'))->endOfDay()->toDateString();
                $akadQuery->whereBetween('tgl_akad', [$s, $e]);
            } else {
                $akadQuery->whereYear('tgl_akad', $now->year)->whereMonth('tgl_akad', $now->month);
            }

            $dataAkad = $akadQuery->orderByDesc('tgl_akad')->orderByDesc('id')->get();
            $headers = ['No', 'Jadwal Akad', 'Total Akad', 'Keterangan'];
            $this->sheet->fromArray($headers, null, 'A1');
            $row = 2;
            $no = 1;
            foreach ($dataAkad as $item) {
                $totalAkad = AkadDetail::where('id_akad', $item->id)->count();
                $this->sheet->fromArray([
                    $no++,
                    $item->tgl_akad ? \Carbon\Carbon::parse($item->tgl_akad)->format('d/m/Y') : '-',
                    $totalAkad,
                    $item->keterangan ?? '-',
                ], null, "A{$row}");
                $row++;
            }
        } else {
            $headers = ['No', 'Tanggal Akad', 'Nama Nasabah', 'No Telp', 'Marketing', 'Lokasi / Perumahan', 'Blok / Unit', 'Status Kehadiran', 'Keterangan'];
            $this->sheet->fromArray($headers, null, 'A1');

            $row = 2;
            $no = 1;
            foreach ($data as $item) {
                $customer = $item->customer;
                $marketing = $customer->marketing->nama_marketing ?? 'Non Marketing';
                $tglAkad = ($item->akad && $item->akad->tgl_akad) ? \Carbon\Carbon::parse($item->akad->tgl_akad)->format('d/m/Y') : '-';
                $statusHadir = ($item->status == 2) ? 'Hadir' : (($item->status == 1) ? 'Belum Hadir' : '-');

                $this->sheet->fromArray([
                    $no++,
                    $tglAkad,
                    $customer->nama_lengkap ?? '-',
                    $customer->no_telp ?? '-',
                    $marketing,
                    $customer->lokasi->nama_kavling ?? '-',
                    $customer->kavling->kode_kavling ?? '-',
                    $statusHadir,
                    $item->keterangan ?? '-',
                ], null, "A{$row}");
                $row++;
            }
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
        $query = BAST::with(['customer.lokasi', 'customer.kavling', 'customer.marketing', 'customer.progres'])
            ->whereHas('customer', function ($q) {
                $q->where('stt_arsip', 0)->where('id_status_progres', 5);
                if (request()->filled('id_lokasi')) {
                    $q->where('id_lokasi', request('id_lokasi'));
                }
                if (request()->filled('id_marketing')) {
                    $q->where('id_marketing', request('id_marketing'));
                }
            })
            ->orderByDesc('id');

        $data = $query->get();

        $headers = ['No', 'Tanggal BAST', 'No. BAST', 'Nama Nasabah', 'No Telp', 'Marketing', 'Lokasi / Perumahan', 'Blok / Unit', 'Status Progres'];
        $this->sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $customer = $item->customer;
            $marketing = $customer->marketing->nama_marketing ?? 'Non Marketing';

            $this->sheet->fromArray([
                $no++,
                $item->tanggal_bast ? \Carbon\Carbon::parse($item->tanggal_bast)->format('d/m/Y') : '-',
                $item->no_bast ?? '-',
                $customer->nama_lengkap ?? '-',
                $customer->no_telp ?? '-',
                $marketing,
                $customer->lokasi->nama_kavling ?? '-',
                $customer->kavling->kode_kavling ?? '-',
                'BAST',
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
