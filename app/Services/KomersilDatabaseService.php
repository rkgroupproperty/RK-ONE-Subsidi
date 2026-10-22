<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class KomersilDatabaseService
{
    /**
     * Master baseline tables that should be seeded into Komersil DB so admin can login
     * and system has core configuration, menus, and master reference data.
     */
    protected array $seedMasterTables = [
        'users',
        'roles',
        'role',
        'permissions',
        'hak_akses',
        'menu',
        'role_user',
        'model_has_roles',
        'konfigurasi',
        'konfigurasi_media',
        'progres_list_penjualan',
        'bank',
        'notaris',
        'pekerjaan',
        'sumber_informasi',
        'komponen_biaya',
        'document_templates',
    ];

    /**
     * Test connection and retrieve info about Subsidi and Komersil databases.
     */
    public function getStatus(): array
    {
        $status = [
            'subsidi' => [
                'configured' => false,
                'connected' => false,
                'database' => config('database.connections.mysql_subsidi.database'),
                'host' => config('database.connections.mysql_subsidi.host'),
                'tables_count' => 0,
                'error' => null,
            ],
            'komersil' => [
                'configured' => false,
                'connected' => false,
                'database' => config('database.connections.mysql_komersil.database'),
                'host' => config('database.connections.mysql_komersil.host'),
                'tables_count' => 0,
                'error' => null,
            ],
            'is_same_database' => false,
            'ready_to_clone' => false,
        ];

        // Check Subsidi
        try {
            $subsidiTables = DB::connection('mysql_subsidi')->select('SHOW TABLES');
            $status['subsidi']['configured'] = true;
            $status['subsidi']['connected'] = true;
            $status['subsidi']['tables_count'] = count($subsidiTables);
        } catch (Exception $e) {
            $status['subsidi']['error'] = $e->getMessage();
        }

        // Check Komersil
        try {
            $komersilTables = DB::connection('mysql_komersil')->select('SHOW TABLES');
            $status['komersil']['configured'] = true;
            $status['komersil']['connected'] = true;
            $status['komersil']['tables_count'] = count($komersilTables);
        } catch (Exception $e) {
            $status['komersil']['error'] = $e->getMessage();
        }

        $subDb = $status['subsidi']['database'];
        $komDb = $status['komersil']['database'];

        if ($subDb && $komDb && strtolower($subDb) === strtolower($komDb)) {
            $status['is_same_database'] = true;
        }

        if ($status['subsidi']['connected'] && $status['komersil']['connected'] && !$status['is_same_database']) {
            $status['ready_to_clone'] = true;
        }

        return $status;
    }

    /**
     * Clone all table schemas from mysql_subsidi to mysql_komersil and seed master tables.
     */
    public function setupKomersilDatabase(bool $copyMasterData = true): array
    {
        $logs = [];
        $status = $this->getStatus();

        if (!$status['subsidi']['connected']) {
            return [
                'success' => false,
                'message' => 'Koneksi ke Database Subsidi gagal: ' . ($status['subsidi']['error'] ?? 'Unknown error'),
                'logs' => $logs,
            ];
        }

        if (!$status['komersil']['connected']) {
            return [
                'success' => false,
                'message' => 'Koneksi ke Database Komersil gagal: ' . ($status['komersil']['error'] ?? 'Unknown error') . 
                             '. Pastikan database komersil sudah dibuat di cPanel MySQL Management dan nilai DB_DATABASE_KOMERSIL di file .env sudah diatur.',
                'logs' => $logs,
            ];
        }

        if ($status['is_same_database']) {
            return [
                'success' => false,
                'message' => 'Database Subsidi dan Database Komersil menggunakan database yang sama (' . $status['subsidi']['database'] . 
                             '). Buat database baru di cPanel dan ubah DB_DATABASE_KOMERSIL di file .env.',
                'logs' => $logs,
            ];
        }

        $srcConn = DB::connection('mysql_subsidi');
        $dstConn = DB::connection('mysql_komersil');

        $srcDb = $status['subsidi']['database'];
        $dstDb = $status['komersil']['database'];

        $logs[] = "Memulai sinkronisasi struktur database: [{$srcDb}] -> [{$dstDb}]";

        try {
            // Disable foreign key checks on destination
            $dstConn->statement('SET FOREIGN_KEY_CHECKS = 0;');
            $logs[] = "Foreign key checks dinonaktifkan pada [{$dstDb}].";

            $rawTables = $srcConn->select('SHOW TABLES');
            $tables = [];
            foreach ($rawTables as $tRow) {
                $arr = (array)$tRow;
                $tables[] = reset($arr);
            }

            $logs[] = "Ditemukan " . count($tables) . " tabel pada database sumber [{$srcDb}].";

            $createdCount = 0;
            $existingCount = 0;

            foreach ($tables as $tbl) {
                // Check if table already exists in destination
                $dstExists = count($dstConn->select("SHOW TABLES LIKE '{$tbl}'")) > 0;
                if ($dstExists) {
                    $existingCount++;
                    continue;
                }

                // Get CREATE TABLE statement from source
                $createRow = (array)$srcConn->selectOne("SHOW CREATE TABLE `{$tbl}`");
                $createSql = $createRow['Create Table'] ?? null;

                if ($createSql) {
                    // Replace "CREATE TABLE" with "CREATE TABLE IF NOT EXISTS"
                    $safeSql = preg_replace('/^CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $createSql, 1);
                    $dstConn->statement($safeSql);
                    $createdCount++;
                    $logs[] = "Berhasil membuat tabel: `{$tbl}`";
                }
            }

            $logs[] = "Pembuatan tabel selesai: {$createdCount} tabel baru dibuat, {$existingCount} tabel sudah ada.";

            // Seed master baseline data if requested
            if ($copyMasterData) {
                $logs[] = "Menyalin data master/administrasi ke database Komersil...";
                $seededTables = 0;

                foreach ($this->seedMasterTables as $mTable) {
                    // Check if table exists in both source and destination
                    $hasSrc = count($srcConn->select("SHOW TABLES LIKE '{$mTable}'")) > 0;
                    $hasDst = count($dstConn->select("SHOW TABLES LIKE '{$mTable}'")) > 0;

                    if (!$hasSrc || !$hasDst) {
                        continue;
                    }

                    // Check if destination table is empty
                    $dstCount = $dstConn->table($mTable)->count();
                    if ($dstCount > 0) {
                        $logs[] = "Data master `{$mTable}` sudah ada ({$dstCount} baris). Melewati.";
                        continue;
                    }

                    // Copy rows from source in chunks
                    $srcRows = $srcConn->table($mTable)->get();
                    if ($srcRows->isNotEmpty()) {
                        $chunks = array_chunk(json_decode(json_encode($srcRows), true), 100);
                        foreach ($chunks as $chunk) {
                            $dstConn->table($mTable)->insert($chunk);
                        }
                        $seededTables++;
                        $logs[] = "Berhasil menyalin " . count($srcRows) . " baris pada master `{$mTable}`.";
                    }
                }

                $logs[] = "Selesai menyalin data master ({$seededTables} tabel master tersalin).";
            }

            // Inisialisasi 2 Proyek Khusus Komersil: The Ammaia Hills dan Avana Hills
            $logs[] = "Menginisialisasi proyek khusus Komersil (The Ammaia Hills & Avana Hills)...";
            if (count($dstConn->select("SHOW TABLES LIKE 'lokasi_kavling'")) > 0) {
                $dstConn->table('lokasi_kavling')->truncate();
                $dstConn->table('lokasi_kavling')->insert([
                    [
                        'id' => 1,
                        'nama_kavling' => 'The Ammaia Hills',
                        'nama_singkat' => 'TAH',
                        'alamat' => 'The Ammaia Hills',
                        'urutan' => 1,
                        'stt_tampil' => 1,
                        'is_cluster' => 0,
                        'header' => '',
                        'no_kwitansi' => 'TAH',
                        'no_bast' => 'TAH',
                        'no_ppjb' => 'TAH',
                        'reset_nomor' => 1,
                    ],
                    [
                        'id' => 2,
                        'nama_kavling' => 'Avana Hills',
                        'nama_singkat' => 'AVH',
                        'alamat' => 'Avana Hills',
                        'urutan' => 2,
                        'stt_tampil' => 1,
                        'is_cluster' => 0,
                        'header' => '',
                        'no_kwitansi' => 'AVH',
                        'no_bast' => 'AVH',
                        'no_ppjb' => 'AVH',
                        'reset_nomor' => 1,
                    ],
                ]);
                $logs[] = "Berhasil membuat 2 proyek perumahan komersil: 1. The Ammaia Hills, 2. Avana Hills.";
            }

            // Reset semua data customer, transaksi, booking, berkas, dan pipeline menjadi 0
            $logs[] = "Mereset semua tabel data transaksi dan customer menjadi 0 (clean slate)...";
            $tablesToReset = [
                'customer',
                'customer_tempo',
                'pengajuan_hold',
                'transaksi',
                'pembayaran',
                'kavling_peta',
                'kavling',
                'arsip_customer',
                'berkas_customer',
                'pindah_unit',
                'ganti_nama',
                'pembelian_cancel',
                'sppr',
                'wawancara',
                'akad',
                'akad_detail',
                'bast',
                'ppjb',
                'balik_nama',
                'marketing_offline',
                'log_aktivitas',
                'retensi',
                'pemasukan',
                'pengeluaran',
                'hutang',
                'piutang',
                'mutasi_saldo',
                'legalitas_bphtb_ssp',
                'legalitas_listrik_air',
                'kavling_foto',
                'master_svg',
                'lokasi_kavling_perusahaan',
            ];

            $resetCount = 0;
            foreach ($tablesToReset as $resetTable) {
                if (count($dstConn->select("SHOW TABLES LIKE '{$resetTable}'")) > 0) {
                    $dstConn->table($resetTable)->truncate();
                    $resetCount++;
                }
            }
            $logs[] = "Berhasil mengosongkan {$resetCount} tabel transaksi dan customer (seluruh data Komersil bernilai 0).";

            // Re-enable foreign key checks
            $dstConn->statement('SET FOREIGN_KEY_CHECKS = 1;');
            $logs[] = "Foreign key checks diaktifkan kembali.";

            return [
                'success' => true,
                'message' => "Database Komersil [{$dstDb}] berhasil disiapkan dan disinkronkan dengan sukses!",
                'logs' => $logs,
            ];
        } catch (Exception $e) {
            try {
                $dstConn->statement('SET FOREIGN_KEY_CHECKS = 1;');
            } catch (Exception $ignored) {}

            $logs[] = "ERROR: " . $e->getMessage();
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyiapkan database: ' . $e->getMessage(),
                'logs' => $logs,
            ];
        }
    }

    /**
     * Seed interactive siteplan and units for Avana Hills (id_lokasi = 2)
     * and The Ammaia Hills (id_lokasi = 1).
     */
    public function seedAvanaHillsSiteplan(): array
    {
        $logs = [];
        $dstConn = DB::connection('mysql_komersil');

        try {
            $logs[] = "Menyiapkan Master SVG dan Unit Kavling Komersil...";

            // 1. Avana Hills (id_lokasi = 2) Master SVG
            $avanaSvgPath = public_path('svg/avana_hills.svg');
            if (!file_exists($avanaSvgPath)) {
                $avanaSvgPath = base_path('public/svg/avana_hills.svg');
            }

            if (file_exists($avanaSvgPath)) {
                $svgRaw = file_get_contents($avanaSvgPath);
                $svgClean = preg_replace('/<\?xml[^>]*\?>/i', '', $svgRaw);
                $svgClean = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svgClean);
                $svgClean = trim($svgClean);

                $svgHeader = preg_replace(
                    '/(<svg[^>]*?)\s+width="[^"]*"\s+height="[^"]*"/i',
                    '$1 width="[[lebar]]" height="[[tinggi]]"',
                    $svgClean,
                    1
                );

                $closingPos = strrpos($svgHeader, '</svg>');
                $svgHeaderOnly = ($closingPos !== false) ? substr($svgHeader, 0, $closingPos) : $svgHeader;

                $dstConn->table('master_svg')->updateOrInsert(
                    ['id_lokasi' => 2],
                    [
                        'header_xml' => '<?xml version="1.0" encoding="UTF-8"?>',
                        'header_svg' => $svgHeaderOnly,
                        'polygon_svg' => '<polygon points="[[1]]" class="kavling-poly" style="fill:[[2]];cursor:pointer;" />',
                        'path_svg' => '<path d="[[1]]" class="kavling-poly" style="fill:[[2]];cursor:pointer;" />',
                        'footer_svg' => '</svg>',
                        'body_svg' => '',
                        'lebar' => 11694,
                        'tinggi' => 8264,
                        'ukuran_dashboard' => 100,
                    ]
                );
                $logs[] = "Master SVG untuk Avana Hills (id_lokasi = 2) berhasil didaftarkan.";
            } else {
                $logs[] = "PERINGATAN: File svg/avana_hills.svg tidak ditemukan di public path.";
            }

            // 2. The Ammaia Hills (id_lokasi = 1) Master SVG
            $ammaiaSvgPath = public_path('svg/the_ammaia_hills.svg');
            if (!file_exists($ammaiaSvgPath)) {
                $ammaiaSvgPath = base_path('public/svg/the_ammaia_hills.svg');
            }

            if (file_exists($ammaiaSvgPath)) {
                $svgRaw = file_get_contents($ammaiaSvgPath);
                $svgClean = preg_replace('/<\?xml[^>]*\?>/i', '', $svgRaw);
                $svgClean = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svgClean);
                $svgClean = trim($svgClean);

                $svgHeader = preg_replace(
                    '/(<svg[^>]*?)\s+width="[^"]*"\s+height="[^"]*"/i',
                    '$1 width="[[lebar]]" height="[[tinggi]]"',
                    $svgClean,
                    1
                );

                $closingPos = strrpos($svgHeader, '</svg>');
                $svgHeaderOnly = ($closingPos !== false) ? substr($svgHeader, 0, $closingPos) : $svgHeader;

                $dstConn->table('master_svg')->updateOrInsert(
                    ['id_lokasi' => 1],
                    [
                        'header_xml' => '<?xml version="1.0" encoding="UTF-8"?>',
                        'header_svg' => $svgHeaderOnly,
                        'polygon_svg' => '<polygon points="[[1]]" class="kavling-poly" style="fill:[[2]];cursor:pointer;" />',
                        'path_svg' => '<path d="[[1]]" class="kavling-poly" style="fill:[[2]];cursor:pointer;" />',
                        'footer_svg' => '</svg>',
                        'body_svg' => '',
                        'lebar' => 1170,
                        'tinggi' => 1572,
                        'ukuran_dashboard' => 100,
                    ]
                );
                $logs[] = "Master SVG untuk The Ammaia Hills (id_lokasi = 1) berhasil didaftarkan.";
            }

            // 3. Seed 46 units for Avana Hills into kavling_peta
            $lots = $this->getAvanaLotsData();
            $unitCount = 0;

            foreach ($lots as $lot) {
                $dstConn->table('kavling_peta')->updateOrInsert(
                    [
                        'id_lokasi' => $lot['id_lokasi'],
                        'kode_kavling' => $lot['kode_kavling'],
                    ],
                    $lot
                );
                $unitCount++;
            }
            $logs[] = "Berhasil memasukkan {$unitCount} unit kavling interaktif untuk Avana Hills ke kavling_peta.";

            return [
                'success' => true,
                'message' => 'Siteplan Avana Hills berhasil diaktifkan dengan 46 unit interaktif!',
                'logs' => $logs,
            ];
        } catch (Exception $e) {
            $logs[] = "ERROR: " . $e->getMessage();
            return [
                'success' => false,
                'message' => 'Gagal setting siteplan: ' . $e->getMessage(),
                'logs' => $logs,
            ];
        }
    }

    /**
     * Compute 46 lot units for Avana Hills with exact coordinates and specs.
     */
    protected function getAvanaLotsData(): array
    {
        $lotCoords = [
            'A1' => ['x' => 9367.78, 'y' => 5448.89],
            'A2' => ['x' => 8989.44, 'y' => 5370.55],
            'A3' => ['x' => 8671.11, 'y' => 5292.22],
            'A4' => ['x' => 8317.77, 'y' => 5213.89],
            'A5' => ['x' => 7999.44, 'y' => 5135.55],
            'A6' => ['x' => 7647.78, 'y' => 5055.55],
            'A7' => ['x' => 7329.44, 'y' => 4980.55],
            'A8' => ['x' => 6976.11, 'y' => 4898.89],
            'A9' => ['x' => 6657.78, 'y' => 4822.22],
            'A10' => ['x' => 6276.11, 'y' => 4735.55],
            'A11' => ['x' => 5982.78, 'y' => 4668.89],
            'A12' => ['x' => 5699.44, 'y' => 4603.89],
            'A12A' => ['x' => 5357.78, 'y' => 4523.88],
            'A14' => ['x' => 5122.78, 'y' => 4468.89],
            'A15' => ['x' => 4824.44, 'y' => 4397.22],
            'A16' => ['x' => 4547.78, 'y' => 4333.89],
            'A17' => ['x' => 4251.11, 'y' => 4263.89],
            'A18' => ['x' => 3972.78, 'y' => 4200.56],
            'A19' => ['x' => 3676.11, 'y' => 4128.89],
            'A20' => ['x' => 3399.44, 'y' => 4065.55],
            'A21' => ['x' => 3106.11, 'y' => 3997.22],
            'A22' => ['x' => 2822.78, 'y' => 3932.22],
            'A23' => ['x' => 2524.44, 'y' => 3860.55],
            'A24' => ['x' => 2246.11, 'y' => 3798.89],
            'A25' => ['x' => 1922.77, 'y' => 3720.55],
            'A26' => ['x' => 1672.78, 'y' => 3663.89],
            
            'B1' => ['x' => 7592.78, 'y' => 4308.88],
            'B2' => ['x' => 7201.11, 'y' => 4218.88],
            'B3' => ['x' => 6836.11, 'y' => 4140.55],
            'B4' => ['x' => 6426.11, 'y' => 4045.55],
            'B5' => ['x' => 6102.78, 'y' => 3970.55],
            'B6' => ['x' => 5816.11, 'y' => 3907.22],
            'B7' => ['x' => 5527.78, 'y' => 3843.88],
            'B8' => ['x' => 5239.45, 'y' => 3780.55],
            'B9' => ['x' => 4951.11, 'y' => 3715.55],
            'B10' => ['x' => 4632.78, 'y' => 3645.55],
            'B11' => ['x' => 4349.44, 'y' => 3583.89],
            'B12' => ['x' => 4056.11, 'y' => 3518.89],
            'B12A' => ['x' => 3736.11, 'y' => 3453.89],
            'B14' => ['x' => 3477.78, 'y' => 3390.55],
            'B15' => ['x' => 3189.44, 'y' => 3325.55],
            'B16' => ['x' => 2902.78, 'y' => 3262.22],
            'B17' => ['x' => 2614.44, 'y' => 3198.89],
            'B18' => ['x' => 2326.11, 'y' => 3133.89],
            'B19' => ['x' => 2037.78, 'y' => 3070.55],
            'B20' => ['x' => 1749.44, 'y' => 3007.22],
        ];

        $ux = 0.9761;
        $uy = 0.2173;
        $vx = -0.2270;
        $vy = 0.9739;

        $results = [];

        foreach ($lotCoords as $code => $center) {
            $isBlockA = (strpos($code, 'A') === 0);
            $blok = $isBlockA ? 'A' : 'B';
            $no = substr($code, 1);

            $hw = 142; // default 6m width
            $hd = 393; // default 16m depth
            
            if (in_array($code, ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10', 'B1', 'B2', 'B3', 'B4'])) {
                $hw = 168; // 7m width
            }
            
            if ($code === 'A1') $hw = 190;
            if ($code === 'A26') $hw = 180;
            if ($code === 'B1') $hw = 190;
            if ($code === 'B20') $hw = 175;

            $cx = $center['x'];
            $cy = $center['y'];

            $offsetY = $isBlockA ? -60 : 40;
            $cx += $vx * $offsetY;
            $cy += $vy * $offsetY;

            $p1x = round($cx - $hw * $ux - $hd * $vx, 1);
            $p1y = round($cy - $hw * $uy - $hd * $vy, 1);
            
            $p2x = round($cx + $hw * $ux - $hd * $vx, 1);
            $p2y = round($cy + $hw * $uy - $hd * $vy, 1);
            
            $p3x = round($cx + $hw * $ux + $hd * $vx, 1);
            $p3y = round($cy + $hw * $uy + $hd * $vy, 1);
            
            $p4x = round($cx - $hw * $ux + $hd * $vx, 1);
            $p4y = round($cy - $hw * $uy + $hd * $vy, 1);
            
            $points = "$p1x,$p1y $p2x,$p2y $p3x,$p3y $p4x,$p4y";

            $tipeLabel = 'Tipe 75 (6X16)';
            $tipe = 75;
            $lb = 75;
            $lt = 96;
            $lebar = 6;
            $panjang = 16;
            $daya = 2200;
            $harga = 685000000;
            
            if (in_array($code, ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10', 'B1', 'B2', 'B3', 'B4'])) {
                $tipeLabel = 'Tipe 125 (7X16)';
                $tipe = 125;
                $lb = 125;
                $lt = 112;
                $lebar = 7;
                $daya = 3500;
                $harga = 985000000;
            } elseif (in_array($code, ['A11', 'A12', 'A12A', 'A14', 'A15', 'A16', 'A17', 'A18', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10', 'B11', 'B12', 'B12A', 'B14'])) {
                $tipeLabel = 'Tipe 96 (6X16)';
                $tipe = 96;
                $lb = 96;
                $lt = 96;
                $lebar = 6;
                $daya = 2200;
                $harga = 795000000;
            }
            
            if ($code === 'A1') { $lt = 181; $harga = 1250000000; $lebar = 8; }
            if ($code === 'A26') { $lt = 128; $harga = 850000000; $lebar = 8; }
            if ($code === 'B1') { $lt = 177; $harga = 1220000000; $lebar = 8; }
            if ($code === 'B20') { $lt = 128; $harga = 850000000; $lebar = 8; }

            $results[] = [
                'id_lokasi' => 2,
                'blok' => $blok,
                'no' => $no,
                'kode_kavling' => $code,
                'panjang_kanan' => $panjang,
                'panjang_kiri' => $panjang,
                'lebar_depan' => $lebar,
                'lebar_belakang' => $lebar,
                'luas_tanah' => $lt,
                'luas_bangunan' => $lb,
                'tipe_bangunan' => $tipe,
                'daya_listrik' => $daya,
                'hrg_jual' => $harga,
                'jenis_map' => 'polygon',
                'map' => $points,
                'matrik' => '',
                'keterangan' => $tipeLabel,
                'status' => 1,
                'stt_cicilan' => 0,
            ];
        }

        return $results;
    }
}
