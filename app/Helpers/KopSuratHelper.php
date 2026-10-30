<?php

namespace App\Helpers;

use App\Models\Customer;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\Perusahaan;

class KopSuratHelper
{
    /**
     * Resolves the company info and kop surat path according to housing project rules:
     * - Alzafa Residence (tahap 3) -> PT Alzafa Perkasa Sriwijaya (kop_surat_alzafa.png)
     * - Bukit Intan Residence Tahap 4 (BIR 4) -> PT Unggul Perkasa Propertindo (kop_surat_upp.png)
     * - Bukit Intan Residence 2 Tahap 2, The Ammaia Hills, Avana Hills, default -> PT Unggul Perkasa Sriwijaya (kop_surat_ups.png)
     *
     * @param mixed $lokasi LokasiKavling|string|null
     * @param mixed $kavling KavlingPeta|null
     * @param mixed $customer Customer|null
     * @return array
     */
    public static function resolveInfo($lokasi = null, $kavling = null, $customer = null): array
    {
        $namaLokasi = '';

        if ($lokasi) {
            if (is_string($lokasi)) {
                $namaLokasi = $lokasi;
            } elseif (is_object($lokasi)) {
                $namaLokasi = ($lokasi->nama_kavling ?? '') . ' ' . ($lokasi->nama_singkat ?? '');
            }
        }

        if (empty($namaLokasi) && $kavling && is_object($kavling)) {
            $namaLokasi = ($kavling->lokasi->nama_kavling ?? '') . ' ' . ($kavling->kode_kavling ?? '');
        }

        if (empty($namaLokasi) && $customer && is_object($customer)) {
            $namaLokasi = ($customer->lokasiKavling->nama_kavling ?? '')
                . ' ' . ($customer->kavlingPeta->lokasi->nama_kavling ?? '');
        }

        $namaLokasiUpper = strtoupper($namaLokasi);

        // Classification
        if (str_contains($namaLokasiUpper, 'TAHAP 4') || str_contains($namaLokasiUpper, 'BIR4') || str_contains($namaLokasiUpper, 'BIR 4')) {
            $companyKey = 'UPP';
            $defaultNama = 'PT UNGGUL PERKASA PROPERTINDO';
            $defaultAlamat = 'Komplek Ruko Palm Rencana Perumnas, Jl. Kelapa Sawit Raya No. 08 RT. 004 Kel. Talang Kelapa, Kec. Alang-Alang Lebar, Palembang';
            $defaultTelp = '0711 - 5645076';
            $kopFile = 'kop_surat_upp.png';
            $queryPerusahaan = function () {
                return Perusahaan::where('nama_perusahaan', 'LIKE', '%PROPERTINDO%')->first();
            };
        } elseif (str_contains($namaLokasiUpper, 'ALZAFA')) {
            $companyKey = 'ALZAFA';
            $defaultNama = 'PT ALZAFA PERKASA SRIWIJAYA';
            $defaultAlamat = 'Komp. Pondok Palem Indah Blok F5 / 11B, Kel. Talang Kelapa, Kec. Alang-Alang Lebar, Palembang';
            $defaultTelp = '0881-0805-03515';
            $kopFile = 'kop_surat_alzafa.png';
            $queryPerusahaan = function () {
                return Perusahaan::where('nama_perusahaan', 'LIKE', '%ALZAFA%')->first();
            };
        } else {
            $companyKey = 'UPS';
            $defaultNama = 'PT UNGGUL PERKASA SRIWIJAYA';
            $defaultAlamat = 'Jl. Sultan M. Mansyur No. 1181 Bukit Lama Palembang';
            $defaultTelp = '0711 441139';
            $kopFile = 'kop_surat_ups.png';
            $queryPerusahaan = function () {
                return Perusahaan::where(function ($q) {
                    $q->where('nama_perusahaan', 'LIKE', '%UNGGUL PERKASA SRIWIJAYA%')
                      ->orWhere(function ($sub) {
                          $sub->where('nama_perusahaan', 'LIKE', '%SRIWIJAYA%')
                              ->where('nama_perusahaan', 'NOT LIKE', '%ALZAFA%');
                      });
                })->first();
            };
        }

        // Try getting model from DB
        $perusahaan = null;
        try {
            $perusahaan = $queryPerusahaan();
        } catch (\Throwable $e) {}

        if (!$perusahaan && $kavling && is_object($kavling) && !empty($kavling->id_perusahaan)) {
            try {
                $perusahaan = Perusahaan::find($kavling->id_perusahaan);
            } catch (\Throwable $e) {}
        }

        if (!$perusahaan) {
            $perusahaan = (object) [
                'nama_perusahaan'       => $defaultNama,
                'alamat_perusahaan'     => $defaultAlamat,
                'telp_perusahaan'       => $defaultTelp,
                'kota_penandatangan'    => 'Palembang',
                'nama_penandatangan'    => 'Admin Keuangan',
                'jabatan_penandatangan' => 'Kasir / Keuangan',
                'nama_mengetahui'       => 'Pimpinan',
                'kop_surat'             => $kopFile,
            ];
        }

        // Resolve absolute path to kop surat image
        $kopPath = public_path('assets/img/' . $kopFile);
        if (!file_exists($kopPath)) {
            $kopPath = base_path('public/assets/img/' . $kopFile);
        }
        // Fallback to existing pt_unggul if ups requested and specific file not found
        if (!file_exists($kopPath) && $companyKey === 'UPS') {
            $fallback = public_path('assets/img/kop_surat_pt_unggul.png');
            if (file_exists($fallback)) {
                $kopPath = $fallback;
            }
        }

        return [
            'company_key'           => $companyKey,
            'nama_perusahaan'       => $perusahaan->nama_perusahaan ?? $defaultNama,
            'alamat_perusahaan'     => $perusahaan->alamat_perusahaan ?? $defaultAlamat,
            'telp_perusahaan'       => $perusahaan->telp_perusahaan ?? $defaultTelp,
            'kota_penandatangan'    => $perusahaan->kota_penandatangan ?? 'Palembang',
            'nama_penandatangan'    => $perusahaan->nama_penandatangan ?? 'Admin Keuangan',
            'jabatan_penandatangan' => $perusahaan->jabatan_penandatangan ?? 'Kasir / Keuangan',
            'nama_mengetahui'       => $perusahaan->nama_mengetahui ?? 'Pimpinan',
            'kop_filename'          => $kopFile,
            'kop_path'              => $kopPath,
            'kop_exists'            => file_exists($kopPath),
            'perusahaan'            => $perusahaan,
        ];
    }
}
