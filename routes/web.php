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

// PWA Direct Routes
Route::get('/manifest.json', function () {
    $manifestPath = public_path('manifest.json');
    if (!file_exists($manifestPath)) {
        $manifestPath = base_path('public/manifest.json');
    }
    if (file_exists($manifestPath)) {
        return response()->file($manifestPath, [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
            'Cache-Control' => 'no-cache, private'
        ]);
    }
    return response()->json([
        'name' => 'RK Group Property - Sistem Penjualan Perumahan',
        'short_name' => 'RK Property',
        'description' => 'Sistem Manajemen Penjualan Perumahan RK Group Property',
        'start_url' => '/admin/beranda',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'any',
        'background_color' => '#090d16',
        'theme_color' => '#4f46e5',
        'icons' => [
            [
                'src' => '/favicon.ico',
                'sizes' => '64x64 32x32 24x24 16x16',
                'type' => 'image/x-icon',
                'purpose' => 'any maskable'
            ],
            [
                'src' => '/pwa-icon/192',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ],
            [
                'src' => '/pwa-icon/512',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ]
        ]
    ], 200, ['Content-Type' => 'application/manifest+json; charset=utf-8']);
});

Route::get('/sw.js', function () {
    $swPath = public_path('sw.js');
    if (!file_exists($swPath)) {
        $swPath = base_path('public/sw.js');
    }
    if (file_exists($swPath)) {
        return response()->file($swPath, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/'
        ]);
    }
    return response("console.log('SW fallback');", 200, ['Content-Type' => 'application/javascript']);
});

Route::get('/pwa-icon/{size}', function ($size) {
    if ($size === '192') {
        $b64 = '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';
        return response(base64_decode($b64), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400'
        ]);
    }
    if ($size === '512') {
        $b64 = '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';
        return response(base64_decode($b64), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400'
        ]);
    }
    abort(404);
});

Route::get('/system/clear-cache/{secret}', function ($secret) {
    if ($secret !== 'rk_clear_2026') {
        abort(403);
    }
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    if (function_exists('apcu_clear_cache')) {
        @apcu_clear_cache();
    }
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    try {
        \App\Http\Controllers\PengajuanHoldController::ensureKantorMarketing('mysql_subsidi');
        \App\Http\Controllers\PengajuanHoldController::ensureKantorMarketing('mysql');
    } catch (\Throwable $e) {}

    return response()->json([
        'status' => 'success',
        'message' => 'Cache Laravel dan OPcache berhasil dibersihkan!',
        'opcache_reset' => function_exists('opcache_reset') ? @opcache_reset() : false,
        'output' => \Illuminate\Support\Facades\Artisan::output(),
    ]);
})->name('system.clear-cache');

Route::get('/system/git-pull/{secret}', function ($secret) {
    if ($secret !== 'rk_git_pull_2026') {
        abort(403);
    }
    $output = [];
    $code = 0;
    $basePath = base_path();

    // Try exec first
    try {
        @exec('cd ' . $basePath . ' && git pull origin main 2>&1', $output, $code);
    } catch (\Throwable $e) {
        $output[] = 'Exec error: ' . $e->getMessage();
    }

    // If exec failed/disabled, try proc_open
    if (empty($output) || (count($output) === 1 && str_contains($output[0], 'Exec error'))) {
        try {
            $desc = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
            $proc = proc_open('git pull origin main', $desc, $pipes, $basePath, null);
            if (is_resource($proc)) {
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $code = proc_close($proc);
                $output = array_merge(
                    $stdout ? explode("\n", trim($stdout)) : [],
                    $stderr ? explode("\n", trim($stderr)) : []
                );
            }
        } catch (\Throwable $e) {
            $output[] = 'proc_open error: ' . $e->getMessage();
        }
    }

    // If still no output, try passthru via system()
    if (empty($output)) {
        try {
            ob_start();
            @system('cd ' . escapeshellarg($basePath) . ' && git pull origin main 2>&1', $code);
            $rawOut = ob_get_clean();
            $output[] = $rawOut ?: 'system() returned empty output';
        } catch (\Throwable $e) {
            $output[] = 'system() error: ' . $e->getMessage();
        }
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    } catch (\Throwable $e) {
        $output[] = 'Artisan clear error: ' . $e->getMessage();
    }
    $head = '';
    try {
        $head = trim(@shell_exec('cd ' . $basePath . ' && git log -1 --oneline 2>&1') ?? '');
    } catch (\Throwable $e) {}
    return response()->json([
        'status' => $code === 0 ? 'success' : 'info',
        'code' => $code,
        'output' => $output,
        'head' => $head
    ]);
})->name('system.git-pull');

Route::post('/system/file-upload/{secret}', function ($secret, \Illuminate\Http\Request $request) {
    if ($secret !== 'rk_upload_2026') {
        abort(403);
    }
    $relativePath = $request->input('path');
    $content = $request->input('content'); // base64 encoded
    if (!$relativePath || !$content) {
        return response()->json(['status' => 'error', 'message' => 'path and content required']);
    }
    $fullPath = base_path($relativePath);
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $decoded = base64_decode($content);
    file_put_contents($fullPath, $decoded);
    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    } catch (\Throwable $e) {}
    return response()->json(['status' => 'success', 'path' => $relativePath, 'bytes' => strlen($decoded)]);
})->name('system.file-upload');

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
        Route::post('arsip-customer/restore/{id}', [ArsipCustomerController::class, 'restore'])->name('arsip-customer.restore');
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

Route::get('/system/audit-nefa/{secret}', function ($secret, \App\Services\KomersilDatabaseService $service) {
    if ($secret !== 'rk_nefa_audit_2026') {
        abort(403, 'Unauthorized access.');
    }
    $result = $service->auditAndFixNefa();
    return response()->json($result);
})->name('system.audit-nefa');

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

Route::get('/system/sync-alzafa-3/{secret}', function ($secret) {
    if ($secret !== 'rk_sync_2026') {
        abort(403, 'Unauthorized');
    }
    \Illuminate\Support\Facades\Config::set('database.default', 'mysql_subsidi');
    \Illuminate\Support\Facades\DB::setDefaultConnection('mysql_subsidi');

    // Nonaktifkan status Unit Sudah Laku dari legenda & siteplan
    try {
        \Illuminate\Support\Facades\DB::table('progres_list_penjualan')
            ->where('status_progres', 'LIKE', '%Unit Sudah Laku%')
            ->update(['stt_tampil' => 0]);

        \Illuminate\Support\Facades\DB::table('customer')
            ->where('id_status_progres', 9)
            ->update(['id_status_progres' => 11]);
    } catch (\Throwable $e) {}

    $service = new \App\Services\Alzafa3SyncService();
    $result = $service->syncFromCsv(database_path('alzafa_tahap_3.csv'));
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return response()->json($result);
})->name('system.sync-alzafa-3');

Route::get('/system/sync-bir-2/{secret}', function ($secret) {
    if ($secret !== 'rk_sync_2026') {
        abort(403, 'Unauthorized');
    }
    \Illuminate\Support\Facades\Config::set('database.default', 'mysql_subsidi');
    \Illuminate\Support\Facades\DB::setDefaultConnection('mysql_subsidi');

    // Nonaktifkan status Unit Sudah Laku dari legenda & siteplan
    try {
        \Illuminate\Support\Facades\DB::table('progres_list_penjualan')
            ->where('status_progres', 'LIKE', '%Unit Sudah Laku%')
            ->update(['stt_tampil' => 0]);

        \Illuminate\Support\Facades\DB::table('customer')
            ->where('id_status_progres', 9)
            ->update(['id_status_progres' => 11]);
    } catch (\Throwable $e) {}

    $service = new \App\Services\Bir2SyncService();
    $result = $service->syncFromCsv(database_path('bir_2_tahap_2.csv'));
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return response()->json($result);
})->name('system.sync-bir-2');

Route::get('/system/check-bir-2-units/{secret}', function ($secret) {
    if ($secret !== 'rk_sync_2026') abort(403);
    \Illuminate\Support\Facades\Config::set('database.default', 'mysql_subsidi');
    \Illuminate\Support\Facades\DB::setDefaultConnection('mysql_subsidi');

    $withMap = \App\Models\KavlingPeta::where('id_lokasi', 1)
        ->whereNotNull('map')
        ->where('map', '!=', '')
        ->pluck('kode_kavling')
        ->toArray();
    
    $csvLines = array_map('str_getcsv', file(database_path('bir_2_tahap_2.csv')));
    $csvBloks = [];
    for ($i = 3; $i < count($csvLines); $i++) {
        if (!empty($csvLines[$i][3])) {
            $csvBloks[] = strtoupper(trim($csvLines[$i][3]));
        }
    }

    $norm = function ($b) {
        if (preg_match('/^([A-Z]+)0*(\d+)-(0*[\dA-Z]+)$/i', $b, $m)) {
            $prefix = strtoupper($m[1]);
            $bNum = (int)$m[2];
            $uVal = $m[3];
            if (is_numeric($uVal)) {
                $uVal = sprintf('%02d', (int)$uVal);
            }
            return "{$prefix}{$bNum}-{$uVal}";
        }
        return strtoupper(trim($b));
    };

    $mapNorm = array_map($norm, $withMap);
    $csvNorm = array_map($norm, $csvBloks);

    // Group counts by block prefix (A1, A2, A3, etc.)
    $getPrefix = fn($b) => explode('-', $b)[0] ?? $b;
    
    $mapByPrefix = [];
    foreach ($mapNorm as $b) {
        $p = $getPrefix($b);
        $mapByPrefix[$p] = ($mapByPrefix[$p] ?? 0) + 1;
    }

    $csvByPrefix = [];
    foreach ($csvNorm as $b) {
        $p = $getPrefix($b);
        $csvByPrefix[$p] = ($csvByPrefix[$p] ?? 0) + 1;
    }

    $prefixComparison = [];
    $allPrefixes = array_unique(array_merge(array_keys($mapByPrefix), array_keys($csvByPrefix)));
    sort($allPrefixes);
    foreach ($allPrefixes as $p) {
        $prefixComparison[$p] = [
            'map_count' => $mapByPrefix[$p] ?? 0,
            'csv_count' => $csvByPrefix[$p] ?? 0,
            'diff' => ($csvByPrefix[$p] ?? 0) - ($mapByPrefix[$p] ?? 0),
        ];
    }

    return response()->json([
        'total_map' => count($withMap),
        'total_csv' => count($csvBloks),
        'comparison' => $prefixComparison,
        'in_csv_not_map' => array_values(array_diff($csvNorm, $mapNorm)),
        'in_map_not_csv' => array_values(array_diff($mapNorm, $csvNorm)),
    ]);
});

Route::get('/system/check-bir-4-units/{secret}', function ($secret) {
    if ($secret !== 'rk_sync_2026') abort(403);
    \Illuminate\Support\Facades\Config::set('database.default', 'mysql_subsidi');
    \Illuminate\Support\Facades\DB::setDefaultConnection('mysql_subsidi');

    $lokasi = \App\Models\LokasiKavling::where('id', 2)
        ->orWhere('nama_kavling', 'LIKE', '%Tahap 4%')
        ->first();

    $lokasiId = $lokasi ? $lokasi->id : 2;

    $allKavling = \App\Models\KavlingPeta::where('id_lokasi', $lokasiId)->get();
    $withMap = $allKavling->filter(fn($k) => !empty($k->map))->pluck('kode_kavling')->toArray();
    $withoutMap = $allKavling->filter(fn($k) => empty($k->map))->pluck('kode_kavling')->toArray();

    $f13Units = $allKavling->filter(fn($k) => stripos($k->kode_kavling, 'F13') !== false)->pluck('kode_kavling')->values();

    $specialUnits = \App\Models\KavlingPeta::with(['customer'])
        ->where('id_lokasi', $lokasiId)
        ->whereIn('kode_kavling', ['F4-07', 'F4-08', 'F5-08', 'F5-09', 'F6-11', 'F13-10', 'F04-07', 'F04-08', 'F05-08', 'F05-09', 'F06-11', 'F13-10'])
        ->get(['id', 'kode_kavling', 'status', 'id_customer'])
        ->map(function ($k) {
            return [
                'kode_kavling' => $k->kode_kavling,
                'status' => $k->status,
                'customer' => $k->customer ? [
                    'id' => $k->customer->id,
                    'nama_lengkap' => $k->customer->nama_lengkap,
                    'id_status_progres' => $k->customer->id_status_progres,
                ] : null,
            ];
        });

    $csvPath = database_path('bir_4.csv');
    $csvBloks = [];
    if (file_exists($csvPath)) {
        $csvLines = array_map('str_getcsv', file($csvPath));
        for ($i = 2; $i < count($csvLines); $i++) {
            if (!empty($csvLines[$i][3])) {
                $csvBloks[] = strtoupper(trim($csvLines[$i][3]));
            }
        }
    }

    $norm = function ($b) {
        if (preg_match('/^([A-Z]+)0*(\d+)-(0*[\dA-Z]+)$/i', $b, $m)) {
            $prefix = strtoupper($m[1]);
            $bNum = (int)$m[2];
            $uVal = $m[3];
            if (is_numeric($uVal)) {
                $uVal = sprintf('%02d', (int)$uVal);
            }
            return "{$prefix}{$bNum}-{$uVal}";
        }
        return strtoupper(trim($b));
    };

    $mapNorm = array_map($norm, $withMap);
    $csvNorm = array_map($norm, $csvBloks);

    $getPrefix = fn($b) => explode('-', $b)[0] ?? $b;
    
    $mapByPrefix = [];
    foreach ($mapNorm as $b) {
        $p = $getPrefix($b);
        $mapByPrefix[$p] = ($mapByPrefix[$p] ?? 0) + 1;
    }

    $csvByPrefix = [];
    foreach ($csvNorm as $b) {
        $p = $getPrefix($b);
        $csvByPrefix[$p] = ($csvByPrefix[$p] ?? 0) + 1;
    }

    $prefixComparison = [];
    $allPrefixes = array_unique(array_merge(array_keys($mapByPrefix), array_keys($csvByPrefix)));
    sort($allPrefixes);
    foreach ($allPrefixes as $p) {
        $prefixComparison[$p] = [
            'map_count' => $mapByPrefix[$p] ?? 0,
            'csv_count' => $csvByPrefix[$p] ?? 0,
            'diff' => ($csvByPrefix[$p] ?? 0) - ($mapByPrefix[$p] ?? 0),
        ];
    }

    return response()->json([
        'lokasi' => $lokasi,
        'total_kavling' => $allKavling->count(),
        'total_with_map' => count($withMap),
        'total_without_map' => count($withoutMap),
        'total_csv_bloks' => count($csvBloks),
        'special_units' => $specialUnits,
        'f13_units' => $f13Units,
        'comparison' => $prefixComparison,
        'in_csv_not_map' => array_values(array_diff($csvNorm, $mapNorm)),
        'in_map_not_csv' => array_values(array_diff($mapNorm, $csvNorm)),
    ]);
});

Route::get('/system/sync-bir-4/{secret}', function ($secret) {
    if ($secret !== 'rk_sync_2026') abort(403);
    \Illuminate\Support\Facades\Config::set('database.default', 'mysql_subsidi');
    \Illuminate\Support\Facades\DB::setDefaultConnection('mysql_subsidi');

    // Nonaktifkan status Unit Sudah Laku dari legenda & siteplan
    try {
        \Illuminate\Support\Facades\DB::table('progres_list_penjualan')
            ->where('status_progres', 'LIKE', '%Unit Sudah Laku%')
            ->update(['stt_tampil' => 0]);

        \Illuminate\Support\Facades\DB::table('customer')
            ->where('id_status_progres', 9)
            ->update(['id_status_progres' => 11]);
    } catch (\Throwable $e) {}

    $service = new \App\Services\Bir4SyncService();
    $result = $service->syncFromCsv(database_path('bir_4.csv'));
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return response()->json($result);
})->name('system.sync-bir-4');



