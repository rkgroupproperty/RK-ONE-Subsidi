<?php

namespace App\Http\Controllers;

use App\Services\ExcelSyncService;
use Illuminate\Http\Request;

class SyncExcelController extends Controller
{
    public function index(ExcelSyncService $syncService)
    {
        $filePath = database_path('data_excel_raw.csv');
        $result = $syncService->syncFromFile($filePath);

        return view('admin.sync_excel_result', [
            'result' => $result,
        ]);
    }
}
