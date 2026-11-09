<?php

namespace App\Services;

use App\Models\KavlingPeta;
use App\Models\MasterSVG;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubsidiSiteplanRestoreService
{
    /**
     * Restore original Subsidi siteplan (BIR 2 and BIR 4) in mysql_subsidi.
     */
    public function restoreSubsidiSiteplan(): array
    {
        $logs = [];
        $sqlPath = database_path('dealsaka.sql');

        if (!file_exists($sqlPath)) {
            return [
                'success' => false,
                'message' => "File dealsaka.sql tidak ditemukan di: {$sqlPath}",
                'logs' => $logs,
            ];
        }

        $targetConnections = ['mysql_subsidi'];
        try {
            if (config('database.connections.mysql.database') && config('database.connections.mysql.database') !== config('database.connections.mysql_komersil.database')) {
                $targetConnections[] = 'mysql';
            }
        } catch (\Throwable $e) {}

        $sqlContent = file_get_contents($sqlPath);
        $logs[] = "Membaca dealsaka.sql (" . strlen($sqlContent) . " bytes)...";

        foreach ($targetConnections as $connName) {
            try {
                $conn = DB::connection($connName);
                $logs[] = "=== Memproses restorasi pada koneksi [{$connName}] ===";

                // 1. Bersihkan kavling komersil yang sempat masuk ke database subsidi
                $deletedAmmaia = $conn->table('kavling_peta')
                    ->where('id_lokasi', 1)
                    ->where(function ($q) {
                        $q->where('kode_kavling', 'LIKE', 'Luna%')
                          ->orWhere('kode_kavling', 'LIKE', 'Ivy%')
                          ->orWhere('kode_kavling', 'LIKE', 'Zora%');
                    })
                    ->delete();
                $logs[] = "Menghapus {$deletedAmmaia} kavling komersil (The Ammaia Hills) yang keliru ada di lokasi 1.";

                $deletedAvana = $conn->table('kavling_peta')
                    ->where('id_lokasi', 2)
                    ->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->where('keterangan', 'LIKE', '%Tipe%')
                                ->whereRaw("kode_kavling REGEXP '^[AB][0-9]+$'");
                        });
                    })
                    ->delete();
                $logs[] = "Menghapus {$deletedAvana} kavling komersil (Avana Hills) yang keliru ada di lokasi 2.";

                // 2. Pulihkan Master SVG dari dealsaka.sql
                $this->restoreMasterSvg($conn, $sqlContent, $logs);

                // 3. Pulihkan koordinat kavling_peta untuk id_lokasi 1 dan 2 dari dealsaka.sql
                $this->restoreKavlingCoordinates($conn, $sqlContent, $logs);

            } catch (\Throwable $e) {
                $logs[] = "ERROR pada [{$connName}]: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")";
            }
        }

        // 4. Pastikan file siteplan_1.svg dan siteplan_2.svg tersedia di public/svg
        $this->ensurePublicSvgFiles($logs);

        // 5. Jalankan ulang sinkronisasi CSV untuk BIR 2 dan BIR 4
        try {
            $logs[] = "Menjalankan sinkronisasi ulang Bir2SyncService dari CSV...";
            Config::set('database.default', 'mysql_subsidi');
            DB::setDefaultConnection('mysql_subsidi');
            $bir2Result = app(\App\Services\Bir2SyncService::class)->syncFromCsv(database_path('bir_2_tahap_2.csv'));
            $logs[] = "Bir2Sync: " . ($bir2Result['status'] ?? 'done');
        } catch (\Throwable $e) {
            $logs[] = "Bir2Sync Error: " . $e->getMessage();
        }

        try {
            $logs[] = "Menjalankan sinkronisasi ulang Bir4SyncService dari CSV...";
            Config::set('database.default', 'mysql_subsidi');
            DB::setDefaultConnection('mysql_subsidi');
            $bir4Result = app(\App\Services\Bir4SyncService::class)->syncFromCsv(database_path('bir_4.csv'));
            $logs[] = "Bir4Sync: " . ($bir4Result['status'] ?? 'done');
        } catch (\Throwable $e) {
            $logs[] = "Bir4Sync Error: " . $e->getMessage();
        }

        // 6. Clear optimize cache
        try {
            Artisan::call('optimize:clear');
            $logs[] = "Cache Laravel berhasil dibersihkan.";
        } catch (\Throwable $e) {}

        return [
            'success' => true,
            'message' => 'Restorasi siteplan Subsidi (BIR 2 & BIR 4) selesai dengan sukses!',
            'logs' => $logs,
        ];
    }

    /**
     * Restore master_svg table rows 1 and 2 from dealsaka.sql
     */
    protected function restoreMasterSvg($conn, string $sqlContent, array &$logs): void
    {
        $lines = explode("\n", $sqlContent);
        $masterSvgStatements = [];
        $inMasterSvg = false;
        $currentHeader = "";
        $currentTuples = [];

        foreach ($lines as $line) {
            $t = trim($line);
            if (strpos($t, "INSERT INTO `master_svg`") !== false) {
                $inMasterSvg = true;
                $currentHeader = $t;
                $currentTuples = [];
                continue;
            }
            if ($inMasterSvg) {
                if (substr($t, 0, 1) === '(') {
                    $currentTuples[] = $t;
                }
                if (substr($t, -1) === ';') {
                    $masterSvgStatements[] = [
                        'header' => $currentHeader,
                        'tuples' => $currentTuples
                    ];
                    $inMasterSvg = false;
                }
            }
        }

        if (!empty($masterSvgStatements)) {
            // Group 0 contains tuples for lokasi 1 and 2
            $group0 = $masterSvgStatements[0];
            $conn->statement("DELETE FROM `master_svg` WHERE `id_lokasi` IN (1, 2);");
            $sqlStmt = $group0['header'] . "\n" . implode("\n", $group0['tuples']);
            $conn->statement($sqlStmt);
            $logs[] = "Berhasil memulihkan master_svg asli untuk id_lokasi 1 dan 2 dari dealsaka.sql.";

            // Kalibrasi viewBox presisi agar pas membingkai seluruh kavling BIR 2 dan BIR 4
            $row1 = $conn->table('master_svg')->where('id_lokasi', 1)->first();
            if ($row1 && !empty($row1->header_svg)) {
                $newHeaderSvg1 = preg_replace('/viewBox="[^"]+"/', 'viewBox="3000 0 35500 45500"', $row1->header_svg);
                $newHeaderSvg1 = preg_replace('/height="[^"]+"/', 'height="455mm"', $newHeaderSvg1);
                $conn->table('master_svg')->where('id_lokasi', 1)->update([
                    'header_svg' => $newHeaderSvg1,
                    'tinggi' => 455,
                    'ukuran_dashboard' => 100
                ]);
                $logs[] = "viewBox lokasi 1 dikalibrasi menjadi '3000 0 35500 45500' agar pas dan utuh.";
            }

            $row2 = $conn->table('master_svg')->where('id_lokasi', 2)->first();
            if ($row2 && !empty($row2->header_svg)) {
                $newHeaderSvg2 = preg_replace('/viewBox="[^"]+"/', 'viewBox="1500 1500 35000 57000"', $row2->header_svg);
                $newHeaderSvg2 = preg_replace('/height="[^"]+"/', 'height="570mm"', $newHeaderSvg2);
                $conn->table('master_svg')->where('id_lokasi', 2)->update([
                    'header_svg' => $newHeaderSvg2,
                    'tinggi' => 570,
                    'ukuran_dashboard' => 100
                ]);
                $logs[] = "viewBox lokasi 2 dikalibrasi menjadi '1500 1500 35000 57000' agar pas dan utuh.";
            }
        } else {
            $logs[] = "Blok INSERT INTO master_svg tidak ditemukan di dealsaka.sql.";
        }
    }

    /**
     * Restore coordinates for kavling_peta rows where id_lokasi IN (1, 2)
     */
    protected function restoreKavlingCoordinates($conn, string $sqlContent, array &$logs): void
    {
        $lines = explode("\n", $sqlContent);
        $inKavling = false;
        $updatedCount = 0;

        foreach ($lines as $line) {
            $t = trim($line);
            if (strpos($t, "INSERT INTO `kavling_peta`") !== false) {
                $inKavling = true;
                continue;
            }
            if ($inKavling) {
                if (substr($t, 0, 1) === '(') {
                    $clean = preg_replace('/^\(|\)[,;]?$/', '', $t);
                    $cols = str_getcsv($clean, ',', "'", "\\");

                    $idLokasi = (int)($cols[1] ?? 0);
                    if (in_array($idLokasi, [1, 2])) {
                        $kodeKavling = trim($cols[7] ?? '');
                        $jenisMap = trim($cols[22] ?? '');
                        $map = trim($cols[23] ?? '');
                        $matrik = trim($cols[24] ?? '');

                        if ($kodeKavling !== '') {
                            $exists = $conn->table('kavling_peta')
                                ->where('id_lokasi', $idLokasi)
                                ->where('kode_kavling', $kodeKavling)
                                ->exists();

                            if ($exists) {
                                $conn->table('kavling_peta')
                                    ->where('id_lokasi', $idLokasi)
                                    ->where('kode_kavling', $kodeKavling)
                                    ->update([
                                        'jenis_map' => $jenisMap,
                                        'map' => $map,
                                        'matrik' => $matrik,
                                    ]);
                            } else {
                                $conn->table('kavling_peta')->insert([
                                    'id_lokasi' => $idLokasi,
                                    'kode_kavling' => $kodeKavling,
                                    'jenis_map' => $jenisMap,
                                    'map' => $map,
                                    'matrik' => $matrik,
                                    'status' => 0,
                                    'status_ready' => 1,
                                    'tipe_bangunan' => 36,
                                    'luas_bangunan' => 36,
                                    'luas_tanah' => 72,
                                    'hrg_jual' => 168000000,
                                ]);
                            }
                            $updatedCount++;
                        }
                    }
                }
                if (substr($t, -1) === ';') {
                    $inKavling = false;
                }
            }
        }

        $logs[] = "Berhasil memperbarui koordinat denah untuk {$updatedCount} kavling di lokasi 1 & 2.";
    }

    /**
     * Ensure public/svg files exist for siteplan 1 and 2
     */
    protected function ensurePublicSvgFiles(array &$logs): void
    {
        $svgDir = public_path('svg');
        if (!is_dir($svgDir)) {
            @mkdir($svgDir, 0755, true);
        }

        // If siteplan_1.svg does not exist, export it from master_svg where id_lokasi = 1
        $siteplan1Path = $svgDir . '/siteplan_1.svg';
        if (!file_exists($siteplan1Path)) {
            $row1 = DB::connection('mysql_subsidi')->table('master_svg')->where('id_lokasi', 1)->first();
            if ($row1 && !empty($row1->header_svg)) {
                $fullSvg1 = $row1->header_xml . "\n" . $row1->header_svg . "\n" . $row1->footer_svg;
                file_put_contents($siteplan1Path, $fullSvg1);
                $logs[] = "Berhasil mengekstrak file public/svg/siteplan_1.svg dari master_svg.";
            }
        }

        // If siteplan_2.svg does not exist, export it from master_svg where id_lokasi = 2
        $siteplan2Path = $svgDir . '/siteplan_2.svg';
        if (!file_exists($siteplan2Path)) {
            $row2 = DB::connection('mysql_subsidi')->table('master_svg')->where('id_lokasi', 2)->first();
            if ($row2 && !empty($row2->header_svg)) {
                $fullSvg2 = $row2->header_xml . "\n" . $row2->header_svg . "\n" . $row2->footer_svg;
                file_put_contents($siteplan2Path, $fullSvg2);
                $logs[] = "Berhasil mengekstrak file public/svg/siteplan_2.svg dari master_svg.";
            }
        }
    }

    /**
     * Calibrate viewBox for BIR 2 and BIR 4 without full reseed
     */
    public function calibrateViewBoxOnly(): array
    {
        $logs = [];
        $targetConnections = ['mysql_subsidi'];
        try {
            if (config('database.connections.mysql.database') && config('database.connections.mysql.database') !== config('database.connections.mysql_komersil.database')) {
                $targetConnections[] = 'mysql';
            }
        } catch (\Throwable $e) {}

        foreach ($targetConnections as $connName) {
            try {
                $conn = DB::connection($connName);

                // BIR 2 (id_lokasi = 1): viewBox="3000 0 35500 45500"
                $row1 = $conn->table('master_svg')->where('id_lokasi', 1)->first();
                if ($row1 && !empty($row1->header_svg)) {
                    $newHeaderSvg1 = preg_replace('/viewBox="[^"]+"/', 'viewBox="3000 0 35500 45500"', $row1->header_svg);
                    $newHeaderSvg1 = preg_replace('/height="[^"]+"/', 'height="455mm"', $newHeaderSvg1);
                    $conn->table('master_svg')->where('id_lokasi', 1)->update([
                        'header_svg' => $newHeaderSvg1,
                        'tinggi' => 455,
                        'ukuran_dashboard' => 100
                    ]);
                    $logs[] = "[$connName] viewBox lokasi 1 dikalibrasi menjadi '3000 0 35500 45500'.";
                }

                // BIR 4 (id_lokasi = 2): viewBox="1500 1500 35000 57000"
                $row2 = $conn->table('master_svg')->where('id_lokasi', 2)->first();
                if ($row2 && !empty($row2->header_svg)) {
                    $newHeaderSvg2 = preg_replace('/viewBox="[^"]+"/', 'viewBox="1500 1500 35000 57000"', $row2->header_svg);
                    $newHeaderSvg2 = preg_replace('/height="[^"]+"/', 'height="570mm"', $newHeaderSvg2);
                    $conn->table('master_svg')->where('id_lokasi', 2)->update([
                        'header_svg' => $newHeaderSvg2,
                        'tinggi' => 570,
                        'ukuran_dashboard' => 100
                    ]);
                    $logs[] = "[$connName] viewBox lokasi 2 dikalibrasi menjadi '1500 1500 35000 57000'.";
                }
            } catch (\Throwable $e) {
                $logs[] = "[$connName] Error: " . $e->getMessage();
            }
        }

        try {
            Artisan::call('optimize:clear');
            $logs[] = "Cache Laravel berhasil dibersihkan.";
        } catch (\Throwable $e) {}

        return [
            'success' => true,
            'message' => 'Kalibrasi viewBox siteplan Subsidi (BIR 2 & BIR 4) berhasil!',
            'logs' => $logs
        ];
    }
}
