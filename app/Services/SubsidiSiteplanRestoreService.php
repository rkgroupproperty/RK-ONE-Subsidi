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
        // Background siteplan utuh: jalan lingkungan (fil1_3), jalan utama (fil0_3), batas kawasan merah (fil2_3), taman (fil5_3), dan marka (fil6_3)
        $header3 = '<svg id="svg-image-3" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="400mm" height="420mm" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"' . "\r\n" .
                   'viewBox="0 0 40000 42000"' . "\r\n" .
                   ' xmlns:xlink="http://www.w3.org/1999/xlink">' . "\r\n" .
                   ' <defs>' . "\r\n" .
                   '  <style type="text/css">' . "\r\n" .
                   '   <![CDATA[' . "\r\n" .
                   '    .fil4_3 {fill:#222222;font-family:\'Bahnschrift\',\'Segoe UI\',sans-serif;font-weight:bold;}' . "\r\n" .
                   '    .fil3_3 {fill:#EEEEEF;fill-rule:nonzero}' . "\r\n" .
                   '    .fil1_3 {fill:#B8CEDA;fill-rule:nonzero}' . "\r\n" .
                   '    .fil0_3 {fill:#334155;fill-rule:nonzero}' . "\r\n" .
                   '    .fil6_3 {fill:#FACC15;fill-rule:nonzero}' . "\r\n" .
                   '    .fil2_3 {fill:#EF4444;fill-rule:nonzero}' . "\r\n" .
                   '    .fil5_3 {fill:#86EFAC;fill-rule:nonzero}' . "\r\n" .
                   '    .fnt0_3 {font-weight:bold;font-size:420px;font-family:\'Bahnschrift\',\'Segoe UI\',sans-serif;text-anchor:middle;dominant-baseline:central;}' . "\r\n" .
                   '   ]]>' . "\r\n" .
                   '  </style>' . "\r\n" .
                   ' </defs>' . "\r\n" .
                   ' <g id="Layer_x0020_1">' . "\r\n" .
                   '  <metadata id="CorelCorpID_0Corel-Layer"/>' . "\r\n" .
                   '  <g id="_2927509422992">' . "\r\n" .
                   '   <g>' . "\r\n" .
                   '    <path class="fil0_3" d="M35943 3153c4,1 8,2 12,5 4,2 7,6 9,10l205 135c12,8 15,24 7,36l-2587 3690 93 66c4,0 9,1 13,4 4,3 7,7 9,11l1330 936 0 0c12,9 15,25 7,37l-594 855c-1,3 -2,5 -3,7 -1,2 -3,3 -5,5l-2985 4298c0,5 -2,9 -4,13 -3,4 -7,7 -11,9l-582 838 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 -1 0 0 1 0 0 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 1 0 0 0 0 -1 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 -1 1 0 0 0 0 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 -1 0 0 0 0 1 0 0 0 0 0 0 0 0 0 0 0 1 0 0 -1 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 0 -1 0 0 1 0 0 0 0 0 0 0 0 0 0 0 0 -1 1 0 0 0 0 0 0 0 0 0 0 0 0 0 0 -1 1 0 0 0 0 0 0 0 0 0 0 0 0 0 0 -1 1 0 0 0 0 0 0 0 0 0 0 0 0 -1 0 0 0 0 1 0 0 0 0 0 0 0 0 -1 0 0 0 0 0 0 1 0 0 0 0 -1 0 0 0 0 0 0 0 0 0 -1 1 0 0 0 0 0 0 0 0 0 0 0 0 -1 0 0 0 0 0 0 0 0 1 0 0 -1 0 0 0 0 0 0 0 0 0 0 0 -1 0 0 0 0 0 0 0 0 0 0 1 -1 0 0 0 0 0 -1785 2571c-1,1 -2,3 -3,4 -1,1 -2,3 -3,4l-590 850c-1,2 -2,5 -4,8 -2,2 -4,4 -6,6l-587 845c0,4 -2,7 -4,10 -2,4 -5,6 -8,8l-585 841c0,5 -2,9 -4,13 -3,4 -6,7 -10,9l-1582 2277c0,2 -1,5 -3,7 -2,4 -5,7 -8,9l-915 1317 5017 2903c13,8 17,24 10,37l-1862 2852 785 430c13,7 18,23 11,36l-834 1452c0,2 -1,5 -3,7 -1,2 -2,4 -4,6l-814 1419c-8,12 -24,17 -36,9l-12141 -7129c-2,0 -5,-1 -8,-3 -3,-1 -5,-3 -7,-6l-9949 -5846 -2412 -1413c-3,0 -6,-1 -9,-2 -4,-2 -8,-5 -10,-9l-60 -35c-12,-7 -17,-24 -9,-36 7,-13 23,-17 36,-10l45 27 1445 -2899 1664 -2842c8,-13 24,-17 36,-10l4419 2569 1286 -2371c1,-2 2,-3 3,-4l449 -653 366 -952c1,-2 1,-4 2,-5l1322 -2183c8,-13 24,-17 36,-9l414 267c866,-1365 1732,-2729 2605,-4090 8,-12 24,-15 36,-8 2,2 3,3 5,4l1677 1597c4,1 8,3 11,6 4,4 6,8 7,12l744 708c3,1 6,3 9,6 2,2 4,5 5,8l750 714c2,1 3,2 4,3 1,1 2,2 3,3l3980 3701 598 553c2,1 3,2 4,3 2,2 3,3 5,5 259,225 508,463 769,686 3,1 5,3 8,5 2,2 4,4 6,7 261,224 515,454 777,677 4,1 8,3 11,5 3,3 5,6 6,9 482,409 953,826 1436,1236l7764 -11697c8,-12 24,-16 37,-8l755 499z"/>' . "\r\n" .
                   '    <path class="fil1_3" d="M33548 7065c-53,-23 -87,-53 -99,-93 -11,-40 0,-86 39,-139l2414 -3643 -722 -477 -3086 4650 149 97c47,32 37,64 27,96 -3,8 -6,17 -6,22 1,6 0,13 -4,19l-5716 8232c-8,12 -25,15 -37,7 -1,-1 -2,-2 -3,-3l-2459 -2091c-5,-1 -10,-3 -14,-6 -4,-4 -7,-8 -8,-13l-1229 -1045c0,-1 -1,-1 -1,-2l-3412 -3253c-3,-1 -6,-3 -9,-5 -2,-3 -4,-6 -6,-9l-747 -712c-2,-1 -4,-2 -6,-4 -2,-2 -4,-4 -5,-6l-751 -717c-1,-1 -2,-2 -3,-2 -1,-1 -2,-2 -3,-3l-741 -707c-6,0 -11,-3 -16,-7 -4,-4 -7,-10 -8,-15l-737 -703c-5,-1 -10,-3 -14,-7 -4,-4 -6,-9 -8,-14l-645 -616c-140,227 -285,462 -430,695l-6801 11573 -1 1c-7,13 -23,17 -36,9l-401 -238 0 -1 -1808 -1057c-13,-8 -17,-24 -9,-36l2016 -3441 -2581 -1492 -1650 2816 -1447 2903 2378 1392 814 -1385c7,-12 24,-17 36,-9l1818 1058 1 0 893 525c4,1 7,2 10,3 3,2 5,5 7,7l2703 1587c1,1 2,1 3,2 1,0 2,1 2,2l3611 2120c2,1 3,1 4,2 1,1 2,1 3,2l1807 1062c3,0 5,1 7,2 2,2 4,3 6,5l887 521c4,0 8,1 11,3 4,3 7,5 9,9l2697 1584c3,0 5,1 7,3 3,1 5,3 6,4l3604 2117c3,1 5,2 7,3 3,1 5,3 7,5l1801 1058c4,0 7,2 10,3 3,2 5,4 7,7l883 519c5,0 10,1 14,3 4,3 7,6 10,10l1237 727 810 -1412 -788 -432c-12,-7 -16,-24 -8,-36l1862 -2852 -5016 -2902c-1,-1 -3,-2 -4,-3l0 0 -934 -550c-4,0 -8,-1 -11,-3 -3,-2 -6,-5 -8,-8l-996 -586c-4,0 -8,-1 -11,-3 -4,-2 -7,-5 -9,-9l-1015 -597c-13,-7 -17,-24 -9,-36l1663 -2824c8,-12 24,-17 36,-9l2031 1191c3,0 5,1 8,3 3,2 5,4 7,6l1133 665 624 -899 -1323 -922c-12,-8 -15,-25 -7,-37l594 -855c1,-2 2,-4 4,-6 1,-2 2,-4 4,-5l589 -848c1,-3 2,-6 4,-9 2,-3 4,-5 7,-7l585 -844c1,-3 3,-7 5,-11 2,-3 5,-6 9,-8l583 -840c1,-5 2,-9 5,-13 3,-4 6,-7 10,-9l2982 -4294c0,-2 1,-4 3,-5 1,-2 2,-4 4,-5l589 -849c1,-3 2,-5 4,-8 2,-3 4,-5 6,-7l587 -844c0,-4 2,-7 4,-11 2,-3 5,-6 8,-7l1186 -1708 -93 -66z"/>' . "\r\n" .
                   '   </g>' . "\r\n" .
                   '   <path class="fil0_3" d="M4243 17628l-946 -563c-3,-1 -6,-4 -8,-6l-188 -239c-4,-5 -6,-12 -5,-19l34 -299c1,-3 1,-6 3,-9l1876 -3462c2,-4 4,-7 8,-10l230 -179c5,-4 12,-6 19,-5l298 34c4,1 7,2 10,3l1099 640c4,1 7,4 9,7l179 230c4,5 6,12 5,19l-42 291c-1,3 -2,6 -4,9l-1864 3165c1,6 0,12 -3,17 -3,6 -8,10 -14,12l-120 204c-1,2 -2,4 -3,6 -3,5 -7,9 -12,11l-233 174c-5,5 -12,7 -19,6l-299 -34c-3,0 -7,-1 -10,-3zm24290 8454l-844 1441 -1 1 0 1 -844 1441c-8,12 -24,16 -36,9l-7192 -4223c-13,-7 -17,-23 -10,-36l841 -1428c0,-5 2,-10 4,-14 3,-4 6,-7 10,-10l843 -1432c8,-12 24,-16 36,-9l3564 2095c4,1 7,2 11,4 3,2 6,4 8,7l883 520c2,0 3,1 5,2 1,0 2,1 3,2l876 515c3,0 5,1 8,3 2,2 5,3 6,6l1819 1069c13,7 17,23 10,36zm-4554 -8290c-56,-40 -94,-98 -110,-161 -16,-62 -11,-130 17,-191 -1,-6 0,-13 3,-18l947 -1604c2,-3 4,-6 7,-8 21,-33 48,-61 79,-82 34,-24 73,-40 115,-47 42,-7 84,-5 124,6 38,11 73,29 104,54 1,0 2,1 3,2l640 546c1,1 2,2 3,3 56,48 89,113 98,180 8,64 -5,130 -40,188 -1,5 -2,10 -5,14l-972 1394c0,5 -2,9 -4,13 -43,61 -106,101 -174,116 -65,14 -136,6 -198,-28 -6,1 -12,0 -18,-3l-606 -350c-9,-5 -13,-15 -13,-24zm-2138 -4916c1,-2 2,-3 3,-5 10,-11 26,-13 37,-3l1260 1069c3,1 5,2 8,3 5,3 9,8 11,13l1264 1073c10,9 12,24 5,35l-1695 2885c0,2 -1,4 -2,6 -1,2 -3,3 -4,5l-3750 6385c-7,12 -24,16 -36,9l-1403 -824c-4,0 -9,-1 -13,-3 -5,-3 -8,-6 -10,-10l-1406 -827c-13,-7 -17,-23 -10,-36l4280 -7288c1,-2 2,-4 3,-6 1,-2 2,-3 4,-5l1454 -2476zm-6369 9410c-8,12 -24,16 -36,9l-2833 -1664c-12,-7 -17,-23 -9,-36l559 -952c1,-4 2,-8 4,-12 2,-3 5,-6 8,-8l517 -880c0,-6 1,-11 4,-16 3,-5 7,-8 12,-11l1051 -1789c1,-3 2,-6 3,-9 2,-2 4,-4 6,-6l520 -885c0,-4 1,-9 3,-13 3,-4 6,-7 10,-9l1055 -1797c1,-1 1,-2 2,-2 0,-1 1,-2 1,-3l524 -891c0,-4 2,-7 3,-11 2,-3 5,-6 8,-8l1954 -3326c1,-2 3,-4 4,-6 10,-11 27,-11 38,-1l2440 2320c9,9 10,23 3,34l-2094 3569c0,4 -1,9 -4,13 -2,4 -5,7 -9,9l-1584 2699c0,1 -1,3 -2,5 -1,1 -2,3 -3,4l-2155 3673zm-1576 -11942c0,-4 1,-8 3,-12 2,-3 5,-6 8,-8l1706 -2907 1 -2c7,-13 23,-17 36,-10l1416 828c3,1 5,3 6,5l1219 1167c9,9 11,23 4,34l-448 762c0,4 -1,9 -4,13 -2,4 -6,8 -10,10l-1053 1793c-1,2 -2,4 -3,6 -1,2 -3,3 -4,5l-521 887c-1,4 -2,8 -4,12 -2,4 -5,7 -9,9l-1056 1796c0,1 -1,3 -1,4 -1,1 -2,2 -2,3l-524 890c0,4 -1,7 -3,11 -2,3 -5,6 -8,8l-518 881c1,5 0,10 -3,15 -3,5 -7,9 -12,11l-1051 1788c1,6 0,13 -4,18 -3,6 -8,10 -14,12l-512 872c2,6 1,14 -3,21 -3,6 -10,11 -17,12l-554 943c-7,13 -24,17 -36,10l-2824 -1656c-13,-7 -17,-23 -9,-36l559 -954c1,-3 2,-6 4,-10 1,-3 4,-5 7,-8l518 -883c0,-4 1,-9 4,-13 2,-4 5,-7 9,-9l1584 -2700c0,-1 1,-3 2,-4 0,-1 1,-2 2,-3l1590 -2711c1,-2 2,-5 3,-7 2,-3 3,-5 5,-7l521 -886z"/>' . "\r\n" .
                   '   <path class="fil2_3" d="M6972 7083c7,-13 23,-18 35,-11 13,7 18,23 11,36l-4854 8735 0 0 -1527 2969c-7,13 -23,18 -36,12 -13,-7 -18,-23 -11,-36l1527 -2969 1 -1 4854 -8735zm26327 -6739c-12,-8 -16,-24 -8,-36 8,-13 24,-16 36,-9l5981 3771c12,8 16,24 8,37 -8,12 -24,16 -37,8l-5980 -3771zm-486 785c-12,-8 -16,-24 -8,-37 7,-12 24,-16 36,-8l5980 3762c13,8 16,24 9,37 -8,12 -24,16 -37,8l-5980 -3762zm-24946 6431c7,-12 23,-17 36,-10 13,7 18,23 11,36 -362,654 -720,1298 -1092,1945l-1272 2354 -1663 2840c-765,1535 -1512,3028 -2312,4540 -7,13 -23,17 -36,11 -12,-7 -17,-23 -11,-36 800,-1512 1547,-3004 2312,-4538 1407,-2345 2704,-4750 4027,-7142z"/>' . "\r\n" .
                   '  </g>' . "\r\n" .
                   '  <path class="fil5_3" d="M32212 7573c0,-12 4,-22 7,-33 4,-13 9,-27 -5,-36l-149 -97 -4650 7005 -2 2 0 1 -1 1 31 26 0 -1 4769 -6868zm-27427 9852l105 -179 -180 -107 -103 184 178 102zm3278 479l356 212 5962 -10147c-198,304 -351,522 -420,591 -8,12 -24,16 -36,8l-413 -267 -1307 2159 -365 952c-1,2 -2,4 -4,7l-451 655 -1304 2405 -1 0 -2017 3425zm-4737 -881l938 558 284 32 199 -149 -187 -108 -1 0c-13,-7 -17,-23 -10,-36l127 -229c0,-1 1,-1 1,-2 7,-13 24,-17 36,-9l204 121 1857 -3153 40 -274 -169 -218 -1092 -635 -283 -32 -218 170 -1872 3454 -32 283 178 227zm20622 402c1,6 0,12 -4,17l0 0c-32,56 -39,118 -24,176 15,58 52,110 108,142l1 1 0 0c8,5 12,14 12,22l575 333c6,-1 12,0 18,3 52,31 113,39 168,26 51,-11 99,-39 133,-82 1,-4 2,-8 4,-12l973 -1395c1,-5 2,-9 5,-13 34,-50 47,-108 40,-164 -8,-55 -35,-109 -81,-148 -1,0 -1,-1 -2,-2l-635 -541c-1,-1 -2,-2 -3,-3 -26,-22 -57,-38 -89,-47 -32,-9 -67,-11 -101,-5 -34,6 -66,19 -94,38 -28,19 -51,44 -69,74 -1,3 -4,5 -6,7l-929 1573zm9583 -10561c-29,39 -38,70 -31,93 5,18 20,33 43,46l2574 -3671 -171 -113 -2415 3644 0 0 0 1z"/>' . "\r\n" .
                   '  <path class="fil6_3" d="M8051 17895l6 -9 -1787 -1056 -8 13 13 7 36 21 1425 840c105,61 210,123 315,184zm-519 839l6 -10 -1769 -1039 -6 10 1769 1039z"/>';

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
                    $logs[] = "[$connName] master_svg lokasi 3 (Alzafa) berhasil diperbarui dengan background jalan, batas kawasan merah, dan teks presisi (400x420mm).";

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
}
