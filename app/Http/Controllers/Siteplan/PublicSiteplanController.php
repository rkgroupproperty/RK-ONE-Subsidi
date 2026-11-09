<?php

namespace App\Http\Controllers\Siteplan;

use App\Http\Controllers\Controller;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\ProgresListPenjualan;
use Illuminate\Http\Request;
use App\Models\PengaturanMedia;
use App\Models\Piutang;
use App\Models\Pemasukan;

class PublicSiteplanController extends Controller
{
    /**
     * Display the public siteplan.
     */
     public function index()
    {
        $isKomersil = (str_contains(request()->getHost(), 'komersil') || (session('app_type') === 'komersil' && !str_contains(request()->getHost(), 'subsidi')));
        $conn = $isKomersil ? 'mysql_komersil' : 'mysql_subsidi';

        if ($isKomersil) {
            try {
                if (KavlingPeta::on('mysql_komersil')->whereIn('id_lokasi', [1, 2])->count() < 105 || \App\Models\MasterSVG::on('mysql_komersil')->whereIn('id_lokasi', [1, 2])->count() < 2) {
                    app(\App\Services\KomersilDatabaseService::class)->seedAvanaHillsSiteplan();
                }
            } catch (\Throwable $ignored) {}
        }

        $lokasiKavling = LokasiKavling::on($conn)->with([
            'masterSvg',
            'kavlingPeta.customer.progres',
            'kavlingPeta.pengajuanHold',
            'kavlingPeta.progres'
        ])
        ->orderBy('urutan', 'asc')
        ->get();

        $legend = ProgresListPenjualan::on($conn)->whereNotNull('warna')
            ->where('warna', '!=', '')
            ->where('stt_tampil', 1)
            ->where('status_progres', 'NOT LIKE', '%Unit Sudah Laku%')
            ->orderBy('urutan', 'asc')
            ->get();

        if (! $legend->contains(function ($item) { return strtolower(trim($item->status_progres)) === 'booking'; })) {
            $bookingItem = (object) [
                'status_progres' => 'Booking',
                'warna'          => '#42f202',
            ];
            $legend->splice(1, 0, [$bookingItem]);
        }

        $legend = $legend->unique(function ($item) {
            return strtolower(trim($item->status_progres));
        })->values();

        $bg = null;
        try {
            $bg = PengaturanMedia::on($conn)->where('jenis_data', 'Background booking')->first();
        } catch (\Throwable $e) {}
        if (!$bg) {
            $bg = PengaturanMedia::where('jenis_data', 'Background booking')->first();
        }

        return view('public_siteplan.index', compact('lokasiKavling', 'legend', 'bg'));
    }

    /**
     * Fetch kavling details for the public popup.
     */
    public function show($id)
    {
        $isKomersil = (str_contains(request()->getHost(), 'komersil') || (session('app_type') === 'komersil' && !str_contains(request()->getHost(), 'subsidi')));
        $conn = $isKomersil ? 'mysql_komersil' : 'mysql_subsidi';

        $data = KavlingPeta::on($conn)->with(['lokasi', 'customer', 'pengajuanHold', 'listrikAir'])->findOrFail($id);

        $tagihanList   = Piutang::on($conn)->where('id_customer', $data->id_customer)->orderBy('id')->get();
        $pemasukanList = Pemasukan::on($conn)->with('kategori')->where('id_customer', $data->id_customer)->get();

        return response()->json([
            'success'         => true,
            'data'            => $data,
            'tagihan'         => $tagihanList,
            'listrik_air'     => $data->listrikAir,
            'pemasukan'       => $pemasukanList,
            'total_tagihan'   => $tagihanList->sum('nominal'),
            'total_pemasukan' => $pemasukanList->sum('nominal'),
        ]);
    }

    /**
     * Unduh Denah Siteplan Format PDF
     */
    public function cetakPDF($id_lokasi)
    {
        return app(SiteplanPenjualanController::class)->cetakPDF($id_lokasi);
    }

    /**
     * Unduh Denah Siteplan Format JPG
     */
    public function cetakJPG($id_lokasi)
    {
        return app(SiteplanPenjualanController::class)->cetakJPG($id_lokasi);
    }
}

