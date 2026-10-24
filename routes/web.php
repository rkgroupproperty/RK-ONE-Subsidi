<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\Customer\ArsipCustomerController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\UploudFileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Keuangan\HutangController;
use App\Http\Controllers\Keuangan\KategoriTransaksiController;
use App\Http\Controllers\Keuangan\LaporanArusKasController;
use App\Http\Controllers\Keuangan\MutasiSaldoController;
use App\Http\Controllers\Keuangan\PemasukanController;
use App\Http\Controllers\Keuangan\PengeluaranController;
use App\Http\Controllers\Keuangan\PiutangController;
use App\Http\Controllers\Keuangan\RetensiController as KeuanganRetensiController;
use App\Http\Controllers\Legal\BphtbSSPController;
use App\Http\Controllers\Legal\BerkasPengajuanController;
use App\Http\Controllers\Legal\ListrikAirController;
use App\Http\Controllers\Marketing\MarketingOfflineController;
use App\Http\Controllers\Master\BankKPRController;
use App\Http\Controllers\Master\BankTransaksiController;
use App\Http\Controllers\Master\KavlingController;
use App\Http\Controllers\Master\LokasiKavlingController;
use App\Http\Controllers\Master\NotarisController;
use App\Http\Controllers\Master\PerusahaanController;
use App\Http\Controllers\Master\RetensiController;
use App\Http\Controllers\Master\UploadTemplateController;
use App\Http\Controllers\PanduanAplikasiController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\Master\AdminPemberkasanController as MasterAdminPemberkasanController;
use App\Http\Controllers\AdminPemberkasanController;
use App\Http\Controllers\PengajuanHoldController;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Http\Controllers\Pengaturan\KontenController;
use App\Http\Controllers\Pengaturan\ListPenjualanController;
use App\Http\Controllers\Pengaturan\LogAktivitasController;
use App\Http\Controllers\Pengaturan\PengaturanMediaController;
use App\Http\Controllers\Pengaturan\PengaturanPenggunaController;
use App\Http\Controllers\Pengaturan\PengaturanProfilController;
use App\Http\Controllers\Pengaturan\RoleUserController;
use App\Http\Controllers\Siteplan\SiteplanPenjualanController;
use App\Http\Controllers\Siteplan\SiteplanListrikController;
use App\Http\Controllers\Siteplan\SiteplanBphtbSSPController;
use App\Http\Controllers\Siteplan\SiteplanAirController;
use App\Http\Controllers\Siteplan\BalikNamaController;
use App\Http\Controllers\Siteplan\SiteplanBalikNamaController;
use App\Http\Controllers\Siteplan\PublicSiteplanController;
use App\Http\Controllers\Transaksi\AccBankController;
use App\Http\Controllers\Transaksi\AkadController;
use App\Http\Controllers\Transaksi\BastController;
use App\Http\Controllers\Transaksi\GantiNamaController;
use App\Http\Controllers\Transaksi\ProsesMarketingController;
use App\Http\Controllers\Transaksi\SPPRController;
use App\Http\Controllers\Transaksi\PembelianCancelController;
use App\Http\Controllers\Transaksi\PindahUnitController;
use App\Http\Controllers\Transaksi\PPJBController;
use App\Http\Controllers\Transaksi\WawancaraController;
use App\Http\Controllers\Transaksi\ExportTransaksiController;
use App\Http\Controllers\SyncExcelController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $frontPage = DB::table('konfigurasi')->value('front_page');

    if ($frontPage == 1) {
        return redirect()->route('homepage');
    }

    if (! Auth::check()) {
        return redirect()->route('login');
    }

    $menus = session('getmenus');

    if (! $menus || $menus->isEmpty()) {
        Auth::logout();

        return redirect()->route('login')->withErrors(['msg' => 'Anda tidak punya akses menu.']);
    }

    $menus = $menus->filter(function ($menu) {
        $menu->setRelation('children', $menu->children->filter(fn ($child) => Route::has($child->route_name))->values());

        return $menu->children->isNotEmpty() || Route::has($menu->route_name);
    })->values();

    session(['getmenus' => $menus]);

    $firstMenu = $menus->first();

    if ($firstMenu) {
        if ($firstMenu->children && $firstMenu->children->isNotEmpty()) {
            return redirect()->route($firstMenu->children->first()->route_name);
        }

        return redirect()->route($firstMenu->route_name);
    }

    return redirect()->route('login');
});

Route::get('/booking', [PengajuanHoldController::class, 'booking'])->name('booking');
Route::get('/booking/subsidi', [PengajuanHoldController::class, 'bookingSubsidi'])->name('booking.subsidi');
Route::get('/booking/komersil', [PengajuanHoldController::class, 'bookingKomersil'])->name('booking.komersil');
Route::get('/booking-sukses', [PengajuanHoldController::class, 'bookingSukses'])->name('booking.sukses');
Route::get('/booking/kwitansi-sementara/{id}', [PengajuanHoldController::class, 'cetakKwitansiSementara'])->name('booking.kwitansi-sementara');
Route::get('siteplan', [PublicSiteplanController::class, 'index'])->name('public.siteplan.index');
Route::get('siteplan/detail/{id}', [PublicSiteplanController::class, 'show'])->name('public.siteplan.show');
Route::get('siteplan/cetak/pdf/{id_lokasi}', [PublicSiteplanController::class, 'cetakPDF'])->name('public.siteplan.cetak.pdf');
Route::get('siteplan/cetak/jpg/{id_lokasi}', [PublicSiteplanController::class, 'cetakJPG'])->name('public.siteplan.cetak.jpg');

Route::get('/get-kavling-hold/{id_lokasi}', [PengajuanHoldController::class, 'getKavlingHold'])->name('pengajuan-hold.getKavling');
Route::get('/get-harga-kavling-hold/{id_kavling}', [PengajuanHoldController::class, 'getHargaKavlingHold'])->name('pengajuan-hold.getHargaKavling');
Route::get('/get-kavling/{id_lokasi}', [PengajuanHoldController::class, 'getKavling'])->name('booking.getKavling');
Route::get('/get-harga-kavling/{id_kavling}', [PengajuanHoldController::class, 'getHargaKavling'])->name('booking.getHargaKavling');
Route::post('booking/store', [PengajuanHoldController::class, 'bookingStore'])->name('store.booking');
Route::get('/homepage', [KontenController::class, 'homepage'])->name('homepage');
Route::get('/agents', [KontenController::class, 'marketing'])->name('marketing');
Route::get('/aboutus', [KontenController::class, 'aboutus'])->name('aboutus');
Route::get('/progres', [KontenController::class, 'progres'])->name('progres');
Route::get('/siteplan/{id}', [KontenController::class, 'siteplan'])->name('konten.siteplan');

Route::group(['middleware' => 'guest'], function () {
    Route::get('admin/login', [AuthController::class, 'getLogin'])->name('login');
    Route::post('admin/post-login', [AuthController::class, 'postLogin'])->name('admin.loginPost');
    Route::post('login', [AuthController::class, 'postLogin'])->name('login.post');
});

Route::get('/hutang/sisa-bayar/{id}', [HutangController::class, 'getSisaBayar']);
Route::get('/piutang/sisa-bayar/{id}', [PiutangController::class, 'getSisaBayar']);

Route::match(['get', 'post'], 'switch-portal/verify', [AuthController::class, 'verifySwitchPortal'])->name('switch.portal.verify');

Route::get('/system/clear-cache/{secret}', function ($secret) {
    if ($secret !== 'rk_clear_2026') {
        abort(403);
    }
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return response()->json([
        'status' => 'success',
        'message' => 'Cache Laravel berhasil dibersihkan!',
        'output' => \Illuminate\Support\Facades\Artisan::output(),
    ]);
})->name('system.clear-cache');

Route::get('/system/git-pull/{secret}', function ($secret) {
    if ($secret !== 'rk_git_pull_2026') {
        abort(403);
    }
    $output = [];
    $code = 0;
    @exec('cd ' . base_path() . ' && git pull origin main 2>&1', $output, $code);
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return response()->json([
        'status' => $code === 0 ? 'success' : 'info',
        'code' => $code,
        'output' => $output,
        'head' => trim(@shell_exec('cd ' . base_path() . ' && git log -1 --oneline 2>&1') ?? '')
    ]);
})->name('system.git-pull');

Route::middleware(['auth'])->group(function () {
    Route::get('switch-portal/{type}', [AuthController::class, 'switchPortal'])->name('switch.portal');
    Route::get('admin/beranda', [BerandaController::class, 'index'])->name('beranda.index');
    Route::get('admin/beranda/chart-data', [BerandaController::class, 'getChartData'])->name('beranda.chart-data');
    Route::get('admin/beranda/marketing-stats', [BerandaController::class, 'getMarketingStats'])->name('beranda.marketing-stats');
    Route::get('admin/beranda/sumber-prospek-data', [BerandaController::class, 'getSumberProspekData'])->name('beranda.sumber-prospek-data');
    Route::get('admin/beranda/export-sumber-prospek', [BerandaController::class, 'exportSumberProspek'])->name('beranda.export-sumber-prospek');
    Route::get('admin/beranda/admin-pemberkasan-data', [BerandaController::class, 'adminPemberkasanData'])->name('beranda.admin-pemberkasan-data');
    Route::get('admin/detail-grafik-penjualan-bulanan', [BerandaController::class, 'detailGrafik'])->name('beranda.detail-grafik');
    Route::get('admin/detail-grafik-penjualan-bulanan/data', [BerandaController::class, 'detailGrafikData'])->name('beranda.detail-grafik.data');
    Route::get('admin/detail-customer-marketing/{id}', [BerandaController::class, 'detailCustomerMarketing'])->name('beranda.detail-customer-marketing');
    Route::get('admin/detail-customer-marketing/{id}/data', [BerandaController::class, 'detailCustomerMarketingData'])->name('beranda.detail-customer-marketing.data');
    Route::get('admin/detail-customer-bank/{id}', [BerandaController::class, 'detailCustomerBank'])->name('beranda.detail-customer-bank');
    Route::get('admin/detail-customer-bank/{id}/data', [BerandaController::class, 'detailCustomerBankData'])->name('beranda.detail-customer-bank.data');
    Route::get('admin/detail-customer-admin-pemberkasan/{id}', [BerandaController::class, 'detailCustomerAdminPemberkasan'])->name('beranda.detail-customer-admin-pemberkasan');
    Route::get('admin/detail-customer-admin-pemberkasan/{id}/data', [BerandaController::class, 'detailCustomerAdminPemberkasanData'])->name('beranda.detail-customer-admin-pemberkasan.data');
    Route::get('admin/sync-excel-data', [SyncExcelController::class, 'index'])->name('admin.sync-excel');
    Route::post('admin/sync-excel-data/process', [SyncExcelController::class, 'process'])->name('admin.sync-excel.process');

    Route::prefix('admin')->controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard.index');
        Route::get('/dashboard/lokasi-penjualan/{id}', 'showLokasiPenjualan')->name('dashboard.lokasi-penjualan-show');
        Route::get('/dashboard/customer-status-progres/{id}', 'showCustomer')->name('dashboard.customer-status-progres-show');
        Route::get('/dashboard/customer-bank/{id}', 'showCustomer')->name('dashboard.customer-bank-show');
        Route::get('/dashboard/customer-marketing/{id}', 'showCustomer')->name('dashboard.customer-marketing-show');
        Route::get('/total-unit', 'totalUnit')->name('dashboard.total-unit');
        Route::get('/booking-unit', 'booking')->name('dashboard.booking-unit');
        Route::get('/wawancara-unit', 'wawancara')->name('dashboard.proses-bank-unit');
        Route::get('/akad-unit', 'akad')->name('dashboard.akad-unit');

        Route::prefix('/siteplan')->group(function () {
            Route::resource('balik-nama', BalikNamaController::class);

            Route::get('st-balik-nama/cetak/pdf/{id_lokasi}', [SiteplanBalikNamaController::class, 'cetakPDF'])->name('st-balik-nama.cetak.pdf');
            Route::get('st-balik-nama/cetak/jpg/{id_lokasi}', [SiteplanBalikNamaController::class, 'cetakJPG'])->name('st-balik-nama.cetak.jpg');
            Route::resource('st-balik-nama', SiteplanBalikNamaController::class);

            Route::get('st-bphtb-ssp/cetak/pdf/{id_lokasi}', [SiteplanBphtbSSPController::class, 'cetakPDF'])->name('st-bphtb-ssp.cetak.pdf');
            Route::get('st-bphtb-ssp/cetak/jpg/{id_lokasi}', [SiteplanBphtbSSPController::class, 'cetakJPG'])->name('st-bphtb-ssp.cetak.jpg');
            Route::resource('st-bphtb-ssp', SiteplanBphtbSSPController::class);

            Route::get('siteplan-air/cetak/pdf/{id_lokasi}', [SiteplanAirController::class, 'cetakPDF'])->name('siteplan-air.cetak.pdf');
            Route::get('siteplan-air/cetak/jpg/{id_lokasi}', [SiteplanAirController::class, 'cetakJPG'])->name('siteplan-air.cetak.jpg');
            Route::resource('siteplan-air', SiteplanAirController::class);

            Route::get('siteplan-listrik/cetak/pdf/{id_lokasi}', [SiteplanListrikController::class, 'cetakPDF'])->name('siteplan-listrik.cetak.pdf');
            Route::get('siteplan-listrik/cetak/jpg/{id_lokasi}', [SiteplanListrikController::class, 'cetakJPG'])->name('siteplan-listrik.cetak.jpg');
            Route::resource('siteplan-listrik', SiteplanListrikController::class);

            Route::resource('siteplan-penjualan', SiteplanPenjualanController::class);
            Route::get('siteplan-penjualan/cetak/pdf/{id_lokasi}', [SiteplanPenjualanController::class, 'cetakPDF'])->name('siteplan-penjualan.cetak.pdf');
            Route::get('siteplan-penjualan/cetak/jpg/{id_lokasi}', [SiteplanPenjualanController::class, 'cetakJPG'])->name('siteplan-penjualan.cetak.jpg');
            Route::post('siteplan-penjualan/cetak', [SiteplanPenjualanController::class, 'cetak'])->name('penjualan.cetak');

        });
    });

    Route::prefix('admin')->group(function () {
        Route::get('/pengajuan-hold/arsip', [PengajuanHoldController::class, 'viewArsip'])->name('pengajuan-hold.arsip');
        Route::post('/pengajuan-hold/{id}/upload', [PengajuanHoldController::class, 'upload'])->name('pengajuan-hold.upload');
        Route::post('/pengajuan-hold/{id}/delete-file', [PengajuanHoldController::class, 'deleteFile'])->name('pengajuan-hold.delete-file');
        Route::get('/pengajuan-hold/{id}/verifikasi', [PengajuanHoldController::class, 'verifikasi'])->name('pengajuan-hold.verifikasi');
        Route::post('/pengajuan-hold/{id}/verifikasi', [PengajuanHoldController::class, 'simpanVerifikasi'])->name('pengajuan-hold.verifikasi.simpan');
        Route::get('/pengajuan-hold/{id}/arsip-detail', [PengajuanHoldController::class, 'arsipDetail'])->name('pengajuan-hold.arsip.detail');
        Route::resource('pengajuan-hold', PengajuanHoldController::class);
    });

    Route::prefix('admin')->group(function () {
        Route::post('admin-pemberkasan/set-admin', [AdminPemberkasanController::class, 'setAdmin'])->name('admin-pemberkasan.set-admin');
        Route::get('admin-pemberkasan/chart-data', [AdminPemberkasanController::class, 'chartData'])->name('admin-pemberkasan.chart-data');
    });

    Route::prefix('admin')->controller(PembayaranController::class)->group(function () {
        Route::get('pembayaran/{id}/detail', 'detail')->name('pembayaran.detail');
        Route::get('pembayaran/detail-tagihan/{id}', 'detailTagihan')->name('pembayaran.detail-tagihan');
        Route::get('pembayaran/detail-pemasukan/{id}', 'detailPemasukan')->name('pembayaran.detail-pemasukan');
        Route::put('pembayaran/update-harga-rumah/{id}', 'UpdateHargaRumah')->name('Pembayaran.update-harga-rumah');
        Route::put('pembayaran/update-estimasi-plafon/{id}', 'updateEstimasiPlafon')->name('Pembayaran.update-estimasi-plafon');
        Route::put('pembayaran/update-sbum/{id}', 'updateSbum')->name('Pembayaran.update-sbum');

        Route::get('Pembayaran/rekap-pembayaran', 'rekapPembayaran')->name('pembayaran.rekap');

        Route::post('pembayaran/tambah-tagihan/{id}', 'tambahTagihan')->name('pembayaran.tambah-tagihan');
        Route::post('pembayaran/tambah-pemasukan/{id}', 'tambahPemasukan')->name('pembayaran.tambah-pemasukan');
        Route::post('pembayaran/tambah-pencairan-kpr/{id}', 'tambahPencairanKpr')->name('pembayaran.tambah-pencairan-kpr');

        Route::delete('pembayaran/delete-tagihan/{id}', 'DeleteTagihan')->name('pembayaran.delete-tagihan');
        Route::put('pembayaran/update-tagihan/{id}', 'updateTagihan')->name('pembayaran.update-tagihan');
        Route::delete('pembayaran/delete-pemasukan/{id}', 'DeletePemasukan')->name('pembayaran.delete-pemasukan');
        Route::get('pembayaran/edit-pemasukan/{id}', 'editPemasukan')->name('pembayaran.edit-pemasukan');
        Route::put('pembayaran/update-pemasukan/{id}', 'updatePemasukan')->name('pembayaran.update-pemasukan');
        Route::get('/customer/cetak-rekap/{id}', 'cetakRekap')->name('customer.cetak-rekap');
        Route::get('/pembayaran/cetak/{id}', 'cetak')->name('pembayaran.cetak');
        Route::get('/pembayaran/print/{id}', 'print')->name('pembayaran.print');
        Route::resource('pembayaran', PembayaranController::class);
    });

    Route::prefix('admin/transaksi')->group(function () {
        Route::resource('wawancara', WawancaraController::class)->names('proses-bank');
        Route::get('/wawancara/detail-customer/{id_customer}', [WawancaraController::class, 'detailCustomer'])->name('proses-bank.detail-customer');
        Route::post('/wawancara/acc-bank/{id_wawancara}', [WawancaraController::class, 'simpanSp3k'])->name('proses-bank.sp3k');
        Route::get('/wawancara/{id}/acc', [WawancaraController::class, 'acc'])->name('proses-bank.acc');
        Route::get('/sp3k/data', [AccBankController::class, 'getDataSp3k'])->name('sp3k.data');
        Route::get('/acc-bank/customer-detail/{id}', [AccBankController::class, 'getCustomerDetail'])->name('sp3k.customer-detail');

        Route::resource('acc-bank', AccBankController::class)->names('sp3k');
        Route::resource('akad', AkadController::class);
        Route::post('akad/seleksi-customer/{id_akad}', [AkadController::class, 'seleksiCustomer'])->name('akad.seleksi-customer');
        Route::get('akad/seleksi-customer/hadir/{id_detail}', [AkadController::class, 'showHadir'])->name('akad.seleksi-customer.get-hadir');
        Route::post('akad/seleksi-customer/hadir/{id_detail}', [AkadController::class, 'updateHadir'])->name('akad.seleksi-customer.update-hadir');

        Route::get('akad/detail-excel/{id}', [AkadController::class, 'cetakDetailExcel'])->name('akad.detail.excel');
        Route::delete('akad/detail/{id}', [AkadController::class, 'destroyDetail'])->name('akad.detail.destroy');
        Route::get('akad/detail-pdf/{id}', [AkadController::class, 'cetakDetailPDF'])->name('akad.detail.pdf');
        Route::get('/akad/{id}/seleksi', [AkadController::class, 'seleksi'])->name('akad.detail.seleksi');
        Route::get('akad/get-customer-detail/{id}', [AkadController::class, 'getCustomerDetail'])->name('akad.getCustomerDetail');
        Route::get('akad/detail/data/{id}', [AkadController::class, 'detailData'])->name('akad.detail.data');
        Route::get('/akad/download/{id}', [AkadController::class, 'downloadWord'])->name('akad.download');

        Route::get('cetak-bast/{id_customer}', [BastController::class, 'cetakBast'])
            ->name('bast.cetak');
        Route::get('/bast/generate-no', [BASTController::class, 'generateNoBAST'])->name('generateNoBAST');
        Route::resource('bast', BASTController::class);
        Route::get('cetak-ppjb/{id_customer}', [PPJBController::class, 'cetakPPJB'])
            ->name('ppjb.cetak');
        Route::resource('ppjb', PPJBController::class);

        Route::get('pindah-unit/kwitansi/{id}', [PindahUnitController::class, 'cetakKwitansi'])->name('pindah-unit.kwitansi');
        Route::get('pindah-unit/cetak-word/{id}', [PindahUnitController::class, 'cetakWord'])->name('pindah-unit.cetak-word');
        Route::get('pindah-unit/detail-customer/{id_customer}', [PindahUnitController::class, 'detailCustomer'])->name('pindah-unit.detail-customer');
        Route::get('pindah-unit/get-kavling-baru/{id_customer}', [PindahUnitController::class, 'getKavlingBaru'])->name('pindah-unit.getKavlingBaru');

        Route::get('pembatalan/kwitansi/{id}', [PembelianCancelController::class, 'cetakKwitansi'])->name('pembatalan.kwitansi');

        Route::resource('pindah-unit', PindahUnitController::class);
        Route::resource('pembelian-cancel', PembelianCancelController::class);
        Route::get('ganti-nama/cetak/{id}', [GantiNamaController::class, 'cetak'])->name('ganti-nama.cetak');
        Route::resource('ganti-nama', GantiNamaController::class);
        Route::get('ganti-nama/{id}/get-customer', [GantiNamaController::class, 'getCustomer'])->name('ganti-nama.get-customer');

        Route::resource('sppr', SPPRController::class)->names('proses-admin');
        Route::get('sppr/{id}/cetak', [SPPRController::class, 'cetak'])->name('proses-admin.cetak');
        Route::get('sppr/get-customer-detail/{id}', [SPPRController::class, 'getCustomerDetail'])->name('proses-admin.get-customer-detail');

        Route::resource('proses-marketing', ProsesMarketingController::class);
        Route::get('proses-marketing/get-customer-detail/{id}', [ProsesMarketingController::class, 'getCustomerDetail'])->name('proses-marketing.get-customer-detail');

        Route::prefix('export/transaksi')->group(function () {
            Route::get('booking', [ExportTransaksiController::class, 'booking'])->name('export.transaksi.booking');
            Route::get('sppr', [ExportTransaksiController::class, 'sppr'])->name('export.transaksi.sppr');
            Route::get('proses-marketing', [ExportTransaksiController::class, 'prosesMarketing'])->name('export.transaksi.proses-marketing');
            Route::get('wawancara', [ExportTransaksiController::class, 'wawancara'])->name('export.transaksi.wawancara');
            Route::get('acc-bank', [ExportTransaksiController::class, 'accBank'])->name('export.transaksi.acc-bank');
            Route::get('ppjb', [ExportTransaksiController::class, 'ppjb'])->name('export.transaksi.ppjb');
            Route::get('akad', [ExportTransaksiController::class, 'akad'])->name('export.transaksi.akad');
            Route::get('bast', [ExportTransaksiController::class, 'bast'])->name('export.transaksi.bast');
            Route::get('pindah-unit', [ExportTransaksiController::class, 'pindahUnit'])->name('export.transaksi.pindah-unit');
            Route::get('ganti-nama', [ExportTransaksiController::class, 'gantiNama'])->name('export.transaksi.ganti-nama');
            Route::get('pembelian-cancel', [ExportTransaksiController::class, 'pembelianCancel'])->name('export.transaksi.pembelian-cancel');
        });
    });

    Route::prefix('admin/customer')->group(function () {
        Route::get('get-kavling/{idLokasi}', [CustomerController::class, 'getKavling'])->name('customer.getKavling');
        Route::get('get-harga-kavling/{id_kavling}', [CustomerController::class, 'getHargaKavling'])->name('customer.getHargaKavling');
        Route::post('unit-sudah-laku', [CustomerController::class, 'unitSudahLakuStore'])->name('customer.unit-sudah-laku-store');
        Route::get('customer/cetak', [CustomerController::class, 'cetakData'])->name('customer.cetak');
        Route::get('customer/{id_customer}/subsidi-cetak', [CustomerController::class, 'cetakFormSubsidi'])->name('subsidi.cetak');
        Route::get('customer/print-document/{template_code}/{id_customer}', [CustomerController::class, 'printDocument'])->name('customer.print-document');

        Route::get('customer/tempo', [CustomerController::class, 'getTempo'])->name('customer.tempo');
        Route::get('customer/tempo/{id}', [CustomerController::class, 'showTempo'])->name('customer.show-tempo');
        Route::put('customer/tempo/{id}', [CustomerController::class, 'postTempo'])->name('customer.post-tempo');
        Route::resource('customer', CustomerController::class);

        Route::resource('upload-file', UploudFileController::class);
        Route::resource('arsip-customer', ArsipCustomerController::class);
    });

    Route::prefix('admin/marketing')->group(function () {
        Route::resource('marketing-offline', MarketingOfflineController::class);
    });

    Route::prefix('admin/legal')->group(function () {
        Route::resource('bphtb-ssp', BphtbSSPController::class);
        Route::resource('listrik-air', ListrikAirController::class);
        Route::resource('pengajuan-berkas', BerkasPengajuanController::class);
    });

    Route::prefix('admin/keuangan')->group(function () {
        Route::get('/laporan-arus-kas/filter', [LaporanArusKasController::class, 'filter'])->name('laporan-arus-kas.filter');
        Route::get('laporan-arus-kas/export-pdf', [LaporanArusKasController::class, 'exportPdf'])->name('laporan-arus-kas.exportPDF');
        Route::get('laporan-arus-kas/export-excel', [LaporanArusKasController::class, 'exportExcel'])->name('laporan-arus-kas.exportExcel');

        Route::resource('pemasukan', PemasukanController::class);
        Route::resource('pengeluaran', PengeluaranController::class);
        Route::resource('hutang', HutangController::class);
        Route::resource('piutang', PiutangController::class);
        Route::resource('kategori-transaksi', KategoriTransaksiController::class);
        Route::resource('mutasi-saldo', MutasiSaldoController::class);
        Route::resource('laporan-arus-kas', LaporanArusKasController::class);
        Route::get('retensi', [KeuanganRetensiController::class, 'index'])->name('keuangan-retensi.index');
    });

    Route::prefix('admin/master')->group(function () {
        // Perusahaan
        Route::resource('perusahaan', PerusahaanController::class)->except('show');
        Route::put('lokasi-kavling/{id}/updateDetail', [LokasiKavlingController::class, 'updateDetail'])->name('LokasiKavling.updateDetail');
        Route::put('lokasi-kavling/{id}/update-detail', [LokasiKavlingController::class, 'updateDetail']);
        Route::get('/lokasi-kavling/{id}/setting', [LokasiKavlingController::class, 'setting'])->name('LokasiKavling.setting');
        Route::put('/lokasi-kavling/{id}/setting', [LokasiKavlingController::class, 'updateSetting'])->name('LokasiKavling.updateSetting');
        Route::get('/lokasi-kavling/{id}/detail', [LokasiKavlingController::class, 'detail'])->name('LokasiKavling.detail');
        Route::get('/lokasi-kavling/export/{id}', [LokasiKavlingController::class, 'exportDetail'])->name('LokasiKavling.export');
        Route::post('/lokasi-kavling/upload-excel', [LokasiKavlingController::class, 'uploadExcel'])->name('LokasiKavling.uploadExcel');
        Route::get('/lokasi-kavling/{id}/edit-detail', [LokasiKavlingController::class, 'editDetail'])->name('LokasiKavling.editDetail');
        Route::get('get-perusahaan', [LokasiKavlingController::class, 'getPerusahaan'])->name('getPerusahaan');

        Route::get('/kavling/cetak-excel/{id_lokasi}', [KavlingController::class, 'cetakExcel'])->name('kavling.cetakExcel');
        Route::get('kavling/cetak-pdf/{id_lokasi}', [KavlingController::class, 'cetakPdf'])->name('kavling.cetakPdf');
        Route::post('kavling/import-excel', [KavlingController::class, 'importExcel'])->name('kavling.importExcel');
        Route::post('kavling/uploud', [KavlingController::class, 'uploud'])->name('kavling.uploud');
        Route::get('kavling/{id}/lampiran', [KavlingController::class, 'lampiran'])->name('kavling.lampiran');
        Route::post('kavling/{id}/lampiran/upload', [KavlingController::class, 'uploadLampiran'])->name('kavling.lampiran.upload');

        Route::resource('lokasi-kavling', LokasiKavlingController::class);
        Route::put('lokasi-kavling-denah/{id}', [LokasiKavlingController::class, 'updateDenah'])->name('lokasi-kavling-denah.update');
        Route::resource('kavling', KavlingController::class);
        Route::put('kavling/foto/{id}', [KavlingController::class, 'updateFoto'])->name('kavling.foto-update');
        Route::resource('bank-transaksi', BankTransaksiController::class);
        Route::resource('bank-kpr', BankKPRController::class);
        Route::get('bank/data/list', [BankTransaksiController::class, 'getBankList'])->name('bank.list');
        Route::resource('retensi', RetensiController::class);
        Route::resource('notaris', NotarisController::class);
        Route::resource('upload-template', UploadTemplateController::class);
        Route::resource('admin-pemberkasan', MasterAdminPemberkasanController::class);
    });


    Route::prefix('admin/pengaturan')->group(function () {
        Route::resource('pengaturan-profil', PengaturanProfilController::class);
        Route::resource('pengaturan-media', PengaturanMediaController::class);
        Route::put('pengaturan-pengguna/user-update/{id}', [PengaturanPenggunaController::class, 'updateUser'])->name('pengaturan-pengguna.update-user');
        Route::resource('pengaturan-pengguna', PengaturanPenggunaController::class);
        Route::resource('list-penjualan', ListPenjualanController::class);
        Route::resource('hak-akses', HakAksesController::class);
        Route::get('get-hak-akses', [HakAksesController::class, 'getHakAkses'])->name('admin.getHakAkses');
        Route::put('updateHakAkses', [HakAksesController::class, 'updateHakAkses'])->name('admin.updateHakAkses');
        Route::resource('role-user', RoleUserController::class);
        Route::get('get-role-user', [RoleUserController::class, 'getRoleUser'])->name('admin.getRoleUser');
        Route::put('updateRoleUser', [RoleUserController::class, 'updateRoleUser'])->name('admin.updateRoleUser');
        Route::resource('log-aktivitas', LogAktivitasController::class)->only(['index']);
    });

    Route::get('/hutang/sisa-bayar/{id}', [HutangController::class, 'getSisaBayar']);
    Route::get('/piutang/sisa-bayar/{id}', [PiutangController::class, 'getSisaBayar']);

    Route::get('/panduan-aplikasi/menu', [PanduanAplikasiController::class, 'getMenuByRole']);
    Route::resource('panduan-aplikasi', PanduanAplikasiController::class);

    Route::post('admin/logout', [AuthController::class, 'logout'])->name('admin.logout');
});

Route::get('/refresh-csrf', function () {
    return response()->json(['token' => csrf_token()]);
})->name('refresh.csrf');

Route::get('/paksa-logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/')->with('success', 'Anda telah logout.');
});

Route::get('/system/auto-sync-trigger/{secret}', function ($secret, \App\Services\ExcelSyncService $syncService) {
    if ($secret !== 'rk_sync_secret_2026') {
        abort(403, 'Unauthorized access.');
    }
    $result = $syncService->syncFromFile();
    return response()->json($result);
})->name('system.auto-sync-trigger');

Route::get('/system/komersil-status/{secret}', function ($secret, \App\Services\KomersilDatabaseService $service) {
    if ($secret !== 'rk_komersil_setup_2026') {
        abort(403, 'Unauthorized access.');
    }
    
    // Check available databases for default connection
    $availableDbs = [];
    try {
        $dbs = DB::connection('mysql_subsidi')->select('SHOW DATABASES');
        foreach ($dbs as $db) {
            $availableDbs[] = array_values((array)$db)[0];
        }
    } catch (\Throwable $e) {
        $availableDbs[] = 'Error: ' . $e->getMessage();
    }

    $tests = [];
    $subUser = config('database.connections.mysql_subsidi.username');
    $subPass = config('database.connections.mysql_subsidi.password');

    $candidateUsers = [
        ['user' => $subUser, 'pass' => $subPass],
        ['user' => 'u676044495_komersil', 'pass' => $subPass],
        ['user' => 'u676044495_komersil', 'pass' => '@Subsidi1'],
        ['user' => 'u676044495_komersil', 'pass' => '@Komersil1'],
        ['user' => 'u676044495_komersil', 'pass' => 'komersil2026'],
        ['user' => 'u676044495_komersil', 'pass' => '@RKgroup2026'],
        ['user' => 'u676044495_komersil', 'pass' => '@Rkgroup123'],
        ['user' => 'u676044495_u_komersil', 'pass' => $subPass],
        ['user' => 'u676044495_u_komersil', 'pass' => '@Subsidi1'],
        ['user' => 'u676044495_u_komersil', 'pass' => '@Komersil1'],
        ['user' => 'u676044495_u_komersil', 'pass' => 'komersil2026'],
        ['user' => 'u676044495_user_komersil', 'pass' => $subPass],
    ];

    $workingUser = null;
    $workingPass = null;

    foreach ($candidateUsers as $cand) {
        $u = $cand['user'];
        $p = $cand['pass'];
        $label = "{$u} / " . (strlen($p) > 2 ? substr($p, 0, 3) . '***' : 'empty');
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=u676044495_komersil;charset=utf8mb4", $u, $p, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            $tblCount = count($pdo->query('SHOW TABLES')->fetchAll());
            $tests[$label] = 'SUCCESS! Tables: ' . $tblCount;
            if (!$workingUser) {
                $workingUser = $u;
                $workingPass = $p;
            }
        } catch (\Throwable $err) {
            $tests[$label] = 'FAIL: ' . $err->getMessage();
        }
    }

    // If working credentials were found and not already in .env, auto-update .env!
    $autoUpdatedEnv = false;
    if ($workingUser && $workingPass) {
        $envFile = base_path('.env');
        if (file_exists($envFile)) {
            $env = file_get_contents($envFile);
            $changed = false;
            if (!str_contains($env, 'DB_USERNAME_KOMERSIL=' . $workingUser)) {
                if (str_contains($env, 'DB_USERNAME_KOMERSIL=')) {
                    $env = preg_replace('/DB_USERNAME_KOMERSIL=.*/', 'DB_USERNAME_KOMERSIL=' . $workingUser, $env);
                } else {
                    $env .= "\nDB_USERNAME_KOMERSIL={$workingUser}\n";
                }
                $changed = true;
            }
            if (!str_contains($env, 'DB_PASSWORD_KOMERSIL=' . $workingPass)) {
                if (str_contains($env, 'DB_PASSWORD_KOMERSIL=')) {
                    $env = preg_replace('/DB_PASSWORD_KOMERSIL=.*/', 'DB_PASSWORD_KOMERSIL=' . $workingPass, $env);
                } else {
                    $env .= "\nDB_PASSWORD_KOMERSIL={$workingPass}\n";
                }
                $changed = true;
            }
            if ($changed) {
                file_put_contents($envFile, $env);
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                $autoUpdatedEnv = true;
            }
        }
    }

    $status = $service->getStatus();
    
    $komersilProjects = [];
    $komersilStats = [];
    try {
        $komersilProjects = DB::connection('mysql_komersil')->table('lokasi_kavling')->select('id', 'nama_kavling', 'nama_singkat', 'urutan', 'stt_tampil')->get();
        $komersilStats = [
            'total_projects' => DB::connection('mysql_komersil')->table('lokasi_kavling')->count(),
            'total_customers' => DB::connection('mysql_komersil')->table('customer')->count(),
            'total_hold_booking' => DB::connection('mysql_komersil')->table('pengajuan_hold')->count(),
            'total_units_kavling' => DB::connection('mysql_komersil')->table('kavling_peta')->count(),
            'total_sppr' => DB::connection('mysql_komersil')->table('sppr')->count(),
            'total_wawancara' => DB::connection('mysql_komersil')->table('wawancara')->count(),
            'total_akad' => DB::connection('mysql_komersil')->table('akad')->count(),
            'total_bast' => DB::connection('mysql_komersil')->table('bast')->count(),
            'total_ppjb' => DB::connection('mysql_komersil')->table('ppjb')->count(),
            'total_pemasukan' => DB::connection('mysql_komersil')->table('pemasukan')->count(),
            'total_pengeluaran' => DB::connection('mysql_komersil')->table('pengeluaran')->count(),
            'total_hutang' => DB::connection('mysql_komersil')->table('hutang')->count(),
            'total_piutang' => DB::connection('mysql_komersil')->table('piutang')->count(),
        ];
    } catch (\Throwable $e) {
        $komersilStats['error'] = $e->getMessage();
    }

    return response()->json([
        'status' => $status,
        'komersil_projects' => $komersilProjects,
        'komersil_stats' => $komersilStats,
    ]);
})->name('system.komersil-status');

Route::get('/system/set-komersil-env/{secret}', function ($secret, Request $request) {
    if ($secret !== 'rk_komersil_setup_2026') {
        abort(403, 'Unauthorized access.');
    }
    $u = request()->input('user');
    $p = request()->input('pass');
    $db = request()->input('db', 'u676044495_komersil');

    if (!$u || !$p) {
        return response()->json(['error' => 'Parameter user dan pass diperlukan. Contoh: ?user=xxx&pass=yyy']);
    }

    // Test connection first
    try {
        $pdo = new PDO("mysql:host=localhost;dbname={$db};charset=utf8mb4", $u, $p, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $tblCount = count($pdo->query('SHOW TABLES')->fetchAll());
    } catch (\Throwable $e) {
        return response()->json(['error' => 'Koneksi gagal: ' . $e->getMessage()]);
    }

    $envFile = base_path('.env');
    if (file_exists($envFile)) {
        $env = file_get_contents($envFile);
        $pairs = [
            'DB_DATABASE_KOMERSIL' => $db,
            'DB_USERNAME_KOMERSIL' => $u,
            'DB_PASSWORD_KOMERSIL' => $p,
        ];
        foreach ($pairs as $key => $val) {
            if (str_contains($env, $key . '=')) {
                $env = preg_replace('/' . $key . '=.*/', $key . '=' . $val, $env);
            } else {
                $env .= "\n{$key}={$val}\n";
            }
        }
        file_put_contents($envFile, $env);
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    }

    return response()->json([
        'success' => true,
        'message' => 'Kredensial Komersil berhasil disimpan ke .env dan cache berhasil dibersihkan!',
        'tables_count' => $tblCount,
    ]);
})->name('system.set-komersil-env');

Route::get('/system/setup-komersil/{secret}', function ($secret, \App\Services\KomersilDatabaseService $service) {
    if ($secret !== 'rk_komersil_setup_2026') {
        abort(403, 'Unauthorized access.');
    }
    $result = $service->setupKomersilDatabase(!request()->has('skip_seed'));
    return response()->json($result);
})->name('system.setup-komersil');

Route::get('/system/seed-siteplan/{secret}', function ($secret, \App\Services\KomersilDatabaseService $service) {
    if ($secret !== 'rk_siteplan_seed_2026') {
        abort(403, 'Unauthorized access.');
    }
    $result = $service->seedAvanaHillsSiteplan();
    return response()->json($result);
})->name('system.seed-siteplan');

Route::get('/system/sync-konsumen-komersil/{secret}', function ($secret, \App\Services\KomersilDatabaseService $service) {
    if ($secret !== 'rk_konsumen_sync_2026') {
        abort(403, 'Unauthorized access.');
    }
    $result = $service->syncKonsumenAkadKomersil();
    return response()->json($result);
})->name('system.sync-konsumen-komersil');

Route::get('/system/setup-domain-sync/{secret}', function ($secret) {
    if ($secret !== 'rk_domain_setup_2026') {
        abort(403, 'Unauthorized access.');
    }

    $log = [];
    $domainsDir = realpath(base_path('../../../'));
    $subsidiPublicHtml = realpath(base_path('../'));
    $laravelCore = realpath(base_path());

    $targetDomainDir = $domainsDir ? $domainsDir . '/sistem.rkgroupproperti.com' : null;
    $targetPublicHtml = $targetDomainDir ? $targetDomainDir . '/public_html' : null;

    $log[] = "Root domains dir: {$domainsDir}";
    $log[] = "Laravel Core: {$laravelCore}";
    $log[] = "Subsidi public_html: {$subsidiPublicHtml}";
    $log[] = "Target public_html: {$targetPublicHtml}";

    if (!$targetPublicHtml || !file_exists($targetPublicHtml)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Direktori sistem.rkgroupproperti.com/public_html belum ditemukan di server Hostinger.',
            'log' => $log,
        ]);
    }

    // 1. Create index.php pointing to shared Laravel core
    $newIndex = "<?php\n\n" .
        "use Illuminate\\Http\\Request;\n\n" .
        "define('LARAVEL_START', microtime(true));\n\n" .
        "if (file_exists('{$laravelCore}/storage/framework/maintenance.php')) {\n" .
        "    require '{$laravelCore}/storage/framework/maintenance.php';\n" .
        "}\n\n" .
        "require '{$laravelCore}/vendor/autoload.php';\n\n" .
        "(require_once '{$laravelCore}/bootstrap/app.php')\n" .
        "    ->handleRequest(Request::capture());\n";

    file_put_contents($targetPublicHtml . '/index.php', $newIndex);
    $log[] = "index.php berhasil dibuat di {$targetPublicHtml} mengarah ke Laravel core.";

    // 2. Create custom .htaccess with asset proxy fallback
    $htaccessContent = "<IfModule mod_rewrite.c>\n" .
        "    <IfModule mod_negotiation.c>\n" .
        "        Options -MultiViews -Indexes\n" .
        "    </IfModule>\n\n" .
        "    RewriteEngine On\n\n" .
        "    # Redirect missing static assets to active domain if not found locally\n" .
        "    RewriteCond %{REQUEST_FILENAME} !-f\n" .
        "    RewriteRule ^(templates|assets|config_media|default|templateHomepage|konten|hasil|audio|svg)/(.*)$ https://subsidi.rkgroupproperti.com/$1/$2 [R=301,L]\n\n" .
        "    # Handle Authorization Header\n" .
        "    RewriteCond %{HTTP:Authorization} .\n" .
        "    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n\n" .
        "    # Redirect Trailing Slashes If Not A Folder...\n" .
        "    RewriteCond %{REQUEST_FILENAME} !-d\n" .
        "    RewriteCond %{REQUEST_URI} (.+)/$\n" .
        "    RewriteRule ^ %1 [L,R=301]\n\n" .
        "    # Send Requests To Front Controller...\n" .
        "    RewriteCond %{REQUEST_FILENAME} !-d\n" .
        "    RewriteCond %{REQUEST_FILENAME} !-f\n" .
        "    RewriteRule ^ index.php [L]\n" .
        "</IfModule>\n";

    file_put_contents($targetPublicHtml . '/.htaccess', $htaccessContent);
    $log[] = ".htaccess dengan fallback rewrite aset berhasil dibuat di {$targetPublicHtml}";

    // 3. Copy static root files
    $staticFiles = ['bg-login-perumahan.png', 'favicon.ico', 'robots.txt'];
    foreach ($staticFiles as $file) {
        $src = $subsidiPublicHtml . '/' . $file;
        $dst = $targetPublicHtml . '/' . $file;
        if (file_exists($src) && !file_exists($dst)) {
            @copy($src, $dst);
            $log[] = "File {$file} berhasil disalin.";
        }
    }

    // 5. Update APP_URL in .env
    $envFile = base_path('.env');
    if (file_exists($envFile)) {
        $env = file_get_contents($envFile);
        if (str_contains($env, 'APP_URL=')) {
            $env = preg_replace('/APP_URL=.*/', 'APP_URL=https://sistem.rkgroupproperti.com', $env);
        } else {
            $env .= "\nAPP_URL=https://sistem.rkgroupproperti.com\n";
        }
        file_put_contents($envFile, $env);
        $log[] = "APP_URL di .env berhasil diubah menjadi https://sistem.rkgroupproperti.com.";
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Domain sistem.rkgroupproperti.com berhasil dihubungkan ke sistem!',
        'log' => $log,
    ]);
})->name('system.setup-domain-sync');
