<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @php
      $logo = \App\Models\PengaturanMedia::where('jenis_data', 'Logo Login')->first();
      $konfigurasi = \App\Models\PengaturanProfil::first();
      $icon = \App\Models\PengaturanMedia::where('jenis_data', 'fav icon')->where('stt_aktif', 1)->first();
      $faviconPath = $icon && $icon->nama_file ? asset('config_media/' . $icon->nama_file) : asset('default/favicon.ico');
      $bgLogin = \App\Models\PengaturanMedia::where('jenis_data', 'Background login')->first();
      $bgImage = $bgLogin && $bgLogin->nama_file ? asset('config_media/' . $bgLogin->nama_file) : asset('bg-login-perumahan.png');
      $initialAppType = session('app_type', 'subsidi');
  @endphp
  <link rel="icon" href="{{ $faviconPath }}" />
  <link rel="shortcut icon" href="{{ $faviconPath }}" />
  <title>{{ $konfigurasi->nama_perusahaan ?? 'RK Group Property' }} - Login Sistem</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('templates/plugins/toastr/toastr.min.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  
  <style>
    :root {
      --primary-dark: #111827;
      --primary-hover: #1f2937;
      --subsidi-green: #059669;
      --subsidi-green-light: #ecfdf5;
      --komersil-blue: #0284c7;
      --komersil-blue-light: #f0f9ff;
      --border-gray: #e5e7eb;
      --text-muted: #6b7280;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      min-height: 100vh;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: #23252a;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      color: #111827;
    }

    /* Container Card matching Image Reference */
    .login-container-card {
      width: 100%;
      max-width: 1040px;
      min-height: 600px;
      background: #ffffff;
      border-radius: 28px;
      overflow: hidden;
      display: flex;
      box-shadow: 0 25px 65px -15px rgba(0, 0, 0, 0.45);
      position: relative;
    }

    /* Left Visual Panel */
    .visual-panel {
      flex: 1 1 50%;
      position: relative;
      background-image: url('{{ $bgImage }}'), url('{{ asset('bg-login-perumahan.png') }}');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 40px;
      color: #ffffff;
      overflow: hidden;
    }

    .visual-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(17, 24, 39, 0.35) 0%, rgba(17, 24, 39, 0.05) 35%, rgba(17, 24, 39, 0.88) 100%);
      z-index: 1;
    }

    .visual-content-top {
      position: relative;
      z-index: 2;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .visual-brand-badge {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(8px);
      padding: 6px 14px 6px 8px;
      border-radius: 50px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    }

    .visual-brand-icon {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #111827;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 14px;
      overflow: hidden;
    }

    .visual-brand-icon img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .visual-brand-name {
      font-size: 14px;
      font-weight: 700;
      color: #111827;
      letter-spacing: -0.01em;
    }

    .visual-content-bottom {
      position: relative;
      z-index: 2;
      max-width: 420px;
    }

    .visual-headline {
      font-size: 36px;
      font-weight: 800;
      line-height: 1.15;
      margin-bottom: 12px;
      color: #ffffff;
      letter-spacing: -0.02em;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
    }

    .visual-subtext {
      font-size: 14px;
      line-height: 1.5;
      color: rgba(255, 255, 255, 0.88);
      margin-bottom: 24px;
      text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
    }

    .visual-indicators {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .dot-pill {
      width: 30px;
      height: 6px;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.3);
    }

    .dot-circle {
      width: 6px;
      height: 6px;
      background: rgba(255, 255, 255, 0.45);
      border-radius: 50%;
    }

    /* Right Form Panel */
    .form-panel {
      flex: 1 1 50%;
      background: #ffffff;
      padding: 44px 48px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .form-top-bar {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-bottom: 20px;
    }

    .badge-portal-status {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 18px;
      border-radius: 50px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.02em;
      border: 1px solid var(--border-gray);
      background: #f9fafb;
      color: #374151;
      cursor: pointer;
      transition: all 0.25s ease;
    }

    .badge-portal-status.status-subsidi {
      background: var(--subsidi-green-light);
      border-color: #a7f3d0;
      color: #065f46;
    }

    .badge-portal-status.status-komersil {
      background: var(--komersil-blue-light);
      border-color: #bae6fd;
      color: #0369a1;
    }

    .form-header {
      margin-bottom: 28px;
    }

    .form-title {
      font-size: 28px;
      font-weight: 800;
      color: #111827;
      letter-spacing: -0.02em;
      margin-bottom: 6px;
    }

    .form-subtitle {
      font-size: 14px;
      color: var(--text-muted);
    }

    /* Form Inputs */
    .form-group-clean {
      margin-bottom: 18px;
      text-align: left;
    }

    .form-label-clean {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 7px;
    }

    .form-control-clean {
      width: 100%;
      height: 46px;
      padding: 10px 14px;
      font-size: 14px;
      font-family: inherit;
      color: #111827;
      background: #ffffff;
      border: 1.5px solid var(--border-gray);
      border-radius: 10px;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control-clean:focus {
      border-color: var(--primary-dark);
      box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.08);
    }

    .input-pass-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-pass-wrapper .form-control-clean {
      padding-right: 42px;
    }

    .btn-toggle-pass {
      position: absolute;
      right: 12px;
      background: transparent;
      border: none;
      color: #9ca3af;
      cursor: pointer;
      font-size: 15px;
      padding: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s ease;
    }

    .btn-toggle-pass:hover {
      color: #374151;
    }

    .form-options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 22px;
      font-size: 13px;
    }

    .remember-wrap {
      display: flex;
      align-items: center;
      gap: 7px;
      cursor: pointer;
      user-select: none;
      color: #4b5563;
      font-weight: 500;
    }

    .remember-wrap input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--primary-dark);
      cursor: pointer;
    }

    .link-muted {
      color: var(--text-muted);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s ease;
    }

    .link-muted:hover {
      color: var(--primary-dark);
    }

    /* Submit Button */
    .btn-submit-clean {
      width: 100%;
      height: 48px;
      background: var(--primary-dark);
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
      box-shadow: 0 4px 12px rgba(17, 24, 39, 0.2);
    }

    .btn-submit-clean:hover {
      background: var(--primary-hover);
      box-shadow: 0 6px 18px rgba(17, 24, 39, 0.28);
    }

    .btn-submit-clean:active {
      transform: scale(0.99);
    }

    /* Divider */
    .divider-clean {
      display: flex;
      align-items: center;
      margin: 22px 0 16px;
      color: #9ca3af;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.08em;
    }

    .divider-clean::before,
    .divider-clean::after {
      content: "";
      flex: 1;
      height: 1px;
      background: var(--border-gray);
    }

    .divider-clean span {
      padding: 0 12px;
    }

    /* Portal Selector Row (Subsidi vs Komersil) */
    .portal-buttons-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 20px;
    }

    .btn-portal-item {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 12px;
      border: 1.5px solid var(--border-gray);
      background: #f9fafb;
      border-radius: 10px;
      font-size: 12px;
      font-weight: 700;
      color: #4b5563;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-portal-item:hover {
      background: #f3f4f6;
      border-color: #d1d5db;
    }

    .btn-portal-item.active-subsidi {
      background: var(--subsidi-green-light);
      border-color: var(--subsidi-green);
      color: #065f46;
      box-shadow: 0 2px 8px rgba(5, 150, 105, 0.15);
    }

    .btn-portal-item.active-komersil {
      background: var(--komersil-blue-light);
      border-color: var(--komersil-blue);
      color: #0369a1;
      box-shadow: 0 2px 8px rgba(2, 132, 199, 0.15);
    }

    .footer-clean {
      text-align: center;
      font-size: 13px;
      color: var(--text-muted);
      margin-top: 10px;
    }

    .footer-clean a {
      color: #111827;
      font-weight: 600;
      text-decoration: none;
    }

    .footer-clean a:hover {
      text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 860px) {
      .login-container-card {
        flex-direction: column;
        max-width: 480px;
        min-height: auto;
      }
      .visual-panel {
        min-height: 240px;
        padding: 28px;
      }
      .visual-headline {
        font-size: 26px;
      }
      .form-panel {
        padding: 32px 24px;
      }
    }
  </style>
</head>
<body>

  <div class="login-container-card">
    
    <!-- LEFT PANEL: Hero Image with Tagline -->
    <section class="visual-panel">
      <div class="visual-content-top">
        <div class="visual-brand-badge">
          <div class="visual-brand-icon">
            @if ($logo && $logo->nama_file)
              <img src="{{ asset('config_media/' . $logo->nama_file) }}" alt="Logo" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-home\'></i>';">
            @else
              <i class="fas fa-home"></i>
            @endif
          </div>
          <span class="visual-brand-name">{{ $konfigurasi->nama_perusahaan ?? 'RK Group Property' }}</span>
        </div>
      </div>

      <div class="visual-content-bottom">
        <h1 class="visual-headline">Find your sweet home</h1>
        <p class="visual-subtext">Schedule visit in just a few clicks & kelola portofolio perumahan subsidi dan komersil secara terintegrasi.</p>
        <div class="visual-indicators">
          <div class="dot-pill"></div>
          <div class="dot-circle"></div>
          <div class="dot-circle"></div>
        </div>
      </div>
    </section>

    <!-- RIGHT PANEL: Clean Form matching Image 1 -->
    <section class="form-panel">
      <div>
        <div class="form-top-bar">
          <button type="button" class="badge-portal-status {{ $initialAppType === 'komersil' ? 'status-komersil' : 'status-subsidi' }}" id="topPortalBadge" onclick="showPortalPrompt()">
            <i class="fas {{ $initialAppType === 'komersil' ? 'fa-city' : 'fa-home' }}" id="topPortalIcon"></i>
            <span id="topPortalText">{{ $initialAppType === 'komersil' ? 'Portal Komersil' : 'Portal Subsidi' }}</span>
          </button>
        </div>

        <div class="form-header">
          <h2 class="form-title">Welcome Back!</h2>
          <p class="form-subtitle">Sign in your account untuk mengakses sistem</p>
        </div>

        <form id="loginForm" method="POST" action="{{ route('login.post') }}" novalidate>
          @csrf
          <input type="hidden" name="app_type" id="inputAppType" value="{{ $initialAppType }}">

          <div class="form-group-clean">
            <label class="form-label-clean" for="username">Your Email / Username</label>
            <input type="text" id="username" name="username" class="form-control-clean" placeholder="info.admin@rkgroupproperti.com" required autofocus autocomplete="username">
          </div>

          <div class="form-group-clean">
            <label class="form-label-clean" for="password">Password</label>
            <div class="input-pass-wrapper">
              <input type="password" id="password" name="password" class="form-control-clean" placeholder="••••••••••••" required autocomplete="current-password">
              <button type="button" class="btn-toggle-pass" id="btnTogglePassword" title="Tampilkan/Sembunyikan password">
                <i class="fas fa-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <div class="form-options-row">
            <label class="remember-wrap">
              <input type="checkbox" id="remember" name="remember" checked>
              <span>Remember Me</span>
            </label>
            <a href="javascript:void(0)" onclick="hubungiAdmin()" class="link-muted">Forgot Password?</a>
          </div>

          <button type="submit" class="btn-submit-clean" id="loginBtn">
            <span class="btn-text">Login</span>
            <span class="spinner-border spinner-border-sm d-none" id="loginSpinner" role="status" aria-hidden="true"></span>
          </button>
        </form>

        <div class="divider-clean">
          <span>PILIH PORTAL</span>
        </div>

        <div class="portal-buttons-grid">
          <button type="button" class="btn-portal-item {{ $initialAppType === 'subsidi' ? 'active-subsidi' : '' }}" id="btnSelectSubsidi" onclick="setPortal('subsidi')">
            <i class="fas fa-home text-success"></i>
            <span>Perumahan Subsidi</span>
          </button>
          
          <button type="button" class="btn-portal-item {{ $initialAppType === 'komersil' ? 'active-komersil' : '' }}" id="btnSelectKomersil" onclick="setPortal('komersil')">
            <i class="fas fa-lock text-info"></i>
            <span>Perumahan Komersil</span>
          </button>
        </div>
      </div>

      <div class="footer-clean">
        Don't have any account? <a href="javascript:void(0)" onclick="hubungiAdmin()">Hubungi Admin</a>
      </div>
    </section>

  </div>

  <script src="{{ asset('templates/plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('templates/plugins/toastr/toastr.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Toggle Password Visibility
    const btnToggle = document.getElementById('btnTogglePassword');
    const passInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    btnToggle.addEventListener('click', function() {
      if (passInput.type === 'password') {
        passInput.type = 'text';
        eyeIcon.className = 'fas fa-eye-slash';
      } else {
        passInput.type = 'password';
        eyeIcon.className = 'fas fa-eye';
      }
    });

    // Portal Switching with Authorization Security
    let currentPortal = "{{ $initialAppType }}";

    function setPortal(type) {
      if (type === 'komersil') {
        // Cek apakah Komersil sudah di-unlock pada sesi ini
        const isUnlocked = sessionStorage.getItem('komersil_unlocked') === 'true';
        if (!isUnlocked) {
          promptKomersilAuth();
          return;
        }
      }

      applyPortalState(type);
    }

    function promptKomersilAuth() {
      Swal.fire({
        title: '<div class="text-primary font-weight-bold" style="font-size: 20px;"><i class="fas fa-shield-alt mr-2"></i> Otorisasi Portal Komersil</div>',
        html: `
          <p class="text-muted mb-3" style="font-size: 13px;">Portal Komersil dibatasi agar tidak dapat diakses oleh divisi Subsidi. Silakan masukkan password otorisasi untuk membuka portal:</p>
          <div class="input-group mb-2">
            <input type="password" id="login_komersil_auth_pass" class="form-control" placeholder="Masukkan password otorisasi..." autocomplete="current-password" style="height: 44px; border-radius: 8px;">
          </div>
          <small class="text-muted d-block text-left" style="font-size: 11px;"><i class="fas fa-lock mr-1"></i> Data unit komersil terpisah secara eksklusif.</small>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-unlock mr-1"></i> Buka Portal Komersil',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0284c7',
        preConfirm: () => {
          const pass = document.getElementById('login_komersil_auth_pass').value;
          if (!pass) {
            Swal.showValidationMessage('Password otorisasi wajib diisi!');
            return false;
          }
          return fetch("{{ route('switch.portal.verify') }}", {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              target_portal: 'komersil',
              password: pass
            })
          })
          .then(res => {
            if (!res.ok) {
              return res.json().then(e => { throw new Error(e.message || 'Password salah!'); });
            }
            return res.json();
          })
          .catch(err => {
            Swal.showValidationMessage(err.message);
          });
        }
      }).then((result) => {
        if (result.isConfirmed && result.value && result.value.status === 'success') {
          sessionStorage.setItem('komersil_unlocked', 'true');
          applyPortalState('komersil');
          toastr.success('Portal Komersil berhasil dibuka!', 'AKSES DIIZINKAN', { timeOut: 2000 });
        }
      });
    }

    function applyPortalState(type) {
      currentPortal = type;
      document.getElementById('inputAppType').value = type;

      const topBadge = document.getElementById('topPortalBadge');
      const topIcon = document.getElementById('topPortalIcon');
      const topText = document.getElementById('topPortalText');
      const btnSubsidi = document.getElementById('btnSelectSubsidi');
      const btnKomersil = document.getElementById('btnSelectKomersil');

      if (type === 'komersil') {
        topBadge.className = 'badge-portal-status status-komersil';
        topIcon.className = 'fas fa-city';
        topText.textContent = 'Portal Komersil';
        btnKomersil.className = 'btn-portal-item active-komersil';
        btnSubsidi.className = 'btn-portal-item';
      } else {
        topBadge.className = 'badge-portal-status status-subsidi';
        topIcon.className = 'fas fa-home';
        topText.textContent = 'Portal Subsidi';
        btnSubsidi.className = 'btn-portal-item active-subsidi';
        btnKomersil.className = 'btn-portal-item';
      }
    }

    function showPortalPrompt() {
      if (currentPortal === 'subsidi') {
        setPortal('komersil');
      } else {
        setPortal('subsidi');
      }
    }

    function hubungiAdmin() {
      Swal.fire({
        icon: 'info',
        title: 'Bantuan Akun',
        text: 'Silakan hubungi Administrator IT RK Group Properti untuk verifikasi akun atau pengaturan ulang password.',
        confirmButtonColor: '#111827'
      });
    }

    // Ajax Form Submit
    $('#loginForm').on('submit', function(e) {
      e.preventDefault();

      const submitBtn = $('#loginBtn');
      const btnText = submitBtn.find('.btn-text');
      const spinner = $('#loginSpinner');

      btnText.text('Memverifikasi...');
      spinner.removeClass('d-none');
      submitBtn.prop('disabled', true);

      $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          toastr.success("Login berhasil! Mengalihkan ke dashboard...", "BERHASIL", {
            progressBar: true,
            timeOut: 1500,
            positionClass: "toast-top-right",
          });
          setTimeout(function() {
            window.location.href = response.redirect_url;
          }, 1000);
        },
        error: function(xhr) {
          spinner.addClass('d-none');
          btnText.text('Login');
          submitBtn.prop('disabled', false);

          if (xhr.status === 422) {
            let errors = xhr.responseJSON.errors;
            let errorMsg = '';
            for (let field in errors) {
              errorMsg += errors[field].join('<br>') + '<br>';
            }
            toastr.error(errorMsg, "LOGIN GAGAL", {
              progressBar: true,
              timeOut: 4000,
              positionClass: "toast-top-right",
            });
          } else {
            toastr.error("Terjadi kesalahan pada sistem. Silakan coba lagi.", "ERROR", {
              progressBar: true,
              timeOut: 4000,
              positionClass: "toast-top-right",
            });
          }
        }
      });
    });
  </script>
</body>
</html>
