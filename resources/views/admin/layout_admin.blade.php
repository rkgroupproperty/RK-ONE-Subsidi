<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $konfigurasi = \App\Models\PengaturanProfil::first();
    @endphp

    <title>{{ $konfigurasi->nama_perusahaan ?? 'Template Aplikasi' }}</title>


    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- PWA Primary Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RK Property">
    <link rel="apple-touch-icon" href="{{ url('pwa-icon/192') }}">
    @php
        $favicon = \App\Models\PengaturanMedia::where('jenis_data', 'fav icon')->where('stt_aktif', 1)->first();
        $faviconPath = $favicon && $favicon->nama_file ? asset('config_media/' . $favicon->nama_file) : asset('default/favicon.ico');
    @endphp

    <link rel="icon" href="{{ $faviconPath }}">
    <link rel="shortcut icon" href="{{ $faviconPath }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/css/fontawesome-iconpicker.min.css" />
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
        href="{{ asset('templates/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <!-- Theme style: AdminLTE CDN fallback & local asset -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="{{ asset('templates/dist/css/adminlte.min.css') }}">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@1.13.1/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="{{ asset('templates/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="{{ asset('templates/plugins/daterangepicker/daterangepicker.css') }}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('templates/plugins/summernote/summernote-bs4.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('templates/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('templates/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('templates/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('templates/plugins/toastr/toastr.min.css') }}">

    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('templates/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

    @stack('css')

    <style>
        .modal-body {
            max-height: calc(100vh - 210px);
            overflow-y: auto;
        }

        /* ===== MODERN ULTRA-PREMIUM SIDEBAR STYLING ===== */
        .main-sidebar {
            background: linear-gradient(180deg, #090d16 0%, #0f172a 45%, #090d16 100%) !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 4px 0 28px rgba(0, 0, 0, 0.35) !important;
            transition: all 0.3s ease-in-out;
        }

        .modern-brand-header {
            background: rgba(255, 255, 255, 0.02) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 16px 14px !important;
            transition: all 0.3s ease;
        }

        /* Styling rapi saat sidebar dalam mode Collapse (sidebar-mini) */
        body.sidebar-collapse .modern-brand-header {
            padding: 12px 4px !important;
            text-align: center !important;
        }

        body.sidebar-collapse .modern-brand-header a {
            width: 100% !important;
            align-items: center !important;
            justify-content: center !important;
        }

        body.sidebar-collapse .brand-company-name,
        body.sidebar-collapse .portal-badge-pill {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        body.sidebar-collapse .brand-logo-box {
            max-width: 40px !important;
            max-height: 40px !important;
            margin-bottom: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        body.sidebar-collapse .brand-img {
            max-height: 36px !important;
            max-width: 36px !important;
        }

        body.sidebar-collapse .user-panel-custom {
            padding: 8px 4px !important;
            justify-content: center !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        body.sidebar-collapse .user-avatar-custom {
            margin-right: 0 !important;
        }

        body.sidebar-collapse .user-info-custom {
            display: none !important;
        }

        /* Menu Panduan Aplikasi di-hidden sesuai permintaan */
        .sidebar-fixed-bottom,
        body.sidebar-collapse .sidebar-fixed-bottom {
            display: none !important;
        }

        .brand-logo-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            max-width: 180px;
            max-height: 50px;
            overflow: hidden;
            border-radius: 8px;
        }

        .brand-img {
            max-height: 46px;
            max-width: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.4));
        }

        .brand-fallback-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .brand-company-name {
            font-size: 13px;
            letter-spacing: 0.05em;
            color: #f8fafc;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .portal-badge-pill {
            font-size: 10px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.04em;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .portal-subsidi-badge {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.3));
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
        }

        .portal-komersil-badge {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(2, 132, 199, 0.3));
            border: 1px solid rgba(14, 165, 233, 0.4);
            color: #38bdf8;
        }

        /* User mini panel */
        .user-panel-custom {
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            backdrop-filter: blur(8px);
        }

        .user-avatar-custom {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(168, 85, 247, 0.25));
            border: 1px solid rgba(129, 140, 248, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a5b4fc;
            font-size: 16px;
            flex-shrink: 0;
        }

        .status-indicator-dot {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #10b981;
            border: 2px solid #0f172a;
        }

        .user-name-text {
            font-size: 13px;
            line-height: 1.2;
        }

        .user-role-badge {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
            display: flex;
            align-items: center;
        }

        /* Modern Nav Links */
        .sidebar {
            position: relative;
            padding-bottom: 75px;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .sidebar .nav-sidebar {
            padding-bottom: 75px;
        }

        .modern-nav-link {
            border-radius: 10px !important;
            margin: 3px 6px !important;
            padding: 9px 12px !important;
            color: #94a3b8 !important;
            font-size: 13.5px;
            font-weight: 500;
            display: flex !important;
            align-items: center !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: 1px solid transparent;
        }

        .modern-nav-link:hover {
            background: rgba(255, 255, 255, 0.06) !important;
            color: #f8fafc !important;
            transform: translateX(3px);
            border-color: rgba(255, 255, 255, 0.05);
        }

        .modern-nav-link.active-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        .modern-nav-link.active-parent {
            background: rgba(99, 102, 241, 0.15) !important;
            color: #c7d2fe !important;
            border: 1px solid rgba(99, 102, 241, 0.3);
            font-weight: 600;
        }

        .nav-icon-box {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 11px;
            font-size: 13px;
            color: #818cf8;
            transition: all 0.2s ease;
        }

        .modern-nav-link:hover .nav-icon-box {
            background: rgba(255, 255, 255, 0.12);
            color: #c7d2fe;
            transform: scale(1.05);
        }

        .modern-nav-link.active-gradient .nav-icon-box {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        .menu-title-text {
            margin: 0 !important;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .angle-arrow {
            font-size: 11px;
            transition: transform 0.25s ease;
            opacity: 0.7;
        }

        /* Treeview Submenu */
        .modern-treeview {
            margin-left: 20px !important;
            padding-left: 8px !important;
            border-left: 2px solid rgba(99, 102, 241, 0.25) !important;
            margin-top: 4px;
            margin-bottom: 6px;
        }

        .modern-sublink {
            border-radius: 8px !important;
            padding: 7px 12px !important;
            margin: 2px 4px !important;
            color: #94a3b8 !important;
            font-size: 13px;
            font-weight: 500;
            display: flex !important;
            align-items: center !important;
            transition: all 0.2s ease !important;
        }

        .modern-sublink:hover {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #f1f5f9 !important;
            transform: translateX(3px);
        }

        .modern-sublink.active-sublink {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(124, 58, 237, 0.25)) !important;
            color: #a5b4fc !important;
            font-weight: 600;
            border-left: 3px solid #6366f1;
        }

        .sublink-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            margin-right: 10px;
            transition: all 0.2s ease;
        }

        .modern-sublink:hover .sublink-dot {
            background: #818cf8;
            box-shadow: 0 0 6px rgba(129, 140, 248, 0.8);
        }

        .sublink-dot-active {
            background: #6366f1 !important;
            box-shadow: 0 0 8px #6366f1 !important;
            transform: scale(1.3);
        }

        .sublink-title {
            margin: 0 !important;
        }

        /* Fixed Bottom */
        .sidebar-fixed-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 250px;
            background: rgba(9, 13, 22, 0.95) !important;
            backdrop-filter: blur(12px) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 8px 12px;
            z-index: 1030;
        }

        .modern-panduan-link {
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #94a3b8 !important;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }

        .modern-panduan-link:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(124, 58, 237, 0.2));
            border-color: rgba(99, 102, 241, 0.4);
            color: #e2e8f0 !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .theme-switch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            margin-right: 14px;
            user-select: none;
        }

        .theme-switch input {
            display: none;
        }

        .theme-switch-slider {
            position: relative;
            width: 48px;
            height: 24px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .35);
            cursor: pointer;
            transition: .2s ease;
        }

        .theme-switch-slider::before {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff;
            transition: .2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .25);
        }

        .theme-switch input:checked + .theme-switch-slider {
            background: rgba(17, 24, 39, .85);
        }

        .theme-switch input:checked + .theme-switch-slider::before {
            transform: translateX(24px);
        }

        body.dark-mode {
            background-color: #111827;
            color: #e5e7eb;
        }

        body.dark-mode .content-wrapper,
        body.dark-mode .main-footer,
        body.dark-mode .modal-content {
            background-color: #111827 !important;
            color: #e5e7eb;
        }

        body.dark-mode .card,
        body.dark-mode .card-body,
        body.dark-mode .card-footer,
        body.dark-mode .modal-body,
        body.dark-mode .modal-footer,
        body.dark-mode .dropdown-menu {
            background-color: #1f2937 !important;
            color: #e5e7eb;
            border-color: #374151;
        }

        body.dark-mode .table,
        body.dark-mode .table td,
        body.dark-mode .table th,
        body.dark-mode label,
        body.dark-mode .text-dark,
        body.dark-mode .dropdown-item {
            color: #e5e7eb !important;
        }

        body.dark-mode .form-control,
        body.dark-mode .custom-select,
        body.dark-mode .select2-container--bootstrap4 .select2-selection {
            background-color: #111827 !important;
            color: #f9fafb !important;
            border-color: #4b5563 !important;
        }

        body.dark-mode .form-control[readonly],
        body.dark-mode textarea[readonly] {
            background-color: #1f2937 !important;
            color: #e5e7eb !important;
        }

        body.dark-mode .input-group-text,
        body.dark-mode .page-link {
            background-color: #374151 !important;
            color: #e5e7eb !important;
            border-color: #4b5563 !important;
        }

        body.dark-mode .table-bordered,
        body.dark-mode .table-bordered td,
        body.dark-mode .table-bordered th {
            border-color: #374151 !important;
        }

        body.dark-mode .dropdown-item:hover {
            background-color: #374151;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <script>
        if (localStorage.getItem('adminDarkMode') === '1') {
            document.body.classList.add('dark-mode');
        }
    </script>


    <div class="wrapper">

        <!-- Preloader -->
        {{-- <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="{{ asset('templates/img/LOGO_ICON_BEASISWA.png') }}" alt="AdminLTELogo"
                height="60" width="60">
        </div> --}}


        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-light bg-indigo">
            <ul class="navbar-nav d-flex align-items-center" style="margin-left: 5px;">
                <li class="nav-item">
                    <a class="nav-link text-white" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <!-- Portal Indicator (Static Display, Hidden Switcher) -->
                <li class="nav-item ml-2">
                    @if(($current_app_type ?? session('app_type', 'subsidi')) === 'komersil')
                        <div class="portal-badge-indicator font-weight-bold px-3 py-1 shadow-sm d-flex align-items-center text-white" style="border-radius: 20px; background: linear-gradient(135deg, #0284c7, #0369a1); border: 1px solid rgba(255,255,255,0.4); font-size: 11.5px; letter-spacing: 0.5px; cursor: default;">
                            <i class="fas fa-city mr-2 text-warning"></i> PORTAL KOMERSIL
                        </div>
                    @else
                        <div class="portal-badge-indicator font-weight-bold px-3 py-1 shadow-sm d-flex align-items-center text-white" style="border-radius: 20px; background: linear-gradient(135deg, #10b981, #059669); border: 1px solid rgba(255,255,255,0.4); font-size: 11.5px; letter-spacing: 0.5px; cursor: default;">
                            <i class="fas fa-home mr-2 text-warning"></i> PORTAL SUBSIDI
                        </div>
                    @endif
                </li>
            </ul>

            <ul class="navbar-nav ml-auto mr-3 d-flex align-items-center">
                <li class="nav-item">
                    <label class="theme-switch mb-0" title="Dark mode">
                        <i class="fas fa-sun"></i>
                        <input type="checkbox" id="darkModeSwitch">
                        <span class="theme-switch-slider"></span>
                        <i class="fas fa-moon"></i>
                    </label>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#"
                        id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <i class="fas fa-user-circle fa-2x mr-2 text-white"></i>
                        <span class="text-white">{{ Auth::user()->username }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="#" onclick="logoutConfirm(event)">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>

        </nav>
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo Header -->
            @php
                $logo = \App\Models\PengaturanMedia::where('jenis_data', 'Logo Aplikasi')->where('stt_aktif', 1)->first();
                $konfigurasi = \App\Models\PengaturanProfil::first();
                $activeAppType = $current_app_type ?? session('app_type', 'subsidi');
            @endphp

            <div class="brand-link modern-brand-header text-center py-3">
                <a href="{{ route('beranda.index') }}" class="d-inline-flex flex-column align-items-center text-decoration-none">
                    <div class="brand-logo-box mb-2">
                        @if($logo && !empty($logo->nama_file))
                            <img src="{{ asset('config_media/' . $logo->nama_file) }}" alt="Logo" class="brand-img"
                                onerror="this.onerror=null; this.src='https://subsidi.rkgroupproperti.com/config_media/{{ $logo->nama_file }}';">
                        @else
                            <div class="brand-fallback-icon">
                                <i class="fas fa-building"></i>
                            </div>
                        @endif
                    </div>
                    <span class="brand-company-name font-weight-bold text-white tracking-wide">
                        {{ $konfigurasi->nama_perusahaan ?? 'RK GROUP PROPERTY' }}
                    </span>
                    <span class="portal-badge-pill mt-2 {{ $activeAppType === 'komersil' ? 'portal-komersil-badge' : 'portal-subsidi-badge' }}">
                        <i class="fas {{ $activeAppType === 'komersil' ? 'fa-city' : 'fa-home' }} mr-1"></i>
                        {{ $activeAppType === 'komersil' ? 'PORTAL KOMERSIL' : 'PORTAL SUBSIDI' }}
                    </span>
                </a>
            </div>

            <!-- Sidebar -->
            <div class="sidebar modern-sidebar-content px-2">
                <!-- User mini-widget -->
                @if(Auth::check())
                <div class="user-panel-custom mt-3 mb-2 d-flex align-items-center p-2">
                    <div class="user-avatar-custom mr-3">
                        <i class="fas fa-user-shield"></i>
                        <span class="status-indicator-dot" title="Online"></span>
                    </div>
                    <div class="user-info-custom text-truncate">
                        <div class="user-name-text text-white font-weight-bold text-truncate">{{ Auth::user()->surname ?? Auth::user()->username }}</div>
                        <div class="user-role-badge"><i class="fas fa-circle text-success mr-1" style="font-size: 7px;"></i> {{ Auth::user()->role->role ?? 'Administrator' }}</div>
                    </div>
                </div>
                @endif

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">

                        @php
                            use Illuminate\Support\Str;
                            use Illuminate\Support\Facades\Route as RouteFacade;
                            $menus = session('getmenus');
                            if (empty($menus) || (is_countable($menus) && count($menus) === 0)) {
                                if (\Illuminate\Support\Facades\Auth::check()) {
                                    $menus = \App\Models\Menu::where('id_parent', 0)
                                        ->where('stt_aktif', 1)
                                        ->with(['children' => function($q) {
                                            $q->where('stt_aktif', 1)->orderBy('urutan', 'asc');
                                        }])
                                        ->orderBy('urutan', 'asc')
                                        ->get();
                                    session(['getmenus' => $menus]);
                                } else {
                                    $menus = collect();
                                }
                            }
                            $currentRoute = request()->route();
                            $currentRouteName = $currentRoute ? (string) $currentRoute->getName() : '';
                            $currentPrefix = $currentRouteName !== '' ? Str::before($currentRouteName, '.') : '';
                        @endphp

                        @foreach ($menus as $menu)
                            @php
                                $children = ($menu->children ?? collect())->filter(fn ($child) => !empty($child->route_name) && RouteFacade::has($child->route_name));
                                $children = $children->map(function ($child) {
                                    if ($child->id == 95 || $child->route_name == 'proses-marketing.index') {
                                        $child->urutan = 2;
                                    } elseif ($child->id == 86 || $child->route_name == 'proses-admin.index') {
                                        $child->urutan = 3;
                                    }
                                    return $child;
                                })->sortBy('urutan')->values();
                                $hasMenuRoute = !empty($menu->route_name) && RouteFacade::has($menu->route_name);

                                if (! $hasMenuRoute && $children->isEmpty()) {
                                    continue;
                                }

                                $isActiveParent = false;
                                foreach ($children as $child) {
                                    $childPrefix = !empty($child->route_name) ? Str::before($child->route_name, '.') : '';
                                    if (
                                        !empty($child->route_name) && (
                                            request()->routeIs($child->route_name . '*') ||
                                            ($currentPrefix !== '' && $currentPrefix === $childPrefix)
                                        )
                                    ) {
                                        $isActiveParent = true;
                                        break;
                                    }
                                }
                            @endphp

                            @if ($children->isEmpty())
                                @php
                                    $menuPrefix = !empty($menu->route_name) ? Str::before($menu->route_name, '.') : '';
                                    $isActiveSingle =
                                        !empty($menu->route_name) && (
                                            request()->routeIs($menu->route_name . '*') ||
                                            ($currentPrefix !== '' && $currentPrefix === $menuPrefix)
                                        );
                                @endphp
                                <li class="nav-item">
                                    <a href="{{ $hasMenuRoute ? route($menu->route_name) : '#' }}"
                                        class="nav-link modern-nav-link {{ $isActiveSingle ? 'active-gradient' : '' }}">
                                        <div class="nav-icon-box">
                                            <i class="fas {{ $menu->icon ?? 'fa-circle' }}"></i>
                                        </div>
                                        <p class="menu-title-text">{{ $menu->title }}</p>
                                    </a>
                                </li>
                            @else
                                <li class="nav-item has-treeview {{ $isActiveParent ? 'menu-open' : '' }}">
                                    <a href="#" class="nav-link modern-nav-link {{ $isActiveParent ? 'active-parent' : '' }}">
                                        <div class="nav-icon-box">
                                            <i class="fas {{ $menu->icon ?? 'fa-folder' }}"></i>
                                        </div>
                                        <p class="menu-title-text">
                                            {{ $menu->title }}
                                            <i class="right fas fa-angle-left angle-arrow"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview modern-treeview">
                                        @foreach ($children as $child)
                                            @php
                                                $childPrefix = !empty($child->route_name) ? Str::before($child->route_name, '.') : '';
                                                $isActiveChild =
                                                    !empty($child->route_name) && (
                                                        request()->routeIs($child->route_name . '*') ||
                                                        ($currentPrefix !== '' && $currentPrefix === $childPrefix)
                                                    );
                                            @endphp
                                            <li class="nav-item">
                                                <a href="{{ RouteFacade::has($child->route_name) ? route($child->route_name) : '#' }}"
                                                    class="nav-link modern-sublink {{ $isActiveChild ? 'active-sublink' : '' }}">
                                                    <span class="sublink-dot {{ $isActiveChild ? 'sublink-dot-active' : '' }}"></span>
                                                    <p class="sublink-title">{{ ($child->id == 95 || $child->route_name == 'proses-marketing.index') ? 'Pemberkasan Marketing' : $child->title }}</p>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        @endforeach

                        <form id="logout-form" action="{{ route('admin.logout') }}" method="POST"
                            style="display: none;">
                        </form>
                    </ul>
                </nav>
                <!-- Fitur menu Panduan Aplikasi di-hidden sesuai permintaan -->
                {{-- <div class="sidebar-fixed-bottom" style="display: none !important;">
                    <a href="{{ route('panduan-aplikasi.index') }}" target="_blank" class="modern-panduan-link">
                        <i class="fas fa-book-reader mr-1 text-primary"></i>
                        <span>Panduan Aplikasi</span>
                    </a>
                </div> --}}

                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        @yield('content')

        <!-- /.content-wrapper -->

        <footer class="main-footer">
            <strong>Copyright &copy; 2026 di Kelola Tim Marcom RK GROUP Property</strong>
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        if (typeof jQuery === 'undefined') {
            document.write('<script src="{{ asset('templates/plugins/jquery/jquery.min.js') }}"><\/script>');
        }
    </script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('templates/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if (typeof ($.fn.modal) === 'undefined') {
            document.write('<script src="{{ asset('templates/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"><\/script>');
        }
    </script>
    <!-- Select2 -->
    <script src="{{ asset('templates/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- ChartJS -->
    <script src="{{ asset('templates/plugins/chart.js/Chart.min.js') }}"></script>
    <!-- Sparkline -->
    <script src="{{ asset('templates/plugins/sparklines/sparkline.js') }}"></script>
    <!-- JQVMap -->
    <script src="{{ asset('templates/plugins/jqvmap/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
    <!-- jQuery Knob Chart -->
    <script src="{{ asset('templates/plugins/jquery-knob/jquery.knob.min.js') }}"></script>
    <!-- daterangepicker -->
    <script src="{{ asset('templates/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="{{ asset('templates/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <!-- Summernote -->
    <script src="{{ asset('templates/plugins/summernote/summernote-bs4.min.js') }}"></script>
    <!-- overlayScrollbars -->
    <script src="{{ asset('templates/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('templates/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('templates/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('templates/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <!-- Page specific script -->
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script>
        if (typeof ($.fn.Layout) === 'undefined') {
            document.write('<script src="{{ asset('templates/dist/js/adminlte.js') }}"><\/script>');
        }
    </script>
    <!-- AdminLTE for demo purposes -->
    {{-- <script src="{{ asset('templates/dist/js/demo.js') }}"></script> --}}
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="{{ asset('templates/dist/js/pages/dashboard.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('templates/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('templates/plugins/toastr/toastr.min.js') }}"></script>
    <!-- bs-custom-file-input -->
    <script src="{{ asset('templates/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>

    <!-- Summernote -->
    <script src="{{ asset('templates/plugins/summernote/summernote-bs4.min.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/js/fontawesome-iconpicker.min.js">
    </script>

    <script>
        var audio = new Audio('{{ asset('audio/notification.ogg') }}');

        function applyDarkMode(enabled) {
            document.body.classList.toggle('dark-mode', enabled);
            $('#darkModeSwitch').prop('checked', enabled);
            localStorage.setItem('adminDarkMode', enabled ? '1' : '0');
        }

        applyDarkMode(localStorage.getItem('adminDarkMode') === '1');

        $(document).on('change', '#darkModeSwitch', function() {
            applyDarkMode(this.checked);
        });

        function formatNumber(value) {
            return value ? value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '';
        }

        function unformatNumber(value) {
            return value ? parseInt(value.replace(/\./g, '')) : 0;
        }

        $(document).on('input', '.format-number', function() {
            let input = $(this).val().replace(/[^\d]/g, '');
            let formatted = input.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            $(this).val(formatted);
        });

        function previewFile(inputId, previewId) {
            $('#' + inputId).on('change', function() {
                const file = this.files[0];
                const previewDiv = $('#' + previewId);

                if (!file) {
                    previewDiv.html('<span style="color:#6c757d;">Tidak ada berkas</span>');
                    return;
                }

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewDiv.html(`
                    <img src="${e.target.result}" style="max-width:100%; max-height:100%;">
                `);
                    };
                    reader.readAsDataURL(file);
                    return;
                }

                if (file.type === 'application/pdf') {
                    const pdfUrl = URL.createObjectURL(file);

                    previewDiv.html(`
                <div class="d-flex justify-content-center align-items-center h-100">
                    <a href="${pdfUrl}" target="_blank">
                        Lihat Berkas
                    </a>
                </div>
            `);
                    return;
                }

                previewDiv.html('<span style="color:#6c757d;">Tidak ada berkas</span>');
            });
        }

        function setPreview(imageName, folder, previewId) {
            const preview = $('#' + previewId);

            if (imageName) {
                let imageUrl = '/' + folder + '/' + imageName;
                preview.html(`<img src="${imageUrl}" style="max-height: 100%; max-width: 100%; object-fit: contain;" onerror="this.onerror=null; this.parentElement.innerHTML='<span style=\\'color:#dc3545; font-size:12px;\\'><i class=\\'fas fa-exclamation-triangle mr-1\\'></i> File belum ada</span>';">`);
            } else {
                preview.html('<span style="color: #6c757d;">Tidak ada berkas</span>');
            }
        }

        $(document).on('click', '.btn-lampiran', function() {
            let file = $(this).data('file');
            let ext = file.split('.').pop().toLowerCase();

            if (ext === 'pdf') {
                $('#modalLampiran .modal-dialog')
                    .removeClass('modal-sm')
                    .addClass('modal-lg');

                $('#modalLampiran .modal-body').html(`
                    <div class="text-center">
                        <iframe src="${file}" class="w-100" style="height:80vh;"></iframe>
                    </div>
                `);
            } else {
                $('#modalLampiran .modal-dialog')
                    .removeClass('modal-lg')
                    .addClass('modal-dialog-centered modal-dialog-scrollable');

                $('#modalLampiran .modal-body').html(`
                    <div class="d-flex justify-content-center">
                        <img src="${file}" class="img-fluid" style="max-height:90vh; object-fit:contain;">
                    </div>
                `);
            }

            $('#modalLampiran').modal('show');
        });

        function logoutConfirm(event) {
            event.preventDefault();

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Anda akan keluar dari sistem.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<span class="swal-btn-text">Ya, Logout!</span>',
                cancelButtonText: 'Batal',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary mx-2',
                    cancelButton: 'btn btn-secondary'
                },
                preConfirm: () => {
                    return new Promise((resolve) => {

                        const confirmBtn = Swal.getConfirmButton();
                        const btnText = confirmBtn.querySelector('.swal-btn-text');

                        btnText.innerHTML =
                            `<span class="spinner-border spinner-border-sm mx-2" role="status"></span> Logging out...`;
                        confirmBtn.disabled = true;

                        fetch('{{ route('refresh.csrf') }}')
                            .then(response => response.json())
                            .then(data => {
                                let form = document.getElementById('logout-form');
                                let tokenField = form.querySelector('input[name="_token"]');

                                if (!tokenField) {
                                    tokenField = document.createElement('input');
                                    tokenField.type = 'hidden';
                                    tokenField.name = '_token';
                                    form.appendChild(tokenField);
                                }

                                tokenField.value = data.token;

                                resolve();

                                form.submit();
                            })
                            .catch(() => {
                                confirmBtn.disabled = false;
                                btnText.innerHTML = `Ya, Logout!`;

                                Swal.showValidationMessage(
                                    `Gagal menghubungi server. Coba lagi.`
                                );
                            });
                    });
                }
            });
        }

        $(document).ready(function() {
            const successMsg = sessionStorage.getItem('success');
            if (successMsg) {
                audioSuccess.play();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: successMsg,
                    showConfirmButton: false,
                    timer: 2000
                })
                sessionStorage.removeItem('success');
            }
        });
    </script>

    @stack('scripts')
    <!-- PWA Install Prompt Banner (Auto-detect) -->
    <div id="pwa-install-banner" style="display:none; position:fixed; bottom:20px; left:20px; right:20px; max-width:420px; margin:0 auto; z-index:99999; background:linear-gradient(135deg, #0f172a, #1e1b4b); color:#fff; border-radius:16px; padding:14px 18px; box-shadow:0 12px 35px rgba(0,0,0,0.5), 0 0 0 1px rgba(99,102,241,0.3); backdrop-filter:blur(10px);">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <img src="{{ url('pwa-icon/192') }}" alt="App Icon" style="width:42px; height:42px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.3);">
                <div>
                    <div style="font-weight:700; font-size:13.5px; letter-spacing:0.3px;">Install Aplikasi RK Property</div>
                    <div style="font-size:11px; color:#cbd5e1;">Akses cepat, ringan & bisa di desktop/HP</div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" id="btn-pwa-install" class="btn btn-sm btn-primary font-weight-bold" style="background:linear-gradient(135deg, #4f46e5, #7c3aed); border:none; border-radius:8px; padding:6px 14px; font-size:12px; box-shadow:0 2px 10px rgba(99,102,241,0.4);">
                    <i class="fas fa-download mr-1"></i> Pasang
                </button>
                <button type="button" id="btn-pwa-close" style="background:transparent; border:none; color:#94a3b8; font-size:16px; cursor:pointer; padding:4px 6px;">
                    &times;
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Quick Refresh Button -->
    <div id="pwa-refresh-container" style="position:fixed; bottom:24px; right:24px; z-index:99990; display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
        <div id="pwa-refresh-tooltip" style="display:none; background:rgba(15, 23, 42, 0.92); color:#fff; font-size:11.5px; font-weight:600; padding:5px 12px; border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); white-space:nowrap; backdrop-filter:blur(6px);">
            Refresh Data Aplikasi
        </div>
        <button type="button" id="btn-pwa-refresh" title="Refresh Aplikasi" style="width:46px; height:46px; border-radius:50%; background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color:#fff; border:2px solid rgba(255,255,255,0.3); box-shadow:0 6px 20px rgba(99,102,241,0.45); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:17px; transition:all 0.25s cubic-bezier(0.4, 0, 0.2, 1); outline:none;">
            <i class="fas fa-sync-alt" id="pwa-refresh-icon"></i>
        </button>
    </div>

    <!-- PWA & Refresh Client Script -->
    <script>
        // 1. Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register("{{ asset('sw.js') }}")
                    .then(function(reg) {
                        console.log('PWA ServiceWorker registered with scope:', reg.scope);

                        // Listen for updates
                        reg.addEventListener('updatefound', function() {
                            const newWorker = reg.installing;
                            newWorker.addEventListener('statechange', function() {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    if (typeof toastr !== 'undefined') {
                                        toastr.info('Versi baru tersedia! Klik tombol refresh untuk memperbarui.', 'PWA Update', {
                                            timeOut: 5000,
                                            closeButton: true,
                                            positionClass: 'toast-bottom-right'
                                        });
                                    }
                                }
                            });
                        });
                    })
                    .catch(function(err) {
                        console.warn('PWA ServiceWorker registration failed:', err);
                    });
            });
        }

        // 2. Install Prompt Handling
        let deferredPrompt = null;
        const installBanner = document.getElementById('pwa-install-banner');
        const btnInstall = document.getElementById('btn-pwa-install');
        const btnClose = document.getElementById('btn-pwa-close');

        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            if (!localStorage.getItem('pwa_prompt_dismissed')) {
                if (installBanner) installBanner.style.display = 'block';
            }
        });

        if (btnInstall) {
            btnInstall.addEventListener('click', async function() {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log('User response to install prompt:', outcome);
                    deferredPrompt = null;
                    if (installBanner) installBanner.style.display = 'none';
                }
            });
        }

        if (btnClose) {
            btnClose.addEventListener('click', function() {
                if (installBanner) installBanner.style.display = 'none';
                localStorage.setItem('pwa_prompt_dismissed', '1');
            });
        }

        // 3. Quick Refresh Feature
        const btnRefresh = document.getElementById('btn-pwa-refresh');
        const refreshIcon = document.getElementById('pwa-refresh-icon');
        const refreshTooltip = document.getElementById('pwa-refresh-tooltip');

        if (btnRefresh) {
            btnRefresh.addEventListener('mouseenter', function() {
                if (refreshTooltip) refreshTooltip.style.display = 'block';
                btnRefresh.style.transform = 'scale(1.08)';
            });
            btnRefresh.addEventListener('mouseleave', function() {
                if (refreshTooltip) refreshTooltip.style.display = 'none';
                btnRefresh.style.transform = 'scale(1)';
            });

            btnRefresh.addEventListener('click', function() {
                if (refreshIcon) refreshIcon.classList.add('fa-spin');
                btnRefresh.disabled = true;

                if (typeof toastr !== 'undefined') {
                    toastr.info('Memuat ulang data aplikasi...', 'Refresh', {
                        timeOut: 1500,
                        positionClass: 'toast-bottom-right'
                    });
                }

                // Reload active DataTables if available
                if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
                    $('.dataTable').each(function() {
                        try {
                            $(this).DataTable().ajax.reload(null, false);
                        } catch(e) {}
                    });
                }

                if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
                }

                setTimeout(function() {
                    window.location.reload();
                }, 400);
            });
        }
    </script>
</body>

</html>

