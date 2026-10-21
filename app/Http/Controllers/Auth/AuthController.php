<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HakAkses;
use App\Models\Menu;
use App\Models\User;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AuthController extends Controller
{
    use LogAktivitasTrait;

    public function getLogin(): View
    {
        return view('admin.login');
    }

    public function postLogin(Request $request)
    {
        $appType = $request->input('app_type', 'subsidi');
        if (!in_array($appType, ['subsidi', 'komersil'])) {
            $appType = 'subsidi';
        }

        // Terapkan koneksi database sesuai pilihan portal sebelum autentikasi
        $connection = ($appType === 'komersil') ? 'mysql_komersil' : 'mysql_subsidi';
        if (config("database.connections.{$connection}")) {
            Config::set('database.default', $connection);
            DB::setDefaultConnection($connection);
            DB::purge();
        }

        session(['app_type' => $appType]);

        $rules = [
            'username' => 'required|exists:users,username',
            'password' => 'required',
        ];

        $messages = [
            'username.required' => 'Username wajib diisi.',
            'username.exists' => 'Username belum terdaftar pada portal ' . ($appType === 'komersil' ? 'Komersil.' : 'Subsidi.'),
            'password.required' => 'Password wajib diisi.',
        ];

        $request->validate($rules, $messages);

        $user = User::where('username', $request->username)->first();

        if (! Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'password' => ['Password salah untuk portal ' . ($appType === 'komersil' ? 'Komersil.' : 'Subsidi.')],
                ],
            ], 422);
        }
        if ($user->status === 'BLOKIR') {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'username' => ['Akun anda telah diblokir.'],
                ],
            ], 422);
        }

        $hakAkses = HakAkses::where('id_user', $user->id)
            ->where('lihat', 1)
            ->get();

        $allowedMenuIds = $hakAkses->pluck('id_menu')->toArray();

        $getmenus = Menu::where('id_parent', 0)
            ->whereIn('id', $allowedMenuIds)
            ->orderBy('urutan')
            ->with(['children' => function ($query) use ($allowedMenuIds) {
                $query->whereIn('id', $allowedMenuIds);
            }])
            ->get()
            ->filter(function ($menu) {
                $menu->setRelation('children', $menu->children->filter(fn ($child) => Route::has($child->route_name))->values());

                return $menu->children->isNotEmpty() || Route::has($menu->route_name);
            })
            ->values();

        session([
            'getmenus' => $getmenus,
            'app_type' => $appType,
            'komersil_unlocked' => ($appType === 'komersil'),
        ]);

        $this->logLogin();

        return response()->json([
            'status' => 'success',
            'redirect_url' => route('beranda.index'),
        ]);
    }

    public function switchPortal(string $type): RedirectResponse
    {
        if (!in_array($type, ['subsidi', 'komersil'])) {
            $type = 'subsidi';
        }

        // Akses ke Komersil memerlukan otorisasi password agar divisi subsidi tidak sembarang masuk
        if ($type === 'komersil' && !session('komersil_unlocked')) {
            return redirect()->back()->with('error', 'Akses ke Portal Komersil memerlukan verifikasi password otorisasi!');
        }

        if ($type === 'subsidi') {
            session()->forget('komersil_unlocked');
        }

        session(['app_type' => $type]);

        // Arahkan koneksi database aktif
        $connection = ($type === 'komersil') ? 'mysql_komersil' : 'mysql_subsidi';
        if (config("database.connections.{$connection}")) {
            Config::set('database.default', $connection);
            DB::setDefaultConnection($connection);
            DB::purge();
        }

        if (Auth::check()) {
            $user = Auth::user();
            if ($user) {
                $allowedMenuIds = HakAkses::where('id_user', $user->id)
                    ->where('lihat', 1)
                    ->pluck('id_menu')
                    ->toArray();

                $getmenus = Menu::where('id_parent', 0)
                    ->whereIn('id', $allowedMenuIds)
                    ->orderBy('urutan')
                    ->with(['children' => function ($query) use ($allowedMenuIds) {
                        $query->whereIn('id', $allowedMenuIds);
                    }])
                    ->get()
                    ->filter(function ($menu) {
                        $menu->setRelation('children', $menu->children->filter(fn ($child) => Route::has($child->route_name))->values());

                        return $menu->children->isNotEmpty() || Route::has($menu->route_name);
                    })
                    ->values();

                session(['getmenus' => $getmenus]);
            }
        }

        return redirect()->route('beranda.index')->with('success', 'Berhasil beralih ke portal ' . ($type === 'komersil' ? 'Perumahan Komersil' : 'Perumahan Subsidi'));
    }

    public function verifySwitchPortal(Request $request)
    {
        $request->validate([
            'target_portal' => 'required|in:subsidi,komersil',
            'password' => 'required|string',
        ]);

        $target = $request->input('target_portal');
        $inputPassword = $request->input('password');

        if ($target === 'komersil') {
            $validMasterPasswords = array_filter([
                env('PORTAL_KOMERSIL_PASSWORD'),
                '@Subsidi1',
                '@Komersil1',
                'komersil2026',
            ]);

            $isValid = in_array($inputPassword, $validMasterPasswords);

            if (!$isValid && Auth::check() && Auth::user()) {
                if (\Illuminate\Support\Facades\Hash::check($inputPassword, Auth::user()->password)) {
                    $isValid = true;
                }
            }

            if (!$isValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Password otorisasi salah! Akses ke divisi Komersil ditolak.',
                ], 403);
            }

            session(['komersil_unlocked' => true]);
        } else {
            session()->forget('komersil_unlocked');
        }

        session(['app_type' => $target]);

        // Terapkan koneksi database aktif
        $connection = ($target === 'komersil') ? 'mysql_komersil' : 'mysql_subsidi';
        if (config("database.connections.{$connection}")) {
            Config::set('database.default', $connection);
            DB::setDefaultConnection($connection);
            DB::purge();
        }

        if (Auth::check()) {
            $user = Auth::user();
            if ($user) {
                $allowedMenuIds = HakAkses::where('id_user', $user->id)
                    ->where('lihat', 1)
                    ->pluck('id_menu')
                    ->toArray();

                $getmenus = Menu::where('id_parent', 0)
                    ->whereIn('id', $allowedMenuIds)
                    ->orderBy('urutan')
                    ->with(['children' => function ($query) use ($allowedMenuIds) {
                        $query->whereIn('id', $allowedMenuIds);
                    }])
                    ->get()
                    ->filter(function ($menu) {
                        $menu->setRelation('children', $menu->children->filter(fn ($child) => Route::has($child->route_name))->values());

                        return $menu->children->isNotEmpty() || Route::has($menu->route_name);
                    })
                    ->values();

                session(['getmenus' => $getmenus]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Otorisasi berhasil. Mengalihkan ke Portal ' . ucfirst($target) . '...',
            'redirect' => route('beranda.index'),
        ]);
    }

    public function logout(): RedirectResponse
    {
        $this->logLogout();
        Auth::guard('web')->logout();

        return redirect()->route('login')->with('success', 'Logout Berhasil.');
    }
}
