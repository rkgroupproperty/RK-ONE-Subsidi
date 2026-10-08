<?php

namespace App\Console\Commands;

use App\Services\ExcelSyncService;
use Illuminate\Console\Command;

class SyncExcelCommand extends Command
{
    protected $signature = 'data:sync-excel {file?}';
    protected $description = 'Sinkronkan data Excel konsumen & kavling ke database aplikasi';

    public function handle(ExcelSyncService $syncService)
    {
        $filePath = $this->argument('file') ?: database_path('data_excel_raw.csv');

        $this->info("Memulai sinkronisasi dari: $filePath");
        $result = $syncService->syncFromFile($filePath);

        if ($result['status'] === 'success') {
            $this->info("Sinkronisasi BERHASIL!");
            $this->table(['Statistik', 'Jumlah'], [
                ['Kavling Diupdate', $result['stats']['kavling_updated'] ?? 0],
                ['Konsumen Baru', $result['stats']['customer_created'] ?? 0],
                ['Konsumen Diupdate', $result['stats']['customer_updated'] ?? 0],
                ['Data SP3K Disinkron', $result['stats']['sp3k_created'] ?? 0],
            ]);
        } else {
            $this->error("Gagal: " . ($result['message'] ?? 'Terjadi kesalahan'));
        }

        return 0;
    }
}
