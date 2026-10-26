<?php

namespace App\Services;

use App\Models\AdminPemberkasan;
use App\Models\Akad;
use App\Models\AkadDetail;
use App\Models\BankKPR;
use App\Models\Customer;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\MarketingOffline;
use App\Models\Notaris;
use App\Models\SPPR;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Alzafa3SyncService
{
    protected array $stats = [
        'total_rows'       => 0,
        'belum_terjual'    => 0,
        'customer_synced'  => 0,
        'akad_synced'      => 0,
        'sp3k_synced'      => 0,
        'bank_synced'      => 0,
        'admin_synced'     => 0,
        'marketing_synced' => 0,
        'errors'           => 0,
    ];

    protected array $logs = [];

    protected function addLog(string $msg): void
    {
        $this->logs[] = "[" . date('H:i:s') . "] " . $msg;
        Log::info("Alzafa3Sync: " . $msg);
    }

    public function syncFromCsv(string $csvPath): array
    {
        Config::set('database.default', 'mysql_subsidi');
        DB::setDefaultConnection('mysql_subsidi');

        DB::beginTransaction();
        try {
            $this->addLog("=== MEMULAI SINKRONISASI ALZAFA RESIDENCE TAHAP 3 ===");

            if (!file_exists($csvPath)) {
                throw new \Exception("File CSV tidak ditemukan di: " . $csvPath);
            }

            // 1. Tentukan Lokasi Alzafa Residence Tahap 3
            $allLokasi = LokasiKavling::all(['id', 'nama_kavling', 'nama_singkat']);
            $this->addLog("Daftar Lokasi di DB: " . json_encode($allLokasi));

            $lokasi = LokasiKavling::where(function ($q) {
                $q->where('nama_kavling', 'LIKE', '%Alzafa%3%')
                  ->orWhere('nama_kavling', 'LIKE', '%Alzafa%Tahap%3%')
                  ->orWhere('nama_kavling', 'LIKE', '%Tahap%3%')
                  ->orWhere('nama_singkat', 'LIKE', '%ALZ%3%')
                  ->orWhere('nama_singkat', 'LIKE', '%ALZAFA%3%')
                  ->orWhere('nama_kavling', 'LIKE', '%Alzafa%');
            })->first();

            if (!$lokasi) {
                $lokasi = LokasiKavling::first();
            }

            if (!$lokasi) {
                throw new \Exception("Lokasi Perumahan tidak ditemukan sama sekali di database!");
            }

            $this->addLog("Lokasi ditemukan: {$lokasi->nama_kavling} (ID: {$lokasi->id})");

            // Cache data master
            $marketings = MarketingOffline::all();
            $banks = BankKPR::all();
            $admins = AdminPemberkasan::all();
            $defaultNotaris = Notaris::first();

            // Default Akad Event
            $defaultAkad = Akad::firstOrCreate(
                ['keterangan' => 'Akad Massal Alzafa Residence Tahap 3'],
                ['tgl_akad' => Carbon::now('Asia/Jakarta')->toDateString()]
            );

            // Buka dan baca CSV
            $handle = fopen($csvPath, 'r');
            if ($handle === false) {
                throw new \Exception("Gagal membuka berkas CSV.");
            }

            // Lewati header (3 baris)
            $rowNum = 0;
            $syncedKavlingIds = [];

            while (($data = fgetcsv($handle, 2000, ",")) !== false) {
                $rowNum++;
                // Skip header lines
                if ($rowNum <= 3) {
                    continue;
                }

                if (empty($data) || count($data) < 4) {
                    continue;
                }

                $this->stats['total_rows']++;

                $no            = trim($data[0] ?? '');
                $namaKonsumen  = trim($data[1] ?? '');
                $perumahan     = trim($data[2] ?? '');
                $blok          = strtoupper(trim($data[3] ?? ''));
                $marketingName = trim($data[4] ?? '');
                $tglBooking    = trim($data[5] ?? '');
                $tglAdmin      = trim($data[6] ?? '');
                $namaAdmin     = trim($data[7] ?? '');
                $tglBank       = trim($data[8] ?? '');
                $namaBank      = trim($data[9] ?? '');
                $tglSp3k       = trim($data[10] ?? '');
                $jadwalAkad    = trim($data[11] ?? '');
                $progresPersen = trim($data[12] ?? '');
                $keterangan    = strtoupper(trim($data[13] ?? ''));

                if (empty($blok)) {
                    continue;
                }

                // Cari atau buat Kavling
                $kavling = $this->resolveKavling($lokasi->id, $blok);
                $syncedKavlingIds[] = $kavling->id;

                // Cek apakah BELUM TERJUAL
                $isBelumTerjual = empty($namaKonsumen) || 
                                  str_contains($keterangan, 'BELUM TERJUAL') || 
                                  str_contains($namaKonsumen, 'RUMAH CONTOH');

                if ($isBelumTerjual) {
                    $this->stats['belum_terjual']++;

                    // Unlink customer lama jika ada
                    if ($kavling->id_customer) {
                        $oldCust = Customer::find($kavling->id_customer);
                        if ($oldCust) {
                            $oldCust->update(['id_kavling' => null, 'stt_arsip' => 1]);
                        }
                    }

                    $kavling->update([
                        'status'      => 0,
                        'id_customer' => null,
                    ]);
                    continue;
                }

                // Resolusi Marketing
                $mkt = null;
                if (!empty($marketingName)) {
                    $mkt = $marketings->first(function ($m) use ($marketingName) {
                        return stripos($m->nama_marketing, $marketingName) !== false;
                    });
                }
                if (!$mkt) {
                    $mkt = $marketings->first(function ($m) {
                        return stripos($m->nama_marketing, 'Kantor') !== false;
                    });
                }

                // Resolusi Bank
                $bank = null;
                if (!empty($namaBank) && !in_array(strtoupper($namaBank), ['CASH', 'SUDAH', '-'])) {
                    $bank = $banks->first(function ($b) use ($namaBank) {
                        return stripos($b->nama, $namaBank) !== false;
                    });
                }

                // Resolusi Admin Pemberkasan
                $admin = null;
                if (!empty($namaAdmin) && !in_array(strtoupper($namaAdmin), ['CASH', 'SUDAH', '-'])) {
                    $admin = $admins->first(function ($a) use ($namaAdmin) {
                        return stripos($a->nama_lengkap, $namaAdmin) !== false;
                    });
                }

                // Tentukan Tanggal Verifikasi
                $tglVerif = $this->parseDate($tglBooking) ?: ($this->parseDate($tglAdmin) ?: Carbon::now('Asia/Jakarta')->toDateString());

                // Tentukan Status Progres & Alur Pipeline
                $idStatusProgres = 9; // Default: Pemberkasan Marketing
                $jenisPembelian = 'KPR';

                if (str_contains($keterangan, 'CASH') || str_contains(strtoupper($namaKonsumen), 'CASH')) {
                    $jenisPembelian = 'Cash';
                    $idStatusProgres = 3; // Akad / Cash Selesai
                } elseif (str_contains($keterangan, 'AKAD DONE') || str_contains($keterangan, 'AKAD') || !empty($jadwalAkad)) {
                    $idStatusProgres = 3; // Akad
                } elseif (str_contains($keterangan, 'SP3K') || !empty($tglSp3k)) {
                    $idStatusProgres = 4; // SP3K
                } elseif (str_contains($keterangan, 'PROSES BANK') || str_contains($keterangan, 'ON PROSES BANK') || !empty($tglBank)) {
                    $idStatusProgres = 7; // Wawancara / Proses Bank
                } elseif (str_contains($keterangan, 'PROSES ADMIN') || str_contains($keterangan, 'ON PROSES ADMIN') || !empty($tglAdmin)) {
                    $idStatusProgres = 10; // SPPR / Proses Admin
                } elseif (str_contains($keterangan, 'PEMBERKASAN MARKETING')) {
                    $idStatusProgres = 9; // Pemberkasan Marketing
                }

                // Cari atau buat Customer untuk kavling ini
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
                        ->where('nama_lengkap', 'LIKE', '%' . $namaKonsumen . '%')
                        ->where('stt_arsip', 0)
                        ->first();
                }

                $custPayload = [
                    'nama_lengkap'          => $namaKonsumen,
                    'id_lokasi'             => $lokasi->id,
                    'id_kavling'            => $kavling->id,
                    'id_marketing'          => $mkt ? $mkt->id : null,
                    'id_bank_kpr'           => $bank ? $bank->id : null,
                    'id_admin_pemberkasan'  => $admin ? $admin->id : null,
                    'id_status_progres'     => $idStatusProgres,
                    'jenis_pembelian'       => $jenisPembelian,
                    'sumber_prospek'        => 'Iklan Kantor',
                    'tanggal_verif'         => $tglVerif,
                    'stt_arsip'             => 0,
                ];

                if ($customer) {
                    $customer->update($custPayload);
                } else {
                    $custPayload['kode_customer'] = 'ALZ3-' . sprintf('%04d', $this->stats['customer_synced'] + 1);
                    $custPayload['total_harga']   = (int) ($kavling->hrg_jual ?: 168000000);
                    $custPayload['nik']           = '1671' . rand(100000000000, 999999999999);
                    $custPayload['jenis_kelamin'] = 'Laki-Laki';
                    $custPayload['tempat_lahir']  = 'Palembang';
                    $custPayload['tgl_lahir']     = '1990-01-01';
                    $custPayload['alamat_ktp']    = 'Palembang';
                    $custPayload['alamat_domisili'] = 'Palembang';
                    $custPayload['status_pernikahan'] = 'Belum Menikah';
                    $custPayload['pekerjaan']     = 'Karyawan Swasta';
                    $custPayload['jenis_perumahan'] = 'Subsidi';

                    $customer = Customer::create($custPayload);
                }

                // Update kavling
                $kavling->update([
                    'status'      => 2,
                    'id_customer' => $customer->id,
                ]);

                $this->stats['customer_synced']++;

                // Sinkronkan relasi alur pipeline transaksi sesuai status
                switch ($idStatusProgres) {
                    case 3: // AKAD / CASH
                        $this->stats['akad_synced']++;
                        $tglAkadParsed = $this->parseDate($jadwalAkad) ?: Carbon::now('Asia/Jakarta')->toDateString();

                        $akadEvent = Akad::firstOrCreate(
                            ['tgl_akad' => $tglAkadParsed],
                            ['keterangan' => 'Akad Massal Alzafa Residence Tahap 3']
                        );

                        AkadDetail::updateOrCreate(
                            [
                                'id_customer' => $customer->id,
                            ],
                            [
                                'id_akad'    => $akadEvent->id,
                                'status'     => 2, // Hadir / Selesai
                                'jenis_akad' => $jenisPembelian === 'Cash' ? 'Cash Keras' : 'Akad KPR',
                                'keterangan' => 'Akad Done Spreadsheet',
                            ]
                        );
                        break;

                    case 4: // SP3K
                        $this->stats['sp3k_synced']++;
                        $tglSp3kParsed = $this->parseDate($tglSp3k) ?: Carbon::now('Asia/Jakarta')->toDateString();

                        $wawancara = Wawancara::updateOrCreate(
                            ['id_customer' => $customer->id],
                            [
                                'id_bank_kpr'       => $bank ? $bank->id : 1,
                                'tgl_wawancara'     => $this->parseDate($tglBank) ?: $tglSp3kParsed,
                                'catatan_wawancara' => 'Lolos verifikasi bank',
                                'status'            => 2, // Selesai / ACC
                            ]
                        );

                        WawancaraSp3k::updateOrCreate(
                            ['id_wawancara' => $wawancara->id],
                            [
                                'id_bank_kpr'     => $bank ? $bank->id : 1,
                                'acc_plafon'      => (int) ($customer->total_harga ?: 168000000),
                                'tenor'           => 20,
                                'id_notaris'      => $defaultNotaris ? $defaultNotaris->id : 1,
                                'tgl_terbit_sp3k' => $tglSp3kParsed,
                                'tgl_expired'     => Carbon::parse($tglSp3kParsed)->addDays(90)->toDateString(),
                                'no_sp3k'         => 'SP3K/ALZ3/' . sprintf('%03d', $customer->id) . '/' . date('Y'),
                                'catatan_acc'     => 'ACC Bank Spreadsheet',
                                'lampiran'        => '-',
                                'status'          => 1, // Aktif
                            ]
                        );
                        break;

                    case 7: // PROSES BANK (Wawancara)
                        $this->stats['bank_synced']++;
                        $tglBankParsed = $this->parseDate($tglBank) ?: Carbon::now('Asia/Jakarta')->toDateString();

                        Wawancara::updateOrCreate(
                            ['id_customer' => $customer->id],
                            [
                                'id_bank_kpr'       => $bank ? $bank->id : 1,
                                'tgl_wawancara'     => $tglBankParsed,
                                'catatan_wawancara' => 'Sedang Proses Bank / Wawancara',
                                'status'            => 1, // Proses
                            ]
                        );

                        // Hapus status lanjutan jika ada sisa data lama
                        WawancaraSp3k::whereHas('wawancara', fn($q) => $q->where('id_customer', $customer->id))->delete();
                        AkadDetail::where('id_customer', $customer->id)->delete();
                        break;

                    case 10: // PROSES ADMIN / SPPR
                        $this->stats['admin_synced']++;
                        $tglAdminParsed = $this->parseDate($tglAdmin) ?: Carbon::now('Asia/Jakarta')->toDateString();

                        SPPR::updateOrCreate(
                            ['id_customer' => $customer->id],
                            [
                                'nama'                     => $customer->nama_lengkap,
                                'nik'                      => $customer->nik ?: '1671000000000000',
                                'alamat'                   => $customer->alamat_ktp ?: 'Palembang',
                                'blok'                     => $blok,
                                'no'                       => $blok,
                                'harga_jual'               => (int) ($customer->total_harga ?: 168000000),
                                'asumsi_plafon_kpr'        => (int) ($customer->total_harga ?: 168000000),
                                'total_yang_harus_dibayar' => (int) ($customer->total_harga ?: 168000000),
                                'no_sppr'                  => 'SPPR/ALZ3/' . sprintf('%04d', $customer->id) . '/' . date('Y'),
                                'keterangan'               => 'SPPR Alzafa 3 Spreadsheet',
                                'id_marketing'             => $customer->id_marketing,
                                'penandatangan'            => $namaAdmin ?: 'Admin',
                            ]
                        );

                        // Hapus status lanjutan
                        Wawancara::where('id_customer', $customer->id)->delete();
                        AkadDetail::where('id_customer', $customer->id)->delete();
                        break;

                    case 9: // PEMBERKASAN MARKETING
                    default:
                        $this->stats['marketing_synced']++;
                        // Bersihkan sisa transaksi lanjutan lama agar murni di tahap pemberkasan
                        SPPR::where('id_customer', $customer->id)->delete();
                        Wawancara::where('id_customer', $customer->id)->delete();
                        AkadDetail::where('id_customer', $customer->id)->delete();
                        break;
                }
            }

            fclose($handle);

            DB::commit();
            $this->addLog("=== SINKRONISASI ALZAFA TAHAP 3 SELESAI ===");

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

    protected function resolveKavling(int $idLokasi, string $blok): KavlingPeta
    {
        $clean = strtoupper(trim(preg_replace('/\s+/', ' ', $blok)));
        $candidates = [$clean, $blok];

        // Format alternatif: E1-01, E01-01, E1 NO 01, E1/01
        if (preg_match('/^([A-Z]+)\s*0*([0-9]+)\s*(?:NO|\/|-)?\s*0*([0-9]+[A-Z]?)$/i', $clean, $m)) {
            $prefix = strtoupper($m[1]);
            $bNum   = (int)$m[2];
            $uNum   = (int)$m[3];
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
            $candidates[] = "$bShort/$uLong";
            $candidates[] = "$bShort/$uShort";
        }

        $candidates = array_unique($candidates);

        $kavling = KavlingPeta::where('id_lokasi', $idLokasi)
            ->where(function ($q) use ($candidates) {
                foreach ($candidates as $c) {
                    $q->orWhere('kode_kavling', $c);
                }
            })
            ->first();

        if (!$kavling) {
            $parts = explode('-', $clean);
            $b = $parts[0] ?? $clean;
            $n = $parts[1] ?? '01';

            $kavling = KavlingPeta::create([
                'id_lokasi'     => $idLokasi,
                'kode_kavling'  => $clean,
                'blok'          => $b,
                'no'            => $n,
                'tipe_bangunan' => 36,
                'luas_bangunan' => 36,
                'luas_tanah'    => 72,
                'hrg_jual'      => 168000000,
                'status'        => 0,
            ]);
        }

        return $kavling;
    }

    protected function parseDate(?string $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        $v = trim($val);
        if (in_array(strtoupper($v), ['CASH', 'SUDAH', '-', 'BELUM', 'NULL'])) {
            return null;
        }

        try {
            return Carbon::parse($v)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
