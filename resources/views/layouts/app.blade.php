<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Booking')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $favicon = \App\Models\PengaturanMedia::where('jenis_data', 'fav icon')->where('stt_aktif', 1)->first();
        $faviconPath = $favicon && $favicon->nama_file ? asset('config_media/' . $favicon->nama_file) : asset('default/favicon.ico');
    @endphp

    <link rel="icon" href="{{ $faviconPath }}">
    <link rel="shortcut icon" href="{{ $faviconPath }}">

    <!-- PWA Progressive Web App: RK-BOS (Form Booking Online RK Group Property) -->
    <link rel="manifest" href="{{ url('/manifest-booking.json') }}">
    <meta name="theme-color" content="#001031">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RK-BOS">
    <link rel="apple-touch-icon" href="{{ url('/pwa-icon/192') }}">

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">

    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('templates/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('templates/plugins/toastr/toastr.min.css') }}">

    @stack('styles')
</head>

<body class="hold-transition sidebar-mini" style="background-color: #ebebeb;">
    <div class="wrapper">

        @yield('content')


    </div>
    <!-- AdminLTE JS -->
    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/adminlte.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('templates/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('templates/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('templates/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <!-- Toastr -->
    <script src="{{ asset('templates/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).on('input', '.format-number', function() {
            let input = $(this).val().replace(/[^\d]/g, '');
            let formatted = input.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            $(this).val(formatted);
        });
    </script>

    <!-- PWA Service Worker & Install Banner for RK-BOS -->
    <div id="pwa-booking-banner" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 99999; background: linear-gradient(135deg, #001031 0%, #0f172a 100%); border: 1.5px solid #f59e0b; border-radius: 16px; padding: 12px 18px; box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.65); max-width: 92%; width: 400px; color: #fff; align-items: center; justify-content: space-between; backdrop-filter: blur(10px); animation: pwaSlideUp 0.4s ease-out;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <img src="{{ url('/pwa-icon/192') }}" alt="RK-BOS" style="width: 44px; height: 44px; border-radius: 10px; border: 1px solid rgba(245,158,11,0.4); box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
            <div>
                <div style="font-weight: 800; font-size: 13.5px; color: #f8fafc; letter-spacing: -0.01em;">RK-BOS</div>
                <div style="font-size: 11px; color: #cbd5e1;">Form Booking Online RK Group Property</div>
            </div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button id="pwa-booking-install-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #001031; border: none; font-weight: 800; font-size: 12px; padding: 8px 14px; border-radius: 8px; cursor: pointer; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4); display: flex; align-items: center; gap: 5px;">
                <i class="fas fa-download"></i> Install
            </button>
            <button id="pwa-booking-close-btn" style="background: transparent; color: #94a3b8; border: none; font-size: 20px; line-height: 1; cursor: pointer; padding: 0 4px;" title="Tutup">&times;</button>
        </div>
    </div>
    <style>
        @keyframes pwaSlideUp {
            from { opacity: 0; transform: translate(-50%, 30px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }
    </style>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register("{{ url('/sw.js') }}", { scope: '/' })
                    .then(function(reg) {
                        console.log('PWA RK-BOS ServiceWorker registered with scope: ', reg.scope);
                    })
                    .catch(function(err) {
                        console.warn('PWA ServiceWorker registration failed: ', err);
                    });
            });
        }

        let deferredPromptBooking;
        const bookingBanner = document.getElementById('pwa-booking-banner');
        const bookingInstallBtn = document.getElementById('pwa-booking-install-btn');
        const bookingCloseBtn = document.getElementById('pwa-booking-close-btn');
        const headerInstallBtn = document.getElementById('btn-header-install-rkbos');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPromptBooking = e;
            if (bookingBanner && !sessionStorage.getItem('pwa_booking_dismissed')) {
                bookingBanner.style.display = 'flex';
            }
            if (headerInstallBtn) {
                headerInstallBtn.style.display = 'inline-flex';
            }
        });

        function triggerInstallBooking() {
            if (deferredPromptBooking) {
                deferredPromptBooking.prompt();
                deferredPromptBooking.userChoice.then((choiceResult) => {
                    console.log('User choice:', choiceResult.outcome);
                    deferredPromptBooking = null;
                    if (bookingBanner) bookingBanner.style.display = 'none';
                    if (headerInstallBtn) headerInstallBtn.style.display = 'none';
                });
            }
        }

        if (bookingInstallBtn) {
            bookingInstallBtn.addEventListener('click', triggerInstallBooking);
        }
        if (headerInstallBtn) {
            headerInstallBtn.addEventListener('click', triggerInstallBooking);
        }

        if (bookingCloseBtn) {
            bookingCloseBtn.addEventListener('click', () => {
                if (bookingBanner) bookingBanner.style.display = 'none';
                sessionStorage.setItem('pwa_booking_dismissed', 'true');
            });
        }

        window.addEventListener('appinstalled', () => {
            if (bookingBanner) bookingBanner.style.display = 'none';
            if (headerInstallBtn) headerInstallBtn.style.display = 'none';
            console.log('RK-BOS PWA successfully installed!');
        });
    </script>

    @stack('scripts')
</body>

</html>
