<?php

namespace App\Http\Controllers;

use App\Services\ExcelSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncExcelController extends Controller
{
    public function index()
    {
        return view('admin.sync_excel_form');
    }

    public function process(Request $request, ExcelSyncService $syncService)
    {
        $filePath = database_path('data_excel_raw.csv');

        // Opsi 1: Sinkronisasi langsung via Google Sheets URL
        if ($request->filled('google_sheet_url')) {
            $url = trim($request->google_sheet_url);

            // Ekstrak Document ID dan GID
            preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $url, $matches);
            $docId = $matches[1] ?? null;

            preg_match('/gid=([0-9]+)/', $url, $gidMatches);
            $gid = $gidMatches[1] ?? '0';

            if ($docId) {
                $exportUrl = "https://docs.google.com/spreadsheets/d/{$docId}/export?format=csv&gid={$gid}";
                try {
                    $response = Http::timeout(30)->get($exportUrl);
                    if ($response->successful()) {
                        $tempPath = storage_path('app/temp_google_sheet.csv');
                        file_put_contents($tempPath, $response->body());
                        $filePath = $tempPath;
                    } else {
                        return back()->with('error', 'Gagal mengunduh Google Spreadsheet. Pastikan pengaturan akses spreadsheet di Google Drive sudah disetel ke "Siapa saja yang memiliki link dapat melihat" (Anyone with the link can view). Status HTTP: ' . $response->status());
                    }
                } catch (\Exception $e) {
                    return back()->with('error', 'Koneksi ke Google Sheets gagal: ' . $e->getMessage());
                }
            } else {
                return back()->with('error', 'Format URL Google Spreadsheet tidak valid.');
            }
        }

        // Opsi 2: Upload File CSV secara manual
        if ($request->hasFile('file_csv')) {
            $file = $request->file('file_csv');
            $tempPath = storage_path('app/uploaded_' . time() . '.csv');
            $file->move(dirname($tempPath), basename($tempPath));
            $filePath = $tempPath;
        }

        // Jalankan service sinkronisasi
        $result = $syncService->syncFromFile($filePath);

        return view('admin.sync_excel_result', [
            'result' => $result,
        ]);
    }
}
