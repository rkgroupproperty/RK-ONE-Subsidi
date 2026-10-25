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
      
      $initialAppType = request('portal') ?? request('type') ?? session('app_type', 'subsidi');
      if (!in_array($initialAppType, ['subsidi', 'komersil'])) {
          $initialAppType = 'subsidi';
      }

      // Slide data untuk 2 ALAM yang berbeda
      $slidesSubsidi = [
          [
              'image' => $bgImage,
              'title' => 'Hunian Asri Subsidi Berkualitas',
              'subtitle' => 'Wujudkan rumah impian keluarga dengan angsuran terjangkau, lingkungan asri dan fasilitas nyaman.',
          ],
          [
              'image' => 'https://subsidi.rkgroupproperti.com/config_media/ChatGPT%20Image%20Jul%2016,%202026,%2002_42_39%20PM.png',
              'title' => 'Kehangatan Keluarga & Masa Depan',
              'subtitle' => 'Kawasan perumahan terpadu ramah keluarga dengan tata ruang hijau dan lingkungan aman.',
          ],
          [
              'image' => 'https://subsidi.rkgroupproperti.com/config_media/86721873-9c19-47f6-94a4-a64a3ca22888.jpg',
              'title' => 'Kemudahan KPR Subsidi Terpercaya',
              'subtitle' => 'Proses kepemilikan rumah mudah dan cepat dengan pendampingan penuh dari tim profesional.',
          ],
          [
              'image' => 'https://subsidi.rkgroupproperti.com/config_media/WhatsApp%20Image%202025-08-05%20at%2011.55.27%20(1).webp',
              'title' => 'Komunitas Asri & Bebas Banjir',
              'subtitle' => 'Lokasi strategis dengan infrastruktur matang yang dirancang untuk kenyamanan jangka panjang.',
          ],
      ];

      $slidesKomersil = [
          [
              'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=1400&auto=format&fit=crop',
              'title' => 'Ultra Modern Living & Prestige',
              'subtitle' => 'Koleksi hunian komersil eksklusif dengan arsitektur modern tropis 2 lantai berkelas dunia.',
          ],
          [
              'image' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?q=80&w=1400&auto=format&fit=crop',
              'title' => 'High-Return Property Investment',
              'subtitle' => 'Portofolio aset komersil bernilai tinggi di lokasi prima dengan pertumbuhan capital gain optimal.',
          ],
          [
              'image' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?q=80&w=1400&auto=format&fit=crop',
              'title' => 'Private Sanctuary & Smart Living',
              'subtitle' => 'Privasi maksimal 24 jam one-gate system, clubhouse privat, dan standar gaya hidup urban premium.',
          ],
          [
              'image' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?q=80&w=1400&auto=format&fit=crop',
              'title' => 'Desain Estetik Arsitektur Mewah',
              'subtitle' => 'Sentuhan material premium, pencahayaan alami berlimpah, dan tata ruang terbuka yang memukau.',
          ],
      ];
  @endphp
  <link rel="icon" href="{{ $faviconPath }}" />
  <link rel="shortcut icon" href="{{ $faviconPath }}" />
  <title>{{ $konfigurasi->nama_perusahaan ?? 'RK Group Property' }} - Login Sistem</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('templates/plugins/toastr/toastr.min.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  
  <style>
    :root {
      --primary-dark: #111827;
      --primary-hover: #1f2937;
      
      /* Theme Subsidi (Alam Asri & Segar) */
      --subsidi-green-deep: #022c22;
      --subsidi-green: #059669;
      --subsidi-green-bright: #10b981;
      --subsidi-green-light: #ecfdf5;
      --subsidi-border: #a7f3d0;
      
      /* Theme Komersil (Alam Modern Luxury & Sapphire) */
      --komersil-blue-deep: #020617;
      --komersil-blue: #2563eb;
      --komersil-blue-bright: #3b82f6;
      --komersil-blue-light: #eff6ff;
      --komersil-border: #bfdbfe;
      --komersil-gold: #f59e0b;
      
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
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      color: #111827;
      overflow-x: hidden;
      transition: background-color 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ========================================================
       2 ALAM LATAR BELAKANG (AMBIENT LIVING LAYERS)
       ======================================================== */
    .bg-ambient-layer {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
      transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .bg-ambient-photo {
      position: fixed;
      inset: -25px;
      z-index: 0;
      background-position: center center;
      background-size: cover;
      transform: scale(1.04);
      animation: ambientPulse 14s ease-in-out infinite alternate;
      transition: background-image 0.8s ease, filter 0.8s ease, opacity 0.8s ease;
    }

    @keyframes ambientPulse {
      0% { transform: scale(1.02) rotate(0deg); opacity: 0.65; }
      100% { transform: scale(1.06) rotate(1deg); opacity: 0.85; }
    }

    .ambient-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(85px);
      opacity: 0.45;
      animation: orbFloat 14s ease-in-out infinite alternate;
      transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .ambient-orb-1 {
      width: 520px;
      height: 520px;
      top: -140px;
      left: -100px;
    }

    .ambient-orb-2 {
      width: 560px;
      height: 560px;
      bottom: -150px;
      right: -110px;
      animation-delay: -6s;
    }

    @keyframes orbFloat {
      0% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(40px, 45px) scale(1.08); }
      100% { transform: translate(-30px, 25px) scale(0.95); }
    }

    /* ---- TEMA 1: ALAM SUBSIDI (Fresh Emerald Eco) ---- */
    body.theme-subsidi {
      background: #021a12;
    }
    body.theme-subsidi .bg-ambient-layer {
      background: radial-gradient(circle at 10% 15%, rgba(16, 185, 129, 0.38) 0%, transparent 50%),
                  radial-gradient(circle at 85% 85%, rgba(5, 150, 105, 0.30) 0%, transparent 50%),
                  radial-gradient(circle at 50% 50%, rgba(52, 211, 153, 0.18) 0%, transparent 55%),
                  linear-gradient(135deg, #021a12 0%, #064e3b 45%, #065f46 75%, #022c22 100%);
    }
    body.theme-subsidi .bg-ambient-photo {
      background-image: url('{{ $bgImage }}'), url('https://subsidi.rkgroupproperti.com/config_media/ChatGPT%20Image%20Jul%2016,%202026,%2002_42_39%20PM.png');
      filter: blur(28px) brightness(0.40) saturate(1.4);
      opacity: 0.70;
    }
    body.theme-subsidi .ambient-orb-1 {
      background: linear-gradient(135deg, #10b981, #047857);
    }
    body.theme-subsidi .ambient-orb-2 {
      background: linear-gradient(135deg, #059669, #34d399);
    }

    /* ---- TEMA 2: ALAM KOMERSIL (Midnight Royal Sapphire & Gold) ---- */
    body.theme-komersil {
      background: #030712;
    }
    body.theme-komersil .bg-ambient-layer {
      background: radial-gradient(circle at 15% 15%, rgba(37, 99, 235, 0.42) 0%, transparent 50%),
                  radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.35) 0%, transparent 50%),
                  radial-gradient(circle at 50% 50%, rgba(245, 158, 11, 0.15) 0%, transparent 60%),
                  linear-gradient(135deg, #020617 0%, #0f172a 40%, #1e1b4b 75%, #020617 100%);
    }
    body.theme-komersil .bg-ambient-photo {
      background-image: url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=1600&auto=format&fit=crop'),
                        url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?q=80&w=1600&auto=format&fit=crop');
      filter: blur(26px) brightness(0.35) saturate(1.35);
      opacity: 0.75;
    }
    body.theme-komersil .ambient-orb-1 {
      background: linear-gradient(135deg, #2563eb, #6366f1);
    }
    body.theme-komersil .ambient-orb-2 {
      background: linear-gradient(135deg, #0284c7, #f59e0b);
    }

    /* ========================================================
       PORTAL WARP / SHIFT EFFECT (SENSASI PINDAH ALAM)
       ======================================================== */
    .portal-warp-flash {
      position: fixed;
      inset: 0;
      z-index: 99999;
      pointer-events: none;
      opacity: 0;
      transform: scale(0.9);
      transition: opacity 0.5s ease;
    }
    .portal-warp-flash.active-warp-subsidi {
      background: radial-gradient(circle at center, rgba(16, 185, 129, 0.35), transparent 70%);
      animation: warpAnim 0.7s cubic-bezier(0.25, 1, 0.5, 1) forwards;
    }
    .portal-warp-flash.active-warp-komersil {
      background: radial-gradient(circle at center, rgba(37, 99, 235, 0.40), rgba(245, 158, 11, 0.15) 50%, transparent 75%);
      animation: warpAnim 0.7s cubic-bezier(0.25, 1, 0.5, 1) forwards;
    }
    @keyframes warpAnim {
      0% { opacity: 0; transform: scale(0.85); filter: blur(0px); }
      40% { opacity: 1; transform: scale(1.02); filter: blur(4px); }
      100% { opacity: 0; transform: scale(1.15); filter: blur(10px); }
    }

    /* ========================================================
       CONTAINER CARD (TRANSISI ELEGAN)
       ======================================================== */
    .login-container-card {
      width: 100%;
      max-width: 1040px;
      min-height: 600px;
      background: #ffffff;
      border-radius: 28px;
      overflow: hidden;
      display: flex;
      position: relative;
      z-index: 1;
      transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.theme-subsidi .login-container-card {
      box-shadow: 0 30px 85px -15px rgba(2, 44, 34, 0.75), 0 0 55px rgba(16, 185, 129, 0.22);
      border: 1.5px solid rgba(16, 185, 129, 0.35);
    }

    body.theme-komersil .login-container-card {
      box-shadow: 0 30px 90px -15px rgba(2, 6, 23, 0.90), 0 0 65px rgba(37, 99, 235, 0.32), 0 0 25px rgba(245, 158, 11, 0.15);
      border: 1.5px solid rgba(59, 130, 246, 0.40);
    }

    /* ========================================================
       LEFT VISUAL PANEL (HERO SLIDESHOW)
       ======================================================== */
    .visual-panel {
      flex: 1 1 50%;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 40px;
      color: #ffffff;
      overflow: hidden;
      transition: background-color 0.8s ease;
    }

    body.theme-subsidi .visual-panel {
      background: #064e3b;
    }

    body.theme-komersil .visual-panel {
      background: #0f172a;
    }

    .visual-slider-container {
      position: absolute;
      inset: 0;
      z-index: 0;
      overflow: hidden;
    }

    .visual-slide {
      position: absolute;
      inset: 0;
      background-size: cover;
      background-position: center center;
      background-repeat: no-repeat;
      opacity: 0;
      transform: scale(1.08);
      transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1), transform 6s cubic-bezier(0.25, 1, 0.5, 1);
    }

    .visual-slide.active {
      opacity: 1;
      transform: scale(1.0);
      z-index: 1;
    }

    .visual-slide::before {
      content: "";
      position: absolute;
      inset: 0;
      transition: background 0.8s ease;
      z-index: 1;
    }

    body.theme-subsidi .visual-slide::before {
      background: radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.12) 0%, transparent 50%),
                  linear-gradient(180deg, rgba(6, 78, 59, 0.25) 0%, rgba(6, 78, 59, 0.10) 40%, rgba(2, 44, 34, 0.94) 100%);
    }

    body.theme-komersil .visual-slide::before {
      background: radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                  linear-gradient(180deg, rgba(15, 23, 42, 0.28) 0%, rgba(15, 23, 42, 0.12) 40%, rgba(2, 6, 23, 0.95) 100%);
    }

    .visual-content-top {
      position: relative;
      z-index: 3;
      display: flex;
      align-items: center;
      justify-content: space-between;
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
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
    }

    .visual-brand-icon {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      overflow: hidden;
      transition: background 0.5s ease, color 0.5s ease;
    }

    body.theme-subsidi .visual-brand-icon {
      background: #065f46;
      color: #ffffff;
    }

    body.theme-komersil .visual-brand-icon {
      background: #1e3a8a;
      color: #fbbf24;
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

    .portal-tag-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 50px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      backdrop-filter: blur(8px);
      transition: all 0.5s ease;
    }

    body.theme-subsidi .portal-tag-pill {
      background: rgba(6, 95, 70, 0.75);
      color: #a7f3d0;
      border: 1px solid rgba(167, 243, 208, 0.4);
    }

    body.theme-komersil .portal-tag-pill {
      background: rgba(30, 58, 138, 0.75);
      color: #fde68a;
      border: 1px solid rgba(253, 230, 138, 0.4);
    }

    .visual-content-bottom {
      position: relative;
      z-index: 3;
      max-width: 440px;
    }

    .text-slide-item {
      display: none;
      animation: fadeInSlide 0.8s ease forwards;
    }

    .text-slide-item.active {
      display: block;
    }

    @keyframes fadeInSlide {
      from {
        opacity: 0;
        transform: translateY(12px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .visual-headline {
      font-size: 32px;
      font-weight: 800;
      line-height: 1.18;
      margin-bottom: 12px;
      color: #ffffff;
      letter-spacing: -0.02em;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    }

    .visual-subtext {
      font-size: 13.5px;
      line-height: 1.55;
      color: rgba(255, 255, 255, 0.92);
      margin-bottom: 24px;
      text-shadow: 0 1px 6px rgba(0, 0, 0, 0.5);
    }

    .visual-indicators {
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .slider-dot {
      width: 8px;
      height: 8px;
      background: rgba(255, 255, 255, 0.45);
      border-radius: 50%;
      cursor: pointer;
      transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .slider-dot:hover {
      background: rgba(255, 255, 255, 0.8);
      transform: scale(1.2);
    }

    .slider-dot.active {
      width: 30px;
      height: 8px;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(255, 255, 255, 0.85);
    }

    /* ========================================================
       RIGHT FORM PANEL
       ======================================================== */
    .form-panel {
      flex: 1 1 50%;
      background: #ffffff;
      padding: 44px 48px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: all 0.5s ease;
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
      gap: 8px;
      padding: 7px 16px;
      border-radius: 50px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.02em;
      border: 1.5px solid var(--border-gray);
      background: #f9fafb;
      color: #374151;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .badge-portal-status.status-subsidi {
      background: var(--subsidi-green-light);
      border-color: #a7f3d0;
      color: #065f46;
      box-shadow: 0 2px 10px rgba(5, 150, 105, 0.12);
    }

    .badge-portal-status.status-komersil {
      background: var(--komersil-blue-light);
      border-color: #bfdbfe;
      color: #1d4ed8;
      box-shadow: 0 2px 10px rgba(37, 99, 235, 0.15);
    }

    .form-header {
      margin-bottom: 26px;
    }

    .form-title {
      font-size: 22px;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 6px;
      line-height: 1.3;
      transition: color 0.4s ease;
    }

    body.theme-subsidi .form-title {
      color: #064e3b;
    }

    body.theme-komersil .form-title {
      color: #0f172a;
    }

    .form-subtitle {
      font-size: 13.5px;
      color: var(--text-muted);
      line-height: 1.45;
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
      transition: border-color 0.25s ease, box-shadow 0.25s ease;
    }

    body.theme-subsidi .form-control-clean:focus {
      border-color: #059669;
      box-shadow: 0 0 0 3.5px rgba(5, 150, 105, 0.15);
    }

    body.theme-komersil .form-control-clean:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3.5px rgba(37, 99, 235, 0.18);
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
      margin-bottom: 20px;
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
      cursor: pointer;
      transition: accent-color 0.4s ease;
    }

    body.theme-subsidi .remember-wrap input[type="checkbox"] {
      accent-color: #059669;
    }

    body.theme-komersil .remember-wrap input[type="checkbox"] {
      accent-color: #2563eb;
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

    /* Submit Button (Gradient Berbeda Tiap Alam) */
    .btn-submit-clean {
      width: 100%;
      height: 48px;
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-size: 14.5px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      transition: all 0.35s ease;
    }

    body.theme-subsidi .btn-submit-clean {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      box-shadow: 0 4px 18px rgba(5, 150, 105, 0.35);
    }
    body.theme-subsidi .btn-submit-clean:hover {
      background: linear-gradient(135deg, #047857 0%, #065f46 100%);
      box-shadow: 0 6px 24px rgba(5, 150, 105, 0.45);
      transform: translateY(-1px);
    }

    body.theme-komersil .btn-submit-clean {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%);
      box-shadow: 0 4px 18px rgba(37, 99, 235, 0.38);
    }
    body.theme-komersil .btn-submit-clean:hover {
      background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
      box-shadow: 0 6px 24px rgba(37, 99, 235, 0.52);
      transform: translateY(-1px);
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

    /* Portal Buttons Grid */
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
      font-size: 12.5px;
      font-weight: 700;
      color: #4b5563;
      cursor: pointer;
      position: relative;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-portal-item:hover {
      background: #f3f4f6;
      border-color: #d1d5db;
      transform: translateY(-1px);
    }

    .btn-portal-item.active-subsidi {
      background: #ecfdf5;
      border-color: #059669;
      color: #065f46;
      font-weight: 800;
      box-shadow: 0 3px 12px rgba(5, 150, 105, 0.22);
    }

    .btn-portal-item.active-komersil {
      background: #eff6ff;
      border-color: #2563eb;
      color: #1d4ed8;
      font-weight: 800;
      box-shadow: 0 3px 12px rgba(37, 99, 235, 0.22);
    }

    .portal-active-pill {
      font-size: 10px;
      padding: 1px 6px;
      border-radius: 20px;
      font-weight: 800;
      margin-left: 2px;
    }

    .active-subsidi .portal-active-pill {
      background: #059669;
      color: #ffffff;
    }

    .active-komersil .portal-active-pill {
      background: #2563eb;
      color: #ffffff;
    }

    .footer-clean {
      text-align: center;
      font-size: 12.5px;
      color: var(--text-muted);
      margin-top: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    body.theme-subsidi .footer-shield {
      color: #059669;
    }

    body.theme-komersil .footer-shield {
      color: #2563eb;
    }

    /* Responsive */
    @media (max-width: 860px) {
      .login-container-card {
        flex-direction: column;
        max-width: 480px;
        min-height: auto;
      }
      .visual-panel {
        min-height: 260px;
        padding: 26px 22px;
      }
      .visual-headline {
        font-size: 24px;
      }
      .form-panel {
        padding: 32px 22px;
      }
      .portal-buttons-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="theme-{{ $initialAppType }}">

  <!-- Sensasi Efek Flash Pindah Alam -->
  <div class="portal-warp-flash" id="warpFlash"></div>

  <!-- Dynamic Ambient Living Background Layers -->
  <div class="bg-ambient-layer">
    <div class="bg-ambient-photo"></div>
    <div class="ambient-orb ambient-orb-1"></div>
    <div class="ambient-orb ambient-orb-2"></div>
  </div>

  <div class="login-container-card">
    
    <!-- LEFT PANEL: Hero Image with Dynamic Dual-World Slideshow -->
    <section class="visual-panel">
      <!-- Background Slides (Diinjeksi dinamis oleh JS berdasarkan portal) -->
      <div class="visual-slider-container" id="visualSliderContainer"></div>

      <div class="visual-content-top">
        <div class="visual-brand-badge">
          <div class="visual-brand-icon">
            @if ($logo && $logo->nama_file)
              <img src="{{ asset('config_media/' . $logo->nama_file) }}" alt="Logo" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-home\'></i>';">
            @else
              <i class="fas fa-home" id="brandIcon"></i>
            @endif
          </div>
          <span class="visual-brand-name">{{ $konfigurasi->nama_perusahaan ?? 'RK Group Property' }}</span>
        </div>

        <div class="portal-tag-pill" id="portalTagPill">
          <i class="fas {{ $initialAppType === 'komersil' ? 'fa-gem' : 'fa-leaf' }}" id="portalTagIcon"></i>
          <span id="portalTagText">{{ $initialAppType === 'komersil' ? 'Komersil Premium' : 'Hunian Subsidi' }}</span>
        </div>
      </div>

      <div class="visual-content-bottom">
        <div id="textSlideContainer"></div>

        <div class="visual-indicators" id="visualIndicators"></div>
      </div>
    </section>

    <!-- RIGHT PANEL: Clean Dynamic Form -->
    <section class="form-panel">
      <div>
        <div class="form-top-bar">
          <button type="button" class="badge-portal-status {{ $initialAppType === 'komersil' ? 'status-komersil' : 'status-subsidi' }}" id="topPortalBadge" onclick="togglePortalQuick()">
            <i class="fas {{ $initialAppType === 'komersil' ? 'fa-city' : 'fa-home' }}" id="topPortalIcon"></i>
            <span id="topPortalText">{{ $initialAppType === 'komersil' ? 'Portal Komersil' : 'Portal Subsidi' }}</span>
          </button>
        </div>

        <div class="form-header">
          <h2 class="form-title" id="formHeaderTitle">
            {{ $initialAppType === 'komersil' ? 'Managemen Penjualan Komersil' : 'Managemen Sistem Penjualan Subsidi' }}
          </h2>
          <p class="form-subtitle mt-1" id="formHeaderSubtitle">
            {{ $initialAppType === 'komersil' ? 'Sign in your account untuk mengelola unit & portofolio komersil' : 'Sign in your account untuk mengakses sistem penjualan subsidi' }}
          </p>
        </div>

        <form id="loginForm" method="POST" action="{{ route('admin.loginPost') }}" novalidate>
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
            <i class="fas {{ $initialAppType === 'komersil' ? 'fa-crown' : 'fa-sign-in-alt' }}" id="loginBtnIcon"></i>
            <span class="btn-text" id="loginBtnText">{{ $initialAppType === 'komersil' ? 'Login ke Portal Komersil' : 'Login ke Portal Subsidi' }}</span>
            <span class="spinner-border spinner-border-sm d-none" id="loginSpinner" role="status" aria-hidden="true"></span>
          </button>
        </form>

        <div class="divider-clean">
          <span>PILIH PORTAL SISTEM</span>
        </div>

        <div class="portal-buttons-grid">
          <button type="button" class="btn-portal-item {{ $initialAppType === 'subsidi' ? 'active-subsidi' : '' }}" id="btnSelectSubsidi" onclick="setPortal('subsidi')">
            <i class="fas fa-home text-success"></i>
            <span>Perumahan Subsidi</span>
            <span class="portal-active-pill {{ $initialAppType === 'subsidi' ? '' : 'd-none' }}" id="pillSubsidi">Aktif</span>
          </button>
          
          <button type="button" class="btn-portal-item {{ $initialAppType === 'komersil' ? 'active-komersil' : '' }}" id="btnSelectKomersil" onclick="setPortal('komersil')">
            <i class="fas fa-building text-primary"></i>
            <span>Perumahan Komersil</span>
            <span class="portal-active-pill {{ $initialAppType === 'komersil' ? '' : 'd-none' }}" id="pillKomersil">Aktif</span>
          </button>
        </div>
      </div>

      <div class="footer-clean">
        <i class="fas fa-shield-alt footer-shield" id="footerShield"></i> Di Kelola Oleh <strong style="color: #0f172a;">Tim Marcom & Digital RK Group</strong>
      </div>
    </section>

  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    if (typeof jQuery === 'undefined') {
      document.write('<script src="{{ asset('templates/plugins/jquery/jquery.min.js') }}"><\/script>');
    }
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  <script>
    if (typeof toastr === 'undefined') {
      document.write('<script src="{{ asset('templates/plugins/toastr/toastr.min.js') }}"><\/script>');
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Data Slide 2 ALAM
    const portalSlidesData = {
      subsidi: @json($slidesSubsidi),
      komersil: @json($slidesKomersil)
    };

    // Preload gambar agar transisi antar alam mulus seketika
    (function preloadImages() {
      const allImages = [...portalSlidesData.subsidi, ...portalSlidesData.komersil];
      allImages.forEach(item => {
        const img = new Image();
        img.src = item.image;
      });
    })();

    // State Portal Saat Ini
    let currentPortal = "{{ $initialAppType }}";
    let currentSlideIndex = 0;
    let slideTimer = null;

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

    // Pindah Alam (Portal Switcher)
    function setPortal(type) {
      if (currentPortal === type) return;

      // Efek Flash Warp Pindah Alam
      const warp = document.getElementById('warpFlash');
      warp.className = 'portal-warp-flash ' + (type === 'komersil' ? 'active-warp-komersil' : 'active-warp-subsidi');
      setTimeout(() => {
        warp.className = 'portal-warp-flash';
      }, 700);

      currentPortal = type;
      applyPortalTheme(type);
    }

    function togglePortalQuick() {
      setPortal(currentPortal === 'subsidi' ? 'komersil' : 'subsidi');
    }

    function applyPortalTheme(type) {
      // 1. Ubah class pada body
      document.body.className = 'theme-' + type;

      // 2. Simpan nilai ke hidden input form
      document.getElementById('inputAppType').value = type;

      // 3. Update Badge Kanan Atas
      const topBadge = document.getElementById('topPortalBadge');
      const topIcon = document.getElementById('topPortalIcon');
      const topText = document.getElementById('topPortalText');

      // 4. Update Tag Pill Kiri Atas
      const tagPill = document.getElementById('portalTagPill');
      const tagIcon = document.getElementById('portalTagIcon');
      const tagText = document.getElementById('portalTagText');

      // 5. Update Header Form
      const formTitle = document.getElementById('formHeaderTitle');
      const formSubtitle = document.getElementById('formHeaderSubtitle');

      // 6. Update Tombol Login Submit
      const loginBtnIcon = document.getElementById('loginBtnIcon');
      const loginBtnText = document.getElementById('loginBtnText');

      // 7. Update Tombol Pilihan Portal di Bawah
      const btnSubsidi = document.getElementById('btnSelectSubsidi');
      const btnKomersil = document.getElementById('btnSelectKomersil');
      const pillSubsidi = document.getElementById('pillSubsidi');
      const pillKomersil = document.getElementById('pillKomersil');

      if (type === 'komersil') {
        topBadge.className = 'badge-portal-status status-komersil';
        topIcon.className = 'fas fa-city';
        topText.textContent = 'Portal Komersil';

        tagIcon.className = 'fas fa-gem';
        tagText.textContent = 'Komersil Premium';

        formTitle.textContent = 'Managemen Penjualan Komersil';
        formSubtitle.textContent = 'Sign in your account untuk mengelola unit & portofolio komersil';

        loginBtnIcon.className = 'fas fa-crown';
        loginBtnText.textContent = 'Login ke Portal Komersil';

        btnKomersil.className = 'btn-portal-item active-komersil';
        btnSubsidi.className = 'btn-portal-item';
        pillKomersil.classList.remove('d-none');
        pillSubsidi.classList.add('d-none');
      } else {
        topBadge.className = 'badge-portal-status status-subsidi';
        topIcon.className = 'fas fa-home';
        topText.textContent = 'Portal Subsidi';

        tagIcon.className = 'fas fa-leaf';
        tagText.textContent = 'Hunian Subsidi';

        formTitle.textContent = 'Managemen Sistem Penjualan Subsidi';
        formSubtitle.textContent = 'Sign in your account untuk mengakses sistem penjualan subsidi';

        loginBtnIcon.className = 'fas fa-sign-in-alt';
        loginBtnText.textContent = 'Login ke Portal Subsidi';

        btnSubsidi.className = 'btn-portal-item active-subsidi';
        btnKomersil.className = 'btn-portal-item';
        pillSubsidi.classList.remove('d-none');
        pillKomersil.classList.add('d-none');
      }

      // 8. Muat Ulang Slideshow Sesuai Alam Yang Dipilih
      loadSlidesForPortal(type);
    }

    // Render & Jalankan Slideshow
    function loadSlidesForPortal(type) {
      const slides = portalSlidesData[type] || portalSlidesData.subsidi;
      const sliderContainer = document.getElementById('visualSliderContainer');
      const textContainer = document.getElementById('textSlideContainer');
      const dotsContainer = document.getElementById('visualIndicators');

      // Bersihkan container
      sliderContainer.innerHTML = '';
      textContainer.innerHTML = '';
      dotsContainer.innerHTML = '';

      slides.forEach((slide, index) => {
        // Image Slide
        const slideEl = document.createElement('div');
        slideEl.className = 'visual-slide' + (index === 0 ? ' active' : '');
        slideEl.style.backgroundImage = `url('${slide.image}')`;
        sliderContainer.appendChild(slideEl);

        // Text Slide
        const textEl = document.createElement('div');
        textEl.className = 'text-slide-item' + (index === 0 ? ' active' : '');
        textEl.innerHTML = `
          <h1 class="visual-headline">${slide.title}</h1>
          <p class="visual-subtext">${slide.subtitle}</p>
        `;
        textContainer.appendChild(textEl);

        // Indicator Dot
        const dotEl = document.createElement('div');
        dotEl.className = 'slider-dot' + (index === 0 ? ' active' : '');
        dotEl.title = `Slide ${index + 1}`;
        dotEl.onclick = function() { goToSlide(index); };
        dotsContainer.appendChild(dotEl);
      });

      currentSlideIndex = 0;
      resetSlideTimer();
    }

    function showSlide(index) {
      const slides = document.querySelectorAll('.visual-slide');
      const textSlides = document.querySelectorAll('.text-slide-item');
      const dots = document.querySelectorAll('.slider-dot');
      const total = slides.length;

      if (!total) return;

      if (index >= total) index = 0;
      if (index < 0) index = total - 1;

      slides.forEach((s, i) => s.classList.toggle('active', i === index));
      textSlides.forEach((t, i) => t.classList.toggle('active', i === index));
      dots.forEach((d, i) => d.classList.toggle('active', i === index));

      currentSlideIndex = index;
    }

    function nextSlide() {
      const slides = document.querySelectorAll('.visual-slide');
      showSlide((currentSlideIndex + 1) % slides.length);
    }

    function goToSlide(index) {
      showSlide(index);
      resetSlideTimer();
    }

    function resetSlideTimer() {
      if (slideTimer) clearInterval(slideTimer);
      slideTimer = setInterval(nextSlide, 4500);
    }

    function hubungiAdmin() {
      Swal.fire({
        icon: 'info',
        title: 'Bantuan Akun',
        text: 'Silakan hubungi Administrator IT RK Group Properti untuk verifikasi akun atau pengaturan ulang password.',
        confirmButtonColor: currentPortal === 'komersil' ? '#2563eb' : '#059669'
      });
    }

    // Ajax Form Submit
    $('#loginForm').on('submit', function(e) {
      e.preventDefault();

      const submitBtn = $('#loginBtn');
      const btnText = submitBtn.find('.btn-text');
      const spinner = $('#loginSpinner');
      const btnIcon = $('#loginBtnIcon');

      btnText.text('Memverifikasi...');
      btnIcon.addClass('d-none');
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
          btnIcon.removeClass('d-none');
          btnText.text(currentPortal === 'komersil' ? 'Login ke Portal Komersil' : 'Login ke Portal Subsidi');
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

    // Inisialisasi awal saat halaman dibuka
    $(document).ready(function() {
      applyPortalTheme(currentPortal);
    });
  </script>
</body>
</html>
