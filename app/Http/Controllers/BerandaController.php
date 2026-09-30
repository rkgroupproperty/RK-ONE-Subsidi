<?php

namespace App\Http\Controllers;

use App\Models\Akad;
use App\Models\AkadDetail;
use App\Models\BAST;
use App\Models\BankKPR;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\Customer;
use App\Models\MarketingOffline;
use App\Models\PengajuanHold;
use App\Models\PengaturanPengguna;
use App\Models\PPJB;
use App\Models\SPPR;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BerandaController extends Controller
{
    public function index(Request $request)
    {
        $username = Auth::user()->username;

        $periode = $this->hitungPeriode($request);
        $periodeFilter = $periode['periode_filter'];
        $filterBulan = $periode['filter_bulan'];
        $filterTahun = $periode['filter_tahun'];
        $customStart = $periode['custom_start'];
        $customEnd = $periode['custom_end'];
        $labelPeriode = $periode['label_periode'];
        $bookingFeePeriode = $periode['booking_fee_periode'];
        $customerPeriode = $periode['customer_periode'];


        $totalUnit = KavlingPeta::count();
        $unitTerjual = Customer::where('stt_arsip', 0)->count();
        $unitReady = KavlingPeta::where('status', 0)->count();
        $piutangTotal = (int) \App\Models\Piutang::sum('sisa_bayar');
        $piutangCustomer = \App\Models\Piutang::where('sisa_bayar', '>', 0)->distinct('id_customer')->count('id_customer');
        $tagihanTempoCust = \App\Models\CustomerTempo::count();
        $tagihanTempoTotal = (int) \App\Models\CustomerTempo::sum('total_harga');

        $summaryMetrics = [
            'jumlah_project' => LokasiKavling::whereIn('stt_tampil', [1, 3])->count(),
            'total_unit' => $totalUnit,
            'unit_terjual' => $unitTerjual,
            'unit_ready' => $unitReady,
            'booking_fee_periode' => $bookingFeePeriode,
            'customer_periode' => $customerPeriode,
            'label_periode' => $labelPeriode,
            'periode_filter' => $periodeFilter,
            'filter_bulan' => $filterBulan,
            'filter_tahun' => $filterTahun,
            'custom_start' => $customStart,
            'custom_end' => $customEnd,
            'piutang' => $piutangTotal,
            'piutang_customer' => $piutangCustomer,
            'tagihan_tempo_customer' => $tagihanTempoCust,
            'tagihan_tempo_total' => $tagihanTempoTotal,
        ];

        $projectStats = LokasiKavling::whereIn('stt_tampil', [1, 3])
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(function (LokasiKavling $lokasi) {
                $lokasiId = $lokasi->id;
                $totalUnit = KavlingPeta::where('id_lokasi', $lokasiId)->count();
                $booking = PengajuanHold::where('id_lokasi', $lokasiId)->where('stt_reg', '!=', 2)->count()
                    + Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 2)->where('stt_arsip', 0)->count();
                $marketing = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 11)->where('stt_arsip', 0)->count();
                $sppr = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 10)->where('stt_arsip', 0)->count()
                    + SPPR::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('customer', fn ($q) => $q->where('id_status_progres', 10))->count();
                $wawancara = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 7)->where('stt_arsip', 0)->count()
                    + Wawancara::where('status', 1)->whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('customer', fn ($q) => $q->where('id_status_progres', 7))->count();
                $accBank = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 4)->where('stt_arsip', 0)->count()
                    + WawancaraSp3k::where('status', 1)->whereHas('wawancara.customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('wawancara.customer', fn ($q) => $q->where('id_status_progres', 4))->count();
                $ppjb = PPJB::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count()
                    + Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 6)->where('stt_arsip', 0)->count();
                $akad = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 3)->where('stt_arsip', 0)->count()
                    + AkadDetail::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('customer', fn ($q) => $q->where('id_status_progres', 3))->count();
                $bast = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 5)->where('stt_arsip', 0)->count()
                    + BAST::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('customer', fn ($q) => $q->where('id_status_progres', 5))->count();
                $terjual = Customer::where('id_lokasi', $lokasiId)->where('stt_arsip', 0)->count();

                return [
                    'id' => $lokasiId,
                    'nama' => $lokasi->nama_kavling,
                    'kode' => $lokasi->nama_singkat,
                    'total_unit' => $totalUnit,
                    'booking' => $booking,
                    'marketing' => $marketing,
                    'sppr' => $sppr,
                    'wawancara' => $wawancara,
                    'acc_bank' => $accBank,
                    'ppjb' => $ppjb,
                    'akad' => $akad,
                    'bast' => $bast,
                    'terjual' => $terjual,
                ];
            });

        $projectTotals = [
            'booking' => $projectStats->sum('booking'),
            'marketing' => $projectStats->sum('marketing'),
            'sppr' => $projectStats->sum('sppr'),
            'wawancara' => $projectStats->sum('wawancara'),
            'acc_bank' => $projectStats->sum('acc_bank'),
            'ppjb' => $projectStats->sum('ppjb'),
            'akad' => $projectStats->sum('akad'),
            'bast' => $projectStats->sum('bast'),
            'terjual' => $projectStats->sum('terjual'),
        ];

        // Pipeline disamakan dengan total per project agar angka kartu dan tabel selalu sinkron
        $pipelineCounts = [
            'booking' => $projectTotals['booking'],
            'marketing' => $projectTotals['marketing'],
            'sppr' => $projectTotals['sppr'],
            'wawancara' => $projectTotals['wawancara'],
            'acc_bank' => $projectTotals['acc_bank'],
            'ppjb' => $projectTotals['ppjb'],
            'akad' => $projectTotals['akad'],
            'bast' => $projectTotals['bast'],
        ];

        $marketingStats = MarketingOffline::where('status', 1)
            ->orderBy('nama_marketing')
            ->get()
            ->map(function (MarketingOffline $marketing) {
                return [
                    'id' => $marketing->id,
                    'nama' => $marketing->nama_marketing,
                    'kode' => $marketing->kode_marketing,
                    'inisial' => mb_substr($marketing->nama_marketing, 0, 1),
                    'jumlah' => Customer::where('id_marketing', $marketing->id)
                        ->where('stt_arsip', 0)
                        ->count(),
                ];
            })
            ->sortByDesc('jumlah')
            ->values();

        $bankStats = BankKPR::orderBy('nama')
            ->get()
            ->map(function (BankKPR $bank) {
                $custBankCount = Customer::where('id_bank_kpr', $bank->id)
                    ->where('stt_arsip', 0)
                    ->count();

                $wawancaraCount = WawancaraSp3k::where('status', 1)
                    ->where('id_bank_kpr', $bank->id)
                    ->count()
                    + Wawancara::where('id_bank_kpr', $bank->id)->count();

                $jumlah = $custBankCount > 0 ? $custBankCount : $wawancaraCount;

                return [
                    'id' => $bank->id,
                    'nama' => $bank->nama,
                    'jumlah' => $jumlah,
                    'persentase' => 0,
                ];
            })
            ->filter(function ($bank) {
                return $bank['jumlah'] > 0;
            });

        $totalBankUsage = $bankStats->sum('jumlah');

        $bankStats = $bankStats->map(function ($bank) use ($totalBankUsage) {
            $bank['persentase'] = $totalBankUsage > 0 ? round(($bank['jumlah'] / $totalBankUsage) * 100) : 0;
            return $bank;
        })
        ->sortByDesc('jumlah')
        ->values();

        $availableYears = Customer::whereNotNull('tanggal_verif')
            ->selectRaw('YEAR(tanggal_verif) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        if ($availableYears->isEmpty()) {
            $availableYears = collect([2026, 2025, 2024]);
        }

        $availableMonths = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $currentYear = Carbon::now('Asia/Jakarta')->year;

        $projectResumes = $this->getProjectResumes();
        $hasilSumber = $this->getSumberProspekMatrix();
        $sumberMatrix = $hasilSumber['data'];
        $tahunAktif = $hasilSumber['tahun_aktif'];
        $tahunLalu = $hasilSumber['tahun_lalu'];

        $monthKeys = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
            5 => 'mei', 6 => 'jun', 7 => 'jul', 8 => 'aug',
            9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'des',
        ];

        $monthlyTotals = [];
        foreach ($monthKeys as $mNum => $mKey) {
            $sum = 0;
            foreach ($sumberMatrix as $src => $item) {
                $sum += ($item[$mKey] ?? 0);
            }
            $monthlyTotals[$mNum] = $sum;
        }

        $totalSpAktif = 0;
        $totalSpLalu = 0;
        foreach ($sumberMatrix as $itemSp) {
            $totalSpAktif += $itemSp['total_aktif'];
            $totalSpLalu += $itemSp['total_lalu'];
        }
        $bulanBerjalanSp = $tahunAktif === (int) Carbon::now('Asia/Jakarta')->year ? max((int) Carbon::now('Asia/Jakarta')->month, 1) : 12;
        $rataSpAktif = round($totalSpAktif / $bulanBerjalanSp, 1);
        $yoySummary = [
            'total_aktif' => $totalSpAktif,
            'total_lalu' => $totalSpLalu,
            'growth' => $totalSpAktif - $totalSpLalu,
            'growth_pct' => $totalSpLalu > 0 ? round(($totalSpAktif - $totalSpLalu) / $totalSpLalu * 100, 1) : 0,
            'growth_rata' => round($rataSpAktif - round($totalSpLalu / 12, 1), 1),
            'rata_aktif' => $rataSpAktif,
        ];
        $spTopSumber = '-';
        foreach ($sumberMatrix as $srcSp => $itemSp) {
            if ($itemSp['total_aktif'] > 0) {
                $spTopSumber = $srcSp . ' (' . $itemSp['total_aktif'] . ' unit)';
                break;
            }
        }

        return view('admin.beranda.index', compact(
            'username',
            'pipelineCounts',
            'summaryMetrics',
            'projectStats',
            'projectTotals',
            'marketingStats',
            'bankStats',
            'availableYears',
            'availableMonths',
            'currentYear',
            'projectResumes',
            'sumberMatrix',
            'monthlyTotals',
            'yoySummary',
            'tahunAktif',
            'tahunLalu',
            'spTopSumber'
        ));
    }

    public function periodeData(Request $request)
    {
        return response()->json($this->hitungPeriode($request));
    }

    private function hitungPeriode(Request $request)
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');

        $periodeFilter = $request->input('periode', 'bulan_ini');
        $filterBulan = (int) $request->input('bulan', $now->month);
        $filterTahun = (int) $request->input('tahun', $now->year);
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        $isFilteredByDate = true;

        switch ($periodeFilter) {
            case 'bulan_kemarin':
                $startCarbon = $now->copy()->subMonth()->startOfMonth();
                $endCarbon = $now->copy()->subMonth()->endOfMonth();
                $labelPeriode = 'Bulan Kemarin (' . $startCarbon->translatedFormat('F Y') . ')';
                break;
            case 'pilih_bulan':
                $startCarbon = Carbon::create($filterTahun, $filterBulan, 1, 0, 0, 0, 'Asia/Jakarta')->startOfMonth();
                $endCarbon = $startCarbon->copy()->endOfMonth();
                $labelPeriode = $startCarbon->translatedFormat('F Y');
                break;
            case 'custom':
                if ($customStart && $customEnd) {
                    $startCarbon = Carbon::parse($customStart, 'Asia/Jakarta')->startOfDay();
                    $endCarbon = Carbon::parse($customEnd, 'Asia/Jakarta')->endOfDay();
                    $labelPeriode = $startCarbon->translatedFormat('d M Y') . ' - ' . $endCarbon->translatedFormat('d M Y');
                } else {
                    $startCarbon = $now->copy()->startOfMonth();
                    $endCarbon = $now->copy()->endOfMonth();
                    $labelPeriode = 'Bulan Ini (' . $startCarbon->translatedFormat('F Y') . ')';
                }
                break;
            case 'semua':
                $isFilteredByDate = false;
                $startCarbon = null;
                $endCarbon = null;
                $labelPeriode = 'Semua Waktu';
                break;
            case 'bulan_ini':
            default:
                $periodeFilter = 'bulan_ini';
                $startCarbon = $now->copy()->startOfMonth();
                $endCarbon = $now->copy()->endOfMonth();
                $labelPeriode = 'Bulan Ini (' . $startCarbon->translatedFormat('F Y') . ')';
                break;
        }

        // Booking Fee yang benar-benar diterima pada periode terpilih (kas masuk kategori Booking Fee)
        $pemasukanBfQuery = \App\Models\Pemasukan::where('id_kategori_transaksi', 1);
        $customerPeriodeQuery = Customer::where('stt_arsip', 0);

        if ($isFilteredByDate && $startCarbon && $endCarbon) {
            $pemasukanBfQuery->whereBetween('tanggal', [$startCarbon->toDateString(), $endCarbon->toDateString()]);
            $customerPeriodeQuery->whereBetween('tanggal_verif', [$startCarbon->toDateTimeString(), $endCarbon->toDateTimeString()]);
        }

        $bookingFeePeriode = (int) $pemasukanBfQuery->sum('nominal');
        $customerPeriode = $customerPeriodeQuery->count();

        return [
            'periode_filter' => $periodeFilter,
            'filter_bulan' => $filterBulan,
            'filter_tahun' => $filterTahun,
            'custom_start' => $customStart,
            'custom_end' => $customEnd,
            'label_periode' => $labelPeriode,
            'booking_fee_periode' => $bookingFeePeriode,
            'customer_periode' => $customerPeriode,
        ];
    }

    public function getChartData(Request $request)
    {
        $tahun = $request->input('tahun', Carbon::now('Asia/Jakarta')->year);
        $status = $request->input('status', 'semua');

        $months = collect(range(1, 12))->map(function ($month) use ($tahun, $status) {
            $monthName = Carbon::create($tahun, $month, 1)->translatedFormat('M');

            $query = Customer::whereYear('tanggal_verif', $tahun)
                ->whereMonth('tanggal_verif', $month)
                ->where('stt_arsip', 0);

            if ($status !== 'semua') {
                $statusMap = [
                    'marketing' => 11,
                    'sppr' => 10,
                    'wawancara' => 7,
                    'sp3k' => 4,
                    'akad' => 3,
                    'bast' => 5,
                ];
                $query->where('id_status_progres', $statusMap[$status] ?? 0);
            }

            $count = $query->count();

            return [
                'month' => $monthName,
                'count' => $count,
            ];
        });

        return response()->json([
            'labels' => $months->pluck('month')->toArray(),
            'data' => $months->pluck('count')->toArray(),
        ]);
    }

    public function detailGrafik(Request $request)
    {
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $status = $request->input('status', 'semua');

        $statusMap = [
            'marketing' => 11,
            'sppr' => 10,
            'wawancara' => 7,
            'sp3k' => 4,
            'akad' => 3,
            'bast' => 5,
        ];

        $namaBulan = Carbon::create($tahun, $bulan, 1)->translatedFormat('F');
        $namaStatus = $status === 'semua' ? 'Semua Status' : ucfirst($status);

        $judul = "Data Penjualan Bulan $namaBulan Tahun $tahun Status $namaStatus";

        return view('admin.beranda.detail_grafik', compact('judul', 'tahun', 'bulan', 'status', 'statusMap'));
    }

    public function detailGrafikData(Request $request)
    {
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $status = $request->input('status', 'semua');

        $data = Customer::with(['marketing', 'lokasi', 'kavling', 'progres'])
            ->where('stt_arsip', 0)
            ->whereYear('tanggal_verif', $tahun)
            ->whereMonth('tanggal_verif', $bulan);

        if ($status !== 'semua') {
            $statusMap = [
                'marketing' => 11,
                'sppr' => 10,
                'wawancara' => 7,
                'sp3k' => 4,
                'akad' => 3,
                'bast' => 5,
            ];
            $data->where('id_status_progres', $statusMap[$status] ?? 0);
        }

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('tgl_terima', function ($row) {
                $tgl = $row->tanggal_verif ? Carbon::parse($row->tanggal_verif)->translatedFormat('d F Y') : '-';
                $kode = $row->kode_customer ? '<strong>' . $row->kode_customer . '</strong>' : '';
                $jenisPembelian = $row->jenis_pembelian
                    ? '<div><small><strong>' . strtoupper($row->jenis_pembelian) . '</strong></small></div>'
                    : '';
                return "$tgl<br>$kode<br>$jenisPembelian";
            })
            ->editColumn('id_marketing', function ($row) {
                return $row->marketing->nama_marketing ?? '-';
            })
            ->editColumn('id_lokasi', function ($row) {
                $namaLokasi = $row->lokasi->nama_kavling ?? '-';
                $kodeKavling = $row->kavling->kode_kavling ?? '-';
                return '<strong>' . $namaLokasi . '</strong><br> ' . $kodeKavling;
            })
            ->editColumn('id_status_progres', function ($row) {
                $status = $row->progres->status_progres ?? '-';
                $badgeColors = [
                    'Ready' => 'secondary',
                    'Booking' => 'warning',
                    'Akad' => 'info',
                    'SP3K' => 'success',
                    'Serah Terima' => 'dark',
                    'PROSES BANK' => 'primary',
                    'Pembelian Cash' => 'success',
                    'Unit Sudah Laku' => 'dark',
                    'Proses Admin' => 'info',
                    'Pemberkasan Marketing' => 'primary',
                ];
                if (array_key_exists($status, $badgeColors)) {
                    $statusDisplay = '<span class="badge bg-' . $badgeColors[$status] . '">' . $status . '</span>';
                } else {
                    $statusDisplay = $status;
                }
                return $statusDisplay;
            })
            ->editColumn('nama_lengkap', function ($row) {
                $nama = '<strong>' . $row->nama_lengkap . '</strong>';
                $wa = $row->no_telp ?? '-';
                $ktp = $row->nik ? '<span class="badge bg-info">NIK: ' . $row->nik . '</span>' : '';
                return "$nama<br>$wa<br>$ktp";
            })
            ->rawColumns(['tgl_terima', 'id_marketing', 'id_lokasi', 'id_status_progres', 'nama_lengkap'])
            ->make(true);
    }

    public function getSumberProspekData(Request $request)
    {
        $filterBulan = $request->input('bulan', 'semua');
        $hasilSumber = $this->getSumberProspekMatrix();
        $sumberMatrix = $hasilSumber['data'];
        $tahunAktif = $hasilSumber['tahun_aktif'];

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthKeys = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
            5 => 'mei', 6 => 'jun', 7 => 'jul', 8 => 'aug',
            9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'des',
        ];

        $labels = [];
        $values = [];

        if ($filterBulan !== 'semua' && isset($monthKeys[(int)$filterBulan])) {
            $mKey = $monthKeys[(int)$filterBulan];
            $monthName = $monthNames[(int)$filterBulan];
            $periodeLabel = "Bulan $monthName $tahunAktif";

            $temp = [];
            foreach ($sumberMatrix as $src => $data) {
                $temp[$src] = $data[$mKey] ?? 0;
            }
            arsort($temp);
            $labels = array_keys($temp);
            $values = array_values($temp);
        } else {
            $periodeLabel = 'Tahun ' . $tahunAktif . ' (Semua Bulan)';
            $temp = [];
            foreach ($sumberMatrix as $src => $data) {
                $temp[$src] = $data['total_aktif'];
            }
            arsort($temp);
            $labels = array_keys($temp);
            $values = array_values($temp);
        }

        $total = array_sum($values);
        $topSource = '-';
        if ($total > 0 && count($labels) > 0 && $values[0] > 0) {
            $topSource = $labels[0] . ' (' . $values[0] . ' unit)';
        }

        return response()->json([
            'labels'        => $labels,
            'data'          => $values,
            'combined'      => $values,
            'total'         => $total,
            'top_source'    => $topSource,
            'periode_label' => $periodeLabel,
            'filter_bln'    => $filterBulan,
        ]);
    }

    private function getSumberProspekMatrix()
    {
        $tahunAktif = (int) Carbon::now('Asia/Jakarta')->year;
        $tahunLalu = $tahunAktif - 1;

        $monthKeys = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
            5 => 'mei', 6 => 'jun', 7 => 'jul', 8 => 'aug',
            9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'des',
        ];
        $monthCols = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'];

        $rows = Customer::where('stt_arsip', 0)
            ->whereNotNull('tanggal_verif')
            ->whereYear('tanggal_verif', '>=', $tahunLalu)
            ->select('sumber_prospek', DB::raw('YEAR(tanggal_verif) as thn'), DB::raw('MONTH(tanggal_verif) as bln'), DB::raw('COUNT(*) as total'))
            ->groupBy('sumber_prospek', 'thn', 'bln')
            ->get();

        $base = [];
        foreach ($rows as $row) {
            $src = trim((string) ($row->sumber_prospek ?? ''));
            if ($src === '') {
                $src = '(Tanpa Sumber)';
            }
            if (! isset($base[$src])) {
                $base[$src] = array_fill_keys($monthCols, 0) + ['total_lalu' => 0, 'total_aktif' => 0, 'rata_rata' => 0, 'growth' => 0, 'growth_rata' => 0];
            }
            if ((int) $row->thn === $tahunAktif) {
                $mKey = $monthKeys[(int) $row->bln] ?? null;
                if ($mKey) {
                    $base[$src][$mKey] += (int) $row->total;
                }
            } elseif ((int) $row->thn === $tahunLalu) {
                $base[$src]['total_lalu'] += (int) $row->total;
            }
        }

        $bulanBerjalan = $tahunAktif === (int) Carbon::now('Asia/Jakarta')->year ? (int) Carbon::now('Asia/Jakarta')->month : 12;
        $bulanBerjalan = max($bulanBerjalan, 1);

        foreach ($base as $k => &$item) {
            $sum = 0;
            foreach ($monthCols as $mk) {
                $sum += $item[$mk];
            }
            $item['total_aktif'] = $sum;
            $item['rata_rata'] = round($sum / $bulanBerjalan, 1);
            $item['growth'] = $sum - $item['total_lalu'];
            $item['growth_rata'] = round($item['rata_rata'] - round($item['total_lalu'] / 12, 1), 1);
        }
        unset($item);

        uasort($base, fn($a, $b) => $b['total_aktif'] <=> $a['total_aktif']);

        return ['data' => $base, 'tahun_aktif' => $tahunAktif, 'tahun_lalu' => $tahunLalu];
    }

    private function getProjectResumes()
    {
        $configs = [
            'bir4' => [
                'id' => 2,
                'nama' => 'Bukit Intan Residence Tahap 4',
                'short_name' => 'BIR Tahap 4',
                'badge' => 'Tahap 4',
                'default_total' => 322,
                'catatan' => 'Belum terjual rumah dibangun (Kantor Pemasaran 2 unit)',
                'fisik_ref' => ['SP3K' => 4, 'Proses Bank' => 6, 'Admin' => 1, 'Marketing' => 15, 'Belum Terjual' => 14],
            ],
            'bir2' => [
                'id' => 1,
                'nama' => 'Bukit Intan Residence 2',
                'short_name' => 'BIR 2',
                'badge' => 'Tahap 2',
                'default_total' => 268,
                'catatan' => 'Belum terjual rumah dibangun (Kantor Pemasaran 2 unit) · Jumlah unit blok A1 dan A2 = 37 unit',
                'fisik_ref' => ['SP3K' => 2, 'Proses Bank' => 0, 'Admin' => 4, 'Marketing' => 9, 'Belum Terjual' => 30],
            ],
            'art3' => [
                'id' => 3,
                'nama' => 'Alzafa Residence Tahap 3',
                'short_name' => 'Alzafa T3',
                'badge' => 'Tahap 3',
                'default_total' => 244,
                'catatan' => 'Belum terjual rumah dibangun (Kantor Pemasaran 1 unit)',
                'fisik_ref' => ['SP3K' => 0, 'Proses Bank' => 0, 'Admin' => 0, 'Marketing' => 0, 'Belum Terjual' => 0],
            ],
        ];

        $resumes = [];
        $totalAllUnits = 0;
        $totalAllTerjual = 0;
        $totalAllAkad = 0;
        $totalAllCash = 0;
        $totalAllProses = 0;
        $totalAllFisik = 0;
        $allProsesItems = [
            'SP3K' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-file-invoice', 'color' => '#8b5cf6'],
            'Proses Bank' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-building-columns', 'color' => '#f97316'],
            'Admin' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-user-tie', 'color' => '#06b6d4'],
            'Marketing' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-bullhorn', 'color' => '#3b82f6'],
            'Belum Terjual (Rumah Dibangun)' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-home', 'color' => '#ec4899'],
        ];

        foreach ($configs as $key => $cfg) {
            $lokasiId = $cfg['id'];
            $totalUnit = KavlingPeta::where('id_lokasi', $lokasiId)->count();
            if ($totalUnit <= 0) $totalUnit = $cfg['default_total'];

            $akad = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 3)->where('stt_arsip', 0)->count()
                + AkadDetail::whereHas('customer', fn ($q) => $q->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('customer', fn ($q) => $q->where('id_status_progres', 3))->count();

            $cash = Customer::where('id_lokasi', $lokasiId)->where('stt_arsip', 0)->whereIn('jenis_pembelian', ['Pembelian Cash', 'Cash Bertahap'])->count();

            $sp3k = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 4)->where('stt_arsip', 0)->count()
                + WawancaraSp3k::where('status', 1)->whereHas('wawancara.customer', fn ($q) => $q->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('wawancara.customer', fn ($q) => $q->where('id_status_progres', 4))->count();

            $bank = Customer::where('id_lokasi', $lokasiId)->whereIn('id_status_progres', [7, 10])->where('stt_arsip', 0)->count()
                + SPPR::whereHas('customer', fn ($q) => $q->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('customer', fn ($q) => $q->where('id_status_progres', 10))->count()
                + Wawancara::where('status', 1)->whereHas('customer', fn ($q) => $q->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->whereDoesntHave('customer', fn ($q) => $q->where('id_status_progres', 7))->count();

            $admin = Customer::where('id_lokasi', $lokasiId)->whereIn('id_status_progres', [1, 8])->where('stt_arsip', 0)->count();

            $marketing = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 11)->where('stt_arsip', 0)->count()
                + PengajuanHold::where('id_lokasi', $lokasiId)->where('stt_reg', '!=', 2)->count();

            $terjual = Customer::where('id_lokasi', $lokasiId)->where('stt_arsip', 0)->count();
            if ($terjual < ($akad + $cash)) $terjual = $akad + $cash;

            $onProses = $sp3k + $bank + $admin + $marketing;
            $sisaUnit = max(0, $totalUnit - $terjual);

            $pctTerjual = $totalUnit > 0 ? round(($terjual / $totalUnit) * 100, 1) : 0;
            $pctProses = $totalUnit > 0 ? round(($onProses / $totalUnit) * 100, 1) : 0;
            $pctSisa = $totalUnit > 0 ? round(($sisaUnit / $totalUnit) * 100, 1) : 0;

            $fSP3K = min($sp3k, $cfg['fisik_ref']['SP3K']);
            $fBank = min($bank, $cfg['fisik_ref']['Proses Bank']);
            $fAdmin = min($admin, $cfg['fisik_ref']['Admin']);
            $fMkt = min($marketing, $cfg['fisik_ref']['Marketing']);
            $fBelum = $cfg['fisik_ref']['Belum Terjual'];
            $totalFisik = $fSP3K + $fBank + $fAdmin + $fMkt + $fBelum;

            $prosesItems = [
                ['label' => 'SP3K', 'unit' => $sp3k, 'fisik' => $fSP3K, 'icon' => 'fa-file-invoice', 'color' => '#8b5cf6'],
                ['label' => 'Proses Bank', 'unit' => $bank, 'fisik' => $fBank, 'icon' => 'fa-building-columns', 'color' => '#f97316'],
                ['label' => 'Admin', 'unit' => $admin, 'fisik' => $fAdmin, 'icon' => 'fa-user-tie', 'color' => '#06b6d4'],
                ['label' => 'Marketing', 'unit' => $marketing, 'fisik' => $fMkt, 'icon' => 'fa-bullhorn', 'color' => '#3b82f6'],
                ['label' => 'Belum Terjual (Rumah Dibangun)', 'unit' => $fBelum, 'fisik' => $fBelum, 'icon' => 'fa-home', 'color' => '#ec4899'],
            ];

            $resumes[$key] = [
                'id' => $lokasiId,
                'nama' => $cfg['nama'],
                'short_name' => $cfg['short_name'],
                'badge' => $cfg['badge'],
                'total_unit' => $totalUnit,
                'total_terjual' => $terjual,
                'persentase_terjual' => $pctTerjual,
                'kpr_akad' => $akad,
                'terjual_cash' => $cash,
                'on_proses' => $onProses,
                'persentase_proses' => $pctProses,
                'sisa_unit' => $sisaUnit,
                'persentase_sisa' => $pctSisa,
                'proses_items' => $prosesItems,
                'total_unit_proses' => $onProses,
                'total_fisik_proses' => $totalFisik,
                'catatan' => $cfg['catatan'],
            ];

            $totalAllUnits += $totalUnit;
            $totalAllTerjual += $terjual;
            $totalAllAkad += $akad;
            $totalAllCash += $cash;
            $totalAllProses += $onProses;
            $totalAllFisik += $totalFisik;

            $allProsesItems['SP3K']['unit'] += $sp3k;
            $allProsesItems['SP3K']['fisik'] += $fSP3K;
            $allProsesItems['Proses Bank']['unit'] += $bank;
            $allProsesItems['Proses Bank']['fisik'] += $fBank;
            $allProsesItems['Admin']['unit'] += $admin;
            $allProsesItems['Admin']['fisik'] += $fAdmin;
            $allProsesItems['Marketing']['unit'] += $marketing;
            $allProsesItems['Marketing']['fisik'] += $fMkt;
            $allProsesItems['Belum Terjual (Rumah Dibangun)']['unit'] += $fBelum;
            $allProsesItems['Belum Terjual (Rumah Dibangun)']['fisik'] += $fBelum;
        }

        $allProsesList = [];
        foreach ($allProsesItems as $lbl => $item) {
            $allProsesList[] = [
                'label' => $lbl,
                'unit' => $item['unit'],
                'fisik' => $item['fisik'],
                'icon' => $item['icon'],
                'color' => $item['color'],
            ];
        }

        $resumes['all'] = [
            'id' => 'all',
            'nama' => 'Total Keseluruhan Proyek',
            'short_name' => 'Semua Proyek',
            'badge' => 'Konsolidasi',
            'total_unit' => $totalAllUnits,
            'total_terjual' => $totalAllTerjual,
            'persentase_terjual' => $totalAllUnits > 0 ? round(($totalAllTerjual / $totalAllUnits) * 100, 1) : 0,
            'kpr_akad' => $totalAllAkad,
            'terjual_cash' => $totalAllCash,
            'on_proses' => $totalAllProses,
            'persentase_proses' => $totalAllUnits > 0 ? round(($totalAllProses / $totalAllUnits) * 100, 1) : 0,
            'sisa_unit' => max(0, $totalAllUnits - $totalAllTerjual),
            'persentase_sisa' => $totalAllUnits > 0 ? round((max(0, $totalAllUnits - $totalAllTerjual) / $totalAllUnits) * 100, 1) : 0,
            'proses_items' => $allProsesList,
            'total_unit_proses' => $totalAllProses,
            'total_fisik_proses' => $totalAllFisik,
            'catatan' => 'Total konsolidasi seluruh Perumahan Aktif (sinkron otomatis dari database)',
        ];

        return $resumes;
    }

    public function adminPemberkasanData(Request $request)
    {
        $tahun = $request->input('tahun', Carbon::now('Asia/Jakarta')->year);
        $status = $request->input('status', 'semua');

        $statusMap = [
            'marketing' => 11,
            'sppr'      => 10,
            'wawancara' => 7,
            'sp3k'      => 4,
            'akad'      => 3,
        ];

        $allAdmins = \App\Models\AdminPemberkasan::where('status', 1)->orderBy('nama_lengkap')->get();

        $query = Customer::where('stt_arsip', 0)
            ->whereNotNull('id_admin_pemberkasan');

        if ($tahun !== 'semua') {
            $query->where(function ($q) use ($tahun) {
                $q->whereYear('tanggal_verif', $tahun)
                  ->orWhereNull('tanggal_verif');
            });
        }

        if ($status !== 'semua') {
            $query->where('id_status_progres', $statusMap[$status] ?? 0);
        }

        $adminCounts = $query->select('id_admin_pemberkasan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('id_admin_pemberkasan')
            ->pluck('jumlah', 'id_admin_pemberkasan')
            ->toArray();

        $labels = [];
        $values = [];
        $ids = [];

        foreach ($allAdmins as $admin) {
            $labels[] = $admin->nama_lengkap;
            $values[] = $adminCounts[$admin->id] ?? 0;
            $ids[] = $admin->id;
        }

        return response()->json([
            'labels' => $labels,
            'data'   => $values,
            'ids'    => $ids,
        ]);
    }

    public function detailCustomerMarketing($id)
    {
        $marketing = MarketingOffline::findOrFail($id);
        return view('admin.beranda.detail_customer_marketing', compact('marketing'));
    }

    public function detailCustomerMarketingData(Request $request, $id)
    {
        $data = Customer::with(['marketing', 'lokasi', 'kavling', 'progres'])
            ->where('stt_arsip', 0)
            ->where('id_marketing', $id);

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('tgl_terima', function ($row) {
                $tgl = $row->tanggal_verif ? Carbon::parse($row->tanggal_verif)->translatedFormat('d F Y') : '-';
                $kode = $row->kode_customer ? '<strong>' . $row->kode_customer . '</strong>' : '';
                $jenisPembelian = $row->jenis_pembelian
                    ? '<div><small><strong>' . strtoupper($row->jenis_pembelian) . '</strong></small></div>'
                    : '';
                return "$tgl<br>$kode<br>$jenisPembelian";
            })
            ->editColumn('id_marketing', function ($row) {
                return $row->marketing->nama_marketing ?? '-';
            })
            ->editColumn('id_lokasi', function ($row) {
                $namaLokasi = $row->lokasi->nama_kavling ?? '-';
                $kodeKavling = $row->kavling->kode_kavling ?? '-';
                return '<strong>' . $namaLokasi . '</strong><br> ' . $kodeKavling;
            })
            ->editColumn('id_status_progres', function ($row) {
                $status = $row->progres->status_progres ?? '-';
                $badgeColors = [
                    'Ready' => 'secondary',
                    'Booking' => 'warning',
                    'Akad' => 'info',
                    'SP3K' => 'success',
                    'Serah Terima' => 'dark',
                    'PROSES BANK' => 'primary',
                    'Pembelian Cash' => 'success',
                    'Unit Sudah Laku' => 'dark',
                    'Proses Admin' => 'info',
                    'Pemberkasan Marketing' => 'primary',
                ];
                if (array_key_exists($status, $badgeColors)) {
                    return '<span class="badge bg-' . $badgeColors[$status] . '">' . $status . '</span>';
                }
                return $status;
            })
            ->editColumn('nama_lengkap', function ($row) {
                $nama = '<strong>' . $row->nama_lengkap . '</strong>';
                $wa = $row->no_telp ?? '-';
                $ktp = $row->nik ? '<span class="badge bg-info">NIK: ' . $row->nik . '</span>' : '';
                return "$nama<br>$wa<br>$ktp";
            })
            ->rawColumns(['tgl_terima', 'id_marketing', 'id_lokasi', 'id_status_progres', 'nama_lengkap'])
            ->make(true);
    }

    public function detailCustomerAdminPemberkasan($id)
    {
        $admin = \App\Models\AdminPemberkasan::findOrFail($id);
        return view('admin.beranda.detail_customer_admin_pemberkasan', compact('admin'));
    }

    public function detailCustomerAdminPemberkasanData(Request $request, $id)
    {
        $data = Customer::with(['marketing', 'lokasi', 'kavling', 'progres'])
            ->where('stt_arsip', 0)
            ->where('id_admin_pemberkasan', $id);

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('tgl_terima', function ($row) {
                $tgl = $row->tanggal_verif ? Carbon::parse($row->tanggal_verif)->translatedFormat('d F Y') : '-';
                $kode = $row->kode_customer ? '<strong>' . $row->kode_customer . '</strong>' : '';
                $jenisPembelian = $row->jenis_pembelian
                    ? '<div><small><strong>' . strtoupper($row->jenis_pembelian) . '</strong></small></div>'
                    : '';
                return "$tgl<br>$kode<br>$jenisPembelian";
            })
            ->editColumn('id_marketing', function ($row) {
                return $row->marketing->nama_marketing ?? '-';
            })
            ->editColumn('id_lokasi', function ($row) {
                $namaLokasi = $row->lokasi->nama_kavling ?? '-';
                $kodeKavling = $row->kavling->kode_kavling ?? '-';
                return '<strong>' . $namaLokasi . '</strong><br> ' . $kodeKavling;
            })
            ->editColumn('id_status_progres', function ($row) {
                $status = $row->progres->status_progres ?? '-';
                $badgeColors = [
                    'Ready' => 'secondary',
                    'Booking' => 'warning',
                    'Akad' => 'info',
                    'SP3K' => 'success',
                    'Serah Terima' => 'dark',
                    'PROSES BANK' => 'primary',
                    'Pembelian Cash' => 'success',
                    'Unit Sudah Laku' => 'dark',
                    'Proses Admin' => 'info',
                    'Pemberkasan Marketing' => 'primary',
                ];
                if (array_key_exists($status, $badgeColors)) {
                    return '<span class="badge bg-' . $badgeColors[$status] . '">' . $status . '</span>';
                }
                return $status;
            })
            ->editColumn('nama_lengkap', function ($row) {
                $nama = '<strong>' . $row->nama_lengkap . '</strong>';
                $wa = $row->no_telp ?? '-';
                $ktp = $row->nik ? '<span class="badge bg-info">NIK: ' . $row->nik . '</span>' : '';
                return "$nama<br>$wa<br>$ktp";
            })
            ->rawColumns(['tgl_terima', 'id_marketing', 'id_lokasi', 'id_status_progres', 'nama_lengkap'])
            ->make(true);
    }
}