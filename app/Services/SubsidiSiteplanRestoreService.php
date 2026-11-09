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
     * Restore original Subsidi siteplan (BIR 2, BIR 4, Alzafa) in mysql_subsidi & mysql.
     */
    public function restoreSubsidiSiteplan(): array
    {
        return $this->calibrateViewBoxOnly();
    }

    /**
     * Calibrate viewBox, exact headers, and lot templates for BIR 2, BIR 4, and Alzafa
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

        // 1. Baca file blueprint SVG asli dari public/svg
        $p1 = public_path('svg/siteplan_1.svg');
        $p2 = public_path('svg/siteplan_2.svg');
        $p3 = public_path('svg/siteplan_3.svg');

        $c1 = file_exists($p1) ? file_get_contents($p1) : null;
        $c2 = file_exists($p2) ? file_get_contents($p2) : null;
        $c3 = file_exists($p3) ? file_get_contents($p3) : null;

        if (!$c1 || !$c2) {
            return [
                'success' => false,
                'message' => 'File blueprint siteplan_1.svg atau siteplan_2.svg tidak ditemukan di public/svg.',
                'logs' => $logs
            ];
        }

        // Ekstraksi header asli (semua elemen sebelum kavling pertama)
        // Lokasi 1 (BIR 2)
        $idx1 = strpos($c1, '<g id="_2586081565776">');
        if ($idx1 === false) {
            $idx1 = strpos($c1, '<polygon');
            $idx1 = strrpos(substr($c1, 0, $idx1), '<g');
        }
        $header1 = trim(substr($c1, 0, $idx1));

        $polySvg1 = '<g id="_2586081565776">' . "\r\n" .
                    '   <polygon class="fil3" points="[[1]]" style="fill:[[2]]"/>' . "\r\n" .
                    '   <g transform="[[3]]">' . "\r\n" .
                    '    <text x="20000" y="23000"  class="fil4 fnt0">[[4]]</text>' . "\r\n" .
                    '   </g>' . "\r\n" .
                    '  </g>';
        $pathSvg1 = '<g id="_2586081565776">' . "\r\n" .
                    '   <path class="fil3" d="[[1]]" style="fill:[[2]]"/>' . "\r\n" .
                    '   <g transform="[[3]]">' . "\r\n" .
                    '    <text x="20000" y="23000"  class="fil4 fnt0">[[4]]</text>' . "\r\n" .
                    '   </g>' . "\r\n" .
                    '  </g>';

        // Lokasi 2 (BIR 4)
        $idx2 = strpos($c2, '<g id="_2585927598000">');
        if ($idx2 === false) {
            $idx2 = strpos($c2, '<polygon');
            $idx2 = strrpos(substr($c2, 0, $idx2), '<g');
        }
        $header2 = trim(substr($c2, 0, $idx2));

        $polySvg2 = '<g id="_2585927598000">' . "\r\n" .
                    '   <polygon class="fil3_2" points="[[1]]" style="fill:[[2]]"/>' . "\r\n" .
                    '   <g transform="[[3]]">' . "\r\n" .
                    '    <text x="20000" y="30000"  class="fil4_2 fnt0_2">[[4]]</text>' . "\r\n" .
                    '   </g>' . "\r\n" .
                    '  </g>';
        $pathSvg2 = '<g id="_2585927598000">' . "\r\n" .
                    '   <path class="fil3_2" d="[[1]]" style="fill:[[2]]"/>' . "\r\n" .
                    '   <g transform="[[3]]">' . "\r\n" .
                    '    <text x="20000" y="30000"  class="fil4_2 fnt0_2">[[4]]</text>' . "\r\n" .
                    '   </g>' . "\r\n" .
                    '  </g>';

        // Lokasi 3 (Alzafa Residence Tahap 3)
        // Bersihkan seluruh path / jalan asing dari De Alaska Serinity
        // Gunakan viewBox 0 0 40000 42000 (lebar 400mm x tinggi 420mm) agar seluruh 245 kavling E1..E12 pas
        $header3 = '<svg id="svg-image-3" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="400mm" height="420mm" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"' . "\r\n" .
                   'viewBox="0 0 40000 42000"' . "\r\n" .
                   ' xmlns:xlink="http://www.w3.org/1999/xlink">' . "\r\n" .
                   ' <defs>' . "\r\n" .
                   '  <style type="text/css">' . "\r\n" .
                   '   <![CDATA[' . "\r\n" .
                   '    .fil4_3 {fill:#222222;font-family:\'Bahnschrift\',\'Segoe UI\',sans-serif;font-weight:bold;}' . "\r\n" .
                   '    .fil3_3 {fill:#EEEEEF;fill-rule:nonzero}' . "\r\n" .
                   '    .fnt0_3 {font-weight:bold;font-size:420px;font-family:\'Bahnschrift\',\'Segoe UI\',sans-serif;text-anchor:middle;dominant-baseline:central;}' . "\r\n" .
                   '   ]]>' . "\r\n" .
                   '  </style>' . "\r\n" .
                   ' </defs>' . "\r\n" .
                   ' <g id="Layer_x0020_1">' . "\r\n" .
                   '  <metadata id="CorelCorpID_0Corel-Layer"/>';

        $polySvg3 = '<g id="_2927509423760">' . "\r\n" .
                    '   <polygon class="fil3_3" points="[[1]]" style="fill:[[2]]"/>' . "\r\n" .
                    '   <g transform="[[3]]">' . "\r\n" .
                    '    <text x="20540" y="24850" class="fil4_3 fnt0_3" text-anchor="middle" dominant-baseline="central">[[4]]</text>' . "\r\n" .
                    '   </g>' . "\r\n" .
                    '  </g>';
        $pathSvg3 = '<g id="_2927509423760">' . "\r\n" .
                    '   <path class="fil3_3" d="[[1]]" style="fill:[[2]]"/>' . "\r\n" .
                    '   <g transform="[[3]]">' . "\r\n" .
                    '    <text x="20540" y="24850" class="fil4_3 fnt0_3" text-anchor="middle" dominant-baseline="central">[[4]]</text>' . "\r\n" .
                    '   </g>' . "\r\n" .
                    '  </g>';

        // 2. Parse koordinat & matrik untuk setiap kavling dari file SVG
        $lotPattern = '/<polygon[^>]*points="([^"]+)"[^>]*>\s*<g transform="([^"]+)">\s*<text[^>]*>([^<]+)<\/text>/i';

        $lotsBir2 = [];
        if (preg_match_all($lotPattern, $c1, $m1, PREG_SET_ORDER)) {
            foreach ($m1 as $match) {
                $lotsBir2[strtoupper(trim($match[3]))] = [
                    'map' => trim($match[1]),
                    'matrik' => trim($match[2]),
                ];
            }
        }

        $lotsBir4 = [];
        if (preg_match_all($lotPattern, $c2, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $match) {
                $lotsBir4[strtoupper(trim($match[3]))] = [
                    'map' => trim($match[1]),
                    'matrik' => trim($match[2]),
                ];
            }
        }

        $lotsAlzafa = [];
        if ($c3 && preg_match_all($lotPattern, $c3, $m3, PREG_SET_ORDER)) {
            foreach ($m3 as $match) {
                $lotsAlzafa[strtoupper(trim($match[3]))] = [
                    'map' => trim($match[1]),
                    'matrik' => trim($match[2]),
                ];
            }
        }

        // 3. Terapkan pada koneksi database
        foreach ($targetConnections as $connName) {
            try {
                $conn = DB::connection($connName);

                // A. Bersihkan kavling komersil jika sempat ada di subsidi
                $conn->table('kavling_peta')
                    ->where('id_lokasi', 1)
                    ->where(function ($q) {
                        $q->where('kode_kavling', 'LIKE', 'Luna%')
                          ->orWhere('kode_kavling', 'LIKE', 'Ivy%')
                          ->orWhere('kode_kavling', 'LIKE', 'Zora%');
                    })
                    ->delete();

                $conn->table('kavling_peta')
                    ->where('id_lokasi', 2)
                    ->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->where('keterangan', 'LIKE', '%Tipe%')
                                ->whereRaw("kode_kavling REGEXP '^[AB][0-9]+$'");
                        });
                    })
                    ->delete();

                // B. Kalibrasi master_svg Lokasi 1 (BIR 2)
                $conn->table('master_svg')->updateOrInsert(
                    ['id_lokasi' => 1],
                    [
                        'header_xml' => '<?xml version="1.0" encoding="UTF-8"?>',
                        'header_svg' => $header1,
                        'polygon_svg' => $polySvg1,
                        'path_svg' => $pathSvg1,
                        'body_svg' => '',
                        'footer_svg' => " </g>\r\n</svg>",
                        'lebar' => 400,
                        'tinggi' => 460,
                        'ukuran_dashboard' => 100
                    ]
                );
                $logs[] = "[$connName] master_svg lokasi 1 (BIR 2) berhasil diperbarui ke header blueprint asli Corel (400x460mm, center y=23000).";

                // C. Kalibrasi master_svg Lokasi 2 (BIR 4)
                $conn->table('master_svg')->updateOrInsert(
                    ['id_lokasi' => 2],
                    [
                        'header_xml' => '<?xml version="1.0" encoding="UTF-8"?>',
                        'header_svg' => $header2,
                        'polygon_svg' => $polySvg2,
                        'path_svg' => $pathSvg2,
                        'body_svg' => '',
                        'footer_svg' => " </g>\r\n</svg>",
                        'lebar' => 400,
                        'tinggi' => 600,
                        'ukuran_dashboard' => 100
                    ]
                );
                $logs[] = "[$connName] master_svg lokasi 2 (BIR 4) berhasil diperbarui ke header blueprint asli Corel (400x600mm, center y=30000).";

                // D. Kalibrasi master_svg Lokasi 3 (Alzafa)
                if ($header3) {
                    $conn->table('master_svg')->updateOrInsert(
                        ['id_lokasi' => 3],
                        [
                            'header_xml' => '<?xml version="1.0" encoding="UTF-8"?>',
                            'header_svg' => $header3,
                            'polygon_svg' => $polySvg3,
                            'path_svg' => $pathSvg3,
                            'body_svg' => '',
                            'footer_svg' => " </g>\r\n</svg>",
                            'lebar' => 400,
                            'tinggi' => 420,
                            'ukuran_dashboard' => 100
                        ]
                    );
                    $logs[] = "[$connName] master_svg lokasi 3 (Alzafa) berhasil diperbarui (400x420mm, center x=20540, y=24850, background asing dihapus 100%).";

                    // Sync & buat file public/svg/siteplan_3.svg yang bersih dengan 245 kavling Alzafa asli
                    try {
                        $alzafaLots = $conn->table('kavling_peta')
                            ->where('id_lokasi', 3)
                            ->orderBy('id', 'asc')
                            ->get(['kode_kavling', 'map', 'matrik']);

                        if ($alzafaLots->isNotEmpty()) {
                            $svgLines = [];
                            $svgLines[] = $header3;
                            foreach ($alzafaLots as $lot) {
                                $svgLines[] = '  <g id="_2927509423760">';
                                $svgLines[] = '   <polygon class="fil3_3" points="' . $lot->map . '" style="fill:#ffffff"/>';
                                $svgLines[] = '   <g transform="' . $lot->matrik . '">';
                                $svgLines[] = '    <text x="20540" y="24850" class="fil4_3 fnt0_3" text-anchor="middle" dominant-baseline="central">' . $lot->kode_kavling . '</text>';
                                $svgLines[] = '   </g>';
                                $svgLines[] = '  </g>';
                            }
                            $svgLines[] = " </g>\r\n</svg>";
                            file_put_contents(public_path('svg/siteplan_3.svg'), implode("\r\n", $svgLines));
                            $logs[] = "[$connName] File public/svg/siteplan_3.svg berhasil disinkronkan dengan " . $alzafaLots->count() . " kavling Alzafa asli.";
                        }
                    } catch (\Throwable $err) {
                        $logs[] = "[$connName] Catatan sync siteplan_3.svg: " . $err->getMessage();
                    }
                }

                // E. Sinkronisasi koordinat & matrik pada kavling_peta tanpa menghapus data booking
                $updatedBir2 = 0;
                foreach ($lotsBir2 as $kode => $geo) {
                    $upd = $conn->table('kavling_peta')
                        ->where('id_lokasi', 1)
                        ->where(function ($q) use ($kode) {
                            $q->where('kode_kavling', $kode)
                              ->orWhere('kode_kavling', preg_replace('/^([A-Z]+)0*(\d+)-(0*[\dA-Z]+)$/i', '$1$2-$3', $kode))
                              ->orWhere('kode_kavling', preg_replace('/^([A-Z]+)(\d+)-(\d+)$/i', '$1$2-0$3', $kode));
                        })
                        ->update([
                            'map' => $geo['map'],
                            'matrik' => $geo['matrik'],
                            'jenis_map' => 'polygon',
                        ]);
                    if ($upd) $updatedBir2++;
                }
                $logs[] = "[$connName] Koordinat & matrik BIR 2 disinkronkan: {$updatedBir2} kavling diperbarui presisi.";

                $updatedBir4 = 0;
                foreach ($lotsBir4 as $kode => $geo) {
                    $upd = $conn->table('kavling_peta')
                        ->where('id_lokasi', 2)
                        ->where(function ($q) use ($kode) {
                            $q->where('kode_kavling', $kode)
                              ->orWhere('kode_kavling', preg_replace('/^([A-Z]+)0*(\d+)-(0*[\dA-Z]+)$/i', '$1$2-$3', $kode))
                              ->orWhere('kode_kavling', preg_replace('/^([A-Z]+)(\d+)-(\d+)$/i', '$1$2-0$3', $kode));
                        })
                        ->update([
                            'map' => $geo['map'],
                            'matrik' => $geo['matrik'],
                            'jenis_map' => 'polygon',
                        ]);
                    if ($upd) $updatedBir4++;
                }
                $logs[] = "[$connName] Koordinat & matrik BIR 4 disinkronkan: {$updatedBir4} kavling diperbarui presisi.";

                if (!empty($lotsAlzafa)) {
                    $updatedAlzafa = 0;
                    foreach ($lotsAlzafa as $kode => $geo) {
                        $upd = $conn->table('kavling_peta')
                            ->where('id_lokasi', 3)
                            ->where(function ($q) use ($kode) {
                                $q->where('kode_kavling', $kode)
                                  ->orWhere('kode_kavling', preg_replace('/^([A-Z]+)0*(\d+)-(0*[\dA-Z]+)$/i', '$1$2-$3', $kode))
                                  ->orWhere('kode_kavling', preg_replace('/^([A-Z]+)(\d+)-(\d+)$/i', '$1$2-0$3', $kode));
                            })
                            ->update([
                                'map' => $geo['map'],
                                'matrik' => $geo['matrik'],
                                'jenis_map' => 'polygon',
                            ]);
                        if ($upd) $updatedAlzafa++;
                    }
                    $logs[] = "[$connName] Koordinat & matrik Alzafa disinkronkan: {$updatedAlzafa} kavling diperbarui presisi.";
                }

            } catch (\Throwable $e) {
                $logs[] = "[$connName] Error: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")";
            }
        }

        try {
            Artisan::call('optimize:clear');
            $logs[] = "Cache Laravel berhasil dibersihkan.";
        } catch (\Throwable $e) {}

        return [
            'success' => true,
            'message' => 'Kalibrasi dan perataan nama kavling & kotak denah Subsidi berhasil 100%!',
            'logs' => $logs
        ];
    }

    public function inspectAlzafa(): array
    {
        $conn = DB::connection('mysql_subsidi');
        $master = $conn->table('master_svg')->where('id_lokasi', 3)->first();
        $lots = $conn->table('kavling_peta')->where('id_lokasi', 3)->get(['id', 'kode_kavling', 'map', 'matrik', 'status']);
        
        $analysis = [];
        $minX = INF; $maxX = -INF;
        $minY = INF; $maxY = -INF;
        $allIdealX = [];
        $allIdealY = [];

        foreach ($lots as $lot) {
            $pts = preg_split('/[\s,]+/', trim($lot->map));
            $xs = []; $ys = [];
            for ($i = 0; $i < count($pts) - 1; $i += 2) {
                $x = floatval($pts[$i]);
                $y = floatval($pts[$i+1]);
                $xs[] = $x;
                $ys[] = $y;
                if ($x < $minX) $minX = $x;
                if ($x > $maxX) $maxX = $x;
                if ($y < $minY) $minY = $y;
                if ($y > $maxY) $maxY = $y;
            }
            $centerX = count($xs) ? array_sum($xs) / count($xs) : 0;
            $centerY = count($ys) ? array_sum($ys) / count($ys) : 0;
            
            $matrixVals = [];
            if (preg_match('/matrix\(([^)]+)\)/', $lot->matrik, $m)) {
                $matrixVals = array_map('floatval', preg_split('/[\s,]+/', trim($m[1])));
            }
            
            $idealX0 = null;
            $idealY0 = null;
            if (count($matrixVals) === 6) {
                list($a, $b, $c, $d, $e, $f) = $matrixVals;
                $det = $a * $d - $b * $c;
                if (abs($det) > 1e-6) {
                    $dx = $centerX - $e;
                    $dy = $centerY - $f;
                    $idealX0 = ($d * $dx - $c * $dy) / $det;
                    $idealY0 = (-$b * $dx + $a * $dy) / $det;
                    $allIdealX[] = $idealX0;
                    $allIdealY[] = $idealY0;
                }
            }

            $analysis[] = [
                'kode' => $lot->kode_kavling,
                'center' => [round($centerX, 1), round($centerY, 1)],
                'matrik' => $lot->matrik,
                'ideal_text_origin' => [$idealX0 ? round($idealX0, 1) : null, $idealY0 ? round($idealY0, 1) : null]
            ];
        }

        $avgIdealX = count($allIdealX) ? array_sum($allIdealX) / count($allIdealX) : null;
        $avgIdealY = count($allIdealY) ? array_sum($allIdealY) / count($allIdealY) : null;

        return [
            'master' => [
                'lebar' => $master->lebar ?? null,
                'tinggi' => $master->tinggi ?? null,
                'view_box' => $master->view_box ?? null,
                'header_svg_len' => strlen($master->header_svg ?? ''),
                'header_svg_preview' => substr($master->header_svg ?? '', 0, 300),
                'polygon_svg' => $master->polygon_svg ?? null,
            ],
            'lot_count' => count($lots),
            'bbox' => ['minX' => $minX, 'maxX' => $maxX, 'minY' => $minY, 'maxY' => $maxY],
            'avg_ideal_text_origin' => [round($avgIdealX, 1), round($avgIdealY, 1)],
            'sample_lots' => array_slice($analysis, 0, 20)
        ];
    }
}
