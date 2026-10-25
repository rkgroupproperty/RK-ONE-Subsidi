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

        // Hitung Booking Fee & Customer pada periode terpilih
        $bookingFeeQuery = PengajuanHold::query();
        $pemasukanBfQuery = \App\Models\Pemasukan::where('id_kategori_transaksi', 1);
        $customerPeriodeQuery = Customer::where('stt_arsip', 0);

        if ($isFilteredByDate && $startCarbon && $endCarbon) {
            $bookingFeeQuery->whereBetween('tgl_booking', [$startCarbon->toDateTimeString(), $endCarbon->toDateTimeString()]);
            $pemasukanBfQuery->whereBetween('tanggal', [$startCarbon->toDateString(), $endCarbon->toDateString()]);
            $customerPeriodeQuery->whereBetween('tanggal_verif', [$startCarbon->toDateTimeString(), $endCarbon->toDateTimeString()]);
        }

        $bookingFeePeriode = (int) $bookingFeeQuery->sum('booking_fee') + (int) $pemasukanBfQuery->sum('nominal');
        $customerPeriode = $customerPeriodeQuery->count();

        // Auto-reconciliation: Pastikan alur status progres konsumen selalu ngeflow sinkron dengan transaksi riil
        try {
            \App\Http\Controllers\PengajuanHoldController::ensureKantorMarketing();

            $bastCustIds = BAST::pluck('id_customer')->filter()->toArray();
            if (!empty($bastCustIds)) {
                Customer::whereIn('id', $bastCustIds)->where('id_status_progres', '!=', 5)->update(['id_status_progres' => 5]);
            }

            $akadCustIds = AkadDetail::where('status', 2)->pluck('id_customer')->filter()->toArray();
            if (!empty($akadCustIds)) {
                Customer::whereIn('id', $akadCustIds)
                    ->whereNotIn('id', $bastCustIds)
                    ->where('id_status_progres', '!=', 3)
                    ->update(['id_status_progres' => 3]);
            }

            $ppjbCustIds = PPJB::pluck('id_customer')->filter()->toArray();
            if (!empty($ppjbCustIds)) {
                Customer::whereIn('id', $ppjbCustIds)
                    ->whereNotIn('id', $bastCustIds)
                    ->whereNotIn('id', $akadCustIds)
                    ->where('id_status_progres', '!=', 6)
                    ->update(['id_status_progres' => 6]);
            }

            $sp3kCustIds = WawancaraSp3k::where('status', 1)
                ->join('wawancara', 'wawancara_sp3k.id_wawancara', '=', 'wawancara.id')
                ->pluck('wawancara.id_customer')
                ->filter()
                ->toArray();
            if (!empty($sp3kCustIds)) {
                Customer::whereIn('id', $sp3kCustIds)
                    ->whereNotIn('id', $bastCustIds)
                    ->whereNotIn('id', $akadCustIds)
                    ->whereNotIn('id', $ppjbCustIds)
                    ->where('id_status_progres', '!=', 4)
                    ->update(['id_status_progres' => 4]);
            }

            $wawancaraCustIds = Wawancara::where('status', 1)->pluck('id_customer')->filter()->toArray();
            if (!empty($wawancaraCustIds)) {
                Customer::whereIn('id', $wawancaraCustIds)
                    ->whereNotIn('id', $bastCustIds)
                    ->whereNotIn('id', $akadCustIds)
                    ->whereNotIn('id', $ppjbCustIds)
                    ->whereNotIn('id', $sp3kCustIds)
                    ->where('id_status_progres', '!=', 7)
                    ->update(['id_status_progres' => 7]);
            }

            $spprCustIds = SPPR::pluck('id_customer')->filter()->toArray();
            if (!empty($spprCustIds)) {
                Customer::whereIn('id', $spprCustIds)
                    ->whereNotIn('id', $bastCustIds)
                    ->whereNotIn('id', $akadCustIds)
                    ->whereNotIn('id', $ppjbCustIds)
                    ->whereNotIn('id', $sp3kCustIds)
                    ->whereNotIn('id', $wawancaraCustIds)
                    ->where('id_status_progres', '!=', 10)
                    ->update(['id_status_progres' => 10]);
            }
        } catch (\Throwable $e) {
            // Fallback aman
        }

        $akadCountQuery = Customer::where('id_status_progres', 3)->where('stt_arsip', 0);
        if ($isFilteredByDate && $startCarbon && $endCarbon) {
            $startDateStr = $startCarbon->toDateString();
            $endDateStr = $endCarbon->toDateString();
            $akadCountQuery->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereHas('akadDetail.akad', function ($qa) use ($startDateStr, $endDateStr) {
                    $qa->whereBetween('tgl_akad', [$startDateStr, $endDateStr]);
                })->orWhere(function ($qb) use ($startDateStr, $endDateStr) {
                    $qb->whereBetween('tanggal_verif', [$startDateStr . ' 00:00:00', $endDateStr . ' 23:59:59']);
                });
            });
        }
        $akadPipelineCount = $akadCountQuery->count();

        $pipelineCounts = [
            'booking' => PengajuanHold::where('stt_reg', '!=', 2)->count()
                + Customer::where('id_status_progres', 2)->where('stt_arsip', 0)->count(),
            'marketing' => Customer::where('id_status_progres', 11)->where('stt_arsip', 0)->count(),
            'sppr' => Customer::where('id_status_progres', 10)->where('stt_arsip', 0)->count(),
            'wawancara' => Customer::where('id_status_progres', 7)->where('stt_arsip', 0)->count(),
            'acc_bank' => Customer::where('id_status_progres', 4)->where('stt_arsip', 0)->count(),
            'ppjb' => Customer::where('id_status_progres', 6)->where('stt_arsip', 0)->count(),
            'akad' => $akadPipelineCount,
            'bast' => Customer::where('id_status_progres', 5)->where('stt_arsip', 0)->count(),
        ];

        if (session('app_type') === 'komersil') {
            if (KavlingPeta::whereIn('id_lokasi', [1, 2])->count() < 105 || \App\Models\MasterSVG::whereIn('id_lokasi', [1, 2])->count() < 2) {
                app(\App\Services\KomersilDatabaseService::class)->seedAvanaHillsSiteplan();
            }
        }

        $isKomersil = session('app_type') === 'komersil';
        $conn = $isKomersil ? 'mysql_komersil' : 'mysql_subsidi';
        $lokasiQuery = DB::connection($conn)->table('lokasi_kavling')->whereIn('stt_tampil', [1, 3]);
        if ($isKomersil) {
            $lokasiQuery->whereIn('id', [1, 2]);
        } else {
            $lokasiQuery->whereNotIn('nama_kavling', ['The Ammaia Hills', 'Avana Hills']);
        }
        $targetLokasiIds = (clone $lokasiQuery)->pluck('id')->toArray();

        $totalUnit = DB::connection($conn)->table('kavling_peta')->whereIn('id_lokasi', $targetLokasiIds)->count();
        $unitAkad = DB::connection($conn)->table('customer')->whereIn('id_lokasi', $targetLokasiIds)->where('stt_arsip', 0)->where('id_status_progres', 3)->count();
        $unitCash = DB::connection($conn)->table('customer')->whereIn('id_lokasi', $targetLokasiIds)->where('stt_arsip', 0)->where('jenis_pembelian', 'Cash')->where('id_status_progres', '!=', 3)->count();
        $unitTerjual = $unitAkad + $unitCash;
        $unitOnProses = DB::connection($conn)->table('customer')->whereIn('id_lokasi', $targetLokasiIds)->where('stt_arsip', 0)->whereIn('id_status_progres', [1, 4, 7, 8, 10, 11])->count();
        $unitReady = max(0, $totalUnit - $unitTerjual - $unitOnProses);
        $piutangTotal = (int) DB::connection($conn)->table('piutang')->sum('sisa_bayar');
        $piutangCustomer = DB::connection($conn)->table('piutang')->where('sisa_bayar', '>', 0)->distinct('id_customer')->count('id_customer');
        $tagihanTempoCust = DB::connection($conn)->table('customer_tempo')->count();
        $tagihanTempoTotal = (int) DB::connection($conn)->table('customer_tempo')->sum('total_harga');

        $summaryMetrics = [
            'jumlah_project' => (clone $lokasiQuery)->count(),
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

        $projectStats = (clone $lokasiQuery)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(function (LokasiKavling $lokasi) use ($isFilteredByDate, $startCarbon, $endCarbon) {
                $lokasiId = $lokasi->id;
                $totalUnit = KavlingPeta::where('id_lokasi', $lokasiId)->count();
                $booking = PengajuanHold::where('id_lokasi', $lokasiId)->where('stt_reg', '!=', 2)->count()
                    + Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 2)->where('stt_arsip', 0)->count();
                $marketing = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 11)->where('stt_arsip', 0)->count();
                $sppr = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 10)->where('stt_arsip', 0)->count();
                $wawancara = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 7)->where('stt_arsip', 0)->count();
                $accBank = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 4)->where('stt_arsip', 0)->count();
                $ppjb = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 6)->where('stt_arsip', 0)->count();

                $akadLokasiQuery = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 3)->where('stt_arsip', 0);
                if ($isFilteredByDate && $startCarbon && $endCarbon) {
                    $startDateStr = $startCarbon->toDateString();
                    $endDateStr = $endCarbon->toDateString();
                    $akadLokasiQuery->where(function ($q) use ($startDateStr, $endDateStr) {
                        $q->whereHas('akadDetail.akad', function ($qa) use ($startDateStr, $endDateStr) {
                            $qa->whereBetween('tgl_akad', [$startDateStr, $endDateStr]);
                        })->orWhere(function ($qb) use ($startDateStr, $endDateStr) {
                            $qb->whereBetween('tanggal_verif', [$startDateStr . ' 00:00:00', $endDateStr . ' 23:59:59']);
                        });
                    });
                }
                $akad = $akadLokasiQuery->count();

                $bast = Customer::where('id_lokasi', $lokasiId)->where('id_status_progres', 5)->where('stt_arsip', 0)->count();
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

        // Marketing Stats: aktif saja, hitung perbulan (default) dan semua waktu
        $startBulanIni = $now->copy()->startOfMonth();
        $endBulanIni = $now->copy()->endOfMonth();

        $activeMarketings = MarketingOffline::where('status', 1)
            ->orderBy('nama_marketing')
            ->get();

        $marketingStatsBulan = $activeMarketings
            ->map(function (MarketingOffline $marketing) use ($startBulanIni, $endBulanIni) {
                $count = Customer::where('id_marketing', $marketing->id)
                    ->where('stt_arsip', 0)
                    ->whereBetween('tanggal_verif', [$startBulanIni, $endBulanIni])
                    ->count();

                return [
                    'id' => $marketing->id,
                    'nama' => $marketing->nama_marketing,
                    'kode' => $marketing->kode_marketing,
                    'inisial' => mb_substr($marketing->nama_marketing, 0, 1),
                    'jumlah' => $count,
                ];
            })
            ->sortByDesc('jumlah')
            ->values();

        $marketingStatsAll = $activeMarketings
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

        $marketingStats = $marketingStatsBulan; // Default perbulan

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
        $sumberMatrix = $this->getSumberProspekMatrix();

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

        $total2025 = 0;
        $total2026 = 0;
        foreach ($sumberMatrix as $src => $item) {
            $total2025 += ($item['y2025'] ?? 0);
            $total2026 += ($item['y2026'] ?? 0);
        }

        $growth = $total2026 - $total2025;
        $growthPct = $total2025 > 0 ? round(($growth / $total2025) * 100, 1) : 0.0;
        $growthRata = round($growth / 12, 1);

        $yoySummary = [
            'total_2025'  => $total2025,
            'total_2026'  => $total2026,
            'growth'      => $growth,
            'growth_pct'  => $growthPct,
            'growth_rata' => $growthRata,
            'rata_2026'   => round($total2026 / 12, 1),
        ];

        return view('admin.beranda.index', compact(
            'username',
            'pipelineCounts',
            'summaryMetrics',
            'projectStats',
            'projectTotals',
            'marketingStats',
            'marketingStatsBulan',
            'marketingStatsAll',
            'bankStats',
            'availableYears',
            'availableMonths',
            'currentYear',
            'projectResumes',
            'sumberMatrix',
            'monthlyTotals',
            'yoySummary'
        ));
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
                    'BOOKING FEE' => 'warning',
                    'PROSES BANK' => 'secondary',
                    'SP3K' => 'success',
                    'AKAD' => 'info',
                    'SERAH TERIMA' => 'dark',
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
        $sumberMatrix = $this->getSumberProspekMatrix();

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
            $periodeLabel = "Bulan $monthName 2026";

            $temp = [];
            foreach ($sumberMatrix as $src => $data) {
                $temp[$src] = $data[$mKey] ?? 0;
            }
            arsort($temp);
            $labels = array_keys($temp);
            $values = array_values($temp);
        } else {
            $periodeLabel = 'Tahun 2026 (Semua Bulan)';
            $temp = [];
            foreach ($sumberMatrix as $src => $data) {
                $temp[$src] = $data['y2026'];
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
        $channels = ['Iklan Kantor', 'Market Place FB', 'Freelance', 'Kanvasing', 'Sosmed Pribadi', 'Sosmed Kantor', 'Referensi', 'WIC'];
        $base = [];
        $monthKeys = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
            5 => 'mei', 6 => 'jun', 7 => 'jul', 8 => 'aug',
            9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'des',
        ];

        foreach ($channels as $ch) {
            $base[$ch] = [
                'jan' => 0, 'feb' => 0, 'mar' => 0, 'apr' => 0,
                'mei' => 0, 'jun' => 0, 'jul' => 0, 'aug' => 0,
                'sep' => 0, 'okt' => 0, 'nov' => 0, 'des' => 0,
                'y2025' => 0, 'y2026' => 0, 'rata_rata' => 0.0, 'growth' => 0, 'growth_rata' => 0.0
            ];
        }

        $normalizeChannel = function ($raw) use ($channels) {
            if (empty($raw)) return 'Referensi';
            $c = strtolower(trim($raw));

            if (str_contains($c, 'iklan kantor') || $c === 'iklan') return 'Iklan Kantor';
            if (str_contains($c, 'market place') || str_contains($c, 'marketplace') || str_contains($c, 'fb') || str_contains($c, 'facebook')) return 'Market Place FB';
            if (str_contains($c, 'freelance') || str_contains($c, 'agen')) return 'Freelance';
            if (str_contains($c, 'kanvas') || str_contains($c, 'canvas') || str_contains($c, 'flyer') || str_contains($c, 'sebar')) return 'Kanvasing';
            if (str_contains($c, 'sosmed kantor') || str_contains($c, 'ig kantor') || str_contains($c, 'tiktok kantor')) return 'Sosmed Kantor';
            if (str_contains($c, 'sosmed') || str_contains($c, 'instagram') || str_contains($c, 'ig') || str_contains($c, 'tiktok') || str_contains($c, 'wa')) return 'Sosmed Pribadi';
            if (str_contains($c, 'wic') || str_contains($c, 'walk') || str_contains($c, 'kantor') || str_contains($c, 'lokasi')) return 'WIC';
            if (str_contains($c, 'referensi') || str_contains($c, 'teman') || str_contains($c, 'keluarga') || str_contains($c, 'konsumen')) return 'Referensi';

            foreach ($channels as $ch) {
                if (strcasecmp($ch, $raw) === 0) return $ch;
            }

            return 'Referensi';
        };

        try {
            // 1. Data Customer Terverifikasi Tahun 2026
            $customers2026 = Customer::where('stt_arsip', 0)
                ->where(function ($q) {
                    $q->whereYear('tanggal_verif', 2026)
                      ->orWhere(function ($q2) {
                          $q2->whereNull('tanggal_verif')->where('id', '>', 455);
                      });
                })
                ->get(['sumber_prospek', 'tanggal_verif', 'id']);

            foreach ($customers2026 as $row) {
                $matchedKey = $normalizeChannel($row->sumber_prospek ?? '');
                $bln = $row->tanggal_verif ? (int) Carbon::parse($row->tanggal_verif)->month : (int) Carbon::now('Asia/Jakarta')->month;
                $mKey = $monthKeys[$bln] ?? null;
                if ($mKey && isset($base[$matchedKey])) {
                    $base[$matchedKey][$mKey]++;
                }
            }

            // 2. Data Booking Masuk Baru di Pengajuan Hold Tahun 2026 yang belum diverifikasi
            $holds2026 = PengajuanHold::where('stt_reg', '!=', 2)
                ->where(function ($q) {
                    $q->whereYear('tgl_booking', 2026)
                      ->orWhereNull('tgl_booking');
                })
                ->get(['sumber_prospek', 'tgl_booking']);

            foreach ($holds2026 as $hold) {
                $matchedKey = $normalizeChannel($hold->sumber_prospek ?? '');
                $bln = $hold->tgl_booking ? (int) Carbon::parse($hold->tgl_booking)->month : (int) Carbon::now('Asia/Jakarta')->month;
                $mKey = $monthKeys[$bln] ?? null;
                if ($mKey && isset($base[$matchedKey])) {
                    $base[$matchedKey][$mKey]++;
                }
            }

            // 3. Data Baseline Tahun 2025
            $customers2025 = Customer::where('stt_arsip', 0)
                ->whereYear('tanggal_verif', 2025)
                ->get(['sumber_prospek']);

            foreach ($customers2025 as $row) {
                $matchedKey = $normalizeChannel($row->sumber_prospek ?? '');
                if (isset($base[$matchedKey])) {
                    $base[$matchedKey]['y2025']++;
                }
            }

            // 4. Kalkulasi Total 2026, Rata-rata, dan Growth
            foreach ($base as $k => &$item) {
                $sum2026 = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $sum2026 += $item[$monthKeys[$m]];
                }
                $item['y2026'] = $sum2026;
                $item['rata_rata'] = round($sum2026 / 12, 1);
                $item['growth'] = $item['y2026'] - $item['y2025'];
                $item['growth_rata'] = round($item['growth'] / 12, 1);
            }
            unset($item);
        } catch (\Exception $e) {
            Log::error('Error getSumberProspekMatrix: ' . $e->getMessage());
        }

        return $base;
    }

    private function getProjectResumes()
    {
        $isKomersil = session('app_type') === 'komersil';
        $conn = $isKomersil ? 'mysql_komersil' : 'mysql_subsidi';

        $query = DB::connection($conn)->table('lokasi_kavling')
            ->whereIn('stt_tampil', [1, 3])
            ->orderBy('urutan', 'asc')
            ->orderBy('id', 'asc');

        if ($isKomersil) {
            $query->whereIn('id', [1, 2]);
        } else {
            $query->whereNotIn('nama_kavling', ['The Ammaia Hills', 'Avana Hills']);
        }
        $lokasis = $query->get();

        $resumes = [];
        $totalAllUnits = 0;
        $totalAllTerjual = 0;
        $totalAllAkad = 0;
        $totalAllCash = 0;
        $totalAllProses = 0;
        $totalAllFisik = 0;
        $totalAllSisa = 0;
        $allProsesItems = [
            'SP3K' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-file-invoice', 'color' => '#8b5cf6'],
            'Proses Bank' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-building-columns', 'color' => '#f97316'],
            'Admin' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-user-tie', 'color' => '#06b6d4'],
            'Marketing' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-bullhorn', 'color' => '#3b82f6'],
            'Belum Terjual (Rumah Dibangun)' => ['unit' => 0, 'fisik' => 0, 'icon' => 'fa-home', 'color' => '#ec4899'],
        ];

        foreach ($lokasis as $lokasi) {
            $lokasiId = $lokasi->id;
            $key = 'lok_' . $lokasiId;
            $totalUnit = DB::connection($conn)->table('kavling_peta')->where('id_lokasi', $lokasiId)->count();

            // 1. Realisasi Terjual: Akad Kredit (id_status_progres = 3) dan Cash Lunas
            $akad = DB::connection($conn)->table('customer')
                ->where('id_lokasi', $lokasiId)
                ->where('stt_arsip', 0)
                ->where('id_status_progres', 3)
                ->count();

            $cash = DB::connection($conn)->table('customer')
                ->where('id_lokasi', $lokasiId)
                ->where('stt_arsip', 0)
                ->where('jenis_pembelian', 'Cash')
                ->where('id_status_progres', '!=', 3)
                ->count();

            $terjual = $akad + $cash;

            // 2. Sedang On Proses: Konsumen aktif dalam alur pembelian (SP3K, Bank, Admin, Marketing)
            $sp3k = DB::connection($conn)->table('customer')
                ->where('id_lokasi', $lokasiId)
                ->where('stt_arsip', 0)
                ->where('id_status_progres', 4)
                ->count();

            $bank = DB::connection($conn)->table('customer')
                ->where('id_lokasi', $lokasiId)
                ->where('stt_arsip', 0)
                ->whereIn('id_status_progres', [7, 10])
                ->count();

            $admin = DB::connection($conn)->table('customer')
                ->where('id_lokasi', $lokasiId)
                ->where('stt_arsip', 0)
                ->whereIn('id_status_progres', [1, 8])
                ->count();

            $marketingCust = DB::connection($conn)->table('customer')
                ->where('id_lokasi', $lokasiId)
                ->where('stt_arsip', 0)
                ->where('id_status_progres', 11)
                ->pluck('id_kavling')
                ->filter()
                ->toArray();

            $holdNonCust = DB::connection($conn)->table('pengajuan_hold')
                ->where('id_lokasi', $lokasiId)
                ->where('stt_reg', '!=', 2)
                ->whereNotIn('id_kavling', $marketingCust)
                ->count();

            $marketing = count($marketingCust) + $holdNonCust;

            $onProses = $sp3k + $bank + $admin + $marketing;

            // 3. Sisa Unit Ready: Secara matematis 100% konsisten (Total Unit - Terjual - On Proses)
            $sisaUnit = max(0, $totalUnit - $terjual - $onProses);

            $pctTerjual = $totalUnit > 0 ? round(($terjual / $totalUnit) * 100, 1) : 0;
            $pctProses = $totalUnit > 0 ? round(($onProses / $totalUnit) * 100, 1) : 0;
            $pctSisa = $totalUnit > 0 ? max(0, round(100 - $pctTerjual - $pctProses, 1)) : 0;

            // 4. Rincian Progres Fisik (Kesiapan Bangunan 80% - 100%):
            // Unit SP3K & Proses Bank sudah dipersiapkan kesiapan fisiknya (80-100%)
            $fSP3K = $sp3k;
            $fBank = $bank;
            $fAdmin = 0;
            $fMkt = 0;
            $fBelum = 0;
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
                'nama' => $lokasi->nama_kavling,
                'short_name' => $lokasi->nama_singkat ?: $lokasi->nama_kavling,
                'badge' => $lokasi->nama_singkat ?: ($isKomersil ? 'Komersil' : 'Subsidi'),
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
                'catatan' => 'Unit siap dipasarkan / booking',
            ];

            $totalAllUnits += $totalUnit;
            $totalAllTerjual += $terjual;
            $totalAllAkad += $akad;
            $totalAllCash += $cash;
            $totalAllProses += $onProses;
            $totalAllFisik += $totalFisik;
            $totalAllSisa += $sisaUnit;

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

        $allPctTerjual = $totalAllUnits > 0 ? round(($totalAllTerjual / $totalAllUnits) * 100, 1) : 0;
        $allPctProses = $totalAllUnits > 0 ? round(($totalAllProses / $totalAllUnits) * 100, 1) : 0;
        $allPctSisa = $totalAllUnits > 0 ? max(0, round(100 - $allPctTerjual - $allPctProses, 1)) : 0;

        $resumes['all'] = [
            'id' => 'all',
            'nama' => $isKomersil ? 'Total Keseluruhan Proyek Komersil' : 'Total Keseluruhan Proyek Subsidi',
            'short_name' => 'Semua Proyek',
            'badge' => 'Konsolidasi',
            'total_unit' => $totalAllUnits,
            'total_terjual' => $totalAllTerjual,
            'persentase_terjual' => $allPctTerjual,
            'kpr_akad' => $totalAllAkad,
            'terjual_cash' => $totalAllCash,
            'on_proses' => $totalAllProses,
            'persentase_proses' => $allPctProses,
            'sisa_unit' => $totalAllSisa,
            'persentase_sisa' => $allPctSisa,
            'proses_items' => $allProsesList,
            'total_unit_proses' => $totalAllProses,
            'total_fisik_proses' => $totalAllFisik,
            'catatan' => $isKomersil ? 'Total konsolidasi seluruh Perumahan Komersil (sinkron otomatis dari database)' : 'Total konsolidasi seluruh Perumahan Subsidi (sinkron otomatis dari database)',
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
                    'BOOKING FEE' => 'warning',
                    'PROSES BANK' => 'secondary',
                    'SP3K' => 'success',
                    'AKAD' => 'info',
                    'SERAH TERIMA' => 'dark',
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
                    'BOOKING FEE' => 'warning',
                    'PROSES BANK' => 'secondary',
                    'SP3K' => 'success',
                    'AKAD' => 'info',
                    'SERAH TERIMA' => 'dark',
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

    public function detailCustomerBank($id)
    {
        $bank = BankKPR::findOrFail($id);
        return view('admin.beranda.detail_customer_bank', compact('bank'));
    }

    public function detailCustomerBankData(Request $request, $id)
    {
        $data = Customer::with(['marketing', 'lokasi', 'kavling', 'progres', 'bank'])
            ->where('stt_arsip', 0)
            ->where(function ($q) use ($id) {
                $q->where('id_bank_kpr', $id)
                  ->orWhereHas('wawancara', fn ($wq) => $wq->where('id_bank_kpr', $id))
                  ->orWhereHas('wawancara.wawancaraSp3k', fn ($sq) => $sq->where('id_bank_kpr', $id));
            });

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
                    'BOOKING FEE' => 'warning',
                    'PROSES BANK' => 'secondary',
                    'SP3K' => 'success',
                    'AKAD' => 'info',
                    'SERAH TERIMA' => 'dark',
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
