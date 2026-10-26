<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\ArsipCustomer;
use App\Models\Customer;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ArsipCustomerController extends Controller
{
    use LogAktivitasTrait;

    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();
        Carbon::setLocale('id');

        if ($request->ajax()) {
            $data = Customer::with(['marketing', 'lokasi', 'kavling', 'progres', 'lokasiKavling', 'kavlingPeta'])
                ->where('stt_arsip', 1)
                ->orderByDesc('id');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tanggal', function ($row) {
                    $tgl = $row->tanggal_verif ? Carbon::parse($row->tanggal_verif)->translatedFormat('d F Y') : '-';
                    $kode = $row->kode_customer ? "<strong>{$row->kode_customer}</strong>" : '';
                    return "$tgl<br>$kode";
                })
                ->editColumn('nama_lengkap', function ($row) {
                    $wa = $row->no_telp ? '<br><small class="text-muted"><i class="fas fa-phone mr-1"></i>' . e($row->no_telp) . '</small>' : '';
                    return '<strong>' . e($row->nama_lengkap) . '</strong>' . $wa;
                })
                ->addColumn('marketing', function ($row) {
                    if ($row->id_marketing && $row->marketing) {
                        $namaMarketing = $row->marketing->nama_marketing;
                    } else {
                        $namaMarketing = null;
                    }

                    if (! $namaMarketing) {
                        return "<span class='badge bg-danger'>Non Marketing</span>";
                    }

                    return e($namaMarketing);
                })
                ->addColumn('lokasi', function ($row) {
                    $lokasi  = $row->lokasiKavling ? $row->lokasiKavling->nama_kavling : ($row->lokasi ? $row->lokasi->nama_kavling : '-');
                    $kavling = $row->kavlingPeta ? $row->kavlingPeta->kode_kavling : ($row->kavling ? $row->kavling->kode_kavling : '-');
                    return "<strong style='font-size: 1.05rem'>$lokasi</strong><br><span class='badge badge-secondary'>$kavling</span>";
                })
                ->addColumn('status', function ($row) {
                    $status     = $row->progres ? $row->progres->status_progres : '-';
                    $badgeClass = match (strtoupper($status)) {
                        'BOOKING FEE'  => 'badge-warning text-dark',
                        'PROSES BANK'  => 'badge-secondary',
                        'AKAD'         => 'badge-info text-white',
                        'SERAH TERIMA' => 'badge-dark',
                        'SOLDOUT'      => 'badge-danger',
                        'SP3K'         => 'badge-success',
                        default        => 'badge-secondary'
                    };

                    return "<span class='badge $badgeClass'>" . strtoupper($status) . "</span>";
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $btn = '<div class="text-center">';

                    if ($permissions['edit'] || $permissions['tambah']) {
                        $restoreUrl = route('arsip-customer.restore', $row->id);
                        $btn .= '<button type="button" class="btn btn-success btn-sm restore-button mr-1" data-url="' . e($restoreUrl) . '" data-id="' . $row->id . '">
                            <i class="fas fa-undo mr-1"></i> Kembalikan
                        </button>';
                    }

                    if ($permissions['hapus']) {
                        $deleteUrl = route('arsip-customer.destroy', $row->id);
                        $btn .= '<button type="button" class="btn btn-danger btn-sm delete-button" data-url="' . e($deleteUrl) . '" data-id="' . $row->id . '">
                            <i class="fas fa-trash-alt mr-1"></i> Hapus
                        </button>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['tanggal', 'nama_lengkap', 'marketing', 'lokasi', 'status', 'action'])
                ->make(true);
        }

        return view('admin.customer.arsip_customer.index', compact('permissions'));
    }

    public function restore($id)
    {
        DB::beginTransaction();
        try {
            $customer = Customer::find($id);
            if ($customer) {
                $customer->stt_arsip = 0;
                $customer->save();
                $this->logEdit('Restore Arsip Customer', $customer->id);
            } else {
                $arsip = ArsipCustomer::find($id);
                if ($arsip) {
                    Customer::create([
                        'id_lokasi'         => $arsip->id_lokasi,
                        'id_kavling'        => $arsip->id_kavling,
                        'id_status_progres' => $arsip->id_status_progres ?? 9,
                        'kode_customer'     => $arsip->kode_customer,
                        'nama_lengkap'      => $arsip->nama_lengkap,
                        'nik'               => $arsip->no_ktp ?? $arsip->nik ?? null,
                        'nik_p'             => $arsip->no_ktp_p ?? $arsip->nik_p ?? null,
                        'jenis_kelamin'     => $arsip->jenis_kelamin ?? 'Laki-Laki',
                        'tempat_lahir'      => $arsip->tempat_lahir ?? '-',
                        'tgl_lahir'         => $arsip->tgl_lahir ?? '1990-01-01',
                        'alamat_ktp'        => $arsip->alamat_ktp ?? '-',
                        'alamat_domisili'   => $arsip->alamat_domisili ?? '-',
                        'no_telp'           => $arsip->no_telp ?? '-',
                        'pekerjaan'         => $arsip->pekerjaan ?? '-',
                        'id_marketing'      => $arsip->id_marketing,
                        'stt_arsip'         => 0,
                    ]);
                    $this->logDelete('Arsip Customer Restored', $arsip->id);
                    $arsip->delete();
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Data customer tidak ditemukan.'], 404);
                }
            }

            DB::commit();
            return response()->json([
                'status'  => 'success',
                'message' => 'Customer berhasil dikembalikan ke daftar aktif.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore Arsip Customer Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengembalikan customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $deleted = false;
            $customer = Customer::find($id);
            if ($customer) {
                $this->logDelete('Customer Permanen', $customer->id);
                $customer->delete();
                $deleted = true;
            }

            $arsip = ArsipCustomer::find($id);
            if ($arsip) {
                $this->logDelete('Arsip Customer Permanen', $arsip->id);
                $arsip->delete();
                $deleted = true;
            }

            if (! $deleted) {
                return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.'], 404);
            }

            DB::commit();
            return response()->json([
                'status'  => 'success',
                'message' => 'Data arsip customer berhasil dihapus secara permanen.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Arsip Customer Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
