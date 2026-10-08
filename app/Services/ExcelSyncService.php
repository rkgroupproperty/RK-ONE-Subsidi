<?php

namespace App\Services;

use App\Models\BankKPR;
use App\Models\Customer;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\MarketingOffline;
use App\Models\Notaris;
use App\Models\ProgresListPenjualan;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExcelSyncService
{
    protected $logs = [];
    protected $stats = [
        'lokasi_found'     => 0,
        'kavling_updated'  => 0,
        'customer_created' => 0,
        'customer_updated' => 0,
        'sp3k_created'     => 0,
        'errors'           => 0,
    ];

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    protected function addLog(string $msg)
    {
        $this->logs[] = "[" . date('H:i:s') . "] " . $msg;
        Log::info("ExcelSync: " . $msg);
    }

    /**
     * Jalankan proses sinkronisasi dari file CSV
     */
    public function syncFromFile(string $csvPath): array
    {
        if (!file_exists($csvPath)) {
            $this->addLog("File tidak ditemukan: $csvPath");
            return ['status' => 'error', 'message' => 'File CSV tidak ditemukan'];
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->addLog("Memulai pembacaan " . count($lines) . " baris data...");

        // Tahap 1: Ekstrak data metadata kontak & sumber (DP Bulanan 2026 & 2025)
        $metaKonsumen = $this->extractMetadataKontak($lines);
        $this->addLog("Ditemukan " . count($metaKonsumen) . " metadata kontak & sumber prospek konsumen.");

        // Tahap 2: Ekstrak daftar SP3K khusus
        $sp3kList = $this->extractSp3kData($lines);
        $this->addLog("Ditemukan " . count($sp3kList) . " data SP3K siap sinkron.");

        // Tahap 3: Ekstrak master kavling & konsumen per perumahan
        $sections = [
            'ALZAFA RESIDENCE TAHAP 2'      => 'Alzafa 2',
            'ALZAFA RESIDENCE TAHAP 3'      => 'Alzafa 3',
            'BUKIT INTAN RESIDENCE TAHAP 4' => 'BIR 4',
            'BUKIT INTAN RESIDENCE 2'       => 'BIR 2',
            'BUKIT INTAN RESIDENCE TAHAP 3' => 'BIR 3',
        ];

        DB::beginTransaction();
        try {
            foreach ($sections as $keyword => $shortName) {
                $unitRows = $this->extractUnitPerumahan($lines, $keyword);
                $this->addLog("Memproses Perumahan [$keyword]: " . count($unitRows) . " unit.");
                $this->processPerumahanUnits($shortName, $unitRows, $metaKonsumen, $sp3kList);
            }

            DB::commit();
            $this->addLog("SINKRONISASI SELESAI DENGAN SUKSES!");
            return [
                'status' => 'success',
                'stats'  => $this->stats,
                'logs'   => $this->logs,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $this->stats['errors']++;
            $this->addLog("ERROR SINKRONISASI: " . $e->getMessage() . " di baris " . $e->getLine());
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
                'stats'   => $this->stats,
                'logs'    => $this->logs,
            ];
        }
    }

    /**
     * Ekstrak metadata: HP, Profesi, dan Sumber Prospek dari tabel DP Bulanan
     */
    protected function extractMetadataKontak(array $lines): array
    {
        $meta = [];
        $isCapturing = false;

        foreach ($lines as $line) {
            $cols = str_getcsv($line);
            $first = trim($cols[0] ?? '');

            if (stripos($first, 'DAFTAR KONSUMEN DP') !== false || stripos($first, 'DATA BOOKING PERIODE') !== false) {
                $isCapturing = true;
                continue;
            }

            if ($isCapturing && (stripos($first, 'DAFTAR NAMA KONSUMEN') !== false || stripos($first, 'REKAP') !== false || stripos($first, 'PERUMAHAN') !== false)) {
                $isCapturing = false;
            }

            if ($isCapturing && count($cols) >= 6) {
                // Cari nama konsumen dan blok
                $nama = '';
                $hp = '';
                $profesi = '';
                $sumber = '';
                $blok = '';

                foreach ($cols as $idx => $val) {
                    $valClean = trim($val);
                    if (preg_match('/^08[0-9]{8,13}$/', $valClean) || preg_match('/^8[0-9]{8,12}$/', $valClean)) {
                        $hp = (str_starts_with($valClean, '8') ? '0' : '') . $valClean;
                    }
                }

                // Coba cocokkan kolom standar: NAMA, NO HP, PROFESI, SUMBER, PERUMAHAN, BLOK
                foreach ($cols as $val) {
                    $u = strtoupper(trim($val));
                    if (in_array($u, ['IKLAN', 'IKLAN KANTOR', 'MARKET PLACE', 'MARKET PLACE FB', 'FB', 'FACEBOOK', 'TIKTOK', 'WIC', 'REFERENSI', 'REFERAL', 'FREELANCE', 'KANVASING', 'SOSMED PRIBADI', 'SOSMED KANTOR'])) {
                        $sumber = $u;
                    }
                    if (preg_match('/[A-Z0-9]+\s*(NO|\/)\s*[0-9]+[A-Z]?/i', $val)) {
                        $blok = $this->cleanBlok($val);
                    }
                }

                // Ambil nama dari kolom ke-1 atau ke-2
                $col1 = trim($cols[1] ?? '');
                $col2 = trim($cols[2] ?? '');
                if (!empty($col1) && !is_numeric($col1) && !str_starts_with($col1, '202')) {
                    $nama = $col1;
                } elseif (!empty($col2) && !is_numeric($col2) && !str_starts_with($col2, '202')) {
                    $nama = $col2;
                }

                if (!empty($nama)) {
                    $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $nama)));
                    $meta[$key] = [
                        'hp'      => $hp,
                        'sumber'  => $sumber,
                        'blok'    => $blok,
                    ];
                }
            }
        }

        return $meta;
    }

    /**
     * Ekstrak Data SP3K (17 Konsumen)
     */
    protected function extractSp3kData(array $lines): array
    {
        $sp3kList = [];
        $isCapture = false;

        foreach ($lines as $line) {
            $cols = str_getcsv($line);
            $first = trim($cols[0] ?? '');

            if (stripos($first, 'DAFTAR NAMA KONSUMEN SP3K') !== false) {
                $isCapture = true;
                continue;
            }

            if ($isCapture && (empty($first) || stripos($first, 'DAFTAR') !== false || stripos($first, 'PERUMAHAN') !== false)) {
                if (empty($first) && empty(trim($cols[1] ?? ''))) {
                    $isCapture = false;
                }
            }

            if ($isCapture && count($cols) >= 5 && is_numeric($first)) {
                $nama = trim($cols[1] ?? '');
                $perumahan = trim($cols[2] ?? '');
                $blok = $this->cleanBlok(trim($cols[3] ?? ''));
                $mkt = trim($cols[4] ?? '');
                $bank = trim($cols[9] ?? '');
                $tglSp3k = $this->cleanDate(trim($cols[10] ?? ''));
                $planAkad = $this->cleanDate(trim($cols[13] ?? ''));

                if (!empty($nama)) {
                    $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $nama)));
                    $sp3kList[$key] = [
                        'nama'       => $nama,
                        'perumahan'  => $perumahan,
                        'blok'       => $blok,
                        'marketing'  => $mkt,
                        'bank'       => $bank,
                        'tgl_sp3k'   => $tglSp3k ?: date('Y-m-d'),
                        'plan_akad'  => $planAkad,
                    ];
                }
            }
        }

        return $sp3kList;
    }

    /**
     * Ekstrak tabel unit kavling per perumahan
     */
    protected function extractUnitPerumahan(array $lines, string $keyword): array
    {
        $units = [];
        $isCapture = false;

        foreach ($lines as $line) {
            $cols = str_getcsv($line);
            $fullText = implode(' ', $cols);

            if (stripos($fullText, 'DAFTAR NAMA KONSUMEN') !== false && stripos($fullText, $keyword) !== false) {
                $isCapture = true;
                continue;
            }

            if ($isCapture && (stripos($fullText, 'RESUME PROJECT') !== false || stripos($fullText, 'DAFTAR NAMA KONSUMEN BATAL') !== false)) {
                $isCapture = false;
            }

            if ($isCapture && count($cols) >= 4) {
                $no = trim($cols[0] ?? '');
                $nama = trim($cols[1] ?? '');
                $blok = '';

                // Deteksi kolom blok (biasanya di cols[3] atau cols[2])
                foreach ($cols as $cVal) {
                    if (preg_match('/[A-Z0-9]+\s*(NO|\/)\s*[0-9]+[A-Z]?/i', $cVal)) {
                        $blok = $this->cleanBlok($cVal);
                        break;
                    }
                }

                if (!empty($blok) && (is_numeric($no) || !empty($nama))) {
                    $units[] = [
                        'no'          => $no,
                        'nama'        => $nama,
                        'perumahan'   => trim($cols[2] ?? ''),
                        'blok'        => $blok,
                        'marketing'   => trim($cols[4] ?? ''),
                        'tgl_booking' => $this->cleanDate(trim($cols[5] ?? '')),
                        'admin_entri' => $this->cleanDate(trim($cols[6] ?? '')),
                        'bank'        => trim($cols[9] ?? ''),
                        'tgl_sp3k'    => $this->cleanDate(trim($cols[10] ?? '')),
                        'tgl_akad'    => $this->cleanDate(trim($cols[11] ?? '')),
                        'progres'     => trim($cols[12] ?? ''),
                        'keterangan'  => trim($cols[13] ?? ''),
                    ];
                }
            }
        }

        return $units;
    }

    /**
     * Proses unit kavling dan sinkronkan dengan database
     */
    protected function processPerumahanUnits(string $shortName, array $unitRows, array $metaKonsumen, array $sp3kList)
    {
        $lokasi = $this->resolveLokasi($shortName);
        if (!$lokasi) {
            $this->addLog("Lokasi untuk [$shortName] tidak ditemukan di database!");
            return;
        }

        $notarisDefault = Notaris::first();

        foreach ($unitRows as $row) {
            $blok = $row['blok'];
            if (empty($blok)) continue;

            // 1. Cari atau buat KavlingPeta
            $kavling = $this->resolveKavling($lokasi->id, $blok);

            $nama = trim($row['nama']);
            $ket = strtoupper(trim($row['keterangan'] ?: $row['progres']));

            // Jika status BELUM TERJUAL atau nama kosong
            if (empty($nama) || str_contains($ket, 'BELUM TERJUAL') || $nama === 'SUDAH') {
                if (empty($nama) || str_contains($ket, 'BELUM TERJUAL')) {
                    $kavling->update(['status' => 0, 'id_customer' => null]);
                    $this->stats['kavling_updated']++;
                    continue;
                }
            }

            // 2. Mapping Status Progres
            $idStatusProgres = 11; // Default: Pemberkasan Marketing
            $kavlingStatus = 2;   // Terjual / Booking
            $sttArsip = 0;
            $jenisPembelian = 'KPR';

            if (str_contains($ket, 'AKAD DONE') || str_contains($ket, 'AKAD') || !empty($row['tgl_akad'])) {
                $idStatusProgres = 3; // Akad
            } elseif (str_contains($ket, 'SP3K') || !empty($row['tgl_sp3k'])) {
                $idStatusProgres = 4; // SP3K
            } elseif (str_contains($ket, 'PROSES BANK') || str_contains($ket, 'ON PROSES BANK')) {
                $idStatusProgres = 7; // Proses Bank (Wawancara)
            } elseif (str_contains($ket, 'PROSES ADMIN') || str_contains($ket, 'ON PROSES ADMIN')) {
                $idStatusProgres = 10; // SPPR / Admin
            } elseif (str_contains($ket, 'PEMBERKASAN MARKETING')) {
                $idStatusProgres = 11;
            } elseif (str_contains($ket, 'BATAL')) {
                $sttArsip = 1;
                $kavlingStatus = 0;
            }

            if (str_contains($ket, 'CASH') || str_contains(strtoupper($nama), 'CASH')) {
                $jenisPembelian = 'Cash';
                if ($idStatusProgres == 11) $idStatusProgres = 3;
            }

            // 3. Mapping Marketing & Bank
            $mkt = $this->resolveMarketing($row['marketing']);
            $bank = $this->resolveBank($row['bank']);

            // 4. Cari metadata tambahan (No HP & Sumber Prospek)
            $nameKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $nama)));
            $meta = $metaKonsumen[$nameKey] ?? [];
            $noTelp = $meta['hp'] ?? null;
            $sumberProspek = $this->normalizeSumber($meta['sumber'] ?? 'Iklan Kantor');

            // Tanggal verifikasi / transaksi
            $tglVerif = $row['tgl_akad'] ?: ($row['tgl_sp3k'] ?: ($row['admin_entri'] ?: ($row['tgl_booking'] ?: date('Y-m-d'))));

            // 5. Cek apakah customer sudah ada untuk unit kavling ini
            $customer = null;
            if ($kavling->id_customer) {
                $customer = Customer::find($kavling->id_customer);
            }
            if (!$customer) {
                $customer = Customer::where('id_lokasi', $lokasi->id)
                    ->where('id_kavling', $kavling->id)
                    ->where('stt_arsip', 0)
                    ->first();
            }
            if (!$customer && !empty($nama)) {
                $customer = Customer::where('id_lokasi', $lokasi->id)
                    ->where('nama_lengkap', $nama)
                    ->where('stt_arsip', 0)
                    ->first();
            }

            $custPayload = [
                'nama_lengkap'      => $nama,
                'id_lokasi'         => $lokasi->id,
                'id_kavling'        => $kavling->id,
                'id_marketing'      => optional($mkt)->id,
                'id_bank_kpr'       => optional($bank)->id,
                'id_status_progres' => $idStatusProgres,
                'jenis_pembelian'   => $jenisPembelian,
                'sumber_prospek'    => $sumberProspek,
                'tanggal_verif'     => $tglVerif,
                'stt_arsip'         => $sttArsip,
            ];
            if (!empty($noTelp)) {
                $custPayload['no_telp'] = $noTelp;
            }

            if ($customer) {
                $customer->update($custPayload);
                $this->stats['customer_updated']++;
            } else {
                $custPayload['kode_customer'] = 'CUST-' . strtoupper(Str::random(6));
                $custPayload['total_harga']   = (int) ($kavling->hrg_jual ?? 168000000);
                $customer = Customer::create($custPayload);
                $this->stats['customer_created']++;
            }

            // Tautkan kembali ke kavling peta
            $kavling->update([
                'status'      => $kavlingStatus,
                'id_customer' => ($sttArsip == 0) ? $customer->id : null,
            ]);
            $this->stats['kavling_updated']++;

            // 6. Tangani Record SP3K jika ada di daftar SP3K khusus atau berstatus SP3K
            $sp3kMeta = $sp3kList[$nameKey] ?? null;
            if ($sp3kMeta || $idStatusProgres == 4 || !empty($row['tgl_sp3k'])) {
                $tglTerbitSp3k = ($sp3kMeta['tgl_sp3k'] ?? null) ?: ($row['tgl_sp3k'] ?: date('Y-m-d'));
                $tglExp = Carbon::parse($tglTerbitSp3k)->addDays(90)->toDateString();
                $idBankSp3k = optional($bank)->id ?: optional($this->resolveBank($sp3kMeta['bank'] ?? 'BTN'))->id;

                $wawancara = Wawancara::firstOrCreate(
                    ['id_customer' => $customer->id],
                    [
                        'id_bank_kpr'   => $idBankSp3k,
                        'tgl_wawancara' => $tglTerbitSp3k,
                        'status'        => 2,
                    ]
                );

                WawancaraSp3k::updateOrCreate(
                    ['id_wawancara' => $wawancara->id],
                    [
                        'id_bank_kpr'     => $idBankSp3k,
                        'acc_plafon'      => (int) ($customer->total_harga ?? 168000000),
                        'tenor'           => 20,
                        'id_notaris'      => optional($notarisDefault)->id ?? 1,
                        'tgl_terbit_sp3k' => $tglTerbitSp3k,
                        'tgl_expired'     => $tglExp,
                        'no_sp3k'         => 'SP3K/' . strtoupper(Str::random(6)) . '/' . date('Y'),
                        'status'          => 1,
                    ]
                );
                $this->stats['sp3k_created']++;
            }
        }
    }

    /**
     * Resolusi Lokasi berdasarkan keyword singkat
     */
    protected function resolveLokasi(string $shortName): ?LokasiKavling
    {
        $q = LokasiKavling::query();
        if ($shortName === 'Alzafa 2') {
            $q->where('nama_kavling', 'LIKE', '%Alzafa%2%');
        } elseif ($shortName === 'Alzafa 3') {
            $q->where('nama_kavling', 'LIKE', '%Alzafa%3%');
        } elseif ($shortName === 'BIR 4') {
            $q->where(function($sub) {
                $sub->where('nama_kavling', 'LIKE', '%Intan%4%')
                    ->orWhere('nama_kavling', 'LIKE', '%BIR%4%');
            });
        } elseif ($shortName === 'BIR 2') {
            $q->where(function($sub) {
                $sub->where('nama_kavling', 'LIKE', '%Intan%2%')
                    ->orWhere('nama_kavling', 'LIKE', '%BIR%2%');
            });
        } elseif ($shortName === 'BIR 3') {
            $q->where(function($sub) {
                $sub->where('nama_kavling', 'LIKE', '%Intan%3%')
                    ->orWhere('nama_kavling', 'LIKE', '%BIR%3%');
            });
        } else {
            $q->where('nama_kavling', 'LIKE', "%$shortName%");
        }

        $loc = $q->first();
        if (!$loc) {
            $loc = LokasiKavling::first();
        }
        return $loc;
    }

    /**
     * Cari atau buat KavlingPeta
     */
    protected function resolveKavling(int $idLokasi, string $blok): KavlingPeta
    {
        $clean = $this->cleanBlok($blok);
        $kavling = KavlingPeta::where('id_lokasi', $idLokasi)
            ->where(function($q) use ($clean, $blok) {
                $q->where('kode_kavling', $clean)
                  ->orWhere('kode_kavling', $blok);
            })->first();

        if (!$kavling) {
            // Coba tanpa spasi
            $noSpace = str_replace(' ', '', $clean);
            $kavling = KavlingPeta::where('id_lokasi', $idLokasi)
                ->whereRaw("REPLACE(kode_kavling, ' ', '') = ?", [$noSpace])
                ->first();
        }

        if (!$kavling) {
            $kavling = KavlingPeta::create([
                'id_lokasi'     => $idLokasi,
                'kode_kavling'  => $clean,
                'tipe_bangunan' => '36/72',
                'hrg_jual'      => 168000000,
                'status'        => 0,
            ]);
        }

        return $kavling;
    }

    /**
     * Cari atau buat Marketing
     */
    protected function resolveMarketing(?string $name): ?MarketingOffline
    {
        if (empty($name) || in_array(strtoupper(trim($name)), ['-', 'SUDAH', 'CASH'])) return null;
        $nameClean = trim($name);

        $mkt = MarketingOffline::whereRaw('LOWER(nama_marketing) = ?', [strtolower($nameClean)])->first();
        if (!$mkt) {
            $mkt = MarketingOffline::where('nama_marketing', 'LIKE', "%$nameClean%")->first();
        }
        if (!$mkt) {
            $mkt = MarketingOffline::create([
                'kode_marketing' => 'MKT-' . strtoupper(Str::random(4)),
                'nama_marketing' => ucwords(strtolower($nameClean)),
                'status'         => 1,
            ]);
        }
        return $mkt;
    }

    /**
     * Cari atau buat Bank KPR
     */
    protected function resolveBank(?string $bankName): ?BankKPR
    {
        if (empty($bankName) || in_array(strtoupper(trim($bankName)), ['-', 'CASH', 'SUDAH'])) return null;
        $b = strtoupper(trim($bankName));

        if (str_contains($b, 'BTN SYARIAH') || str_contains($b, 'BTN IB') || str_contains($b, 'BTNS') || $b === 'BSN') {
            $bank = BankKPR::where('nama', 'LIKE', '%BTN Syariah%')->first();
            if (!$bank) $bank = BankKPR::where('nama', 'LIKE', '%BTNS%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'BTN Syariah']);
            return $bank;
        }

        if (str_contains($b, 'BTN')) {
            $bank = BankKPR::where('nama', 'LIKE', '%BTN%')->where('nama', 'NOT LIKE', '%Syariah%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'BTN Konvensional']);
            return $bank;
        }

        if (str_contains($b, 'BNI')) {
            $bank = BankKPR::where('nama', 'LIKE', '%BNI%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'BNI']);
            return $bank;
        }

        if (str_contains($b, 'BJB')) {
            $bank = BankKPR::where('nama', 'LIKE', '%BJB%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'BJB']);
            return $bank;
        }

        if (str_contains($b, 'BSI')) {
            $bank = BankKPR::where('nama', 'LIKE', '%BSI%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'BSI']);
            return $bank;
        }

        if (str_contains($b, 'MANDIRI')) {
            $bank = BankKPR::where('nama', 'LIKE', '%Mandiri%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'Mandiri']);
            return $bank;
        }

        if (str_contains($b, 'BRI')) {
            $bank = BankKPR::where('nama', 'LIKE', '%BRI%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'BRI']);
            return $bank;
        }

        if (str_contains($b, 'BSB KONVEN')) {
            $bank = BankKPR::where('nama', 'LIKE', '%Sumsel%Konven%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'BSB Konvensional']);
            return $bank;
        }

        if (str_contains($b, 'BSB SYARIAH')) {
            $bank = BankKPR::where('nama', 'LIKE', '%Sumsel%Syariah%')->first();
            if (!$bank) $bank = BankKPR::create(['nama' => 'BSB Syariah']);
            return $bank;
        }

        $bank = BankKPR::where('nama', 'LIKE', "%$b%")->first();
        if (!$bank) {
            $bank = BankKPR::create(['nama' => $b]);
        }
        return $bank;
    }

    /**
     * Pembersih format tanggal
     */
    protected function cleanDate(?string $raw): ?string
    {
        if (empty($raw)) return null;
        $r = trim($raw);
        if (in_array(strtoupper($r), ['-', 'SUDAH', 'CASH', 'KOSONG', 'Sudah SP3K'])) return null;

        // YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        // DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // 31/082026 atau 11/052026
        if (preg_match('/^(\d{1,2})\/(\d{2})(\d{4})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // 03.02/2026
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\/(\d{4})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // 10//06/2024
        if (preg_match('/^(\d{1,2})\/\/(\d{1,2})\/(\d{4})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Tahun salah ketik 0202 -> 2026
        if (str_contains($r, '0202')) {
            $fix = str_replace('0202', '2026', $r);
            return $this->cleanDate($fix);
        }

        try {
            return Carbon::parse($r)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Pembersih format Blok Kavling (contoh: C01 NO 01 -> C1 NO 01)
     */
    protected function cleanBlok(string $raw): string
    {
        $b = strtoupper(trim($raw));
        $b = preg_replace('/\s+/', ' ', $b);
        $b = str_replace(['NO. ', 'NO.', 'NO '], 'NO ', $b);
        return $b;
    }

    /**
     * Normalisasi nama saluran/sumber prospek
     */
    protected function normalizeSumber(string $raw): string
    {
        $c = strtolower(trim($raw));
        if (str_contains($c, 'iklan') || str_contains($c, 'kantor') || str_contains($c, 'grand opening')) return 'Iklan Kantor';
        if (str_contains($c, 'market place') || str_contains($c, 'marketplace') || str_contains($c, 'fb') || str_contains($c, 'facebook')) return 'Market Place FB';
        if (str_contains($c, 'freelance') || str_contains($c, 'agen')) return 'Freelance';
        if (str_contains($c, 'kanvas') || str_contains($c, 'canvas')) return 'Kanvasing';
        if (str_contains($c, 'sosmed kantor')) return 'Sosmed Kantor';
        if (str_contains($c, 'sosmed') || str_contains($c, 'tiktok') || str_contains($c, 'wa')) return 'Sosmed Pribadi';
        if (str_contains($c, 'wic') || str_contains($c, 'walk')) return 'WIC';
        if (str_contains($c, 'referensi') || str_contains($c, 'referal')) return 'Referensi';
        return 'Referensi';
    }
}
