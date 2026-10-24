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
        // 1. Dapatkan portal yang dipilih (bisa dari query param ?portal= / ?type=, path /booking/komersil, session, atau host)
        $portalQuery = $request->query('portal') ?? $request->query('type');
        if ($portalQuery && in_array(strtolower($portalQuery), ['subsidi', 'komersil'])) {
            session(['app_type' => strtolower($portalQuery)]);
        }

        // Cek path URL (misal /booking/komersil atau /booking/subsidi)
        if ($request->is('booking/komersil*') || $request->is('siteplan/komersil*')) {
            session(['app_type' => 'komersil']);
        } elseif ($request->is('booking/subsidi*') || $request->is('siteplan/subsidi*')) {
            session(['app_type' => 'subsidi']);
        }

        // Cek domain/host
        $host = strtolower($request->getHost());
        if (str_contains($host, 'sistem.') || str_starts_with($host, 'komersil.')) {
            // Jika akses lewat domain sistem.rkgroupproperti.com, utamakan komersil jika session belum diset atau portal query komersil
            if (!$request->has('portal') && !$request->has('type')) {
                if ($request->is('booking*') || session('app_type') !== 'subsidi') {
                    session(['app_type' => 'komersil']);
                }
            }
        } elseif (str_contains($host, 'subsidi.')) {
            if (!$request->has('portal') && !$request->has('type')) {
                if ($request->is('booking*') || session('app_type') !== 'komersil') {
                    session(['app_type' => 'subsidi']);
                }
            }
        }

        $appType = session('app_type');

        if (!$appType || !in_array($appType, ['subsidi', 'komersil'])) {
            $appType = (str_contains($host, 'sistem.') || str_starts_with($host, 'komersil.')) ? 'komersil' : 'subsidi';
            session(['app_type' => $appType]);
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
