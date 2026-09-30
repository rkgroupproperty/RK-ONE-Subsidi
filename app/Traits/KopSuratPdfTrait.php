<?php

namespace App\Traits;

use App\Models\LokasiKavling;

trait KopSuratPdfTrait
{
    public function kopSuratLokasi($id_lokasi): ?string
    {
        $kop = LokasiKavling::with('perusahaan')->find($id_lokasi)?->perusahaan?->kop_surat;

        if (! $kop || ! preg_match('/\.(jpe?g|png)$/i', $kop)) {
            return null;
        }

        $path = public_path('assets/lokasi_perumahan/kop_surat/' . $kop);

        return file_exists($path) ? $path : null;
    }

    public function gambarKopSurat($pdf, $kopPath): void
    {
        $lebar  = 200;
        $tinggi = 40;

        $ukuran = @getimagesize($kopPath);
        if ($ukuran && $ukuran[0] > 0) {
            $tinggi = $lebar * $ukuran[1] / $ukuran[0];
        }

        if ($tinggi > 45) {
            $tinggi = 45;
            $lebar  = $ukuran && $ukuran[1] > 0 ? $tinggi * $ukuran[0] / $ukuran[1] : 190;
        }

        $posisiX = (210 - $lebar) / 2;

        $pdf->Image($kopPath, $posisiX, 6, $lebar, $tinggi);
        $pdf->SetY(6 + $tinggi + 2);
    }
}
