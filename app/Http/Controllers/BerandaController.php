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
use App\Models\PPJB;
use App\Models\SPPR;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class BerandaController extends Controller
{
    public function index(Request $request)
    {
        $username = Auth::user()->username;

        $pipelineCounts = [
            'booking' => PengajuanHold::where('stt_reg', '!=', 2)->count(),
            'sppr' => SPPR::count(),
            'wawancara' => Wawancara::where('status', 1)
                ->whereHas('customer', fn ($query) => $query->where('stt_arsip', 0))
                ->count(),
            'acc_bank' => WawancaraSp3k::where('status', 1)
                ->whereHas('wawancara.customer', fn ($query) => $query->where('stt_arsip', 0))
                ->count(),
            'ppjb' => PPJB::whereHas('customer', fn ($query) => $query->where('stt_arsip', 0))->count(),
            'akad' => Akad::count(),
            'bast' => BAST::whereHas('customer', fn ($query) => $query->where('stt_arsip', 0))->count(),
        ];

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $summaryMetrics = [
            'jumlah_project' => LokasiKavling::whereIn('stt_tampil', [1, 3])->count(),
            'total_unit' => KavlingPeta::count(),
            'booking_fee_hari_ini' => PengajuanHold::whereDate('tgl_booking', $today)->sum('booking_fee'),
            'piutang' => 0,
            'piutang_customer' => 0,
            'tagihan_tempo_customer' => 0,
            'tagihan_tempo_total' => 0,
        ];

        $projectStats = LokasiKavling::orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(function (LokasiKavling $lokasi) {
                $lokasiId = $lokasi->id;

                return [
                    'id' => $lokasiId,
                    'nama' => $lokasi->nama_kavling,
                    'kode' => $lokasi->nama_singkat,
                    'total_unit' => KavlingPeta::where('id_lokasi', $lokasiId)->count(),
                    'booking' => PengajuanHold::where('id_lokasi', $lokasiId)->where('stt_reg', '!=', 2)->count(),
                    'sppr' => SPPR::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count(),
                    'wawancara' => Wawancara::where('status', 1)
                        ->whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))
                        ->count(),
                    'acc_bank' => WawancaraSp3k::where('status', 1)
                        ->whereHas('wawancara.customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))
                        ->count(),
                    'ppjb' => PPJB::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count(),
                    'akad' => AkadDetail::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count(),
                    'bast' => BAST::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count(),
                ];
            });

        $projectTotals = [
            'booking' => $projectStats->sum('booking'),
            'sppr' => $projectStats->sum('sppr'),
            'wawancara' => $projectStats->sum('wawancara'),
            'acc_bank' => $projectStats->sum('acc_bank'),
            'ppjb' => $projectStats->sum('ppjb'),
            'akad' => $projectStats->sum('akad'),
            'bast' => $projectStats->sum('bast'),
        ];

        $marketingStats = MarketingOffline::orderBy('nama_marketing')
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

        $totalBankUsage = WawancaraSp3k::where('status', 1)->count();

        $bankStats = BankKPR::orderBy('nama')
            ->get()
            ->map(function (BankKPR $bank) use ($totalBankUsage) {
                $jumlah = WawancaraSp3k::where('status', 1)
                    ->where('id_bank_kpr', $bank->id)
                    ->count();

                return [
                    'id' => $bank->id,
                    'nama' => $bank->nama,
                    'jumlah' => $jumlah,
                    'persentase' => $totalBankUsage > 0 ? round(($jumlah / $totalBankUsage) * 100) : 0,
                ];
            })
            ->filter(fn ($bank) => $bank['jumlah'] > 0)
            ->sortByDesc('jumlah')
            ->values();

        $availableYears = Customer::whereNotNull('tanggal_verif')
            ->selectRaw('YEAR(tanggal_verif) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $currentYear = Carbon::now('Asia/Jakarta')->year;

        return view('admin.beranda.index', compact(
            'username',
            'pipelineCounts',
            'summaryMetrics',
            'projectStats',
            'projectTotals',
            'marketingStats',
            'bankStats',
            'availableYears',
            'currentYear'
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
                    'wawancara' => 7,
                    'sp3k' => 4,
                    'akad' => 3,
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
            'wawancara' => 7,
            'sp3k' => 4,
            'akad' => 3,
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
                'wawancara' => 7,
                'sp3k' => 4,
                'akad' => 3,
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
        $filter = $request->input('filter', 'semua');

        $options = [
            'Iklan Kantor',
            'Market Place FB',
            'Freelance',
            'Kanvasing',
            'Sosmed Pribadi',
            'Sosmed Kantor',
            'Referensi',
            'WIC',
        ];

        $bookingData = [];
        $customerData = [];

        foreach ($options as $option) {
            $bookingCount = PengajuanHold::where('sumber_prospek', $option)
                ->where('stt_reg', '!=', 2)
                ->count();

            $customerCount = Customer::where('sumber_prospek', $option)
                ->where('stt_arsip', 0)
                ->count();

            $bookingData[] = $bookingCount;
            $customerData[] = $customerCount;
        }

        if ($filter === 'booking') {
            $combined = $bookingData;
        } elseif ($filter === 'customer') {
            $combined = $customerData;
        } else {
            $combined = array_map(function ($b, $c) {
                return $b + $c;
            }, $bookingData, $customerData);
        }

        return response()->json([
            'labels' => $options,
            'booking' => $bookingData,
            'customer' => $customerData,
            'combined' => $combined,
        ]);
    }
}
