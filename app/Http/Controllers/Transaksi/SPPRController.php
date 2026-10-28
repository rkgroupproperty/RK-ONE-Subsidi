<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\LokasiKavling;
use App\Models\MarketingOffline;
use App\Models\AdminPemberkasan;
use App\Models\SPPR;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class SPPRController extends Controller
{
    use LogAktivitasTrait;

    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = SPPR::with(['customer.lokasi', 'customer.kavling', 'customer.marketing', 'customer.progres', 'customer.adminPemberkasan', 'marketing'])
                ->whereHas('customer', function ($q) use ($request) {
                    $q->where('stt_arsip', 0)->where('id_status_progres', 10);
                    if ($request->filled('id_lokasi')) {
                        $q->where('id_lokasi', $request->id_lokasi);
                    }
                    if ($request->filled('id_marketing')) {
                        $q->where('id_marketing', $request->id_marketing);
                    }
                    if ($request->filled('id_admin_pemberkasan')) {
                        $q->where('id_admin_pemberkasan', $request->id_admin_pemberkasan);
                    }
                })
                ->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tanggal', function ($row) {
                    $tgl = ($row->customer && $row->customer->tanggal_verif) ? $row->customer->tanggal_verif : $row->created_at;
                    return $tgl ? Carbon::parse($tgl)->translatedFormat('d F Y') : '-';
                })
                ->addColumn('customer_nama', function ($row) {
                    $nama = '<strong>' . e($row->nama) . '</strong>';
                    $telp = ($row->no_telp || ($row->customer && $row->customer->no_telp))
                        ? '<br><small class="text-muted"><i class="fas fa-phone-alt mr-1"></i>' . e($row->no_telp ?: $row->customer->no_telp) . '</small>'
                        : '';
                    return $nama . $telp;
                })
                ->addColumn('marketing', function ($row) {
                    $mkt = $row->customer->marketing->nama_marketing ?? $row->marketing->nama_marketing ?? 'Non Marketing';
                    return '<span class="badge bg-secondary">' . e($mkt) . '</span>';
                })
                ->addColumn('admin_pemberkasan', function ($row) {
                    $admin = $row->customer->adminPemberkasan->nama_lengkap ?? null;
                    if ($admin) {
                        return '<span class="badge" style="background-color: #6366f1; color: #ffffff;"><i class="fas fa-user-shield mr-1"></i>' . e($admin) . '</span>';
                    }
                    return '<span class="badge bg-light text-muted border">-</span>';
                })
                ->addColumn('customer_lokasi', function ($row) {
                    $lokasi = '<strong>' . e($row->customer->lokasi->nama_kavling ?? '-') . '</strong>';
                    $unit = '<br><span class="badge bg-primary text-white">' . e($row->blok . ' ' . $row->no) . '</span>';
                    return $lokasi . $unit;
                })
                ->addColumn('status_progres', function ($row) {
                    return '<span class="badge" style="background-color: #0891b2; color: #ffffff;">Proses Admin</span>';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $cetakUrl = route('proses-admin.cetak', $row->id);
                    $editUrl = route('proses-admin.edit', $row->id);
                    $deleteUrl = route('proses-admin.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-info btn-sm mx-1 edit-button"
                                data-id="' . e($row->id) . '"
                                data-url="' . e($editUrl) . '">Edit</button>';
                    }
                    $btn .= '<a href="' . e($cetakUrl) . '" target="_blank" class="btn btn-dark btn-sm mx-1">Cetak</a>';
                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="delete-button btn btn-danger btn-sm mx-1">Hapus</button>
                        </form>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action', 'customer_nama', 'marketing', 'admin_pemberkasan', 'customer_lokasi', 'status_progres'])
                ->make(true);
        }

        $customerList = Customer::where('stt_arsip', 0)->where('id_status_progres', 11)->orderBy('nama_lengkap')->get();
        $marketingList = MarketingOffline::orderBy('nama_marketing')->get();
        $adminPemberkasanList = AdminPemberkasan::orderBy('nama_lengkap')->get();
        $lokasiList = LokasiKavling::whereIn('stt_tampil', [1, 3])->orderBy('nama_kavling')->get();

        $lastId = SPPR::max('id') ?? 0;
        $nextNoSppr = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);

        return view('admin.transaksi.sppr.index', compact('permissions', 'customerList', 'marketingList', 'adminPemberkasanList', 'lokasiList', 'nextNoSppr'));
    }

    public function getCustomerDetail($id)
    {
        try {
            $customer = Customer::with(['lokasi', 'kavling', 'pemasukans', 'marketing'])->findOrFail($id);

            $bookingFee = (int) $customer->pemasukans()
                ->where('id_kategori_transaksi', 1)
                ->sum('nominal');

            $blok = '';
            $no = '';
            if ($customer->kavling) {
                $kav = $customer->kavling;
                $blok = $kav->blok ?: '';
                $no = $kav->no ?: '';

                if (!$blok || !$no) {
                    $kode = trim($kav->kode_kavling ?? '');
                    if (str_contains($kode, '-')) {
                        $parts = explode('-', $kode, 2);
                        if (!$blok) $blok = $parts[0];
                        if (!$no) $no = $parts[1] ?? '';
                    } elseif (preg_match('/^([a-zA-Z\s]+)\s*(\d+)$/', $kode, $m)) {
                        if (!$blok) $blok = trim($m[1]);
                        if (!$no) $no = trim($m[2]);
                    } else {
                        if (!$blok) $blok = $kode;
                        if (!$no) $no = '-';
                    }
                }
            }
            if (!$no) {
                $no = '-';
            }

            $sppr = SPPR::where('id_customer', $id)->first();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'nama_lengkap' => $customer->nama_lengkap,
                    'id_admin_pemberkasan' => $customer->id_admin_pemberkasan,
                    'tanggal_proses' => $customer->tanggal_verif ? Carbon::parse($customer->tanggal_verif)->format('Y-m-d') : date('Y-m-d'),
                    'alamat' => $customer->alamat_ktp ?? $customer->alamat_domisili ?? '',
                    'nik' => $customer->nik,
                    'no_telp' => $customer->no_telp,
                    'id_marketing' => $customer->id_marketing,
                    'nama_marketing' => $customer->marketing->nama_marketing ?? '',
                    'luas_bangunan' => $customer->kavling->luas_bangunan ?? 0,
                    'luas_tanah' => $customer->kavling->luas_tanah ?? 0,
                    'blok' => $blok,
                    'no' => $no,
                    'harga_jual' => $sppr->harga_jual ?? ($customer->hrg_jual ?? 0),
                    'asumsi_plafon_kpr' => $sppr->asumsi_plafon_kpr ?? ($customer->estimasi_plafon ?? 0),
                    'biaya_surat_surat' => $sppr->biaya_surat_surat ?? ($customer->biaya_surat ?? 0),
                    'peningkatan_mutu' => $sppr->peningkatan_mutu ?? ($customer->peningkatan_mutu ?? 0),
                    'biaya_kelebihan_tanah' => $sppr->biaya_kelebihan_tanah ?? 0,
                    'biaya_sudut' => $sppr->biaya_sudut ?? 0,
                    'biaya_lain_lain' => $sppr->biaya_lain_lain ?? 0,
                    'cicilan_per_bulan' => $sppr->cicilan_per_bulan ?? 0,
                    'promo' => $sppr->promo ?? '',
                    'perubahan_posisi' => $sppr->perubahan_posisi ?? '',
                    'jumlah_booking_fee' => $sppr->jumlah_booking_fee ?? $bookingFee,
                    'keterangan_booking' => $sppr->keterangan_booking ?? '',
                    'nominal_dp' => $sppr->nominal_dp ?? 0,
                    'keterangan_dp' => $sppr->keterangan_dp ?? '',
                    'nominal_biaya_posisi_unit' => $sppr->nominal_biaya_posisi_unit ?? 0,
                    'keterangan_posisi_unit' => $sppr->keterangan_posisi_unit ?? '',
                    'nominal_biaya_kpr' => $sppr->nominal_biaya_kpr ?? 0,
                    'keterangan_kpr' => $sppr->keterangan_kpr ?? '',
                    'nominal_blokir_angsuran' => $sppr->nominal_blokir_angsuran ?? 0,
                    'keterangan_blokir_angsuran' => $sppr->keterangan_blokir_angsuran ?? '',
                    'nominal_biaya_materai' => $sppr->nominal_biaya_materai ?? 0,
                    'keterangan_materai' => $sppr->keterangan_materai ?? '',
                    'nominal_biaya_buka_tabungan' => $sppr->nominal_biaya_buka_tabungan ?? 0,
                    'keterangan_tabungan' => $sppr->keterangan_tabungan ?? '',
                    'keterangan_shm' => $sppr->keterangan_shm ?? '',
                    'total_yang_harus_dibayar' => $sppr->total_yang_harus_dibayar ?? 0,
                    'no_sppr' => $sppr->no_sppr ?? '',
                    'pekerjaan' => $customer->pekerjaan ?? ($sppr->pekerjaan ?? ''),
                    'agama' => $customer->agama ?? ($sppr->agama ?? ''),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Data customer tidak ditemukan.'], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_customer' => 'required|integer|exists:customer,id',
            'id_admin_pemberkasan' => 'nullable|integer|exists:admin_pemberkasan,id',
            'tanggal_proses' => 'nullable|date',
            'no_sppr' => 'nullable',
            'nama' => 'required',
            'alamat' => 'required',
            'nik' => 'required',
            'no_telp' => 'required',
            'luas_bangunan' => 'required|numeric',
            'luas_tanah' => 'required|numeric',
            'blok' => 'required',
            'no' => 'nullable',
            'harga_jual' => 'nullable|numeric',
            'asumsi_plafon_kpr' => 'nullable|numeric',
            'biaya_surat_surat' => 'nullable|numeric',
            'peningkatan_mutu' => 'nullable|numeric',
            'biaya_kelebihan_tanah' => 'nullable|numeric',
            'biaya_sudut' => 'nullable|numeric',
            'biaya_lain_lain' => 'nullable|numeric',
            'jumlah_booking_fee' => 'nullable|numeric',
            'cicilan_per_bulan' => 'nullable|numeric',
            'id_marketing' => 'nullable|integer',
            'penandatangan' => 'nullable',
            'keterangan' => 'nullable',
            'agama' => 'nullable',
            'pekerjaan' => 'nullable',
            'promo' => 'nullable',
            'perubahan_posisi' => 'nullable',
            'keterangan_booking' => 'nullable',
            'nominal_dp' => 'nullable|numeric',
            'keterangan_dp' => 'nullable',
            'nominal_biaya_posisi_unit' => 'nullable|numeric',
            'keterangan_posisi_unit' => 'nullable',
            'nominal_biaya_kpr' => 'nullable|numeric',
            'keterangan_kpr' => 'nullable',
            'nominal_blokir_angsuran' => 'nullable|numeric',
            'keterangan_blokir_angsuran' => 'nullable',
            'nominal_biaya_materai' => 'nullable|numeric',
            'keterangan_materai' => 'nullable',
            'nominal_biaya_buka_tabungan' => 'nullable|numeric',
            'keterangan_tabungan' => 'nullable',
            'keterangan_shm' => 'nullable',
        ], [
            'id_customer.required' => 'Customer wajib dipilih.',
            'nama.required' => 'Nama wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'nik.required' => 'NIK wajib diisi.',
            'no_telp.required' => 'No Telp wajib diisi.',
            'luas_bangunan.required' => 'Luas Bangunan wajib diisi.',
            'luas_tanah.required' => 'Luas Tanah wajib diisi.',
            'blok.required' => 'Blok wajib diisi.',
        ]);

        $total = $this->hitungTotal($request);
        $tanggalProses = $request->filled('tanggal_proses') ? Carbon::parse($request->tanggal_proses) : Carbon::now();

        $sppr = SPPR::where('id_customer', $request->id_customer)->first();
        if (!$sppr) {
            $sppr = new SPPR();
        }

        $sppr->fill([
            'id_customer' => $request->id_customer,
            'no_sppr' => $request->no_sppr,
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'nik' => $request->nik,
            'no_telp' => $request->no_telp,
            'luas_bangunan' => $request->luas_bangunan,
            'luas_tanah' => $request->luas_tanah,
            'blok' => $request->blok,
            'no' => $request->no ?: '-',
            'harga_jual' => $request->harga_jual,
            'asumsi_plafon_kpr' => $request->asumsi_plafon_kpr,
            'biaya_surat_surat' => $request->biaya_surat_surat,
            'peningkatan_mutu' => $request->peningkatan_mutu,
            'biaya_kelebihan_tanah' => $request->biaya_kelebihan_tanah,
            'biaya_sudut' => $request->biaya_sudut,
            'biaya_lain_lain' => $request->biaya_lain_lain,
            'total_yang_harus_dibayar' => $total,
            'jumlah_booking_fee' => $request->jumlah_booking_fee,
            'cicilan_per_bulan' => $request->cicilan_per_bulan,
            'id_marketing' => $request->id_marketing,
            'penandatangan' => $request->penandatangan,
            'keterangan' => $request->keterangan,
            'agama' => $request->agama,
            'pekerjaan' => $request->pekerjaan,
            'promo' => $request->promo,
            'perubahan_posisi' => $request->perubahan_posisi,
            'keterangan_booking' => $request->keterangan_booking,
            'nominal_dp' => $request->nominal_dp,
            'keterangan_dp' => $request->keterangan_dp,
            'nominal_biaya_posisi_unit' => $request->nominal_biaya_posisi_unit,
            'keterangan_posisi_unit' => $request->keterangan_posisi_unit,
            'nominal_biaya_kpr' => $request->nominal_biaya_kpr,
            'keterangan_kpr' => $request->keterangan_kpr,
            'nominal_blokir_angsuran' => $request->nominal_blokir_angsuran,
            'keterangan_blokir_angsuran' => $request->keterangan_blokir_angsuran,
            'nominal_biaya_materai' => $request->nominal_biaya_materai,
            'keterangan_materai' => $request->keterangan_materai,
            'nominal_biaya_buka_tabungan' => $request->nominal_biaya_buka_tabungan,
            'keterangan_tabungan' => $request->keterangan_tabungan,
            'keterangan_shm' => $request->keterangan_shm,
        ]);
        $sppr->created_at = $tanggalProses;
        $sppr->save();

        $this->logCreate('Proses Admin', $sppr->id);

        $customerUpdate = [
            'id_status_progres' => 10,
            'tanggal_verif' => $tanggalProses->format('Y-m-d'),
        ];
        if ($request->filled('id_admin_pemberkasan')) {
            $customerUpdate['id_admin_pemberkasan'] = $request->id_admin_pemberkasan;
        }
        Customer::where('id', $request->id_customer)->update($customerUpdate);

        try {
            $adminName = null;
            if ($request->filled('id_admin_pemberkasan')) {
                $adm = \App\Models\AdminPemberkasan::find($request->id_admin_pemberkasan);
                $adminName = $adm ? ($adm->nama_lengkap ?? $adm->nama_admin ?? '') : null;
            }
            \App\Services\GoogleSheetProgressSyncService::syncCustomer($request->id_customer, [
                'entriAdmin' => $tanggalProses->format('d/m/Y'),
                'namaAdmin'  => $adminName,
                'keterangan' => 'PROSES ADMIN',
            ]);
        } catch (\Throwable $e) {
            Log::error("GAS Sync SPPR store: " . $e->getMessage());
        }

        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $sppr = SPPR::with('customer')->findOrFail($id);
        $data = $sppr->toArray();
        $data['tanggal_proses'] = ($sppr->customer && $sppr->customer->tanggal_verif)
            ? Carbon::parse($sppr->customer->tanggal_verif)->format('Y-m-d')
            : ($sppr->created_at ? Carbon::parse($sppr->created_at)->format('Y-m-d') : date('Y-m-d'));
        $data['id_admin_pemberkasan'] = $sppr->customer->id_admin_pemberkasan ?? null;

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $sppr = SPPR::findOrFail($id);

        $request->validate([
            'id_customer' => 'required|integer|exists:customer,id',
            'id_admin_pemberkasan' => 'nullable|integer|exists:admin_pemberkasan,id',
            'tanggal_proses' => 'nullable|date',
            'no_sppr' => 'nullable',
            'nama' => 'required',
            'alamat' => 'required',
            'nik' => 'required',
            'no_telp' => 'required',
            'luas_bangunan' => 'required|numeric',
            'luas_tanah' => 'required|numeric',
            'blok' => 'required',
            'no' => 'nullable',
            'harga_jual' => 'nullable|numeric',
            'asumsi_plafon_kpr' => 'nullable|numeric',
            'biaya_surat_surat' => 'nullable|numeric',
            'peningkatan_mutu' => 'nullable|numeric',
            'biaya_kelebihan_tanah' => 'nullable|numeric',
            'biaya_sudut' => 'nullable|numeric',
            'biaya_lain_lain' => 'nullable|numeric',
            'jumlah_booking_fee' => 'nullable|numeric',
            'cicilan_per_bulan' => 'nullable|numeric',
            'id_marketing' => 'nullable|integer',
            'penandatangan' => 'nullable',
            'keterangan' => 'nullable',
            'agama' => 'nullable',
            'pekerjaan' => 'nullable',
            'promo' => 'nullable',
            'perubahan_posisi' => 'nullable',
            'keterangan_booking' => 'nullable',
            'nominal_dp' => 'nullable|numeric',
            'keterangan_dp' => 'nullable',
            'nominal_biaya_posisi_unit' => 'nullable|numeric',
            'keterangan_posisi_unit' => 'nullable',
            'nominal_biaya_kpr' => 'nullable|numeric',
            'keterangan_kpr' => 'nullable',
            'nominal_blokir_angsuran' => 'nullable|numeric',
            'keterangan_blokir_angsuran' => 'nullable',
            'nominal_biaya_materai' => 'nullable|numeric',
            'keterangan_materai' => 'nullable',
            'nominal_biaya_buka_tabungan' => 'nullable|numeric',
            'keterangan_tabungan' => 'nullable',
            'keterangan_shm' => 'nullable',
        ]);

        $total = $this->hitungTotal($request);

        $sppr->update([
            'id_customer' => $request->id_customer,
            'no_sppr' => $request->no_sppr,
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'nik' => $request->nik,
            'no_telp' => $request->no_telp,
            'luas_bangunan' => $request->luas_bangunan,
            'luas_tanah' => $request->luas_tanah,
            'blok' => $request->blok,
            'no' => $request->no ?: '-',
            'harga_jual' => $request->harga_jual,
            'asumsi_plafon_kpr' => $request->asumsi_plafon_kpr,
            'biaya_surat_surat' => $request->biaya_surat_surat,
            'peningkatan_mutu' => $request->peningkatan_mutu,
            'biaya_kelebihan_tanah' => $request->biaya_kelebihan_tanah,
            'biaya_sudut' => $request->biaya_sudut,
            'biaya_lain_lain' => $request->biaya_lain_lain,
            'total_yang_harus_dibayar' => $total,
            'jumlah_booking_fee' => $request->jumlah_booking_fee,
            'cicilan_per_bulan' => $request->cicilan_per_bulan,
            'id_marketing' => $request->id_marketing,
            'penandatangan' => $request->penandatangan,
            'keterangan' => $request->keterangan,
            'agama' => $request->agama,
            'pekerjaan' => $request->pekerjaan,
            'promo' => $request->promo,
            'perubahan_posisi' => $request->perubahan_posisi,
            'keterangan_booking' => $request->keterangan_booking,
            'nominal_dp' => $request->nominal_dp,
            'keterangan_dp' => $request->keterangan_dp,
            'nominal_biaya_posisi_unit' => $request->nominal_biaya_posisi_unit,
            'keterangan_posisi_unit' => $request->keterangan_posisi_unit,
            'nominal_biaya_kpr' => $request->nominal_biaya_kpr,
            'keterangan_kpr' => $request->keterangan_kpr,
            'nominal_blokir_angsuran' => $request->nominal_blokir_angsuran,
            'keterangan_blokir_angsuran' => $request->keterangan_blokir_angsuran,
            'nominal_biaya_materai' => $request->nominal_biaya_materai,
            'keterangan_materai' => $request->keterangan_materai,
            'nominal_biaya_buka_tabungan' => $request->nominal_biaya_buka_tabungan,
            'keterangan_tabungan' => $request->keterangan_tabungan,
            'keterangan_shm' => $request->keterangan_shm,
        ]);

        if ($request->filled('tanggal_proses')) {
            $sppr->created_at = Carbon::parse($request->tanggal_proses);
            $sppr->save();
        }

        $this->logEdit('Proses Admin', $sppr->id);

        $custId = $sppr->id_customer ?? $request->id_customer;
        if ($custId) {
            $custUpd = [];
            if ($request->filled('id_admin_pemberkasan')) {
                $custUpd['id_admin_pemberkasan'] = $request->id_admin_pemberkasan;
            }
            if ($request->filled('tanggal_proses')) {
                $custUpd['tanggal_verif'] = Carbon::parse($request->tanggal_proses)->format('Y-m-d');
            }
            if (!empty($custUpd)) {
                Customer::where('id', $custId)->update($custUpd);
            }

            try {
                $adminName = null;
                if ($request->filled('id_admin_pemberkasan')) {
                    $adm = \App\Models\AdminPemberkasan::find($request->id_admin_pemberkasan);
                    $adminName = $adm ? ($adm->nama_lengkap ?? $adm->nama_admin ?? '') : null;
                }
                $tglAdmin = $request->filled('tanggal_proses') ? Carbon::parse($request->tanggal_proses)->format('d/m/Y') : null;
                \App\Services\GoogleSheetProgressSyncService::syncCustomer($custId, [
                    'entriAdmin' => $tglAdmin,
                    'namaAdmin'  => $adminName,
                    'keterangan' => 'PROSES ADMIN',
                ]);
            } catch (\Throwable $e) {
                Log::error("GAS Sync SPPR update: " . $e->getMessage());
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $sppr = SPPR::findOrFail($id);
        $customerId = $sppr->id_customer;

        $this->logDelete('Proses Admin', $sppr->id);
        $sppr->delete();

        if ($customerId) {
            Customer::where('id', $customerId)->update(['id_status_progres' => 11]);
        }

        return response()->json(['status' => 'success']);
    }


    public function cetak($id)
    {
        $sppr = SPPR::with('customer.lokasi', 'customer.kavling', 'customer.pemasukans', 'customer.marketing', 'marketing')->findOrFail($id);
        $customer = $sppr->customer;
        $namaPerum = $customer->lokasi->nama_kavling ?? '-';
        $lokasiPerum = $customer->lokasi->alamat ?? '-';

        $templatePath = public_path('templates/template_sppr/template_sppr.docx');

        if (!file_exists($templatePath)) {
            abort(404, 'Template SPPR tidak ditemukan.');
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        $tanggalDoc = $sppr->created_at ? Carbon::parse($sppr->created_at) : Carbon::now();

        $fmt = function ($val) {
            return number_format((int) ($val ?? 0), 0, ',', '.');
        };

        $templateProcessor->setValues([
            'tanggal'                    => $tanggalDoc->format('d'),
            'bulan'                      => $tanggalDoc->locale('id')->isoFormat('MMMM'),
            'tahun'                      => $tanggalDoc->format('Y'),
            'hari'                       => $tanggalDoc->locale('id')->isoFormat('dddd'),
            'nama_lengkap'               => $sppr->nama,
            'nik'                        => $sppr->nik,
            'alamat'                     => $sppr->alamat,
            'agama'                      => $sppr->agama ?? '-',
            'pekerjaan'                  => $sppr->pekerjaan ?? $customer->pekerjaan ?? '-',
            'no_telp'                    => $sppr->no_telp,
            'blok'                       => $sppr->blok,
            'no_unit'                    => $sppr->no,
            'luas_tanah'                 => (string) ($sppr->luas_tanah ?? 0),
            'biaya_kelebihan_tanah'      => $fmt($sppr->biaya_kelebihan_tanah),
            'promo'                      => $sppr->promo ?? '-',
            'perubahan_posisi'           => $sppr->perubahan_posisi ?? '-',
            'lokasi_perumahan'           => $namaPerum,
            'nominal_booking'            => $fmt($sppr->jumlah_booking_fee),
            'keterangan_booking'         => $sppr->keterangan_booking ?? '-',
            'nominal_dp'                 => $fmt($sppr->nominal_dp),
            'keterangan_dp'              => $sppr->keterangan_dp ?? '-',
            'nominal_biaya_posisi_unit'  => $fmt($sppr->nominal_biaya_posisi_unit),
            'keterangan_posisi_unit'     => $sppr->keterangan_posisi_unit ?? '-',
            'nominal_biaya_kpr'          => $fmt($sppr->nominal_biaya_kpr),
            'keterangan_kpr'             => $sppr->keterangan_kpr ?? '-',
            'nominal_blokir_angsuran'    => $fmt($sppr->nominal_blokir_angsuran),
            'keterangan_blokir_angsuran' => $sppr->keterangan_blokir_angsuran ?? '-',
            'nominal_biaya_materai'      => $fmt($sppr->nominal_biaya_materai),
            'keterangan_materai'         => $sppr->keterangan_materai ?? '-',
            'nominal_biaya_buka_tabungan'=> $fmt($sppr->nominal_biaya_buka_tabungan),
            'keterangan_tabungan'        => $sppr->keterangan_tabungan ?? '-',
            'biaya_peningkatan_shm'      => $fmt($sppr->peningkatan_mutu),
            'keterangan_shm'             => $sppr->keterangan_shm ?? '-',
            'total_biaya'                => $fmt(
                (int) $sppr->jumlah_booking_fee
                + (int) $sppr->nominal_dp
                + (int) $sppr->nominal_biaya_posisi_unit
                + (int) $sppr->nominal_biaya_kpr
                + (int) $sppr->nominal_blokir_angsuran
                + (int) $sppr->nominal_biaya_materai
                + (int) $sppr->nominal_biaya_buka_tabungan
                + (int) $sppr->peningkatan_mutu
                + 4000000
            ),
        ]);

        $filename = 'SPPR_' . str_replace(' ', '_', $sppr->nama) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'sppr_');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }


    private function hitungTotal(Request $request)
    {
        return (int) ($request->jumlah_booking_fee ?? 0)
            + (int) ($request->nominal_dp ?? 0)
            + (int) ($request->nominal_biaya_posisi_unit ?? 0)
            + (int) ($request->nominal_biaya_kpr ?? 0)
            + (int) ($request->nominal_blokir_angsuran ?? 0)
            + (int) ($request->nominal_biaya_materai ?? 0)
            + (int) ($request->nominal_biaya_buka_tabungan ?? 0)
            + (int) ($request->peningkatan_mutu ?? 0)
            + 4000000;
    }
}
