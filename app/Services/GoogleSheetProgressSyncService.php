<?php

namespace App\Services;

use App\Models\AdminPemberkasan;
use App\Models\BankKPR;
use App\Models\Customer;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\MarketingOffline;
use App\Models\PengajuanHold;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleSheetProgressSyncService
{
    const GAS_URL = 'https://script.google.com/macros/s/AKfycbxiIkWh0FtoVkOURdEWtMo29OdoXmPvHsOH4wr1ZKPm2siecmzZOLkyl6ac52CQY4KC/exec';

    /**
     * Tentukan nama sheet target berdasarkan lokasi.
     * Mengembalikan null jika lokasi komersil atau tidak valid.
     */
    public static function resolveSheetName($lokasi): ?string
    {
        if (!$lokasi) {
            return null;
        }

        $namaLokasi = strtoupper(($lokasi->nama_kavling ?? '') . ' ' . ($lokasi->nama_singkat ?? ''));

        // Abaikan proyek Komersil
        if (str_contains($namaLokasi, 'AMMAIA') || str_contains($namaLokasi, 'AVANA')) {
            return null;
        }

        if (str_contains($namaLokasi, 'TAHAP 4') || str_contains($namaLokasi, 'BIRT4') || str_contains($namaLokasi, 'BIR 4') || ($lokasi->id ?? 0) == 2) {
            return 'BIRT4';
        }
        if (str_contains($namaLokasi, 'ALZAFA') && (str_contains($namaLokasi, 'TAHAP 2') || str_contains($namaLokasi, 'T2') || str_contains($namaLokasi, 'AR 2'))) {
            return 'AR T2';
        }
        if (str_contains($namaLokasi, 'ALZAFA') || ($lokasi->id ?? 0) == 3) {
            return 'AR T3';
        }
        if (str_contains($namaLokasi, 'BIR2') || str_contains($namaLokasi, 'BUKIT INTAN') || ($lokasi->id ?? 0) == 1) {
            return 'BIR2';
        }

        return null;
    }

    /**
     * Format kode kavling menjadi standar Google Sheet [Blok] NO [Nomor].
     * Misal: F01-01 -> F01 NO 01, B6-08 -> B06 NO 08, E1-01 -> E1 NO 01
     */
    public static function formatBlockName(string $rawBlok, string $sheetName): string
    {
        $rawBlok = trim($rawBlok);
        if (preg_match('/^([A-Za-z]+)\s*0*(\d+)[\s\-\.]*0*(\d+[A-Za-z]?)$/', $rawBlok, $m)) {
            $prefix = strtoupper($m[1]);
            $bNum   = (int)$m[2];
            $uNum   = sprintf('%02d', (int)$m[3]);

            if (str_starts_with($sheetName, 'AR')) {
                // Alzafa menggunakan 1 digit blok (contoh: E1 NO 01)
                return "{$prefix}{$bNum} NO {$uNum}";
            } else {
                // BIR2 dan BIRT4 menggunakan 2 digit blok (contoh: F01 NO 01, A01 NO 01, B06 NO 08)
                $bNum2 = sprintf('%02d', $bNum);
                return "{$prefix}{$bNum2} NO {$uNum}";
            }
        }

        return $rawBlok;
    }

    /**
     * Kirim data payload ke Webhook Google Apps Script.
     */
    public static function sendToGas(array $payload): array
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::GAS_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res       = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            return [
                'httpCode'  => $httpCode,
                'response'  => $res,
                'curlError' => $curlError,
            ];
        } catch (\Throwable $e) {
            return [
                'httpCode'  => 0,
                'response'  => null,
                'curlError' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sinkronkan booking dari PengajuanHold (pendaftaran awal).
     */
    public static function syncPengajuanHold($lokasi, $kavling, $marketing, $pengajuan): void
    {
        try {
            $sheetName = self::resolveSheetName($lokasi);
            if (!$sheetName) {
                return;
            }

            $rawBlok = trim($kavling->kode_kavling ?? '');
            if (empty($rawBlok)) {
                return;
            }

            $formattedBlok = self::formatBlockName($rawBlok, $sheetName);
            $namaKonsumen  = trim($pengajuan->nama_lengkap ?? '');
            $namaMarketing = strtoupper(trim($marketing->nama_marketing ?? ''));

            $tglBooking = now()->format('d/m/Y');
            if (!empty($pengajuan->tgl_booking)) {
                try {
                    $tglBooking = Carbon::parse($pengajuan->tgl_booking)->format('d/m/Y');
                } catch (\Throwable $e) {}
            }

            $payload = [
                'sheetName'    => $sheetName,
                'blok'         => $formattedBlok,
                'namaKonsumen' => $namaKonsumen,
                'marketing'    => $namaMarketing,
                'booking'      => $tglBooking,
                'keterangan'   => 'PEMBERKASAN MARKETING',
            ];

            if (!empty($pengajuan->id_admin_pemberkasan)) {
                $adm = $pengajuan->adminPemberkasan ?? AdminPemberkasan::find($pengajuan->id_admin_pemberkasan);
                if ($adm) {
                    $payload['namaAdmin']  = strtoupper(trim($adm->nama_lengkap ?? $adm->nama_admin ?? ''));
                    $payload['entriAdmin'] = now()->format('d/m/Y');
                    $payload['keterangan'] = 'PROSES ADMIN';
                }
            }

            $result = self::sendToGas($payload);
            Log::info("GAS Sync (Booking): Sheet=$sheetName, Blok=$formattedBlok, HTTP={$result['httpCode']}, Res={$result['response']}");

            // Coba kirim rawBlok jika belum match
            if (($result['httpCode'] != 200 || str_contains($result['response'], '"error"')) && $formattedBlok !== $rawBlok) {
                $payload['blok'] = $rawBlok;
                self::sendToGas($payload);
            }
        } catch (\Throwable $e) {
            Log::error("GAS Sync Booking Exception: " . $e->getMessage());
        }
    }

    /**
     * Sinkronkan penugasan admin untuk unit yang masih dalam status PengajuanHold (sebelum di-ACC jadi Customer).
     */
    public static function syncPengajuanHoldAdmin($pengajuan, string $adminName): void
    {
        try {
            $lokasi = $pengajuan->lokasi ?? LokasiKavling::find($pengajuan->id_lokasi);
            $sheetName = self::resolveSheetName($lokasi);
            if (!$sheetName) {
                return;
            }

            $kavling = $pengajuan->kavling ?? KavlingPeta::find($pengajuan->id_kavling);
            $rawBlok = trim($kavling->kode_kavling ?? '');
            if (empty($rawBlok)) {
                return;
            }

            $formattedBlok = self::formatBlockName($rawBlok, $sheetName);
            $namaKonsumen  = trim($pengajuan->nama_lengkap ?? '');

            $payload = [
                'sheetName'    => $sheetName,
                'blok'         => $formattedBlok,
                'namaKonsumen' => $namaKonsumen,
                'entriAdmin'   => now()->format('d/m/Y'),
                'namaAdmin'    => strtoupper(trim($adminName)),
                'keterangan'   => 'PROSES ADMIN',
            ];

            $result = self::sendToGas($payload);
            Log::info("GAS Sync (PengajuanHold Admin): Sheet=$sheetName, Blok=$formattedBlok, HTTP={$result['httpCode']}, Res={$result['response']}");

            if (($result['httpCode'] != 200 || str_contains($result['response'], '"error"')) && $formattedBlok !== $rawBlok) {
                $payload['blok'] = $rawBlok;
                self::sendToGas($payload);
            }
        } catch (\Throwable $e) {
            Log::error("GAS Sync PengajuanHold Admin Exception: " . $e->getMessage());
        }
    }

    /**
     * Sinkronkan progres customer (Proses Admin, Bank, SP3K, Akad).
     */
    public static function syncCustomer($customerOrId, array $extra = []): void
    {
        try {
            $customer = is_numeric($customerOrId)
                ? Customer::with(['lokasi', 'kavling', 'marketing', 'adminPemberkasan', 'bank', 'bankKpr'])->find($customerOrId)
                : $customerOrId;

            if (!$customer) {
                return;
            }

            $sheetName = self::resolveSheetName($customer->lokasi);
            if (!$sheetName) {
                return;
            }

            $rawBlok = trim($customer->kavling->kode_kavling ?? '');
            if (empty($rawBlok)) {
                $sppr = \App\Models\SPPR::where('id_customer', $customer->id)->first();
                if ($sppr && (!empty($sppr->blok) || !empty($sppr->no))) {
                    $rawBlok = trim($sppr->blok . '-' . ($sppr->no ?: ''));
                }
            }
            if (empty($rawBlok)) {
                return;
            }

            if (empty($customer->id_kavling) && !empty($rawBlok)) {
                $foundKav = \App\Models\KavlingPeta::where('id_lokasi', $customer->id_lokasi)
                    ->where(function($q) use ($rawBlok) {
                        $q->where('kode_kavling', $rawBlok)
                          ->orWhere('kode_kavling', str_replace(' ', '', $rawBlok));
                    })->first();
                if ($foundKav) {
                    try {
                        \App\Models\Customer::where('id', $customer->id)->update(['id_kavling' => $foundKav->id]);
                        $foundKav->update(['id_customer' => $customer->id]);
                    } catch (\Throwable $e) {}
                }
            }

            $formattedBlok = self::formatBlockName($rawBlok, $sheetName);
            $namaKonsumen  = trim($customer->nama_lengkap ?? '');
            $namaMarketing = strtoupper(trim($customer->marketing->nama_marketing ?? ''));

            $payload = [
                'sheetName'    => $sheetName,
                'blok'         => $formattedBlok,
                'namaKonsumen' => $namaKonsumen,
            ];

            if ($namaMarketing) {
                $payload['marketing'] = $namaMarketing;
            }

            // Tanggal Booking jika ada
            if (!empty($extra['booking'])) {
                $payload['booking'] = $extra['booking'];
            }

            // Entri Admin & Nama Admin
            if (!empty($extra['entriAdmin'])) {
                $payload['entriAdmin'] = $extra['entriAdmin'];
            } elseif (!empty($customer->tanggal_verif)) {
                try {
                    $payload['entriAdmin'] = Carbon::parse($customer->tanggal_verif)->format('d/m/Y');
                } catch (\Throwable $e) {}
            }

            if (!empty($extra['namaAdmin'])) {
                $payload['namaAdmin'] = strtoupper(trim($extra['namaAdmin']));
            } elseif ($customer->adminPemberkasan) {
                $payload['namaAdmin'] = strtoupper(trim($customer->adminPemberkasan->nama_lengkap ?? $customer->adminPemberkasan->nama_admin ?? ''));
            } else {
                $phAdmin = \App\Models\PengajuanHold::where(function($q) use ($customer) {
                    if ($customer->id_kavling) {
                        $q->where('id_kavling', $customer->id_kavling);
                    }
                    if ($customer->nik) {
                        $q->orWhere('nik', $customer->nik);
                    }
                })->whereNotNull('id_admin_pemberkasan')->with('adminPemberkasan')->first();

                if ($phAdmin && $phAdmin->adminPemberkasan) {
                    $payload['namaAdmin'] = strtoupper(trim($phAdmin->adminPemberkasan->nama_lengkap ?? $phAdmin->adminPemberkasan->nama_admin ?? ''));
                    try {
                        \App\Models\Customer::where('id', $customer->id)->update(['id_admin_pemberkasan' => $phAdmin->id_admin_pemberkasan]);
                    } catch (\Throwable $e) {}
                }
            }

            // Entri Bank & Bank
            if (!empty($extra['entriBank'])) {
                $payload['entriBank'] = $extra['entriBank'];
            }
            if (!empty($extra['bank'])) {
                $payload['bank'] = strtoupper(trim($extra['bank']));
            } elseif ($customer->bankKpr) {
                $payload['bank'] = strtoupper(trim($customer->bankKpr->nama));
            } elseif ($customer->bank) {
                $payload['bank'] = strtoupper(trim($customer->bank->nama_bank));
            }

            // SP3K
            if (!empty($extra['sp3k'])) {
                $payload['sp3k'] = $extra['sp3k'];
            }

            // Akad & Progress
            if (!empty($extra['akad'])) {
                $payload['akad'] = $extra['akad'];
            }
            if (!empty($extra['progress'])) {
                $payload['progress'] = $extra['progress'];
            }

            // Keterangan status
            if (!empty($extra['keterangan'])) {
                $payload['keterangan'] = $extra['keterangan'];
            } else {
                // Tentukan otomatis berdasarkan kelengkapan data
                if (!empty($payload['akad'])) {
                    $payload['keterangan'] = 'AKAD DONE';
                } elseif (!empty($payload['sp3k'])) {
                    $payload['keterangan'] = 'SP3K';
                } elseif (!empty($payload['entriBank']) || !empty($payload['bank'])) {
                    $payload['keterangan'] = 'PROSES BANK';
                } elseif (!empty($payload['entriAdmin']) || !empty($payload['namaAdmin'])) {
                    $payload['keterangan'] = 'PROSES ADMIN';
                } else {
                    $payload['keterangan'] = 'PEMBERKASAN MARKETING';
                }
            }

            $result = self::sendToGas($payload);
            Log::info("GAS Sync (Customer Progress): Sheet=$sheetName, Blok=$formattedBlok, HTTP={$result['httpCode']}, Res={$result['response']}");

            // Coba kirim rawBlok jika belum match
            if (($result['httpCode'] != 200 || str_contains($result['response'], '"error"')) && $formattedBlok !== $rawBlok) {
                $payload['blok'] = $rawBlok;
                self::sendToGas($payload);
            }
        } catch (\Throwable $e) {
            Log::error("GAS Sync Customer Exception: " . $e->getMessage());
        }
    }
}
