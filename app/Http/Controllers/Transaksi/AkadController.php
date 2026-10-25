<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Akad;
use App\Models\AkadDetail;
use App\Models\Customer;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\MarketingOffline;
use App\Models\PersyaratanLegal;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\TemplateProcessor;
use TCPDF;
use Yajra\DataTables\DataTables;

class AkadController extends Controller
{
    use LogAktivitasTrait;
    public function refreshDeadlineAkad()
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay();

        $expired = WawancaraSp3k::where('status', 1)
            ->whereDate('tgl_expired', '<', $today)
            ->get();

        if ($expired->isEmpty()) {
            return response()->json(['status' => 'ok']);
        }

        $wawancaraIds = $expired->pluck('id_wawancara')->unique();

        WawancaraSp3k::whereIn('id', $expired->pluck('id'))
            ->update(['status' => 2]);

        Wawancara::whereIn('id', $wawancaraIds)
            ->update(['status' => 1]);

        return response()->json(['status' => 'updated']);
    }

    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $permissions = HakAksesController::getUserPermissions();

        $this->refreshDeadlineAkad();

        if ($request->ajax()) {
            $tab = $request->get('tab', 'konsumen');
            $now = Carbon::now('Asia/Jakarta');
            $filter = $request->get('filter', 'bulan_ini');

            if ($tab === 'jadwal') {
                $query = Akad::query();

                if ($filter === 'semua') {
                    // Tampilkan semua data tanpa filter tanggal
                } elseif ($filter === 'bulan_kemarin') {
                    $lastMonth = $now->copy()->subMonth();
                    $query->whereYear('tgl_akad', $lastMonth->year)->whereMonth('tgl_akad', $lastMonth->month);
                } elseif ($filter === 'pilih_bulan' && $request->filled('bulan') && $request->filled('tahun')) {
                    $bulan = (int) $request->get('bulan');
                    $tahun = (int) $request->get('tahun');
                    $query->whereYear('tgl_akad', $tahun)->whereMonth('tgl_akad', $bulan);
                } elseif ($filter === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
                    $startDate = Carbon::parse($request->get('start_date'))->startOfDay()->toDateString();
                    $endDate = Carbon::parse($request->get('end_date'))->endOfDay()->toDateString();
                    $query->whereBetween('tgl_akad', [$startDate, $endDate]);
                } else {
                    // Default: bulan ini
                    $query->whereYear('tgl_akad', $now->year)->whereMonth('tgl_akad', $now->month);
                }

                $data = $query->orderBy('tgl_akad', 'desc')->orderBy('id', 'desc');

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('total_akad', function ($row) {
                        return AkadDetail::where('id_akad', $row->id)->count();
                    })
                    ->editColumn('tgl_akad', function ($row) {
                        return Carbon::parse($row->tgl_akad)->translatedFormat('d F Y');
                    })
                    ->addColumn('action', function ($row) use ($permissions) {
                        $detailUrl = route('akad.show', $row->id);
                        $editUrl   = route('akad.edit', $row->id);
                        $deleteUrl = route('akad.destroy', $row->id);

                        $btn = '<div class="d-flex justify-content-center">';
                        if ($permissions['edit']) {
                            $btn .= '<a href="' . e($detailUrl) . '" class="btn btn-info btn-xs mr-1"><i class="fas fa-eye mr-1"></i>Detail</a>';
                            $btn .= '<button class="btn btn-primary btn-xs mx-1 edit-button" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '"><i class="fas fa-edit mr-1"></i>Edit</button>';
                        }
                        if ($permissions['hapus']) {
                            $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">'
                                . csrf_field()
                                . method_field('DELETE')
                                . '<button type="submit" class="delete-button btn btn-danger btn-xs"><i class="fas fa-trash mr-1"></i>Hapus</button></form>';
                        }
                        return $btn . '</div>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            // Tab Konsumen (Default): Data Nama Konsumen Akad
            $query = Customer::where('id_status_progres', 3)
                ->where('stt_arsip', 0)
                ->with(['lokasi', 'kavling', 'marketing', 'akadDetail.akad']);

            if ($request->filled('id_lokasi')) {
                $query->where('id_lokasi', $request->get('id_lokasi'));
            }

            if ($request->filled('id_marketing')) {
                $query->where('id_marketing', $request->get('id_marketing'));
            }

            if ($filter === 'semua') {
                // Tampilkan semua data konsumen akad
            } elseif ($filter === 'bulan_kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $query->where(function ($q) use ($lastMonth) {
                    $q->whereHas('akadDetail.akad', function ($qa) use ($lastMonth) {
                        $qa->whereYear('tgl_akad', $lastMonth->year)->whereMonth('tgl_akad', $lastMonth->month);
                    })->orWhere(function ($qb) use ($lastMonth) {
                        $qb->whereYear('tanggal_verif', $lastMonth->year)->whereMonth('tanggal_verif', $lastMonth->month);
                    });
                });
            } elseif ($filter === 'pilih_bulan' && $request->filled('bulan') && $request->filled('tahun')) {
                $bulan = (int) $request->get('bulan');
                $tahun = (int) $request->get('tahun');
                $query->where(function ($q) use ($bulan, $tahun) {
                    $q->whereHas('akadDetail.akad', function ($qa) use ($bulan, $tahun) {
                        $qa->whereYear('tgl_akad', $tahun)->whereMonth('tgl_akad', $bulan);
                    })->orWhere(function ($qb) use ($bulan, $tahun) {
                        $qb->whereYear('tanggal_verif', $tahun)->whereMonth('tanggal_verif', $bulan);
                    });
                });
            } elseif ($filter === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->get('start_date'))->startOfDay()->toDateString();
                $endDate = Carbon::parse($request->get('end_date'))->endOfDay()->toDateString();
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereHas('akadDetail.akad', function ($qa) use ($startDate, $endDate) {
                        $qa->whereBetween('tgl_akad', [$startDate, $endDate]);
                    })->orWhere(function ($qb) use ($startDate, $endDate) {
                        $qb->whereBetween('tanggal_verif', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                    });
                });
            } else {
                // Default: bulan ini
                $query->where(function ($q) use ($now) {
                    $q->whereHas('akadDetail.akad', function ($qa) use ($now) {
                        $qa->whereYear('tgl_akad', $now->year)->whereMonth('tgl_akad', $now->month);
                    })->orWhere(function ($qb) use ($now) {
                        $qb->whereYear('tanggal_verif', $now->year)->whereMonth('tanggal_verif', $now->month);
                    });
                });
            }

            $data = $query->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tgl_akad', function ($row) {
                    $firstDetail = $row->akadDetail->first();
                    if ($firstDetail && $firstDetail->akad && $firstDetail->akad->tgl_akad) {
                        return '<span class="font-weight-bold text-dark"><i class="far fa-calendar-alt text-primary mr-1"></i>' . Carbon::parse($firstDetail->akad->tgl_akad)->translatedFormat('d F Y') . '</span>';
                    }
                    if ($row->tanggal_verif) {
                        return '<span class="text-secondary"><i class="far fa-calendar-alt mr-1"></i>' . Carbon::parse($row->tanggal_verif)->translatedFormat('d F Y') . '</span>';
                    }
                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('nama_lengkap', function ($row) {
                    $nama = '<span class="font-weight-bold text-primary" style="font-size:14px">' . e($row->nama_lengkap) . '</span>';
                    if ($row->nik) {
                        $nama .= '<br><small class="text-muted"><i class="far fa-id-card mr-1"></i>NIK: ' . e($row->nik) . '</small>';
                    }
                    return $nama;
                })
                ->addColumn('no_telp', function ($row) {
                    $telp = $row->no_telp ?? $row->no_hp ?? '-';
                    if ($telp && $telp !== '-') {
                        $cleanTelp = preg_replace('/[^0-9]/', '', $telp);
                        $waNum = str_starts_with($cleanTelp, '0') ? ('62' . substr($cleanTelp, 1)) : $cleanTelp;
                        return '<a href="https://wa.me/' . $waNum . '" target="_blank" class="btn btn-xs btn-outline-success font-weight-bold px-2 py-1"><i class="fab fa-whatsapp mr-1"></i>' . e($telp) . '</a>';
                    }
                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('marketing', function ($row) {
                    return $row->marketing ? '<span class="badge badge-light border text-dark font-weight-bold"><i class="fas fa-user-tie text-info mr-1"></i>' . e($row->marketing->nama_marketing) . '</span>' : '<span class="text-muted">Non Marketing</span>';
                })
                ->addColumn('lokasi_kavling', function ($row) {
                    $lokasi = $row->lokasi->nama_kavling ?? '-';
                    $kavling = $row->kavling->nama_kavling ?? ($row->kavling->kode_kavling ?? '-');
                    return '<strong class="text-indigo"><i class="fas fa-city mr-1"></i>' . e($lokasi) . '</strong><br><span class="badge badge-light border font-weight-bold" style="font-size:11px">Blok: ' . e($kavling) . '</span>';
                })
                ->addColumn('status_akad', function ($row) {
                    $firstDetail = $row->akadDetail->first();
                    if ($firstDetail) {
                        if ($firstDetail->status == 2) {
                            $jenis = $firstDetail->jenis_akad ? ' (' . e($firstDetail->jenis_akad) . ')' : '';
                            return '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Selesai Akad' . $jenis . '</span>';
                        }
                        return '<span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i>Dijadwalkan</span>';
                    }
                    return '<span class="badge badge-info px-2 py-1"><i class="fas fa-file-signature mr-1"></i>Akad Kredit</span>';
                })
                ->addColumn('keterangan', function ($row) {
                    $firstDetail = $row->akadDetail->first();
                    if ($firstDetail && !empty($firstDetail->keterangan)) {
                        return '<small class="text-dark">' . e($firstDetail->keterangan) . '</small>';
                    }
                    if ($firstDetail && $firstDetail->akad && !empty($firstDetail->akad->keterangan)) {
                        return '<small class="text-muted">' . e($firstDetail->akad->keterangan) . '</small>';
                    }
                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('action', function ($row) {
                    $detailUrl = route('customer.show', $row->id);
                    return '<div class="d-flex justify-content-center"><a href="' . e($detailUrl) . '" class="btn btn-info btn-xs shadow-sm"><i class="fas fa-eye mr-1"></i>Detail</a></div>';
                })
                ->rawColumns(['tgl_akad', 'nama_lengkap', 'no_telp', 'marketing', 'lokasi_kavling', 'status_akad', 'keterangan', 'action'])
                ->make(true);
        }

        $lokasiList = LokasiKavling::where('status', 1)->orderBy('nama_kavling')->get();
        $marketingList = MarketingOffline::where('status', 1)->orderBy('nama_marketing')->get();

        $now = Carbon::now('Asia/Jakarta');
        $availableYears = Akad::selectRaw('YEAR(tgl_akad) as tahun')
            ->whereNotNull('tgl_akad')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();
        if (empty($availableYears)) {
            $availableYears = [$now->year];
        }

        $availableMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('admin.transaksi.akad.index', compact('permissions', 'availableYears', 'availableMonths', 'lokasiList', 'marketingList'));
    }

    public function show(Request $request, $id)
    {
        $akad = Akad::findOrFail($id);

        if ($request->ajax()) {

            $filter = $request->get('filter', 1);

            $idWawancara = WawancaraSp3k::where('status', 1)
                ->pluck('id_wawancara');

            $customers = Wawancara::with([
                'customer.kavling',
                'customer.lokasi',
                'customer.persyaratan',
            ])
                ->whereIn('id', $idWawancara)
                ->whereHas('customer', function ($q) {
                    $q->where('stt_arsip', 0);
                })
                ->get()
                ->pluck('customer')
                ->unique('id');

            $customerHadirAkadLain = AkadDetail::where('status', 2)
                ->where('id_akad', '!=', $id)
                ->pluck('id_customer')
                ->toArray();

            $customers = $customers->reject(function ($customer) use ($customerHadirAkadLain) {
                return in_array($customer->id, $customerHadirAkadLain);
            });

            $akadDetails = AkadDetail::where('id_akad', $id)
                ->get()
                ->keyBy('id_customer');

            if ($filter == 2) {
                $customers = $customers->filter(function ($customer) use ($akadDetails) {
                    return $akadDetails->has($customer->id);
                });
            }

            return DataTables::of($customers->values())
                ->addIndexColumn()

                ->addColumn('checkbox', function ($row) use ($akadDetails) {

                    $checked  = '';
                    $disabled = '';

                    if ($akadDetails->has($row->id)) {
                        $detail  = $akadDetails[$row->id];
                        $checked = 'checked';

                        if ($detail->status == 2) {
                            $disabled = 'disabled';
                        }
                    }

                    return '
                    <input type="hidden" name="customer[' . $row->id . ']" value="0">
                    <input type="checkbox"
                        class="row-check"
                        name="customer[' . $row->id . ']"
                        value="1"
                        ' . $checked . '
                        ' . $disabled . '>
                ';
                })

                ->addColumn('nama_lengkap', function ($row) use ($akadDetails) {
                    $nama  = e($row->nama_lengkap);
                    $badge = '';

                    if ($akadDetails->has($row->id)) {
                        $detail = $akadDetails[$row->id];
                        if ($detail->jenis_akad) {
                            $badge = '<br><span class="badge badge-primary">' . e($detail->jenis_akad) . '</span>';
                        }
                    }

                    return $nama . $badge;
                })

                ->addColumn('lokasi_rumah', function ($row) {
                    $lokasi  = $row->lokasi->nama_kavling ?? '-';
                    $kavling = $row->kavling->kode_kavling ?? '-';
                    return '<strong>' . $lokasi . '</strong><br>' . $kavling;
                })

                ->addColumn(
                    'iph',
                    fn($row) =>
                    $row->persyaratan?->IPH
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>'
                )
                ->addColumn(
                    'shgb',
                    fn($row) =>
                    $row->persyaratan?->SHGB
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>'
                )
                ->addColumn(
                    'ssp',
                    fn($row) =>
                    $row->persyaratan?->SSP
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>'
                )
                ->addColumn(
                    'bphtb',
                    fn($row) =>
                    $row->persyaratan?->BPHTB
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>'
                )
                ->addColumn(
                    'sikumbang',
                    fn($row) =>
                    $row->persyaratan?->SIKUMBANG
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>'
                )
                ->addColumn(
                    'daftar_sikasep',
                    fn($row) =>
                    $row->persyaratan?->DAFTAR_SIKASEP
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>'
                )
                ->addColumn(
                    'foto_sikasep',
                    fn($row) =>
                    $row->persyaratan?->FOTO_SIKASEP
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>'
                )
                ->addColumn(
                    'trilogi',
                    fn($row) =>
                    $row->persyaratan?->TRILOGI
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>'
                )

                ->addColumn('action', function ($row) use ($akadDetails) {

                    if (! $akadDetails->has($row->id)) {
                        return '';
                    }

                    $detail = $akadDetails[$row->id];

                    if ($detail->status == 1) {
                        $showUrl = route('akad.seleksi-customer.get-hadir', $detail->id);

                        return '
                        <button class="btn bg-indigo btn-sm hadir-button"
                            type="button"
                            data-id="' . e($detail->id) . '"
                            data-url="' . e($showUrl) . '">
                            <i class="fas fa-user-check mr-1"></i>
                            Hadir
                        </button>
                    ';
                    }

                    if ($detail->status == 2) {
                        return '
                        <button class="btn btn-success btn-sm" type="button" disabled>
                            <i class="fas fa-check-circle mr-1"></i>
                            Hadir
                        </button>
                    ';
                    }

                    return '';
                })

                ->rawColumns(['checkbox', 'nama_lengkap', 'lokasi_rumah', 'iph', 'shgb', 'ssp', 'bphtb', 'sikumbang', 'daftar_sikasep', 'foto_sikasep', 'trilogi', 'action'])
                ->make(true);
        }

        return view('admin.transaksi.akad.detail', compact('akad'));
    }

    public function seleksiCustomer($id, Request $request)
    {
        DB::beginTransaction();

        try {

            $customers = $request->input('customer', []);

            foreach ($customers as $idCustomer => $key) {

                $detail = AkadDetail::where('id_akad', $id)
                    ->where('id_customer', $idCustomer)
                    ->first();

                if ($key == 1) {

                    if (! $detail) {
                        $persyaratan    = PersyaratanLegal::where('id_customer', $idCustomer)->first();
                        $persyaratanId  = $persyaratan?->id;
                        $detail         = AkadDetail::create([
                            'id_akad'        => $id,
                            'id_customer'    => $idCustomer,
                            'id_persyaratan' => $persyaratanId,
                            'status'         => 1,
                            'keterangan'     => '',
                            'jenis_akad'     => '',
                        ]);

                        $this->logCreate('Detail Akad', $detail->id);
                    }
                } else {

                    if ($detail && $detail->status == 1) {
                        $detail->delete();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Error seleksi customer', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function showHadir($id)
    {
        $list = AkadDetail::with('customer', 'customer.lokasi', 'customer.kavling')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ]);
    }

    public function updateHadir($id, Request $request)
    {
        $request->validate([
            'jenis_akad' => 'required',
        ], [
            'jenis_akad.required' => 'Jenis akad wajib diisi.',
        ]);

        DB::beginTransaction();
        try {

            $detail = AkadDetail::findOrFail($id);

            $detail->update([
                'status'     => 2,
                'jenis_akad' => $request->jenis_akad,
                'keterangan' => $request->keterangan ?? '',
            ]);

            AkadDetail::where('id_customer', $detail->id_customer)
                ->where('id', '!=', $detail->id)
                ->delete();

            Customer::where('id', $detail->id_customer)
                ->update(['id_status_progres' => 3]);

            $this->logEdit('Detail Akad', $detail->id);
            DB::commit();

            return response()->json([
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error update hadir akad: ' . $e->getMessage());

            return response()->json([
                'success' => false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl_akad'   => 'required',
            'keterangan' => 'required',
        ], [
            'tgl_akad.required'   => 'Kolom tanggal wajib diisi.',
            'keterangan.required' => 'keterangan wajib diisi.',
        ]);

        $akad = Akad::create([
            'tgl_akad'   => $request->tgl_akad,
            'keterangan' => $request->keterangan ?? '',
        ]);

        $this->logCreate('Akad', $akad->id);

        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $list = Akad::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ]);
    }

    public function destroy($id)
    {
        $data = Akad::findOrFail($id);

        $this->logDelete('Akad', $data->id);
        $data->delete();

        return response()->json(['status' => 'success']);
    }

    public function update(Request $request, $id)
    {
        $data = Akad::findOrFail($id);

        $request->validate([
            'tgl_akad'   => 'required|date',
            'keterangan' => 'required',
        ], [
            'keterangan.required' => 'keterangan wajib diisi.',
            'tgl_akad.required'   => 'Kolom tanggal wajib diisi.',
            'tgl_akad.date'       => 'Format tanggal tidak valid.',
        ]);

        $db = [
            'tgl_akad'   => $request->tgl_akad,
            'keterangan' => $request->keterangan,
        ];

        $this->logEdit('Akad', $data->id);
        $data->update($db);

        return response()->json(['status' => 'success']);
    }

    public function downloadWord($id)
    {
        $akad     = Akad::findOrFail($id);
        $customer = Customer::findOrFail($akad->id_customer);
        $kavling  = KavlingPeta::findOrFail($akad->id_kavling);
        $lokasi   = $kavling->lokasi;

        $templatePath = public_path('templates/template.docx');
        $template     = new TemplateProcessor($templatePath);

        $tanggal      = Carbon::parse($akad->tgl_akad);
        $hari         = $tanggal->locale('id')->isoFormat('dddd');
        $tanggalBulan = $tanggal->locale('id')->isoFormat('D MMMM');
        $tahun        = $tanggal->year;

        $hrgJual = $kavling->hrg_jual ?? 0;
        $hrgText = ucwords($this->terbilang($hrgJual)) . ' rupiah';

        $template->setValue('nama_lengkap', $customer->nama_lengkap);
        $template->setValue('no_ktp', $customer->no_ktp);
        $template->setValue('no_wa', $customer->no_wa);
        $template->setValue('alamat', $customer->alamat);
        $template->setValue('hari', $hari);
        $template->setValue('tanggal_bulan', $tanggalBulan);
        $template->setValue('tahun', $tahun);
        $template->setValue('lebar', $kavling->lebar_depan . ' x ' . $kavling->lebar_belakang);
        $template->setValue('panjang', $kavling->panjang_kanan . ' x ' . $kavling->panjang_kiri);
        $template->setValue('luas_tanah', $kavling->luas_tanah);
        $template->setValue('no_blok', $kavling->kode_kavling);
        $template->setValue('hrg_jual', number_format($hrgJual, 0, ',', '.'));
        $template->setValue('hrg_text', $hrgText);

        $fileName = 'akad_kavling_' . $customer->nama_lengkap . '.docx';
        $filePath = storage_path('app/public/' . $fileName);
        $template->saveAs($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    private function terbilang($angka)
    {
        $angka    = (int) $angka;
        $bilangan = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        if ($angka < 12) {
            return $bilangan[$angka];
        }

        if ($angka < 20) {
            return $this->terbilang($angka - 10) . " belas";
        }

        if ($angka < 100) {
            return $this->terbilang($angka / 10) . " puluh " . $this->terbilang($angka % 10);
        }

        if ($angka < 200) {
            return "seratus " . $this->terbilang($angka - 100);
        }

        if ($angka < 1000) {
            return $this->terbilang($angka / 100) . " ratus " . $this->terbilang($angka % 100);
        }

        if ($angka < 2000) {
            return "seribu " . $this->terbilang($angka - 1000);
        }

        if ($angka < 1000000) {
            return $this->terbilang($angka / 1000) . " ribu " . $this->terbilang($angka % 1000);
        }

        if ($angka < 1000000000) {
            return $this->terbilang($angka / 1000000) . " juta " . $this->terbilang($angka % 1000000);
        }

        return "terlalu besar";
    }

    public function cetakDetailPDF($id)
    {
        $akad       = Akad::findOrFail($id);
        $detailList = AkadDetail::with(['customer.kavling'])
            ->where('id_akad', $id) // Perbaikan: gunakan id_akad bukan id
            ->get();

        Carbon::setLocale('id');
        $tglFormatted = Carbon::parse($akad->tgl_akad)->translatedFormat('d F Y');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetTitle('Daftar Peserta Akad - ' . $tglFormatted);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(10, 10, 10, true);
        $pdf->AddPage();

        // Header dengan styling lebih baik
        $html = '<style>
        h3 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 16px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 8px;
        }
        td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        .status-hadir {
            color: green;
            font-weight: bold;
        }
        .status-belum {
            color: red;
            font-weight: bold;
        }
    </style>';

        $html .= '<h3>DAFTAR PESERTA AKAD<br/>Tanggal: ' . $tglFormatted . '</h3>';

        $html .= '<table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th width="8%">No</th>
                <th width="30%">Nama Lengkap</th>
                <th width="20%">Kode Kavling</th>
                <th width="17%">Status Kehadiran</th>
                <th width="25%">Keterangan</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($detailList as $index => $item) {
            $nama        = $item->customer->nama_lengkap ?? '-';
            $kodeKavling = $item->customer->kavling->kode_kavling ?? '-';
            $status      = $item->status;
            $keterangan  = $item->keterangan ?? '-';

            $statusInt   = (int) $status;
            $statusText  = match ($statusInt) {
                1 => 'Belum Hadir',
                2 => 'Hadir',
                default => 'Belum Hadir'
            };
            $statusClass = $statusInt == 2 ? 'status-hadir' : 'status-belum';

            $html .= '<tr>
            <td width="8%" align="center">' . ($index + 1) . '</td>
            <td width="30%">' . $nama . '</td>
            <td width="20%" align="center">' . $kodeKavling . '</td>
            <td width="17%" align="center" class="' . $statusClass . '">' . $statusText . '</td>
            <td width="25%">' . $keterangan . '</td>
        </tr>';
        }

        $html .= '</tbody></table>';

        // Ringkasan kehadiran
        $totalPeserta     = $detailList->count();
        $jumlahHadir      = $detailList->where('status', '2')->count();
        $jumlahBelumHadir = $totalPeserta - $jumlahHadir;

        $html .= '<br/><div style="margin-top: 20px;">
            <table border="1" cellpadding="6" cellspacing="0" style="width: 50%;">
                <tr>
                    <td width="60%" style="background-color: #f2f2f2;"><strong>Total Peserta</strong></td>
                    <td width="40%" align="center"><strong>' . $totalPeserta . '</strong></td>
                </tr>
                <tr>
                    <td style="background-color: #f2f2f2;"><strong>Hadir</strong></td>
                    <td align="center" style="color: green;"><strong>' . $jumlahHadir . '</strong></td>
                </tr>
                <tr>
                    <td style="background-color: #f2f2f2;"><strong>Belum Hadir</strong></td>
                    <td align="center" style="color: red;"><strong>' . $jumlahBelumHadir . '</strong></td>
                </tr>
            </table>
            </div>
        </div>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('detail-akad-' . now()->format('d-m-Y') . '.pdf', 'I');
    }

    public function cetakDetailExcel($id)
    {
        $akad       = Akad::findOrFail($id);
        $detailList = AkadDetail::with(['customer.kavling'])
            ->where('id_akad', $id) // Perbaikan: gunakan id_akad bukan id
            ->get();

        Carbon::setLocale('id');
        $tglFormatted = Carbon::parse($akad->tgl_akad)->translatedFormat('d F Y');

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Header Judul
        $sheet->setCellValue('A1', 'DAFTAR PESERTA AKAD');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Sub Judul - Tanggal
        $sheet->setCellValue('A2', 'Tanggal: ' . $tglFormatted);
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Header Tabel
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Nama Lengkap');
        $sheet->setCellValue('C4', 'Kode Kavling');
        $sheet->setCellValue('D4', 'Status Kehadiran');
        $sheet->setCellValue('E4', 'Keterangan');

        // Styling Header Tabel
        $headerStyle = [
            'font'      => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill'      => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders'   => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A4:E4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(25);

        // Data Rows
        $row              = 5;
        $jumlahHadir      = 0;
        $jumlahBelumHadir = 0;

        foreach ($detailList as $index => $item) {
            $status     = $item->status;
            $keterangan = $item->keterangan ?? '-';

            $statusInt  = (int) $status;
            $statusText = match ($statusInt) {
                1 => 'Belum Hadir',
                2 => 'Hadir',
                default => 'Belum Hadir'
            };

            if ($statusInt == 2) {
                $jumlahHadir++;
            } else {
                $jumlahBelumHadir++;
            }

            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $item->customer->nama_lengkap ?? '-');
            $sheet->setCellValue("C{$row}", $item->customer->kavling->kode_kavling ?? '-');
            $sheet->setCellValue("D{$row}", $statusText);
            $sheet->setCellValue("E{$row}", $keterangan);

            if ($statusInt == 2) {
                $sheet->getStyle("D{$row}")->getFont()->getColor()->setRGB('008000'); // Hijau
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
            } else {
                $sheet->getStyle("D{$row}")->getFont()->getColor()->setRGB('FF0000'); // Merah
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
            }

            // Border untuk setiap cell
            $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // Alignment
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Ringkasan Kehadiran
        $summaryRow   = $row + 2;
        $totalPeserta = $detailList->count();

        $sheet->setCellValue("A{$summaryRow}", 'RINGKASAN KEHADIRAN');
        $sheet->mergeCells("A{$summaryRow}:B{$summaryRow}");
        $sheet->getStyle("A{$summaryRow}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$summaryRow}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');

        $summaryRow++;
        $sheet->setCellValue("A{$summaryRow}", 'Total Peserta');
        $sheet->setCellValue("B{$summaryRow}", $totalPeserta);
        $sheet->getStyle("A{$summaryRow}:B{$summaryRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("A{$summaryRow}")->getFont()->setBold(true);

        $summaryRow++;
        $sheet->setCellValue("A{$summaryRow}", 'Hadir');
        $sheet->setCellValue("B{$summaryRow}", $jumlahHadir);
        $sheet->getStyle("B{$summaryRow}")->getFont()->getColor()->setRGB('008000');
        $sheet->getStyle("B{$summaryRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$summaryRow}:B{$summaryRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("A{$summaryRow}")->getFont()->setBold(true);

        $summaryRow++;
        $sheet->setCellValue("A{$summaryRow}", 'Belum Hadir');
        $sheet->setCellValue("B{$summaryRow}", $jumlahBelumHadir);
        $sheet->getStyle("B{$summaryRow}")->getFont()->getColor()->setRGB('FF0000');
        $sheet->getStyle("B{$summaryRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$summaryRow}:B{$summaryRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("A{$summaryRow}")->getFont()->setBold(true);

        // Auto Size Columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set minimum width untuk kolom keterangan
        $sheet->getColumnDimension('E')->setWidth(30);

        $filename = 'detail-akad-' . now()->format('d-m-Y') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
