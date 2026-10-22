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

            // 3. Seed 59 units for The Ammaia Hills into kavling_peta
            $ammaiaLots = $this->getAmmaiaLotsData();
            $ammaiaCount = 0;
            foreach ($ammaiaLots as $lot) {
                $dstConn->table('kavling_peta')->updateOrInsert(
                    [
                        'id_lokasi' => $lot['id_lokasi'],
                        'kode_kavling' => $lot['kode_kavling'],
                    ],
                    $lot
                );
                $ammaiaCount++;
            }
            $logs[] = "Berhasil memasukkan {$ammaiaCount} unit kavling interaktif untuk The Ammaia Hills ke kavling_peta.";

            // 4. Seed 46 units for Avana Hills into kavling_peta
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
                'message' => 'Siteplan The Ammaia Hills (59 unit) & Avana Hills (46 unit) berhasil diaktifkan!',
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
    /**
     * Compute 46 lot units for Avana Hills with exact divider coordinates matching CorelDRAW blueprint.
     */
        /**
     * Compute 59 lot units for The Ammaia Hills (Luna 1-10, Ivy 1-20, Zora 1-29).
     */
    protected function getAmmaiaLotsData(): array
    {
        return [
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 1,
                'kode_kavling' => 'Luna 1',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 166,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1850000000,
                'jenis_map' => 'polygon',
                'map' => '406.0,1401.0 456.0,1354.0 373.0,1246.0 323.0,1293.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 2,
                'kode_kavling' => 'Luna 2',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 117,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '456.0,1354.0 495.0,1318.0 412.0,1210.0 373.0,1246.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 3,
                'kode_kavling' => 'Luna 3',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 117,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '495.0,1318.0 534.0,1282.0 451.0,1174.0 412.0,1210.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 4,
                'kode_kavling' => 'Luna 4',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 117,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '534.0,1282.0 573.0,1246.0 490.0,1138.0 451.0,1174.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 5,
                'kode_kavling' => 'Luna 5',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 117,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '573.0,1246.0 612.0,1210.0 529.0,1102.0 490.0,1138.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 6,
                'kode_kavling' => 'Luna 6',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 117,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '612.0,1210.0 651.0,1174.0 568.0,1066.0 529.0,1102.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 1,
                'kode_kavling' => 'Ivy 1',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 126,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '267.0,1240.0 321.0,1190.0 386.0,1274.0 332.0,1324.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 2,
                'kode_kavling' => 'Ivy 2',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '321.0,1190.0 360.0,1154.0 425.0,1238.0 386.0,1274.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 3,
                'kode_kavling' => 'Ivy 3',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '360.0,1154.0 399.0,1118.0 464.0,1202.0 425.0,1238.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 4,
                'kode_kavling' => 'Ivy 4',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '399.0,1118.0 438.0,1082.0 503.0,1166.0 464.0,1202.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 5,
                'kode_kavling' => 'Ivy 5',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 105,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1150000000,
                'jenis_map' => 'polygon',
                'map' => '438.0,1082.0 483.0,1040.0 548.0,1124.0 503.0,1166.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 7,
                'kode_kavling' => 'Luna 7',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 117,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '657.0,1152.0 696.0,1116.0 613.0,1008.0 574.0,1044.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 8,
                'kode_kavling' => 'Luna 8',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 117,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '696.0,1116.0 735.0,1080.0 652.0,972.0 613.0,1008.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 9,
                'kode_kavling' => 'Luna 9',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 117,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '735.0,1080.0 774.0,1044.0 691.0,936.0 652.0,972.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'LUNA',
                'no' => 10,
                'kode_kavling' => 'Luna 10',
                'panjang_kanan' => 18,
                'panjang_kiri' => 18,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 186,
                'luas_bangunan' => 93,
                'tipe_bangunan' => 93,
                'daya_listrik' => 3500,
                'hrg_jual' => 1950000000,
                'jenis_map' => 'polygon',
                'map' => '774.0,1044.0 837.0,986.0 754.0,878.0 691.0,936.0',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 6,
                'kode_kavling' => 'Ivy 6',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 107,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1150000000,
                'jenis_map' => 'polygon',
                'map' => '502.0,972.0 548.0,930.0 613.0,1014.0 567.0,1056.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 7,
                'kode_kavling' => 'Ivy 7',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '548.0,930.0 587.0,894.0 652.0,978.0 613.0,1014.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 8,
                'kode_kavling' => 'Ivy 8',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '587.0,894.0 626.0,858.0 691.0,942.0 652.0,978.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 9,
                'kode_kavling' => 'Ivy 9',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '626.0,858.0 665.0,822.0 730.0,906.0 691.0,942.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 10,
                'kode_kavling' => 'Ivy 10',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 132,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '665.0,822.0 721.0,770.0 786.0,854.0 730.0,906.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 20,
                'kode_kavling' => 'Ivy 20',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 122,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '300.0,1162.0 352.0,1114.0 287.0,1030.0 235.0,1078.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 19,
                'kode_kavling' => 'Ivy 19',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '352.0,1114.0 391.0,1078.0 326.0,994.0 287.0,1030.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 18,
                'kode_kavling' => 'Ivy 18',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '391.0,1078.0 430.0,1042.0 365.0,958.0 326.0,994.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 17,
                'kode_kavling' => 'Ivy 17',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '430.0,1042.0 469.0,1006.0 404.0,922.0 365.0,958.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 1,
                'kode_kavling' => 'Zora 1',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 150,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1450000000,
                'jenis_map' => 'polygon',
                'map' => '165.0,988.0 220.0,939.0 290.0,1029.0 235.0,1078.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 2,
                'kode_kavling' => 'Zora 2',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 97,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1200000000,
                'jenis_map' => 'polygon',
                'map' => '220.0,939.0 259.0,903.0 329.0,993.0 290.0,1029.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 3,
                'kode_kavling' => 'Zora 3',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 97,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1200000000,
                'jenis_map' => 'polygon',
                'map' => '259.0,903.0 298.0,867.0 368.0,957.0 329.0,993.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 16,
                'kode_kavling' => 'Ivy 16',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 126,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '486.0,988.0 540.0,938.0 475.0,854.0 421.0,904.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 15,
                'kode_kavling' => 'Ivy 15',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '540.0,938.0 579.0,902.0 514.0,818.0 475.0,854.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 14,
                'kode_kavling' => 'Ivy 14',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '579.0,902.0 618.0,866.0 553.0,782.0 514.0,818.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => '12A',
                'kode_kavling' => 'Ivy 12A',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '618.0,866.0 657.0,830.0 592.0,746.0 553.0,782.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 12,
                'kode_kavling' => 'Ivy 12',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 91,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1050000000,
                'jenis_map' => 'polygon',
                'map' => '657.0,830.0 696.0,794.0 631.0,710.0 592.0,746.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'IVY',
                'no' => 11,
                'kode_kavling' => 'Ivy 11',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 153,
                'luas_bangunan' => 65,
                'tipe_bangunan' => 65,
                'daya_listrik' => 2200,
                'hrg_jual' => 1400000000,
                'jenis_map' => 'polygon',
                'map' => '696.0,794.0 762.0,733.0 697.0,649.0 631.0,710.0',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 4,
                'kode_kavling' => 'Zora 4',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 123,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1350000000,
                'jenis_map' => 'polygon',
                'map' => '346.0,823.0 395.0,778.0 465.0,868.0 416.0,913.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 5,
                'kode_kavling' => 'Zora 5',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 97,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1200000000,
                'jenis_map' => 'polygon',
                'map' => '395.0,778.0 434.0,742.0 504.0,832.0 465.0,868.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 6,
                'kode_kavling' => 'Zora 6',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 97,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1200000000,
                'jenis_map' => 'polygon',
                'map' => '434.0,742.0 473.0,706.0 543.0,796.0 504.0,832.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 7,
                'kode_kavling' => 'Zora 7',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 97,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1200000000,
                'jenis_map' => 'polygon',
                'map' => '473.0,706.0 512.0,670.0 582.0,760.0 543.0,796.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 8,
                'kode_kavling' => 'Zora 8',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 97,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1200000000,
                'jenis_map' => 'polygon',
                'map' => '512.0,670.0 551.0,634.0 621.0,724.0 582.0,760.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 9,
                'kode_kavling' => 'Zora 9',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 97,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1200000000,
                'jenis_map' => 'polygon',
                'map' => '551.0,634.0 590.0,598.0 660.0,688.0 621.0,724.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 10,
                'kode_kavling' => 'Zora 10',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 149,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1450000000,
                'jenis_map' => 'polygon',
                'map' => '590.0,598.0 650.0,542.0 720.0,632.0 660.0,688.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/97',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 11,
                'kode_kavling' => 'Zora 11',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 170,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '289.0,878.0 298.0,814.0 200.0,801.0 191.0,865.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 12,
                'kode_kavling' => 'Zora 12',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 102,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '298.0,814.0 304.0,772.0 206.0,759.0 200.0,801.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => '12A',
                'kode_kavling' => 'Zora 12A',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 102,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '304.0,772.0 310.0,730.0 212.0,717.0 206.0,759.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 14,
                'kode_kavling' => 'Zora 14',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 102,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '310.0,730.0 316.0,688.0 218.0,675.0 212.0,717.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 15,
                'kode_kavling' => 'Zora 15',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 102,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '316.0,688.0 322.0,646.0 224.0,633.0 218.0,675.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 16,
                'kode_kavling' => 'Zora 16',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 102,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '322.0,646.0 328.0,604.0 230.0,591.0 224.0,633.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 17,
                'kode_kavling' => 'Zora 17',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 102,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '328.0,604.0 334.0,562.0 236.0,549.0 230.0,591.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 18,
                'kode_kavling' => 'Zora 18',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 100,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '334.0,562.0 340.0,520.0 242.0,507.0 236.0,549.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 19,
                'kode_kavling' => 'Zora 19',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 122,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1350000000,
                'jenis_map' => 'polygon',
                'map' => '340.0,520.0 349.0,458.0 251.0,445.0 242.0,507.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 20,
                'kode_kavling' => 'Zora 20',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 132,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1400000000,
                'jenis_map' => 'polygon',
                'map' => '330.0,265.0 376.0,273.0 342.0,138.0 296.0,130.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 21,
                'kode_kavling' => 'Zora 21',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '376.0,273.0 418.0,280.0 384.0,145.0 342.0,138.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 22,
                'kode_kavling' => 'Zora 22',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '418.0,280.0 460.0,287.0 426.0,152.0 384.0,145.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 23,
                'kode_kavling' => 'Zora 23',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 116,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1350000000,
                'jenis_map' => 'polygon',
                'map' => '460.0,287.0 510.0,295.0 476.0,160.0 426.0,152.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 24,
                'kode_kavling' => 'Zora 24',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '510.0,295.0 552.0,302.0 518.0,167.0 476.0,160.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 25,
                'kode_kavling' => 'Zora 25',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '552.0,302.0 594.0,309.0 560.0,174.0 518.0,167.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 26,
                'kode_kavling' => 'Zora 26',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 151,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1500000000,
                'jenis_map' => 'polygon',
                'map' => '594.0,309.0 658.0,320.0 624.0,185.0 560.0,174.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 27,
                'kode_kavling' => 'Zora 27',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 173,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1550000000,
                'jenis_map' => 'polygon',
                'map' => '645.0,295.0 681.0,360.0 769.0,312.0 733.0,247.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 28,
                'kode_kavling' => 'Zora 28',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '681.0,360.0 705.0,402.0 793.0,354.0 769.0,312.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 29,
                'kode_kavling' => 'Zora 29',
                'panjang_kanan' => 15,
                'panjang_kiri' => 15,
                'lebar_depan' => 6.5,
                'lebar_belakang' => 6.5,
                'luas_tanah' => 112,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1350000000,
                'jenis_map' => 'polygon',
                'map' => '705.0,402.0 733.0,452.0 821.0,404.0 793.0,354.0',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 1,
                'stt_cicilan' => 0,
            ],
        ];
    }

    protected function getAvanaLotsData(): array
    {
        // 27 exact divider lines for Row A (defines 26 lots: A26 down to A1)
        $rowA_divs = [
            0  => ['p_front' => ['x' => 1662.8, 'y' => 3487.2], 'p_back' => ['x' => 1484.4, 'y' => 4253.9]],
            1  => ['p_front' => ['x' => 1951.1, 'y' => 3553.9], 'p_back' => ['x' => 1771.1, 'y' => 4320.6]],
            2  => ['p_front' => ['x' => 2237.8, 'y' => 3620.6], 'p_back' => ['x' => 2059.4, 'y' => 4387.2]],
            3  => ['p_front' => ['x' => 2526.1, 'y' => 3687.2], 'p_back' => ['x' => 2346.1, 'y' => 4453.9]],
            4  => ['p_front' => ['x' => 2812.8, 'y' => 3755.6], 'p_back' => ['x' => 2634.4, 'y' => 4522.2]],
            5  => ['p_front' => ['x' => 3101.1, 'y' => 3822.2], 'p_back' => ['x' => 2921.1, 'y' => 4588.9]],
            6  => ['p_front' => ['x' => 3387.8, 'y' => 3888.9], 'p_back' => ['x' => 3209.4, 'y' => 4655.6]],
            7  => ['p_front' => ['x' => 3676.1, 'y' => 3955.6], 'p_back' => ['x' => 3497.8, 'y' => 4723.9]],
            8  => ['p_front' => ['x' => 3962.8, 'y' => 4023.9], 'p_back' => ['x' => 3784.4, 'y' => 4790.6]],
            9  => ['p_front' => ['x' => 4251.1, 'y' => 4090.6], 'p_back' => ['x' => 4072.8, 'y' => 4857.2]],
            10 => ['p_front' => ['x' => 4537.8, 'y' => 4157.2], 'p_back' => ['x' => 4359.4, 'y' => 4923.9]],
            11 => ['p_front' => ['x' => 4826.1, 'y' => 4225.6], 'p_back' => ['x' => 4647.8, 'y' => 4992.2]],
            12 => ['p_front' => ['x' => 5112.8, 'y' => 4292.2], 'p_back' => ['x' => 4934.4, 'y' => 5058.9]],
            13 => ['p_front' => ['x' => 5401.1, 'y' => 4358.9], 'p_back' => ['x' => 5222.8, 'y' => 5125.6]],
            14 => ['p_front' => ['x' => 5689.4, 'y' => 4425.6], 'p_back' => ['x' => 5509.4, 'y' => 5192.2]],
            15 => ['p_front' => ['x' => 5976.1, 'y' => 4493.9], 'p_back' => ['x' => 5797.8, 'y' => 5260.6]],
            16 => ['p_front' => ['x' => 6264.4, 'y' => 4560.6], 'p_back' => ['x' => 6084.4, 'y' => 5327.2]],
            17 => ['p_front' => ['x' => 6599.4, 'y' => 4638.9], 'p_back' => ['x' => 6419.4, 'y' => 5405.6]],
            18 => ['p_front' => ['x' => 6934.4, 'y' => 4717.2], 'p_back' => ['x' => 6756.1, 'y' => 5483.9]],
            19 => ['p_front' => ['x' => 7269.4, 'y' => 4795.6], 'p_back' => ['x' => 7091.1, 'y' => 5562.2]],
            20 => ['p_front' => ['x' => 7606.1, 'y' => 4873.9], 'p_back' => ['x' => 7426.1, 'y' => 5640.6]],
            21 => ['p_front' => ['x' => 7941.1, 'y' => 4952.2], 'p_back' => ['x' => 7762.8, 'y' => 5718.9]],
            22 => ['p_front' => ['x' => 8276.1, 'y' => 5030.6], 'p_back' => ['x' => 8097.8, 'y' => 5797.2]],
            23 => ['p_front' => ['x' => 8611.1, 'y' => 5108.9], 'p_back' => ['x' => 8432.8, 'y' => 5875.6]],
            24 => ['p_front' => ['x' => 8947.8, 'y' => 5187.2], 'p_back' => ['x' => 8767.8, 'y' => 5953.9]],
            25 => ['p_front' => ['x' => 9282.8, 'y' => 5265.6], 'p_back' => ['x' => 9104.4, 'y' => 6032.2]],
            26 => ['p_front' => ['x' => 9666.1, 'y' => 5353.9], 'p_back' => ['x' => 9487.8, 'y' => 6120.6]],
        ];

        // 21 exact divider lines for Row B (defines 20 lots: B20 down to B1)
        $rowB_divs = [
            0  => ['p_front' => ['x' => 1702.8, 'y' => 3055.6], 'p_back' => ['x' => 1874.4, 'y' => 2287.2]],
            1  => ['p_front' => ['x' => 1991.1, 'y' => 3120.6], 'p_back' => ['x' => 2162.8, 'y' => 2350.6]],
            2  => ['p_front' => ['x' => 2279.4, 'y' => 3183.9], 'p_back' => ['x' => 2449.4, 'y' => 2415.6]],
            3  => ['p_front' => ['x' => 2572.8, 'y' => 3223.9], 'p_back' => ['x' => 2744.4, 'y' => 2453.9]],
            4  => ['p_front' => ['x' => 2856.1, 'y' => 3312.2], 'p_back' => ['x' => 3026.1, 'y' => 2542.2]],
            5  => ['p_front' => ['x' => 3144.4, 'y' => 3375.6], 'p_back' => ['x' => 3314.4, 'y' => 2607.2]],
            6  => ['p_front' => ['x' => 3432.8, 'y' => 3438.9], 'p_back' => ['x' => 3602.8, 'y' => 2670.6]],
            7  => ['p_front' => ['x' => 3721.1, 'y' => 3502.2], 'p_back' => ['x' => 3891.1, 'y' => 2733.9]],
            8  => ['p_front' => ['x' => 4009.4, 'y' => 3567.2], 'p_back' => ['x' => 4179.4, 'y' => 2798.9]],
            9  => ['p_front' => ['x' => 4297.8, 'y' => 3630.6], 'p_back' => ['x' => 4467.8, 'y' => 2862.2]],
            10 => ['p_front' => ['x' => 4586.1, 'y' => 3693.9], 'p_back' => ['x' => 4756.1, 'y' => 2925.6]],
            11 => ['p_front' => ['x' => 4874.4, 'y' => 3758.9], 'p_back' => ['x' => 5044.4, 'y' => 2990.6]],
            12 => ['p_front' => ['x' => 5162.8, 'y' => 3822.2], 'p_back' => ['x' => 5332.8, 'y' => 3053.9]],
            13 => ['p_front' => ['x' => 5451.1, 'y' => 3885.6], 'p_back' => ['x' => 5621.1, 'y' => 3117.2]],
            14 => ['p_front' => ['x' => 5739.4, 'y' => 3950.6], 'p_back' => ['x' => 5909.4, 'y' => 3182.2]],
            15 => ['p_front' => ['x' => 6027.8, 'y' => 4013.9], 'p_back' => ['x' => 6197.8, 'y' => 3245.6]],
            16 => ['p_front' => ['x' => 6316.1, 'y' => 4077.2], 'p_back' => ['x' => 6486.1, 'y' => 3308.9]],
            17 => ['p_front' => ['x' => 6699.4, 'y' => 4163.9], 'p_back' => ['x' => 6871.1, 'y' => 3393.9]],
            18 => ['p_front' => ['x' => 7084.4, 'y' => 4248.9], 'p_back' => ['x' => 7254.4, 'y' => 3478.9]],
            19 => ['p_front' => ['x' => 7469.4, 'y' => 4333.9], 'p_back' => ['x' => 7639.4, 'y' => 3565.6]],
            20 => ['p_front' => ['x' => 7852.8, 'y' => 4418.9], 'p_back' => ['x' => 8022.8, 'y' => 3650.6]],
        ];

        $lotsInOrderA = ['A26', 'A25', 'A24', 'A23', 'A22', 'A21', 'A20', 'A19', 'A18', 'A17', 'A16', 'A15', 'A14', 'A12A', 'A12', 'A11', 'A10', 'A9', 'A8', 'A7', 'A6', 'A5', 'A4', 'A3', 'A2', 'A1'];
        $lotsInOrderB = ['B20', 'B19', 'B18', 'B17', 'B16', 'B15', 'B14', 'B12A', 'B12', 'B11', 'B10', 'B9', 'B8', 'B7', 'B6', 'B5', 'B4', 'B3', 'B2', 'B1'];

        $results = [];

        // Build Row A (Lower row, facing street to the north)
        for ($i = 0; $i < count($lotsInOrderA); $i++) {
            $code = $lotsInOrderA[$i];
            $d1 = $rowA_divs[$i];
            $d2 = $rowA_divs[$i + 1];

            $points = "{$d1['p_front']['x']},{$d1['p_front']['y']} {$d2['p_front']['x']},{$d2['p_front']['y']} {$d2['p_back']['x']},{$d2['p_back']['y']} {$d1['p_back']['x']},{$d1['p_back']['y']}";

            $tipeLabel = 'Tipe 75 (6X16)';
            $tipe = 75;
            $lb = 75;
            $lt = 96;
            $lebar = 6;
            $panjang = 16;
            $daya = 2200;
            $harga = 685000000;

            if (in_array($code, ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10'])) {
                $tipeLabel = 'Tipe 125 (7X16)';
                $tipe = 125;
                $lb = 125;
                $lt = 112;
                $lebar = 7;
                $daya = 3500;
                $harga = 985000000;
            } elseif (in_array($code, ['A11', 'A12', 'A12A', 'A14', 'A15', 'A16', 'A17', 'A18'])) {
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

            $results[] = [
                'id_lokasi' => 2,
                'blok' => 'A',
                'no' => substr($code, 1),
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

        // Build Row B (Upper row, facing street to the south)
        for ($i = 0; $i < count($lotsInOrderB); $i++) {
            $code = $lotsInOrderB[$i];
            $d1 = $rowB_divs[$i];
            $d2 = $rowB_divs[$i + 1];

            $points = "{$d1['p_back']['x']},{$d1['p_back']['y']} {$d2['p_back']['x']},{$d2['p_back']['y']} {$d2['p_front']['x']},{$d2['p_front']['y']} {$d1['p_front']['x']},{$d1['p_front']['y']}";

            $tipeLabel = 'Tipe 75 (6X16)';
            $tipe = 75;
            $lb = 75;
            $lt = 96;
            $lebar = 6;
            $panjang = 16;
            $daya = 2200;
            $harga = 685000000;

            if (in_array($code, ['B1', 'B2', 'B3', 'B4'])) {
                $tipeLabel = 'Tipe 125 (7X16)';
                $tipe = 125;
                $lb = 125;
                $lt = 112;
                $lebar = 7;
                $daya = 3500;
                $harga = 985000000;
            } elseif (in_array($code, ['B5', 'B6', 'B7', 'B8', 'B9', 'B10', 'B11', 'B12', 'B12A', 'B14'])) {
                $tipeLabel = 'Tipe 96 (6X16)';
                $tipe = 96;
                $lb = 96;
                $lt = 96;
                $lebar = 6;
                $daya = 2200;
                $harga = 795000000;
            }

            if ($code === 'B1') { $lt = 177; $harga = 1220000000; $lebar = 8; }
            if ($code === 'B20') { $lt = 128; $harga = 850000000; $lebar = 8; }

            $results[] = [
                'id_lokasi' => 2,
                'blok' => 'B',
                'no' => substr($code, 1),
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
