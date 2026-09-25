<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\MarketingOffline;
use App\Models\ProgresListPenjualan;
use App\Models\UploudFile;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ProsesMarketingController extends Controller
{
    use LogAktivitasTrait;

    private const BERKAS = [
        'foto_pemohon' => 'Foto Pemohon',
        'foto_ktp'     => 'Foto KTP',
        'foto_npwp'    => 'Foto NPWP',
        'foto_kk'      => 'Foto KK',
        'foto_bpjs'    => 'Foto BPJS',
        'foto_ktp_p'   => 'Foto KTP Pasangan',
        'file_bukti'   => 'Bukti Transfer Booking',
        'file_sppr'    => 'Bukti Proses Admin',
    ];

    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = Customer::with(['lokasi', 'kavling', 'progres'])
                ->where('stt_arsip', 0)
                ->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('blok_unit', function ($row) {
                    return $row->kavling->kode_kavling ?? '-';
                })
                ->addColumn('lokasi_nama', function ($row) {
                    return $row->lokasi->nama_kavling ?? '-';
                })
                ->addColumn('status_progres', function ($row) {
                    $status = $row->progres->status_progres ?? '-';
                    $warna  = $row->progres->warna ?? '#6c757d';
                    $rgb    = sscanf($warna, '#%02x%02x%02x') ?: [108, 117, 125];
                    $terang = ($rgb[0] * 0.299 + $rgb[1] * 0.587 + $rgb[2] * 0.114) > 150;

                    return '<span class="badge" style="background-color:' . e($warna) . ';color:' . ($terang ? '#1f2937' : '#ffffff') . '">' . e($status) . '</span>';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl = route('proses-marketing.edit', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-warning btn-sm mx-1 edit-button"
                                data-id="' . e($row->id) . '"
                                data-url="' . e($editUrl) . '">Edit</button>';
                    }
                    $btn .= '</div>';

                    return $btn;
                })
                ->rawColumns(['action', 'status_progres'])
                ->make(true);
        }

        $customerList = Customer::where('stt_arsip', 0)->orderBy('nama_lengkap')->get();
        $marketingList = MarketingOffline::orderBy('nama_marketing')->get();

        return view('admin.transaksi.proses_marketing.index', compact('permissions', 'customerList', 'marketingList'));
    }

    public function getCustomerDetail($id)
    {
        try {
            $customer = Customer::with(['lokasi', 'kavling'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $this->dataForm($customer),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Data customer tidak ditemukan.'], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_customer' => 'required|integer|exists:customer,id',
        ], [
            'id_customer.required' => 'Customer wajib dipilih.',
            'id_customer.exists'   => 'Customer tidak ditemukan.',
        ]);

        $customer = Customer::with('progres')->findOrFail($request->id_customer);

        return $this->simpanData($request, $customer, 'create');
    }

    public function edit($id)
    {
        $customer = Customer::with(['lokasi', 'kavling'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $this->dataForm($customer),
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::with('progres')->findOrFail($id);

        return $this->simpanData($request, $customer, 'edit');
    }

    private function simpanData(Request $request, Customer $customer, string $aksi)
    {
        $request->merge([
            'besaran_dp'  => $request->besaran_dp ? str_replace('.', '', $request->besaran_dp) : 0,
            'diskon'      => $request->diskon ? str_replace('.', '', $request->diskon) : null,
        ]);

        $rules = [
            'nama_lengkap'      => 'required|string|max:100',
            'nik'               => 'required|string|max:50',
            'tempat_lahir'      => 'required|string|max:50',
            'tgl_lahir'         => 'required|date',
            'no_telp'           => 'required|string|max:20',
            'jenis_kelamin'     => 'required|string|max:10',
            'email'             => 'nullable|string|email|max:100',
            'npwp'              => 'nullable|string|max:30',
            'pekerjaan'         => 'nullable|string|max:100',
            'no_bpjs_kes'       => 'nullable|string|max:35',
            'alamat_ktp'        => 'required',
            'alamat_domisili'   => 'required',
            'status_pernikahan' => 'required|string',
            'nama_p'            => 'nullable|string|max:225',
            'nik_p'             => 'nullable|string|max:255',
            'nama_saudara'      => 'nullable|string|max:255',
            'no_telp_saudara'   => 'nullable|string|max:255',
            'id_marketing'      => 'required|integer',
            'sumber_prospek'    => 'required|string|max:50',
            'jenis_perumahan'   => 'required|string',
            'jenis_pembelian'   => 'required|string',
            'besaran_dp'        => 'required|numeric',
            'diskon'            => 'nullable|numeric',
            'foto_pemohon'      => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_ktp'          => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_npwp'         => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_kk'           => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_bpjs'         => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_ktp_p'        => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'file_bukti'        => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
            'file_sppr'         => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
        ];

        $messages = [
            'nama_lengkap.required'     => 'Nama lengkap wajib diisi.',
            'nik.required'              => 'NIK wajib diisi.',
            'tempat_lahir.required'     => 'Tempat lahir wajib diisi.',
            'tgl_lahir.required'        => 'Tanggal lahir wajib diisi.',
            'tgl_lahir.date'            => 'Tanggal lahir harus berupa tanggal yang valid.',
            'no_telp.required'          => 'No. Telp / WA wajib diisi.',
            'jenis_kelamin.required'    => 'Jenis kelamin wajib dipilih.',
            'alamat_ktp.required'       => 'Alamat KTP wajib diisi.',
            'alamat_domisili.required'  => 'Alamat domisili wajib diisi.',
            'status_pernikahan.required' => 'Status pernikahan wajib dipilih.',
            'id_marketing.required'     => 'Marketing wajib dipilih.',
            'sumber_prospek.required'   => 'Sumber prospek wajib dipilih.',
            'jenis_perumahan.required'  => 'Jenis perumahan wajib dipilih.',
            'jenis_pembelian.required'  => 'Jenis pembelian wajib dipilih.',
            'besaran_dp.required'       => 'Besaran DP wajib diisi.',
            'besaran_dp.numeric'        => 'Besaran DP harus berupa angka.',
            'diskon.numeric'            => 'Diskon harus berupa angka.',
            'email.email'               => 'Format email tidak valid.',
            'foto_ktp.mimes'            => 'Foto KTP harus berformat JPG atau PNG.',
            'foto_ktp.max'              => 'Foto KTP maksimal 2 MB.',
            'foto_kk.mimes'             => 'Foto KK harus berformat JPG atau PNG.',
            'foto_kk.max'               => 'Foto KK maksimal 2 MB.',
            'foto_npwp.mimes'           => 'Foto NPWP harus berformat JPG atau PNG.',
            'foto_npwp.max'             => 'Foto NPWP maksimal 2 MB.',
            'foto_bpjs.mimes'           => 'Foto BPJS harus berformat JPG atau PNG.',
            'foto_bpjs.max'             => 'Foto BPJS maksimal 2 MB.',
            'foto_pemohon.mimes'        => 'Foto pemohon harus berformat JPG atau PNG.',
            'foto_pemohon.max'          => 'Foto pemohon maksimal 2 MB.',
            'foto_ktp_p.mimes'          => 'Foto KTP pasangan harus berformat JPG atau PNG.',
            'foto_ktp_p.max'            => 'Foto KTP pasangan maksimal 2 MB.',
            'file_bukti.mimes'          => 'Bukti transfer harus berformat JPG, PNG, atau PDF.',
            'file_bukti.max'            => 'Bukti transfer maksimal 2 MB.',
            'file_sppr.mimes'           => 'Bukti proses admin harus berformat JPG, PNG, atau PDF.',
            'file_sppr.max'             => 'Bukti proses admin maksimal 2 MB.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            $customer->update([
                'nama_lengkap'      => $request->nama_lengkap,
                'nik'               => $request->nik,
                'tempat_lahir'      => $request->tempat_lahir,
                'tgl_lahir'         => $request->tgl_lahir,
                'no_telp'           => $request->no_telp,
                'jenis_kelamin'     => $request->jenis_kelamin,
                'email'             => $request->email,
                'npwp'              => $request->npwp,
                'pekerjaan'         => $request->pekerjaan === 'Lain-lain' ? ($request->pekerjaan_lain ?: $request->pekerjaan) : $request->pekerjaan,
                'no_bpjs_kes'       => $request->no_bpjs_kes,
                'alamat_ktp'        => $request->alamat_ktp,
                'alamat_domisili'   => $request->alamat_domisili,
                'status_pernikahan' => $request->status_pernikahan,
                'nama_p'            => $request->nama_p,
                'nik_p'             => $request->nik_p,
                'nama_saudara'      => $request->nama_saudara,
                'no_telp_saudara'   => $request->no_telp_saudara,
                'id_marketing'      => $request->id_marketing,
                'sumber_prospek'    => $request->sumber_prospek,
                'jenis_perumahan'   => $request->jenis_perumahan,
                'jenis_pembelian'   => $request->jenis_pembelian,
                'besaran_dp'        => $request->besaran_dp,
                'diskon'            => $request->diskon,
            ]);

            $this->simpanBerkas($request, $customer);
            $this->ubahStatusProgres($customer);

            if ($aksi === 'create') {
                $this->logCreate('Proses Marketing', $customer->id);
            } else {
                $this->logEdit('Proses Marketing', $customer->id);
            }

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Simpan Proses Marketing: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

    private function simpanBerkas(Request $request, Customer $customer)
    {
        foreach (self::BERKAS as $field => $label) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file     = $request->file($field);
            $filename = Str::random(25) . '.' . strtolower($file->getClientOriginalExtension());
            $file->move(public_path('assets/customer/'), $filename);

            $berkas = UploudFile::where('id_customer', $customer->id)
                ->where('nama_file', $label)
                ->first();

            if ($berkas) {
                $berkas->update([
                    'tanggal'  => Carbon::now(),
                    'lampiran' => $filename,
                ]);
            } else {
                UploudFile::create([
                    'tanggal'     => Carbon::now(),
                    'id_customer' => $customer->id,
                    'nama_file'   => $label,
                    'keterangan'  => '',
                    'lampiran'    => $filename,
                ]);
            }
        }
    }

    private function ubahStatusProgres(Customer $customer)
    {
        $status = ProgresListPenjualan::find(11);

        if (! $status) {
            return;
        }

        if (! $customer->progres || $customer->progres->urutan <= $status->urutan) {
            $customer->update(['id_status_progres' => $status->id]);
        }
    }

    private function dataForm(Customer $customer)
    {
        $berkas = [];
        foreach ($customer->berkas as $item) {
            $berkas[$item->nama_file] = $item->lampiran;
        }

        return [
            'id_customer'        => $customer->id,
            'nama_lengkap'       => $customer->nama_lengkap,
            'nik'                => $customer->nik,
            'tempat_lahir'       => $customer->tempat_lahir,
            'tgl_lahir'          => $customer->tgl_lahir,
            'no_telp'            => $customer->no_telp,
            'jenis_kelamin'      => $customer->jenis_kelamin,
            'email'              => $customer->email,
            'npwp'               => $customer->npwp,
            'pekerjaan'          => $customer->pekerjaan,
            'no_bpjs_kes'        => $customer->no_bpjs_kes,
            'alamat_ktp'         => $customer->alamat_ktp,
            'alamat_domisili'    => $customer->alamat_domisili,
            'status_pernikahan'  => $customer->status_pernikahan,
            'nama_p'             => $customer->nama_p,
            'nik_p'              => $customer->nik_p,
            'nama_saudara'       => $customer->nama_saudara,
            'no_telp_saudara'    => $customer->no_telp_saudara,
            'id_marketing'       => $customer->id_marketing,
            'sumber_prospek'     => $customer->sumber_prospek,
            'jenis_perumahan'    => $customer->jenis_perumahan,
            'jenis_pembelian'    => $customer->jenis_pembelian,
            'besaran_dp'         => $customer->besaran_dp,
            'diskon'             => $customer->diskon,
            'lokasi_nama'        => $customer->lokasi->nama_kavling ?? '-',
            'kode_kavling'       => $customer->kavling->kode_kavling ?? '-',
            'total_harga'        => $customer->total_harga,
            'berkas'             => $berkas,
        ];
    }
}