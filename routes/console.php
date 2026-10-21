<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:reset-app', function () {
    $tables = DB::select('SHOW TABLES');
    $dbName = DB::getDatabaseName();
    $key = "Tables_in_{$dbName}";

    $keep = ['users', 'konfigurasi_media', 'konfigurasi', 'hak_akses', 'menu', 'role', 'role_user'];

    $toTruncate = [];
    foreach ($tables as $table) {
        $name = $table->$key;
        if (!in_array($name, $keep)) {
            $toTruncate[] = $name;
        }
    }

    if (empty($toTruncate)) {
        $this->warn('Tidak ada tabel yang perlu di-truncate.');
        return;
    }

    $this->info('Tabel yang AKAN di-truncate (' . count($toTruncate) . ' tabel):');
    foreach ($toTruncate as $t) {
        $this->line("  - {$t}");
    }

    if (!$this->confirm('Lanjutkan truncate?')) {
        $this->warn('Dibatalkan.');
        return;
    }

    DB::statement('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($toTruncate as $name) {
        DB::statement("TRUNCATE TABLE `{$name}`");
        $this->info("  OK: {$name}");
    }
    DB::statement('SET FOREIGN_KEY_CHECKS = 1');

    $this->info('Selesai! Semua tabel berhasil di-reset.');
})->purpose('Reset semua tabel aplikasi (kecuali users, konfigurasi, permission)');

Artisan::command('sync:excel', function (\App\Services\ExcelSyncService $syncService) {
    $this->info("Menjalankan sinkronisasi data spreadsheet ke database...");
    $result = $syncService->syncFromFile();
    if ($result['status'] === 'success') {
        $this->info("SINKRONISASI BERHASIL!");
        foreach ($result['logs'] as $log) {
            $this->line("  " . $log);
        }
    } else {
        $this->error("TERJADI ERROR: " . ($result['message'] ?? 'Unknown error'));
    }
})->purpose('Sinkronisasi data Excel ke database');

Artisan::command('komersil:status', function (\App\Services\KomersilDatabaseService $service) {
    $this->info("=== STATUS DATABASE SUBSIDI & KOMERSIL ===");
    $status = $service->getStatus();
    $this->line("Database Subsidi  : " . ($status['subsidi']['connected'] ? "<fg=green>ONLINE</> ({$status['subsidi']['database']} - {$status['subsidi']['tables_count']} tabel)" : "<fg=red>OFFLINE: " . $status['subsidi']['error'] . "</>"));
    $this->line("Database Komersil : " . ($status['komersil']['connected'] ? "<fg=green>ONLINE</> ({$status['komersil']['database']} - {$status['komersil']['tables_count']} tabel)" : "<fg=red>OFFLINE: " . $status['komersil']['error'] . "</>"));
    if ($status['is_same_database']) {
        $this->warn("PERINGATAN: Kedua koneksi mengarah ke database yang sama!");
    } elseif ($status['ready_to_clone']) {
        $this->info("Status: Siap untuk inisialisasi / cloning (komersil:setup-db)");
    }
})->purpose('Cek status koneksi Database Subsidi dan Database Komersil');

Artisan::command('komersil:setup-db {--skip-seed : Jangan salin data master} {--force : Jalankan tanpa konfirmasi}', function (\App\Services\KomersilDatabaseService $service) {
    $this->info("Menyiapkan struktur dan data Database Komersil...");
    $skipSeed = $this->option('skip-seed');
    $force = $this->option('force');

    if (!$force && !$this->confirm('Lanjutkan inisialisasi tabel Database Komersil?')) {
        $this->warn('Dibatalkan.');
        return;
    }

    $result = $service->setupKomersilDatabase(!$skipSeed);
    foreach ($result['logs'] as $log) {
        $this->line("  " . $log);
    }

    if ($result['success']) {
        $this->info("\nBERHASIL: " . $result['message']);
    } else {
        $this->error("\nGAGAL: " . $result['message']);
    }
})->purpose('Inisialisasi skema tabel dan seed master data ke Database Komersil');
