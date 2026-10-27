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
        if (session('app_type') === 'komersil') {
            if (KavlingPeta::whereIn('id_lokasi', [1, 2])->count() < 105 || \App\Models\MasterSVG::whereIn('id_lokasi', [1, 2])->count() < 2) {
                app(\App\Services\KomersilDatabaseService::class)->seedAvanaHillsSiteplan();
            }
        }

        $lokasiKavling = LokasiKavling::with(['masterSvg', 'kavlingPeta.customer.progres', 'kavlingPeta.pengajuanHold', 'kavlingPeta.progres'])
            ->orderBy('urutan', 'asc')
            ->get();

        $legend = ProgresListPenjualan::whereNotNull('warna')
            ->where('warna', '!=', '')
            ->where('stt_tampil', 1)
            ->orderBy('urutan', 'asc')
            ->get();

        if (! $legend->contains('status_progres', 'Booking')) {
            $bookingItem = (object) [
                'status_progres' => 'Booking',
                'warna'          => '#42f202',
            ];
            $legend->splice(1, 0, [$bookingItem]);
        }

        $bg = PengaturanMedia::where('jenis_data', 'Background booking')->first();

        return view('public_siteplan.index', compact('lokasiKavling', 'legend', 'bg'));
    }

    /**
     * Fetch kavling details for the public popup.
     */
    public function show($id)
    {
        $data = KavlingPeta::with(['lokasi', 'customer', 'pengajuanHold', 'listrikAir'])->findOrFail($id);

        $tagihanList   = Piutang::where('id_customer', $data->id_customer)->orderBy('id')->get();
        $pemasukanList = Pemasukan::with('kategori')->where('id_customer', $data->id_customer)->get();

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

