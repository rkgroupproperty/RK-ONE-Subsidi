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
}
