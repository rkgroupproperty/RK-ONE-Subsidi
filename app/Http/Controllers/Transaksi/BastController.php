<?php
namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GenerateNumberController;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\BAST;
use App\Models\Customer;
use App\Models\ListrikAir;
use App\Models\LokasiKavling;
use App\Models\MarketingOffline;
use App\Services\DocumentGenerator;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BastController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();
        Carbon::setLocale('id');

        if ($request->ajax()) {
            $data = BAST::with(
                'customer.lokasi',
                'customer.kavling',
                'customer.marketing',
                'customer.progres'
            )
                ->whereHas('customer', function ($q) use ($request) {
                    $q->where('stt_arsip', 0)->where('id_status_progres', 5);
                    if ($request->filled('id_lokasi')) {
                        $q->where('id_lokasi', $request->id_lokasi);
                    }
                    if ($request->filled('id_marketing')) {
                        $q->where('id_marketing', $request->id_marketing);
                    }
                })
                ->orderByDesc('id');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('tanggal_bast', function ($row) {
                    return $row->tanggal_bast ? Carbon::parse($row->tanggal_bast)->translatedFormat('d F Y') : '-';
                })
                ->addColumn('nama_nasabah', function ($row) {
                    $nama = '<strong>' . e($row->customer->nama_lengkap ?? '-') . '</strong>';
                    $telp = ($row->customer && $row->customer->no_telp)
                        ? '<br><small class="text-muted"><i class="fas fa-phone-alt mr-1"></i>' . e($row->customer->no_telp) . '</small>'
                        : '';
                    return $nama . $telp;
                })
                ->addColumn('marketing', function ($row) {
                    $mkt = $row->customer->marketing->nama_marketing ?? 'Non Marketing';
                    return '<span class="badge bg-secondary">' . e($mkt) . '</span>';
                })
                ->addColumn('lokasi_rumah', function ($row) {
                    $lokasi  = '<strong>' . e($row->customer->lokasi->nama_kavling ?? '-') . '</strong>';
                    $kavling = ($row->customer->kavling && $row->customer->kavling->kode_kavling)
                        ? '<br><span class="badge bg-primary text-white">' . e($row->customer->kavling->kode_kavling) . '</span>'
                        : '';
                    return $lokasi . $kavling;
                })
                ->editColumn('no_bast', function ($row) {
                    return '<span class="badge badge-info">' . e($row->no_bast ?? '-') . '</span>';
                })
                ->addColumn('status_progres', function ($row) {
                    return '<span class="badge" style="background-color: #10b981; color: #ffffff;">BAST Selesai</span>';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $deleteUrl = route('bast.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';

                    if ($permissions['edit']) {
                        $documents = json_encode([
                            ['name' => 'Cetak BAST', 'route' => route('bast.cetak', $row->id_customer), 'checked' => true],
                        ]);
                        $btn .= '<button class="btn btn-dark btn-sm btn-cetak-item mr-2"
                                        data-id="' . $row->id . '"
                                        data-nama="' . e($row->customer->nama_lengkap ?? '') . '"
                                        data-documents=\'' . $documents . '\'><i class="fas fa-print mr-1"></i> Cetak</button>';
                    }

                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">'
                        . csrf_field()
                        . method_field('DELETE')
                            . '<button type="submit" class="delete-button btn btn-danger btn-sm"><i class="fas fa-trash-alt mr-1"></i> Hapus</button></form>';
                    }

                    return $btn . '</div>';
                })
                ->rawColumns(['lokasi_rumah', 'nama_nasabah', 'marketing', 'no_bast', 'status_progres', 'action'])
                ->make(true);
        }

        $customerList = Customer::with(['lokasi', 'kavling'])
            ->where('stt_arsip', 0)
            ->where('id_status_progres', 3)
            ->orderBy('nama_lengkap')
            ->get();
        $lokasiList = LokasiKavling::whereIn('stt_tampil', [1, 3])->orderBy('nama_kavling')->get();
        $marketingList = MarketingOffline::orderBy('nama_marketing')->get();

        return view('admin.transaksi.bast.index', compact('permissions', 'customerList', 'lokasiList', 'marketingList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_bast' => 'required',
            'id_customer'  => 'required',
        ], [
            'tanggal_bast.required' => 'Tanggal BAST wajib diisi.',
            'id_customer.required'  => 'Customer wajib dipilih.',
        ]);

        DB::beginTransaction();
        try {

            $customer = Customer::with('lokasi')
                ->lockForUpdate()
                ->findOrFail($request->id_customer);

            $generator = new GenerateNumberController();

            $noBast = $generator->generateNomorDokumen(
                $customer->lokasi,
                'no_bast',
                BAST::class
            );

            $bast = BAST::create([
                'tanggal_bast' => $request->tanggal_bast,
                'id_customer'  => $request->id_customer,
                'no_bast'      => $noBast,
            ]);

            $customer->update(['id_status_progres' => 5]);

            $this->logCreate('BAST', $bast->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'no_bast' => $noBast,
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function cetakBast($id_customer)
    {
        $customer = Customer::with(['kavlingPeta.lokasi', 'kavlingPeta.perusahaan', 'lokasiKavling'])
            ->findOrFail($id_customer);
        $bast       = BAST::where('id_customer', $id_customer)->firstOrFail();
        $listrikAir = ListrikAir::where('id_kavling', $customer->id_kavling)->first();

        $tanggal          = Carbon::parse($bast->tanggal_bast)->locale('id');
        $tanggalTerbilang = ucwords($this->numberToWords($tanggal->day));
        $hari             = $bast->hari_bast ?? $tanggal->translatedFormat('l');
        $bulan            = $tanggal->translatedFormat('F');
        $tahun            = $tanggal->format('Y');
        $tahun_terbilang  = ucwords($this->numberToWords($tahun));

        $extra = [
            'no_bast'         => $bast->no_bast,
            'hari_bast'       => $hari,
            'tanggal'         => $tanggalTerbilang,
            'bulan'           => $bulan,
            'tahun_terbilang' => $tahun_terbilang,
            'pekerjaan_cust'  => $customer->pekerjaan ?? '-',
            'norek_listrik'   => $listrikAir->norek_listrik ?? '-',
            'norek_air'       => $listrikAir->norek_air ?? '-',
            'tanggal_bast'    => $tanggal->translatedFormat('j F Y'),
        ];

        return DocumentGenerator::generateDocx(
            templatePath: public_path('templates/template_bast.docx'),
            customer: $customer,
            extraValues: $extra,
            outputFilename: 'BAST_' . Str::slug($customer->nama_lengkap) . '.docx'
        );
    }

    private function numberToWords($number)
    {
        $f = new \NumberFormatter("id", \NumberFormatter::SPELLOUT);
        return $f->format($number);
    }

    public function destroy($id)
    {
        $data = BAST::findOrFail($id);
        $customerId = $data->id_customer;

        $this->logDelete('BAST', $data->id);
        $data->delete();

        if ($customerId) {
            $prev = 3;
            if (!\App\Models\AkadDetail::where('id_customer', $customerId)->where('status', 2)->exists()) {
                if (\App\Models\PPJB::where('id_customer', $customerId)->exists()) {
                    $prev = 6;
                } elseif (\App\Models\WawancaraSp3k::whereHas('wawancara', fn($q) => $q->where('id_customer', $customerId))->where('status', 1)->exists()) {
                    $prev = 4;
                } else {
                    $prev = 10;
                }
            }
            Customer::where('id', $customerId)->update(['id_status_progres' => $prev]);
        }

        return response()->json(['status' => 'success']);
    }
}
