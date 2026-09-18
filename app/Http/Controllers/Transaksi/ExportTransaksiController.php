<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Services\ExportTransaksiService;

class ExportTransaksiController extends Controller
{
    protected ExportTransaksiService $exportService;

    public function __construct(ExportTransaksiService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function booking()
    {
        return $this->exportService->exportBooking();
    }

    public function sppr()
    {
        return $this->exportService->exportSPPR();
    }

    public function wawancara()
    {
        return $this->exportService->exportWawancara();
    }

    public function accBank()
    {
        return $this->exportService->exportAccBank();
    }

    public function ppjb()
    {
        return $this->exportService->exportPPJB();
    }

    public function akad()
    {
        return $this->exportService->exportAkad();
    }

    public function bast()
    {
        return $this->exportService->exportBAST();
    }

    public function pindahUnit()
    {
        return $this->exportService->exportPindahUnit();
    }

    public function gantiNama()
    {
        return $this->exportService->exportGantiNama();
    }

    public function pembelianCancel()
    {
        return $this->exportService->exportPembelianCancel();
    }
}
