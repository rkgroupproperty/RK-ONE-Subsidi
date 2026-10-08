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
        'bir2_akad_count'  => 0,
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
     * Jalankan proses sinkronisasi lengkap
     */
    public function syncFromFile(?string $csvPath = null): array
    {
        DB::beginTransaction();
        try {
            $this->addLog("=== MEMULAI PROSES SINKRONISASI DATA DARI SPREADSHEET ===");

            // 1. Eksekusi Sinkronisasi Khusus Blok A3 s/d B2 di BIR 2 (Harus Akad Semua)
            $this->syncKavlingAkadBir2();

            // 2. Eksekusi Sinkronisasi Khusus 17 Konsumen SP3K
            $this->syncDirectSp3k();

            // 3. Jika file CSV tersedia, baca dan parse data unit tambahan
            if ($csvPath && file_exists($csvPath)) {
                $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $this->addLog("Membaca file data eksternal: " . count($lines) . " baris.");

                $metaKonsumen = $this->extractMetadataKontak($lines);
                $sp3kList = $this->extractSp3kData($lines);

                $sections = [
                    'ALZAFA RESIDENCE TAHAP 2'      => 'Alzafa 2',
                    'ALZAFA RESIDENCE TAHAP 3'      => 'Alzafa 3',
                    'BUKIT INTAN RESIDENCE TAHAP 4' => 'BIR 4',
                    'BUKIT INTAN RESIDENCE 2'       => 'BIR 2',
                    'BUKIT INTAN RESIDENCE TAHAP 3' => 'BIR 3',
                ];

                foreach ($sections as $keyword => $shortName) {
                    $unitRows = $this->extractUnitPerumahan($lines, $keyword);
                    if (count($unitRows) > 0) {
                        $this->addLog("Memproses unit perumahan [$keyword]: " . count($unitRows) . " unit.");
                        $this->processPerumahanUnits($shortName, $unitRows, $metaKonsumen, $sp3kList);
                    }
                }
            }

            DB::commit();
            $this->addLog("=== SINKRONISASI SELESAI DENGAN SUKSES! ===");

            return [
                'status' => 'success',
                'stats'  => $this->stats,
                'logs'   => $this->logs,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $this->stats['errors']++;
            $this->addLog("ERROR SINKRONISASI: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")");
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
                'stats'   => $this->stats,
                'logs'    => $this->logs,
            ];
        }
    }

    /**
     * Pastikan seluruh kavling blok A3 sampai B2 di Bukit Intan Residence 2 (BIR 2) sudah berstatus AKAD
     */
    public function syncKavlingAkadBir2()
    {
        $this->addLog("Memeriksa dan menyelaraskan status Akad untuk Blok A3 s/d B2 di Bukit Intan Residence 2 (BIR 2)...");

        // Cari lokasi BIR 2
        $lokasi = LokasiKavling::where('nama_kavling', 'LIKE', '%Intan%2%')
            ->orWhere('nama_kavling', 'LIKE', '%BIR%2%')
            ->orWhere('nama_singkat', 'BIR2')
            ->first();

        if (!$lokasi) {
            $this->addLog("Peringatan: Lokasi Bukit Intan Residence 2 tidak ditemukan!");
            return;
        }

        $this->addLog("Lokasi ditemukan: " . $lokasi->nama_kavling . " (ID: " . $lokasi->id . ")");

        // Pola blok target: A3, A4, A5, A6, A7, B1, B2
        $targetBloks = ['A3', 'A4', 'A5', 'A6', 'A7', 'B1', 'B2'];
        $kavlings = KavlingPeta::where('id_lokasi', $lokasi->id)->get();

        $updatedCount = 0;

        foreach ($kavlings as $kavling) {
            $kode = strtoupper(trim($kavling->kode_kavling));
            $isTarget = false;

            foreach ($targetBloks as $tb) {
                // Cocokkan: A3-01, A03-01, A3 NO 01, A03 NO 01, A3/01, dll.
                $p1 = $tb . '-';
                $p2 = preg_replace('/^([A-Z]+)([0-9]+)$/', '$10$2', $tb) . '-'; // A03-
                $p3 = $tb . ' NO';
                $p4 = preg_replace('/^([A-Z]+)([0-9]+)$/', '$10$2', $tb) . ' NO'; // A03 NO
                $p5 = $tb . '/';

                if (str_starts_with($kode, $p1) || str_starts_with($kode, $p2) || 
                    str_starts_with($kode, $p3) || str_starts_with($kode, $p4) ||
                    str_starts_with($kode, $p5) || $kode === $tb) {
                    $isTarget = true;
                    break;
                }
            }

            if ($isTarget) {
                // 1. Set status kavling menjadi 2 (Terjual / Akad)
                $kavling->status = 2;

                // 2. Hubungkan atau update customer
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

                if ($customer) {
                    // Update ke status Akad (ID: 3)
                    $customer->update([
                        'id_status_progres' => 3,
                        'stt_arsip'         => 0,
                        'tanggal_verif'     => $customer->tanggal_verif ?: '2024-06-01',
                    ]);
                } else {
                    // Buat customer baru
                    $customer = Customer::create([
                        'kode_customer'     => 'CUST-' . strtoupper(Str::random(6)),
                        'nama_lengkap'      => 'KONSUMEN AKAD (' . $kavling->kode_kavling . ')',
                        'id_lokasi'         => $lokasi->id,
                        'id_kavling'        => $kavling->id,
                        'id_status_progres' => 3, // Akad
                        'jenis_pembelian'   => 'KPR',
                        'sumber_prospek'    => 'Iklan Kantor',
                        'tanggal_verif'     => '2024-06-01',
                        'total_harga'       => (int) ($kavling->hrg_jual ?: 168000000),
                        'stt_arsip'         => 0,
                    ]);
                }

                $kavling->id_customer = $customer->id;
                $kavling->save();
                $updatedCount++;
            }
        }

        $this->stats['bir2_akad_count'] = $updatedCount;
        $this->stats['kavling_updated'] += $updatedCount;
        $this->addLog("Berhasil menyetel $updatedCount unit kavling Blok A3 s/d B2 di BIR 2 menjadi status AKAD (ID: 3)!");
    }

    /**
     * Sinkronisasi langsung 17 Konsumen SP3K resmi dari Spreadsheet
     */
    public function syncDirectSp3k()
    {
        $this->addLog("Menyinkronkan 17 data konsumen SP3K resmi dari spreadsheet...");

        $sp3kData = [
            ['nama' => 'Rahman Wahyudi', 'perumahan' => 'Alzafa 2', 'blok' => 'C1 NO 02A', 'marketing' => 'Ernawati', 'bank' => 'BTN', 'sp3k' => '2026-10-01', 'plan' => '2026-10-12'],
            ['nama' => 'Sri Rezeki', 'perumahan' => 'Alzafa 2', 'blok' => 'C2 NO 20', 'marketing' => 'Mentari', 'bank' => 'BTN', 'sp3k' => '2026-09-25', 'plan' => '2026-10-12'],
            ['nama' => 'Fikhih Andradinata', 'perumahan' => 'Alzafa 2', 'blok' => 'C3 NO 03A', 'marketing' => 'Ernawati', 'bank' => 'BTN', 'sp3k' => '2026-09-11', 'plan' => '2026-10-07'],
            ['nama' => 'Sutiyarsa', 'perumahan' => 'Alzafa 3', 'blok' => 'E3 NO 22', 'marketing' => 'Ernawati', 'bank' => 'BNI', 'sp3k' => '2026-10-06', 'plan' => null],
            ['nama' => 'Chandra', 'perumahan' => 'Alzafa 3', 'blok' => 'E4 NO 03', 'marketing' => 'Niya', 'bank' => 'BNI', 'sp3k' => '2026-09-25', 'plan' => '2026-10-09'],
            ['nama' => 'Heny Yuliani', 'perumahan' => 'Alzafa 3', 'blok' => 'E4 NO 12', 'marketing' => 'Niya', 'bank' => 'BNI', 'sp3k' => '2026-09-22', 'plan' => '2026-10-19'],
            ['nama' => 'Annisya', 'perumahan' => 'Alzafa 3', 'blok' => 'E5 NO 20', 'marketing' => 'Ernawati', 'bank' => 'BSN', 'sp3k' => '2026-07-22', 'plan' => '2026-10-05'],
            ['nama' => 'Juwita', 'perumahan' => 'Alzafa 3', 'blok' => 'E5 NO 21', 'marketing' => 'Ernawati', 'bank' => 'BJB', 'sp3k' => '2026-09-30', 'plan' => null],
            ['nama' => 'Sholeh Ibrahim', 'perumahan' => 'Alzafa 3', 'blok' => 'E5 NO 22', 'marketing' => 'Akbar', 'bank' => 'BTN', 'sp3k' => '2026-08-29', 'plan' => null],
            ['nama' => 'Hamza', 'perumahan' => 'Alzafa 3', 'blok' => 'E7 NO 02', 'marketing' => 'Dian', 'bank' => 'BSN', 'sp3k' => '2026-10-05', 'plan' => null],
            ['nama' => 'Barokah', 'perumahan' => 'Alzafa 3', 'blok' => 'E7 NO 05', 'marketing' => 'Niya', 'bank' => 'BNI', 'sp3k' => '2026-08-27', 'plan' => null],
            ['nama' => 'Marlina', 'perumahan' => 'Alzafa 3', 'blok' => 'E7 NO 08', 'marketing' => 'Niya', 'bank' => 'BNI', 'sp3k' => '2026-08-07', 'plan' => null],
            ['nama' => 'Ella', 'perumahan' => 'Alzafa 3', 'blok' => 'E7 NO 19', 'marketing' => 'Ernawati', 'bank' => 'BTN', 'sp3k' => '2026-09-17', 'plan' => null],
            ['nama' => 'Annisa Yuda', 'perumahan' => 'BIR 4', 'blok' => 'F12 NO 04', 'marketing' => 'Tami', 'bank' => 'BTN', 'sp3k' => '2026-09-16', 'plan' => '2026-10-12'],
            ['nama' => 'Sanuriya', 'perumahan' => 'BIR 4', 'blok' => 'F15 NO 20', 'marketing' => 'Vira', 'bank' => 'BTN', 'sp3k' => '2026-09-16', 'plan' => '2026-10-05'],
            ['nama' => 'Kgs Julian Muliarido', 'perumahan' => 'BIR 2', 'blok' => 'B02 NO 06', 'marketing' => 'Hendrik', 'bank' => 'BTN', 'sp3k' => '2026-08-05', 'plan' => '2026-10-05'],
            ['nama' => 'Muhammad Junaidi', 'perumahan' => 'BIR 2', 'blok' => 'B04 NO 07', 'marketing' => 'Hendrik', 'bank' => 'BTN', 'sp3k' => '2026-09-11', 'plan' => null],
        ];

        $notarisDefault = Notaris::first();

        foreach ($sp3kData as $item) {
            $lokasi = $this->resolveLokasi($item['perumahan']);
            if (!$lokasi) continue;

            $kavling = $this->resolveKavling($lokasi->id, $item['blok']);
            $mkt = $this->resolveMarketing($item['marketing']);
            $bank = $this->resolveBank($item['bank']);

            // Cari atau buat customer
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
            if (!$customer) {
                $customer = Customer::where('id_lokasi', $lokasi->id)
                    ->where('nama_lengkap', 'LIKE', '%' . $item['nama'] . '%')
                    ->where('stt_arsip', 0)
                    ->first();
            }

            $payload = [
                'nama_lengkap'      => $item['nama'],
                'id_lokasi'         => $lokasi->id,
                'id_kavling'        => $kavling->id,
                'id_marketing'      => optional($mkt)->id,
                'id_bank_kpr'       => optional($bank)->id,
                'id_status_progres' => 4, // SP3K
                'jenis_pembelian'   => 'KPR',
                'sumber_prospek'    => 'Iklan Kantor',
                'tanggal_verif'     => $item['sp3k'],
                'stt_arsip'         => 0,
            ];

            if ($customer) {
                $customer->update($payload);
            } else {
                $payload['kode_customer'] = 'CUST-' . strtoupper(Str::random(6));
                $payload['total_harga']   = (int) ($kavling->hrg_jual ?: 168000000);
                $customer = Customer::create($payload);
            }

            $kavling->update(['status' => 2, 'id_customer' => $customer->id]);

            // Sinkronkan Wawancara & SP3K
            $wawancara = Wawancara::firstOrCreate(
                ['id_customer' => $customer->id],
                [
                    'id_bank_kpr'   => optional($bank)->id,
                    'tgl_wawancara' => $item['sp3k'],
                    'status'        => 2,
                ]
            );

            $tglExp = Carbon::parse($item['sp3k'])->addDays(90)->toDateString();

            WawancaraSp3k::updateOrCreate(
                ['id_wawancara' => $wawancara->id],
                [
                    'id_bank_kpr'     => optional($bank)->id,
                    'acc_plafon'      => (int) ($customer->total_harga ?: 168000000),
                    'tenor'           => 20,
                    'id_notaris'      => optional($notarisDefault)->id ?: 1,
                    'tgl_terbit_sp3k' => $item['sp3k'],
                    'tgl_expired'     => $tglExp,
                    'no_sp3k'         => 'SP3K/' . strtoupper(Str::random(6)) . '/' . date('Y'),
                    'status'          => 1,
                ]
            );

            $this->stats['sp3k_created']++;
        }

        $this->addLog("Berhasil menyinkronkan 17 data konsumen SP3K!");
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
                $nama = '';
                $hp = '';
                $sumber = '';
                $blok = '';

                foreach ($cols as $val) {
                    $valClean = trim($val);
                    if (preg_match('/^08[0-9]{8,13}$/', $valClean) || preg_match('/^8[0-9]{8,12}$/', $valClean)) {
                        $hp = (str_starts_with($valClean, '8') ? '0' : '') . $valClean;
                    }
                    $u = strtoupper($valClean);
                    if (in_array($u, ['IKLAN', 'IKLAN KANTOR', 'MARKET PLACE', 'MARKET PLACE FB', 'FB', 'FACEBOOK', 'TIKTOK', 'WIC', 'REFERENSI', 'REFERAL', 'FREELANCE', 'KANVASING', 'SOSMED PRIBADI', 'SOSMED KANTOR'])) {
                        $sumber = $u;
                    }
                    if (preg_match('/[A-Z0-9]+\s*(NO|\/|-)\s*[0-9]+[A-Z]?/i', $valClean)) {
                        $blok = $valClean;
                    }
                }

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
                        'hp'     => $hp,
                        'sumber' => $sumber,
                        'blok'   => $blok,
                    ];
                }
            }
        }

        return $meta;
    }

    /**
     * Ekstrak Data SP3K dari file
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
                $blok = trim($cols[3] ?? '');
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

            // Deteksi header perumahan baik dengan "DAFTAR NAMA KONSUMEN" maupun "PERUMAHAN"
            if ((stripos($fullText, 'DAFTAR NAMA KONSUMEN') !== false || stripos($fullText, 'PERUMAHAN') !== false) 
                && stripos($fullText, $keyword) !== false) {
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

                foreach ($cols as $cVal) {
                    if (preg_match('/[A-Z0-9]+\s*(NO|\/|-)\s*[0-9]+[A-Z]?/i', $cVal)) {
                        $blok = trim($cVal);
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

        foreach ($unitRows as $row) {
            $blok = $row['blok'];
            if (empty($blok)) continue;

            $kavling = $this->resolveKavling($lokasi->id, $blok);
            $nama = trim($row['nama']);
            $ket = strtoupper(trim($row['keterangan'] ?: $row['progres']));

            // Jika status BELUM TERJUAL atau kosong
            if (empty($nama) || str_contains($ket, 'BELUM TERJUAL')) {
                $kavling->update(['status' => 0, 'id_customer' => null]);
                $this->stats['kavling_updated']++;
                continue;
            }

            // Mapping Status Progres
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

            $mkt = $this->resolveMarketing($row['marketing']);
            $bank = $this->resolveBank($row['bank']);

            $nameKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $nama)));
            $meta = $metaKonsumen[$nameKey] ?? [];
            $noTelp = $meta['hp'] ?? null;
            $sumberProspek = $this->normalizeSumber($meta['sumber'] ?? 'Iklan Kantor');
            $tglVerif = $row['tgl_akad'] ?: ($row['tgl_sp3k'] ?: ($row['admin_entri'] ?: ($row['tgl_booking'] ?: date('Y-m-d'))));

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
                $custPayload['total_harga']   = (int) ($kavling->hrg_jual ?: 168000000);
                $customer = Customer::create($custPayload);
                $this->stats['customer_created']++;
            }

            $kavling->update([
                'status'      => $kavlingStatus,
                'id_customer' => ($sttArsip == 0) ? $customer->id : null,
            ]);
            $this->stats['kavling_updated']++;
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
                    ->orWhere('nama_kavling', 'LIKE', '%BIR%2%')
                    ->orWhere('nama_singkat', 'BIR2');
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
     * Cari atau buat KavlingPeta dengan pencocokan format fleksibel
     */
    protected function resolveKavling(int $idLokasi, string $blok): KavlingPeta
    {
        $clean = strtoupper(trim(preg_replace('/\s+/', ' ', $blok)));
        $candidates = [$clean, $blok];

        // Normalisasi format Blok (misal: A03 NO 01, A3-01, A3/01)
        if (preg_match('/^([A-Z]+)\s*0*([0-9]+)\s*(?:NO|\/|-)?\s*0*([0-9]+[A-Z]?)$/i', $clean, $m)) {
            $prefix = strtoupper($m[1]);
            $bNum = (int)$m[2];
            $uNum = (int)$m[3];
            $suffix = preg_replace('/^[0-9]+/', '', $m[3]);

            $bShort = $prefix . $bNum;
            $bLong  = $prefix . sprintf('%02d', $bNum);
            $uShort = $uNum . $suffix;
            $uLong  = sprintf('%02d', $uNum) . $suffix;

            $candidates[] = "$bShort-$uLong";
            $candidates[] = "$bShort-$uShort";
            $candidates[] = "$bLong-$uLong";
            $candidates[] = "$bLong-$uShort";
            $candidates[] = "$bShort NO $uLong";
            $candidates[] = "$bShort NO $uShort";
            $candidates[] = "$bLong NO $uLong";
            $candidates[] = "$bLong NO $uShort";
            $candidates[] = "$bShort/$uLong";
            $candidates[] = "$bShort/$uShort";
        }

        $candidates = array_unique($candidates);

        $kavling = KavlingPeta::where('id_lokasi', $idLokasi)
            ->where(function($q) use ($candidates) {
                foreach ($candidates as $cand) {
                    $q->orWhere('kode_kavling', $cand);
                }
            })->first();

        if (!$kavling) {
            foreach ($candidates as $cand) {
                $noSpace = str_replace([' ', '-', '/'], '', $cand);
                $kavling = KavlingPeta::where('id_lokasi', $idLokasi)
                    ->whereRaw("REPLACE(REPLACE(REPLACE(kode_kavling, ' ', ''), '-', ''), '/', '') = ?", [$noSpace])
                    ->first();
                if ($kavling) break;
            }
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

        $bank = BankKPR::where('nama', 'LIKE', "%$b%")->first();
        if (!$bank) {
            $bank = BankKPR::create(['nama' => $b]);
        }
        return $bank;
    }

    protected function cleanDate(?string $raw): ?string
    {
        if (empty($raw)) return null;
        $r = trim($raw);
        if (in_array(strtoupper($r), ['-', 'SUDAH', 'CASH', 'KOSONG', 'SUDAH SP3K'])) return null;

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/^(\d{1,2})\/(\d{2})(\d{4})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\/(\d{4})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/^(\d{1,2})\/\/(\d{1,2})\/(\d{4})$/', $r, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
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
