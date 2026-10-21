<?php
namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\PengaturanMedia;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PengaturanMediaController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();
        if ($request->ajax()) {

            $data = PengaturanMedia::where('stt_aktif', 1)
                ->orderBy('urutan')
                ->orderBy('id');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('preview', function ($row) {
                    if (empty($row->nama_file)) {
                        return '<span class="badge badge-light text-muted p-2 border" style="font-size:11px;"><i class="fas fa-image mr-1"></i> Belum ada gambar</span>';
                    }

                    $url = asset('config_media/' . $row->nama_file);

                    return '<a href="' . $url . '" target="_blank" title="Klik untuk lihat ukuran penuh">
                                <img src="' . $url . '" height="50" style="max-width:120px; object-fit:contain; border-radius:6px; border:1px solid #cbd5e1; background:#f8fafc; padding:2px;" 
                                     onerror="this.onerror=null; this.parentElement.innerHTML=\'<span class=\\\'badge badge-warning text-dark p-1\\\' style=\\\'font-size:10px;\\\'><i class=\\\'fas fa-exclamation-triangle mr-1\\\'></i> File tidak ditemukan</span>\';">
                            </a>';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl = route('pengaturan-media.edit', $row->id);
                    $btn     = '<div class="text-center">';

                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-sm edit-button" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '">Edit</button>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action', 'preview'])
                ->make(true);
        }

        return view('admin.pengaturan.pengaturan_media.index', compact('permissions'));
    }

    public function edit($id)
    {
        $data = PengaturanMedia::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = PengaturanMedia::findOrFail($id);

        $rules = [
            'nama_file' => 'required|mimes:jpg,jpeg,png,webp,ico|max:10240',
        ];

        $messages = [
            'nama_file.required' => 'File wajib diisi.',
            'nama_file.mimes'    => 'File harus berformat JPG, JPEG, PNG, WEBP, atau ICO.',
            'nama_file.max'      => 'Ukuran file maksimal 10 MB.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {

            if ($request->hasFile('nama_file')) {
                // Pastikan direktori config_media ada dan memiliki izin tulis
                $targetDir = public_path('config_media');
                if (!file_exists($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }

                // Hapus file lama jika ada
                if (! empty($data->nama_file)) {
                    $possibleOldPaths = [
                        public_path('config_media/' . $data->nama_file),
                        base_path('../config_media/' . $data->nama_file),
                        base_path('../public_html/config_media/' . $data->nama_file),
                    ];
                    foreach ($possibleOldPaths as $oldPath) {
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                }

                $nama_file = $request->file('nama_file');
                $ext       = strtolower($nama_file->getClientOriginalExtension());
                $filename  = Str::random(25) . '.' . $ext;
                
                // Simpan ke direktori utama
                $nama_file->move($targetDir, $filename);

                // Sinkronkan ke path publik cPanel jika terdeteksi
                $cpanelDirs = [
                    base_path('../config_media'),
                    base_path('../public_html/config_media'),
                ];
                foreach ($cpanelDirs as $altDir) {
                    if (file_exists($altDir) && is_dir($altDir)) {
                        @copy($targetDir . '/' . $filename, $altDir . '/' . $filename);
                    }
                }

                $data->update([
                    'nama_file' => $filename,
                ]);
            }

            $this->logEdit('Pengaturan Media', $data->id);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Media berhasil diperbarui!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

}
