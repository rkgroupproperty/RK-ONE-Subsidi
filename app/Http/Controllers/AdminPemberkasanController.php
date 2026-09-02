<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\PengajuanHold;
use App\Models\AdminPemberkasan;
use App\Models\MarketingOffline;
use App\Models\LokasiKavling;
use App\Models\ProgresListPenjualan;
use App\Models\Bank;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AdminPemberkasanController extends Controller
{
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = PengajuanHold::with(['marketing', 'lokasi', 'kavling', 'adminPemberkasan'])
                ->where('stt_reg', '!=', 2)
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_lengkap', function ($row) {
                    $nama   = '<strong>' . e($row->nama_lengkap) . '</strong>';
                    $noTelp = $row->no_telp
                        ? '<br><small class="text-primary">' . e($row->no_telp) . '</small>'
                        : '';

                    return $nama . $noTelp;
                })

                ->addColumn('stt_reg', function ($row): string {
                    switch ($row->stt_reg) {
                        case 1:
                            return '<span class="badge bg-dark">Pending</span>';
                        case 2:
                            return '<span class="badge bg-success">Disetujui</span>';
                        case 3:
                            return '<span class="badge bg-danger">Ditolak</span>';
                        default:
                            return '<span class="badge bg-secondary">Unknown</span>';
                    }
                })

                ->addColumn('nama_marketing', function ($row) {
                    if ((int) $row->id_marketing === 0) {
                        return 'Non Marketing';
                    }

                    return $row->marketing->nama_marketing ?? '-';
                })

                ->addColumn('kode_kavling', function ($row) {
                    $namaLokasi  = '<strong>' . ($row->lokasi->nama_kavling ?? '-') . '</strong>';
                    $kodeKavling = $row->kavling->kode_kavling ?? '-';

                    return $namaLokasi . '<br>' . $kodeKavling;
                })

                ->addColumn('admin_pemberkasan', function ($row) {
                    if ($row->id_admin_pemberkasan && $row->adminPemberkasan) {
                        return '<span class="badge bg-info">' . e($row->adminPemberkasan->nama_lengkap) . '</span>';
                    }
                    return '-';
                })

                ->addColumn('action', function ($row) use ($permissions): string {
                    $btn = '<div class="text-start">';

                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-info btn-xs mr-1 setting-admin-button" data-id="' . e($row->id) . '" data-admin="' . e($row->id_admin_pemberkasan ?? '') . '">Setting Admin</button>';
                    }

                    $btn .= '</div>';

                    return $btn;
                })

                ->rawColumns([
                    'nama_marketing',
                    'nama_lengkap',
                    'kode_kavling',
                    'stt_reg',
                    'admin_pemberkasan',
                    'action',
                ])

                ->make(true);
        }

        Carbon::setLocale('id');

        $marketing = MarketingOffline::all();
        $bank      = Bank::all();
        $progres   = ProgresListPenjualan::all();
        $lokasi    = LokasiKavling::all();
        $admins    = AdminPemberkasan::where('status', 1)->get();

        return view('admin.admin_pemberkasan.index', compact('permissions', 'marketing', 'lokasi', 'progres', 'bank', 'admins'));
    }

    public function setAdmin(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pengajuan_hold,id',
            'id_admin_pemberkasan' => 'nullable|exists:admin_pemberkasan,id',
        ], [
            'id.required' => 'Data tidak valid.',
            'id.exists'   => 'Data pengajuan tidak ditemukan.',
            'id_admin_pemberkasan.exists' => 'Admin Pemberkasan tidak ditemukan.',
        ]);

        DB::beginTransaction();
        try {
            PengajuanHold::where('id', $request->id)->update([
                'id_admin_pemberkasan' => $request->id_admin_pemberkasan ?: null,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

    public function chartData()
    {
        $data = PengajuanHold::where('stt_reg', '!=', 2)
            ->whereNotNull('id_admin_pemberkasan')
            ->select('id_admin_pemberkasan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('id_admin_pemberkasan')
            ->with('adminPemberkasan')
            ->get();

        $labels = [];
        $values = [];

        foreach ($data as $item) {
            $labels[] = $item->adminPemberkasan->nama_lengkap ?? '-';
            $values[] = $item->jumlah;
        }

        return response()->json([
            'labels' => $labels,
            'data'   => $values,
        ]);
    }
}
