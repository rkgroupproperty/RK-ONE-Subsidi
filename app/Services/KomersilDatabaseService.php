<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
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
                        'polygon_svg' => '<polygon points="[[1]]" class="kavling-poly" data-kode="[[4]]" style="fill:[[2]];cursor:pointer;"><title>[[4]]</title></polygon>',
                        'path_svg' => '<path d="[[1]]" class="kavling-poly" data-kode="[[4]]" style="fill:[[2]];cursor:pointer;"><title>[[4]]</title></path>',
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
                        'polygon_svg' => '<polygon points="[[1]]" class="kavling-poly" data-kode="[[4]]" style="fill:[[2]];cursor:pointer;"><title>[[4]]</title></polygon>',
                        'path_svg' => '<path d="[[1]]" class="kavling-poly" data-kode="[[4]]" style="fill:[[2]];cursor:pointer;"><title>[[4]]</title></path>',
                        'footer_svg' => '</svg>',
                        'body_svg' => '',
                        'lebar' => 8264,
                        'tinggi' => 11695,
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
                'map' => '3246.12,7834.36 3488.06,7706.83 3065.15,6770.37 2823.21,6897.89 3246.12,7834.36',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '3488.06,7706.83 3729.99,7579.31 3307.08,6642.84 3065.15,6770.37 3488.06,7706.83',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '3729.99,7579.31 3971.93,7451.78 3549.02,6515.31 3307.08,6642.84 3729.99,7579.31',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '3971.93,7451.78 4213.86,7324.25 3790.95,6387.78 3549.02,6515.31 3971.93,7451.78',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '4213.86,7324.25 4455.8,7196.72 4032.89,6260.26 3790.95,6387.78 4213.86,7324.25',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '4455.8,7196.72 4697.73,7069.2 4274.82,6132.73 4032.89,6260.26 4455.8,7196.72',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '4743.45,6075.63 4837.75,6007.1 5043.49,5733.02 4949.19,5801.54 4743.45,6075.63',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '4837.75,6007.1 4932.05,5938.58 5137.79,5664.49 5043.49,5733.02 4837.75,6007.1',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '4932.05,5938.58 5026.34,5870.06 5232.08,5595.97 5137.79,5664.49 4932.05,5938.58',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'map' => '5026.34,5870.06 5120.64,5801.54 5326.38,5527.45 5232.08,5595.97 5026.34,5870.06',
                'matrik' => '',
                'keterangan' => 'Luna 93/117',
                'status' => 0,
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
                'hrg_jual' => 1260000000,
                'jenis_map' => 'polygon',
                'map' => '2823.21,6897.89 3113.53,6744.86 2896.36,6276.63 2606.04,6429.66 2823.21,6897.89',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '3113.53,6744.86 3403.85,6591.83 3186.68,6123.59 2896.36,6276.63 3113.53,6744.86',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '3403.85,6591.83 3694.18,6438.79 3477.01,5970.56 3186.68,6123.59 3403.85,6591.83',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '3694.18,6438.79 3984.5,6285.76 3767.33,5817.53 3477.01,5970.56 3694.18,6438.79',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 1092000000,
                'jenis_map' => 'polygon',
                'map' => '3984.5,6285.76 4274.82,6132.73 4057.65,5664.49 3767.33,5817.53 3984.5,6285.76',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 1108000000,
                'jenis_map' => 'polygon',
                'map' => '4171.95,5310.46 4286.25,5241.94 4491.99,4956.43 4377.69,5024.95 4171.95,5310.46',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '4286.25,5241.94 4400.55,5173.42 4606.29,4887.91 4491.99,4956.43 4286.25,5241.94',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '4400.55,5173.42 4514.85,5104.9 4720.59,4819.39 4606.29,4887.91 4400.55,5173.42',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '4514.85,5104.9 4629.15,5036.38 4834.89,4750.87 4720.59,4819.39 4514.85,5104.9',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 1308000000,
                'jenis_map' => 'polygon',
                'map' => '4629.15,5036.38 4743.45,4967.85 4949.19,4682.34 4834.89,4750.87 4629.15,5036.38',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 1476000000,
                'jenis_map' => 'polygon',
                'map' => '4295.78,4406.35 4400.55,4339.73 4606.29,4054.22 4501.52,4120.84 4295.78,4406.35',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '4191,4472.97 4295.78,4406.35 4501.52,4120.84 4396.74,4187.46 4191,4472.97',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '4086.23,4539.59 4191,4472.97 4396.74,4187.46 4291.97,4254.08 4086.23,4539.59',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '3981.45,4606.21 4086.23,4539.59 4291.97,4254.08 4187.19,4320.7 3981.45,4606.21',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '3876.68,4672.83 3981.45,4606.21 4187.19,4320.7 4082.42,4387.32 3876.68,4672.83',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 1260000000,
                'jenis_map' => 'polygon',
                'map' => '3771.9,4739.45 3876.68,4672.83 4082.42,4387.32 3977.64,4453.94 3771.9,4739.45',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '3137.54,5670.2 3371.85,5550.29 3223.26,5196.26 2988.95,5316.17 3137.54,5670.2',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '2903.22,5790.12 3137.54,5670.2 2988.95,5316.17 2754.63,5436.09 2903.22,5790.12',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 980000000,
                'jenis_map' => 'polygon',
                'map' => '2668.91,5910.03 2903.22,5790.12 2754.63,5436.09 2520.32,5556 2668.91,5910.03',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 1228000000,
                'jenis_map' => 'polygon',
                'map' => '2434.59,6029.95 2668.91,5910.03 2520.32,5556 2286,5675.91 2434.59,6029.95',
                'matrik' => '',
                'keterangan' => 'Ivy 65/91',
                'status' => 0,
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
                'hrg_jual' => 1696250000,
                'jenis_map' => 'polygon',
                'map' => '2034.54,5139.16 2346.96,4975.47 2186.94,4632.86 1874.52,4796.55 2034.54,5139.16',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'luas_tanah' => 97.5,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '2346.96,4975.47 2659.38,4811.77 2499.36,4469.16 2186.94,4632.86 2346.96,4975.47',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'luas_tanah' => 97.5,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '2659.38,4811.77 2971.8,4648.08 2811.78,4305.47 2499.36,4469.16 2659.38,4811.77',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'hrg_jual' => 1466750000,
                'jenis_map' => 'polygon',
                'map' => '3143.25,4396.84 3265.71,4315.26 3494.31,4029.75 3371.85,4111.33 3143.25,4396.84',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'luas_tanah' => 97.5,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '3265.71,4315.26 3388.18,4233.69 3616.78,3948.18 3494.31,4029.75 3265.71,4315.26',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'luas_tanah' => 97.5,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '3388.18,4233.69 3510.64,4152.11 3739.24,3866.6 3616.78,3948.18 3388.18,4233.69',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'luas_tanah' => 97.5,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '3510.64,4152.11 3633.11,4070.54 3861.71,3785.03 3739.24,3866.6 3510.64,4152.11',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'luas_tanah' => 97.5,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '3633.11,4070.54 3755.57,3988.97 3984.17,3703.46 3861.71,3785.03 3633.11,4070.54',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'luas_tanah' => 97.5,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1250000000,
                'jenis_map' => 'polygon',
                'map' => '3755.57,3988.97 3878.04,3907.39 4106.64,3621.88 3984.17,3703.46 3755.57,3988.97',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
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
                'hrg_jual' => 1687750000,
                'jenis_map' => 'polygon',
                'map' => '3878.04,3907.39 4000.5,3825.82 4229.1,3540.31 4106.64,3621.88 3878.04,3907.39',
                'matrik' => '',
                'keterangan' => 'Zora 75/97.5',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 11,
                'kode_kavling' => 'Zora 11',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 170,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1948000000,
                'jenis_map' => 'polygon',
                'map' => '2286,4396.84 2329.43,4153.58 1715.64,4174.14 1668.78,4431.1 2286,4396.84',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 12,
                'kode_kavling' => 'Zora 12',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2329.43,4153.58 2372.87,3910.33 1762.51,3917.18 1715.64,4174.14 2329.43,4153.58',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => '12A',
                'kode_kavling' => 'Zora 12A',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2372.87,3910.33 2416.3,3667.07 1809.37,3660.22 1762.51,3917.18 2372.87,3910.33',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 14,
                'kode_kavling' => 'Zora 14',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2416.3,3667.07 2459.74,3423.82 1856.23,3403.26 1809.37,3660.22 2416.3,3667.07',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 15,
                'kode_kavling' => 'Zora 15',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2459.74,3423.82 2503.17,3180.57 1903.1,3146.31 1856.23,3403.26 2459.74,3423.82',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 16,
                'kode_kavling' => 'Zora 16',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2503.17,3180.57 2546.6,2937.31 1949.96,2889.35 1903.1,3146.31 2503.17,3180.57',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 17,
                'kode_kavling' => 'Zora 17',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2546.6,2937.31 2590.04,2694.06 1996.82,2632.39 1949.96,2889.35 2546.6,2937.31',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 18,
                'kode_kavling' => 'Zora 18',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2590.04,2694.06 2633.47,2450.81 2043.68,2375.43 1996.82,2632.39 2590.04,2694.06',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 19,
                'kode_kavling' => 'Zora 19',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2633.47,2450.81 2676.91,2207.55 2090.55,2118.48 2043.68,2375.43 2633.47,2450.81',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 20,
                'kode_kavling' => 'Zora 20',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 132,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1606000000,
                'jenis_map' => 'polygon',
                'map' => '2676.91,2207.55 2720.34,1964.3 2137.41,1861.52 2090.55,2118.48 2676.91,2207.55',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 21,
                'kode_kavling' => 'Zora 21',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2720.34,1964.3 2954.66,2023.31 2687.96,1079.22 2308.86,970.73 2720.34,1964.3',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 22,
                'kode_kavling' => 'Zora 22',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '2954.66,2023.31 3188.97,2082.31 3067.05,1187.72 2687.96,1079.22 2954.66,2023.31',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 23,
                'kode_kavling' => 'Zora 23',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '3188.97,2082.31 3423.29,2141.32 3446.15,1296.21 3067.05,1187.72 3188.97,2082.31',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 24,
                'kode_kavling' => 'Zora 24',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '3423.29,2141.32 3657.6,2200.32 3825.24,1404.7 3446.15,1296.21 3423.29,2141.32',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 25,
                'kode_kavling' => 'Zora 25',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '3657.6,2200.32 3891.92,2259.33 4204.34,1513.2 3825.24,1404.7 3657.6,2200.32',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 26,
                'kode_kavling' => 'Zora 26',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 151,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1777000000,
                'jenis_map' => 'polygon',
                'map' => '3891.92,2259.33 4126.23,2318.33 4583.43,1621.69 4204.34,1513.2 3891.92,2259.33',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 27,
                'kode_kavling' => 'Zora 27',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 173,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1975000000,
                'jenis_map' => 'polygon',
                'map' => '4126.23,2318.33 4404.36,2504.86 4724.4,1895.78 4583.43,1621.69 4126.23,2318.33',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 28,
                'kode_kavling' => 'Zora 28',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 98,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1300000000,
                'jenis_map' => 'polygon',
                'map' => '4404.36,2504.86 4682.49,2691.4 4865.37,2169.87 4724.4,1895.78 4404.36,2504.86',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
                'stt_cicilan' => 0,
            ],
            [
                'id_lokasi' => 1,
                'blok' => 'ZORA',
                'no' => 29,
                'kode_kavling' => 'Zora 29',
                'panjang_kanan' => 14,
                'panjang_kiri' => 14,
                'lebar_depan' => 7,
                'lebar_belakang' => 7,
                'luas_tanah' => 112,
                'luas_bangunan' => 75,
                'tipe_bangunan' => 75,
                'daya_listrik' => 2200,
                'hrg_jual' => 1426000000,
                'jenis_map' => 'polygon',
                'map' => '4682.49,2691.4 4960.62,2877.93 5006.34,2443.96 4865.37,2169.87 4682.49,2691.4',
                'matrik' => '',
                'keterangan' => 'Zora 75/98',
                'status' => 0,
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
                'status' => 0,
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
                'status' => 0,
                'stt_cicilan' => 0,
            ];
        }

        return $results;
    }

    /**
     * Sinkronisasi data konsumen komersil Avana Hills yang sudah Akad Kredit
     */
    public function syncKonsumenAkadKomersil(): array
    {
        $logs = [];
        $targetConnections = ['mysql_komersil'];

        $konsumens = [
            [
                'nama' => 'Primanda',
                'blok' => 'B5',
                'tipe' => '97/96',
                'tgl_booking' => '2025-08-25',
                'marketing' => 'Kantor',
                'cara_bayar' => 'KPR',
                'bank' => 'BRI',
            ],
            [
                'nama' => 'Veni Apri',
                'blok' => 'A1',
                'tipe' => '125/128',
                'tgl_booking' => '2025-09-30',
                'marketing' => 'Adit',
                'cara_bayar' => 'KPR',
                'bank' => 'BRI',
            ],
            [
                'nama' => 'Heni Hafiza',
                'blok' => 'B18',
                'tipe' => '76/96',
                'tgl_booking' => '2025-12-27',
                'marketing' => 'Jody',
                'cara_bayar' => 'KPR',
                'bank' => 'BNI',
            ],
            [
                'nama' => 'Rian Mitra',
                'blok' => 'B4',
                'tipe' => '125',
                'tgl_booking' => '2026-01-20',
                'marketing' => 'Ilham',
                'cara_bayar' => 'KPR',
                'bank' => 'BRI',
            ],
            [
                'nama' => 'Esi esanti',
                'blok' => 'A2',
                'tipe' => '125',
                'tgl_booking' => '2026-02-07',
                'marketing' => 'Jody',
                'cara_bayar' => 'Cash Bertahap',
                'bank' => null,
            ],
            [
                'nama' => 'Linggar',
                'blok' => 'B2',
                'tipe' => '125',
                'tgl_booking' => '2026-03-29',
                'marketing' => 'Nico',
                'cara_bayar' => 'KPR',
                'bank' => 'BRI',
            ],
            [
                'nama' => 'Athika',
                'blok' => 'B3',
                'tipe' => '125',
                'tgl_booking' => '2026-09-11',
                'marketing' => 'Ilham',
                'cara_bayar' => 'Cash Bertahap',
                'bank' => null,
            ],
            [
                'nama' => 'Faisal Irawan',
                'blok' => 'A3',
                'tipe' => '125',
                'tgl_booking' => '2026-06-28',
                'marketing' => 'Adit',
                'cara_bayar' => 'KPR',
                'bank' => 'BRI',
            ],
        ];

        foreach ($targetConnections as $connName) {
            try {
                $conn = DB::connection($connName);
                $logs[] = "Menghubungkan ke database [{$connName}]...";

                // 1. Marketing
                $marketingNames = ['Kantor', 'Adit', 'Jody', 'Ilham', 'Nico'];
                $marketingMap = [];
                foreach ($marketingNames as $mName) {
                    $mkt = $conn->table('marketing_offline')->where('nama_marketing', $mName)->first();
                    if (!$mkt) {
                        $mktId = $conn->table('marketing_offline')->insertGetId([
                            'kode_marketing' => 'MKT-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $mName), 0, 6)),
                            'nama_marketing' => $mName,
                            'status' => 1,
                        ]);
                    } else {
                        $mktId = $mkt->id;
                    }
                    $marketingMap[$mName] = $mktId;
                }
                $logs[] = "Data Marketing dipetakan: " . json_encode($marketingMap);

                // 2. Bank
                $bankNames = ['BRI', 'BNI'];
                $bankMap = [];
                foreach ($bankNames as $bName) {
                    $b = $conn->table('bank_kpr')->where('nama', $bName)->first();
                    if (!$b) {
                        $bId = $conn->table('bank_kpr')->insertGetId(['nama' => $bName]);
                    } else {
                        $bId = $b->id;
                    }
                    $bankMap[$bName] = $bId;
                }
                $logs[] = "Data Bank KPR dipetakan: " . json_encode($bankMap);

                // 2b. Notaris
                $notaris = $conn->table('notaris')->first();
                if (!$notaris) {
                    $notarisId = $conn->table('notaris')->insertGetId([
                        'nama_notaris' => 'Notaris RK Group',
                        'alamat_notaris' => '-',
                        'telp_notaris' => '-',
                        'keterangan_notaris' => 'Notaris Utama',
                    ]);
                } else {
                    $notarisId = $notaris->id;
                }
                $logs[] = "Data Notaris dipetakan (ID: {$notarisId})";

                // 3. Progres List Penjualan - Pastikan Akad (ID 3) ada
                $progresAkad = $conn->table('progres_list_penjualan')->where('id', 3)->first();
                if (!$progresAkad) {
                    $conn->table('progres_list_penjualan')->insert([
                        'id' => 3,
                        'status_progres' => 'Akad',
                        'warna' => '#ffff00',
                        'urutan' => 3,
                        'stt_tampil' => 1,
                    ]);
                } else {
                    $conn->table('progres_list_penjualan')->where('id', 3)->update([
                        'status_progres' => 'Akad',
                        'warna' => '#ffff00',
                        'stt_tampil' => 1,
                    ]);
                }

                // 4. Jadwal Akad di tabel akad
                $akad = $conn->table('akad')->where('keterangan', 'LIKE', '%Avana Hills%')->first();
                if (!$akad) {
                    $akadId = $conn->table('akad')->insertGetId([
                        'tgl_akad' => '2026-10-24',
                        'keterangan' => 'Akad Kredit Avana Hills',
                    ]);
                } else {
                    $akadId = $akad->id;
                }

                // 5. Masukkan / Update masing-masing konsumen
                $syncedCount = 0;
                foreach ($konsumens as $k) {
                    $kavling = $conn->table('kavling_peta')
                        ->where('id_lokasi', 2)
                        ->where('kode_kavling', $k['blok'])
                        ->first();

                    $mktId = $marketingMap[$k['marketing']] ?? null;
                    $bankId = $k['bank'] ? ($bankMap[$k['bank']] ?? null) : null;
                    $kavlingId = $kavling ? $kavling->id : null;
                    $hargaJual = $kavling ? ($kavling->hrg_jual ?: 1250000000) : 1250000000;

                    $existingCust = $conn->table('customer')
                        ->where('id_lokasi', 2)
                        ->where(function ($q) use ($k, $kavlingId) {
                            $q->where('nama_lengkap', $k['nama']);
                            if ($kavlingId) {
                                $q->orWhere('id_kavling', $kavlingId);
                            }
                        })
                        ->first();

                    $custPayload = [
                        'kode_customer' => 'CUST-AVH-' . $k['blok'],
                        'nama_lengkap' => $k['nama'],
                        'id_lokasi' => 2,
                        'id_kavling' => $kavlingId,
                        'id_marketing' => $mktId,
                        'id_bank_kpr' => $bankId,
                        'jenis_pembelian' => $k['cara_bayar'],
                        'sumber_prospek' => 'Marketing ' . $k['marketing'],
                        'id_status_progres' => 3, // Akad
                        'tanggal_verif' => $k['tgl_booking'],
                        'total_harga' => $hargaJual,
                        'hrg_jual' => $hargaJual,
                        'stt_arsip' => 0,
                    ];

                    if ($existingCust) {
                        $conn->table('customer')->where('id', $existingCust->id)->update($custPayload);
                        $custId = $existingCust->id;
                    } else {
                        $custId = $conn->table('customer')->insertGetId($custPayload);
                    }

                    // Update kavling_peta
                    if ($kavlingId) {
                        $conn->table('kavling_peta')->where('id', $kavlingId)->update([
                            'id_customer' => $custId,
                            'status' => 2, // Terjual / Akad
                        ]);
                    }

                    // Update persyaratan_legal
                    $conn->table('persyaratan_legal')->updateOrInsert(
                        ['id_customer' => $custId],
                        [
                            'IPH' => 1,
                            'SHGB' => 1,
                            'SSP' => 1,
                            'BPHTB' => 1,
                            'SIKUMBANG' => 1,
                            'DAFTAR_SIKASEP' => 1,
                            'FOTO_SIKASEP' => 1,
                            'TRILOGI' => 1,
                        ]
                    );
                    $persyaratan = $conn->table('persyaratan_legal')->where('id_customer', $custId)->first();
                    $persyaratanId = $persyaratan ? $persyaratan->id : null;

                    // Update wawancara
                    $bankIdForWawancara = $bankId ?: 1;
                    $wawancara = $conn->table('wawancara')->where('id_customer', $custId)->first();
                    if (!$wawancara) {
                        $wawancaraId = $conn->table('wawancara')->insertGetId([
                            'tgl_wawancara' => $k['tgl_booking'],
                            'id_customer' => $custId,
                            'catatan_wawancara' => 'Wawancara Selesai',
                            'status' => 1,
                            'id_bank_kpr' => $bankIdForWawancara,
                        ]);
                    } else {
                        $wawancaraId = $wawancara->id;
                        $conn->table('wawancara')->where('id', $wawancaraId)->update([
                            'status' => 1,
                            'id_bank_kpr' => $bankIdForWawancara,
                        ]);
                    }

                    // Update wawancara_sp3k
                    $sp3k = $conn->table('wawancara_sp3k')->where('id_wawancara', $wawancaraId)->first();
                    if (!$sp3k) {
                        $conn->table('wawancara_sp3k')->insert([
                            'id_wawancara' => $wawancaraId,
                            'id_bank_kpr' => $bankIdForWawancara,
                            'acc_plafon' => $hargaJual,
                            'tenor' => 15,
                            'id_notaris' => $notarisId,
                            'tgl_terbit_sp3k' => $k['tgl_booking'],
                            'no_sp3k' => 'SP3K/AVH/' . $k['blok'],
                            'tgl_expired' => Carbon::parse($k['tgl_booking'])->addMonths(6)->toDateString(),
                            'catatan_acc' => 'SP3K Disetujui',
                            'status' => 1,
                            'lampiran' => '',
                        ]);
                    }

                    // Update detail_akad
                    $conn->table('detail_akad')->updateOrInsert(
                        [
                            'id_akad' => $akadId,
                            'id_customer' => $custId,
                        ],
                        [
                            'id_persyaratan' => $persyaratanId,
                            'status' => 2, // Hadir (Selesai Akad)
                            'jenis_akad' => 'Akad Kredit',
                            'keterangan' => 'Akad Kredit Selesai',
                        ]
                    );

                    $syncedCount++;
                    $logs[] = "Konsumen [{$k['nama']}] (Blok {$k['blok']}) berhasil diset Akad Kredit (ID Customer: {$custId}).";
                }

                $logs[] = "Database [{$connName}]: Berhasil mensinkronkan {$syncedCount} konsumen Akad Kredit.";
            } catch (\Exception $e) {
                $logs[] = "Error pada [{$connName}]: " . $e->getMessage();
            }
        }

        return [
            'success' => true,
            'message' => 'Berhasil memproses 8 data konsumen Avana Hills menjadi status Akad Kredit!',
            'logs' => $logs,
        ];
    }

    /**
     * Audit dan bersihkan duplikat data konsumen NEFA
     */
    public function auditAndFixNefa(): array
    {
        $conn = DB::connection('mysql_komersil');
        $logs = [];

        // 1. Audit customer NEFA
        $customers = $conn->table('customer')
            ->where('nama_lengkap', 'LIKE', '%NEFA%')
            ->get();
        $logs['customers'] = $customers;

        // 2. Audit sppr NEFA
        $spprs = $conn->table('sppr')
            ->where(function ($q) use ($customers) {
                $q->where('nama', 'LIKE', '%NEFA%');
                if ($customers->isNotEmpty()) {
                    $q->orWhereIn('id_customer', $customers->pluck('id'));
                }
            })
            ->get();
        $logs['spprs'] = $spprs;

        // 3. Jika SPPR duplikat
        if ($spprs->count() > 1) {
            // Urutkan berdasarkan ID (ambil yang pertama, hapus sisanya)
            $keepSppr = $spprs->first();
            $deleteSpprIds = $spprs->slice(1)->pluck('id')->all();
            $conn->table('sppr')->whereIn('id', $deleteSpprIds)->delete();
            $logs['deleted_sppr_ids'] = $deleteSpprIds;
            $logs['action_sppr'] = "Berhasil menghapus " . count($deleteSpprIds) . " duplikat SPPR (ID dihapus: " . implode(', ', $deleteSpprIds) . ", ID dipertahankan: {$keepSppr->id})";
        } else {
            $logs['action_sppr'] = "SPPR tidak duplikat (" . $spprs->count() . " data).";
        }

        // 4. Jika Customer duplikat
        if ($customers->count() > 1) {
            $keepCust = $customers->first();
            $deleteCustIds = $customers->slice(1)->pluck('id')->all();

            // Pastikan relasi SPPR menunjuk ke keepCust
            $conn->table('sppr')->whereIn('id_customer', $deleteCustIds)->update(['id_customer' => $keepCust->id]);

            $conn->table('customer')->whereIn('id', $deleteCustIds)->delete();
            $logs['deleted_cust_ids'] = $deleteCustIds;
            $logs['action_customer'] = "Berhasil menghapus " . count($deleteCustIds) . " duplikat Customer (ID dihapus: " . implode(', ', $deleteCustIds) . ", ID dipertahankan: {$keepCust->id})";
        } else {
            $logs['action_customer'] = "Customer tidak duplikat (" . $customers->count() . " data).";
        }

        return [
            'success' => true,
            'message' => 'Audit dan pembersihan data duplikat NEFA selesai.',
            'logs' => $logs,
        ];
    }
}
