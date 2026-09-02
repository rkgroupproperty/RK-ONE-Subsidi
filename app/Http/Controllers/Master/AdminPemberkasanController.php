<?php
namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\AdminPemberkasan;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class AdminPemberkasanController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();
        if ($request->ajax()) {

            $data = AdminPemberkasan::orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    return $row->status == 1
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-danger">Tidak Aktif</span>';
                })
                ->addColumn('action', function ($row) use ($permissions): string {
                    $editUrl   = route('admin-pemberkasan.edit', $row->id);
                    $deleteUrl = route('admin-pemberkasan.destroy', $row->id);
                    $btn       = '<div class="text-center">';

                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-sm edit-button" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '">Edit</button>';
                    }

                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="delete-button btn btn-danger btn-sm">
                            Hapus
                        </button>
                     </form>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('admin.master.admin_pemberkasan.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $rules = [
            'nama_lengkap' => 'required',
        ];

        $messages = [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {

            $filename = '';
            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $ext  = $foto->getClientOriginalExtension();

                $filename = Str::random(25) . '.' . $ext;
                $foto->move(public_path('assets/admin_pemberkasan/'), $filename);
            }

            AdminPemberkasan::create([
                'nama_lengkap' => $request->nama_lengkap,
                'no_telp'      => $request->no_telp ?? '',
                'foto'         => $filename,
                'status'       => $request->status ?? 1,
            ]);

            $this->logCreate('Admin Pemberkasan', 0);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $data = AdminPemberkasan::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = AdminPemberkasan::findOrFail($id);

        $rules = [
            'nama_lengkap' => 'required',
        ];

        $messages = [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {

            $db = [
                'nama_lengkap' => $request->nama_lengkap,
                'no_telp'      => $request->no_telp ?? '',
                'status'       => $request->status ?? 1,
            ];

            if ($request->hasFile('foto')) {
                if (! empty($data->foto) && file_exists(public_path('assets/admin_pemberkasan/' . $data->foto))) {
                    unlink(public_path('assets/admin_pemberkasan/' . $data->foto));
                }

                $foto     = $request->file('foto');
                $ext      = $foto->getClientOriginalExtension();
                $filename = Str::random(25) . '.' . $ext;
                $foto->move(public_path('assets/admin_pemberkasan/'), $filename);
                $db['foto'] = $filename;
            }

            $data->update($db);
            $this->logEdit('Admin Pemberkasan', $data->id);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $data = AdminPemberkasan::findOrFail($id);

            if (! empty($data->foto) && file_exists(public_path('assets/admin_pemberkasan/' . $data->foto))) {
                unlink(public_path('assets/admin_pemberkasan/' . $data->foto));
            }

            $this->logDelete('Admin Pemberkasan', $data->id);
            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }
}
