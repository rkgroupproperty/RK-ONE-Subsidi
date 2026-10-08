<?php
namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\BankKPR;
use App\Models\Notaris;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

Carbon::setLocale('id');

class AccBankController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        app(AkadController::class)->refreshDeadlineAkad();

        if ($request->ajax()) {
            $data = WawancaraSp3k::with(
                'wawancara.customer',
                'wawancara.customer.lokasi',
                'wawancara.customer.kavling',
                'bankKPR'
            )
                ->where('status', 1)
                ->whereHas('wawancara.customer', function ($q) {
                    $q->where('stt_arsip', 0);
                })
                ->orderByDesc('id');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('lokasi_rumah', function ($row) {
                    $kode = $row->wawancara->customer->kavling->kode_kavling ?? '-';
                    $nama = $row->wawancara->customer->lokasi->nama_kavling ?? '-';
                    return "$kode - $nama";
                })
                ->addColumn('bankKPR', function ($row) {
                    return optional($row->bankKPR)->nama ?? '-';
                })
                ->editColumn('harga_jual', function ($row) {
                    return '
                    <div class="d-flex justify-content-between harga-format w-100">
                        <span>Rp.</span>
                        <span>' . number_format($row->wawancara->customer->kavling->hrg_jual, 0, ',', '.') . '</span>
                    </div>';
                })
                ->editColumn('acc_plafon', function ($row) {
                    return '
                    <div class="d-flex justify-content-between harga-format w-100">
                        <span>Rp.</span>
                        <span>' . number_format($row->acc_plafon, 0, ',', '.') . '</span>
                    </div>';
                })
                ->editColumn('tgl_terbit_sp3k', function ($row) {
                    return $row->tgl_terbit_sp3k ? Carbon::parse($row->tgl_terbit_sp3k)->translatedFormat('d F Y') : '-';
                })

                ->editColumn('tgl_expired', function ($row) {
                    return $row->tgl_expired ? Carbon::parse($row->tgl_expired)->translatedFormat('d F Y') : '-';
                })

                ->addColumn('sisa_hari', function ($row) {
                    if ($row->tgl_terbit_sp3k && $row->tgl_expired) {
                        try {
                            $tglExpired = Carbon::parse($row->tgl_expired)->startOfDay();
                            $today      = Carbon::now()->startOfDay();

                            $sisaHari = $today->diffInDays($tglExpired, false);

                            return $sisaHari > 0
                                ? $sisaHari . ' hari'
                                : '<span class="text-danger">Kadaluarsa</span>';
                        } catch (\Exception $e) {
                            return '<span class="text-danger">Tanggal tidak valid</span>';
                        }
                    }

                    return '-';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl   = route('sp3k.show', $row->id_wawancara);
                    $deleteUrl = route('sp3k.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<a href="' . e($editUrl) . '" class="btn btn-primary btn-sm mx-1">Detail</a>';
                    }

                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="delete-button btn btn-danger btn-sm">Batalkan</button></form>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })

                ->rawColumns(['action', 'harga_jual', 'acc_plafon', 'sisa_hari'])
                ->make(true);
        }

        $customerList = Customer::with(['lokasi', 'kavling'])
            ->where('stt_arsip', 0)
            ->orderBy('nama_lengkap', 'asc')
            ->get();
        $bankKPRList  = BankKPR::orderBy('nama', 'asc')->get();
        $notarisList  = Notaris::orderBy('nama_notaris', 'asc')->get();

        return view('admin.transaksi.acc_bank.index', compact('permissions', 'customerList', 'bankKPRList', 'notarisList'));
    }

    public function getCustomerDetail($id)
    {
        try {
            $customer = Customer::with(['lokasi', 'kavling', 'marketing', 'piutangs'])->findOrFail($id);

            $hargaJual = (int) ($customer->kavling->hrg_jual ?? $customer->total_harga ?? 0);
            $sisaBayar = (int) $customer->piutangs->sum('sisa_bayar');

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'id'             => $customer->id,
                    'nama'           => $customer->nama_lengkap,
                    'nik'            => $customer->nik,
                    'lokasi'         => $customer->lokasi->nama_kavling ?? '-',
                    'blok'           => $customer->kavling->kode_kavling ?? '-',
                    'tipe'           => $customer->kavling->tipe_bangunan ?? '-',
                    'harga_jual'     => $hargaJual,
                    'harga_jual_fmt' => number_format($hargaJual, 0, ',', '.'),
                    'sisa_bayar'     => $sisaBayar,
                    'sisa_bayar_fmt' => number_format($sisaBayar, 0, ',', '.'),
                    'id_bank_kpr'    => $customer->id_bank_kpr ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Customer tidak ditemukan'], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_customer'     => 'required|exists:customer,id',
            'id_bank_kpr'     => 'required|exists:bank_kpr,id',
            'no_sp3k'         => 'required|string',
            'acc_plafon'      => 'required',
            'tenor'           => 'required',
            'tgl_terbit_sp3k' => 'required|date',
            'id_notaris'      => 'required|exists:notaris,id',
            'lampiran'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'catatan_acc'     => 'nullable|string',
        ], [
            'id_customer.required'     => 'Customer wajib dipilih.',
            'id_bank_kpr.required'     => 'Bank KPR wajib dipilih.',
            'no_sp3k.required'         => 'Nomor SP3K wajib diisi.',
            'acc_plafon.required'      => 'ACC Plafon wajib diisi.',
            'tenor.required'           => 'Tenor wajib diisi.',
            'tgl_terbit_sp3k.required' => 'Tanggal Terbit SP3K wajib diisi.',
            'id_notaris.required'      => 'Notaris wajib dipilih.',
            'lampiran.mimes'           => 'Lampiran harus berformat JPG, JPEG, PNG, atau PDF.',
            'lampiran.max'             => 'Ukuran lampiran maksimal 2 MB.',
        ]);

        DB::beginTransaction();
        try {
            $tglTerbit  = Carbon::parse($request->tgl_terbit_sp3k);
            $tglExpired = $tglTerbit->copy()->addDays(90);

            $customer = Customer::with('kavling')->findOrFail($request->id_customer);

            // Cari atau buat Wawancara (Proses Bank) jika belum ada
            $wawancara = Wawancara::where('id_customer', $customer->id)->first();
            if (!$wawancara) {
                $wawancara = Wawancara::create([
                    'id_customer'   => $customer->id,
                    'id_bank_kpr'   => $request->id_bank_kpr,
                    'tgl_pengajuan' => Carbon::now('Asia/Jakarta'),
                    'status'        => 2,
                ]);
            } else {
                $wawancara->update([
                    'id_bank_kpr' => $request->id_bank_kpr,
                    'status'      => 2,
                ]);
            }

            $filename = '';
            if ($request->hasFile('lampiran')) {
                $file     = $request->file('lampiran');
                $filename = Str::random(25) . '.' . strtolower($file->getClientOriginalExtension());
                $file->move(public_path('assets/SP3K/'), $filename);
            }

            $accPlafon = (int) str_replace(['.', ',', ' '], '', $request->acc_plafon);
            $tenor     = (int) str_replace(['.', ',', ' '], '', $request->tenor);

            $sp3k = WawancaraSp3k::create([
                'id_wawancara'    => $wawancara->id,
                'id_bank_kpr'     => $request->id_bank_kpr,
                'acc_plafon'      => $accPlafon,
                'tenor'           => $tenor,
                'id_notaris'      => $request->id_notaris,
                'tgl_terbit_sp3k' => $tglTerbit,
                'tgl_expired'     => $tglExpired,
                'lampiran'        => $filename,
                'no_sp3k'         => $request->no_sp3k,
                'catatan_acc'     => $request->catatan_acc ?? '',
                'status'          => 1,
            ]);

            // Update status customer ke SP3K (id 4)
            $customer->update([
                'id_status_progres' => 4,
                'id_bank_kpr'       => $request->id_bank_kpr,
            ]);

            $this->logCreate('Data SP3K', $sp3k->id);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data SP3K berhasil ditambahkan.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store SP3K: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $data = Wawancara::with('customer', 'customer.lokasi', 'customer.kavling')->findOrFail($id);

        if ($request->ajax()) {
            $data = WawancaraSp3k::with('wawancara.customer', 'wawancara.customer.lokasi', 'wawancara.customer.kavling', 'wawancara.bankKPR')
                ->where('id_wawancara', $id)->orderBy('id', 'asc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('bankKPR', function ($row) {
                    return optional($row->bankKPR)->nama ?? '-';
                })
                ->editColumn('acc_plafon', function ($row) {
                    return '
                    <div class="d-flex justify-content-between harga-format w-100">
                        <span>Rp.</span>
                        <span>' . number_format($row->acc_plafon, 0, ',', '.') . '</span>
                    </div>';
                })
                ->editColumn('tgl_terbit_sp3k', function ($row) {
                    return $row->tgl_terbit_sp3k ? Carbon::parse($row->tgl_terbit_sp3k)->translatedFormat('d F Y') : '-';
                })

                ->editColumn('tgl_expired', function ($row) {
                    return $row->tgl_expired ? Carbon::parse($row->tgl_expired)->translatedFormat('d F Y') : '-';
                })

                ->addColumn('sisa_hari', function ($row) {
                    if ($row->tgl_terbit_sp3k && $row->tgl_expired) {
                        try {
                            $tglExpired = Carbon::parse($row->tgl_expired)->startOfDay();
                            $today      = Carbon::now()->startOfDay();

                            $sisaHari = $today->diffInDays($tglExpired, false);

                            return $sisaHari > 0
                                ? $sisaHari . ' hari'
                                : '<span class="text-danger">Kadaluarsa</span>';
                        } catch (\Exception $e) {
                            return '<span class="text-danger">Tanggal tidak valid</span>';
                        }
                    }

                    return '-';
                })
                ->rawColumns(['acc_plafon', 'sisa_hari'])
                ->make(true);
        }

        return view('admin.transaksi.acc_bank.detail', compact('data'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $data = WawancaraSp3k::with('wawancara', 'wawancara.customer')->findOrFail($id);

            $data->wawancara->update([
                'status' => 1,
            ]);

            $data->wawancara->customer->update([
                'id_status_progres' => 7,
            ]);

            $data->delete();

            $this->logDelete('Proses Bank SP3K', $data->id);

            DB::commit();

            return response()->json([
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error batalkan SP3K : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
