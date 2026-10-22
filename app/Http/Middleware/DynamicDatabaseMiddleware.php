<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class DynamicDatabaseMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Dapatkan portal yang dipilih (bisa dari query param ?portal=, session, atau host)
        $portalQuery = $request->query('portal');
        if ($portalQuery && in_array($portalQuery, ['subsidi', 'komersil'])) {
            session(['app_type' => $portalQuery]);
        }

        $host = $request->getHost();
        if (str_starts_with($host, 'komersil.')) {
            session(['app_type' => 'komersil']);
        }

        $appType = session('app_type');

        if (!$appType || !in_array($appType, ['subsidi', 'komersil'])) {
            $appType = 'subsidi';
            session(['app_type' => 'subsidi']);
        }

        // 2. Tentukan nama koneksi target
        $targetConnection = ($appType === 'komersil') ? 'mysql_komersil' : 'mysql_subsidi';

        // 3. Terapkan koneksi database aktif dengan fallback otomatis jika terjadi gangguan
        if (config("database.connections.{$targetConnection}")) {
            if ($targetConnection === 'mysql_komersil') {
                try {
                    // Cek koneksi ke database Komersil
                    DB::connection('mysql_komersil')->getPdo();
                } catch (\Throwable $e) {
                    // Fallback koneksi database ke subsidi agar aplikasi tidak crash, namun portal UI tetap Komersil
                    $targetConnection = 'mysql_subsidi';
                }
            }

            Config::set('database.default', $targetConnection);
            DB::setDefaultConnection($targetConnection);
        }

        // 4. Bagikan variabel status portal ke seluruh Blade View
        View::share('current_app_type', $appType);
        View::share('is_komersil', $appType === 'komersil');
        View::share('is_subsidi', $appType === 'subsidi');
        View::share('portal_label', $appType === 'komersil' ? 'Perumahan Komersil' : 'Perumahan Subsidi');

        return $next($request);
    }
}
