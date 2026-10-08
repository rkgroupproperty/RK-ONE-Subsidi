@extends('admin.layout_admin')

@section('content')
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    :root {
      --bg: #f6f8fc;
      --surface: #ffffff;
      --text: #111827;
      --muted: #6b7280;
      --line: #e5e7eb;
      --primary: #5b2cff;
      --primary-soft: #ede9fe;
      --shadow: 0 10px 30px rgba(17, 24, 39, .07);
      --radius: 18px;
    }

    * { box-sizing: border-box; }
    .dashboard-shell {
      margin: 0;
      font-family: "Inter", sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .dashboard-shell {
      width: 100%;
      min-height: 100vh;
      padding: 28px;
    }

    .dashboard-wrap {
      max-width: 1550px;
      margin: 0 auto;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 20px;
      margin-bottom: 22px;
    }

    .title h1 {
      margin: 0 0 8px;
      font-size: 32px;
      line-height: 1.2;
    }

    .title p {
      margin: 0;
      color: var(--muted);
      font-size: 14px;
    }

    .top-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .icon-btn, .filter-btn {
      border: 1px solid var(--line);
      background: #fff;
      color: #374151;
      border-radius: 12px;
      min-height: 42px;
      padding: 0 14px;
      display: inline-flex;
      align-items: center;
      gap: 9px;
      font-weight: 600;
      box-shadow: 0 5px 15px rgba(17,24,39,.04);
      cursor: pointer;
    }

    .date-btn {
      border: 1px solid var(--line);
      background: #fff;
      color: #374151;
      border-radius: 12px;
      min-height: 42px;
      padding: 0 14px;
      display: inline-flex;
      align-items: center;
      gap: 9px;
      font-weight: 600;
      box-shadow: 0 5px 15px rgba(17,24,39,.04);
      cursor: default !important;
      pointer-events: none;
    }

    .icon-btn { width: 42px; justify-content: center; padding: 0; }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 18px;
    }

    .kpi-card {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      padding: 18px;
      display: flex;
      align-items: center;
      gap: 15px;
      box-shadow: var(--shadow);
      min-height: 118px;
      min-width: 0;
      overflow: hidden;
    }

    .kpi-card > div:last-child {
      min-width: 0;
      flex: 1;
    }

    .kpi-icon {
      width: 54px;
      height: 54px;
      border-radius: 15px;
      display: grid;
      place-items: center;
      color: #fff;
      font-size: 23px;
      flex: 0 0 auto;
    }

    .blue { background: linear-gradient(135deg,#0ea5e9,#2563eb); }
    .green { background: linear-gradient(135deg,#22c55e,#15803d); }
    .orange { background: linear-gradient(135deg,#f59e0b,#f97316); }
    .purple { background: linear-gradient(135deg,#7c3aed,#4f46e5); }
    .red { background: linear-gradient(135deg,#fb7185,#e11d48); }

    .kpi-label {
      font-size: 13px;
      color: #4b5563;
      margin-bottom: 7px;
      white-space: nowrap;
    }

    .kpi-value {
      font-size: clamp(17px, 1.25vw, 21px);
      font-weight: 800;
      margin-bottom: 5px;
      line-height: 1.15;
      overflow-wrap: anywhere;
      word-break: normal;
    }

    .kpi-value.money {
      font-size: clamp(15px, 1.08vw, 20px);
      letter-spacing: -.4px;
      white-space: normal;
    }

    .kpi-note {
      color: var(--muted);
      font-size: 12px;
    }

    .positive { color: #16a34a; font-weight: 600; }

    .panel {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      margin-bottom: 18px;
      overflow: hidden;
    }

    .panel-body { padding: 20px; }

    .section-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      margin-bottom: 16px;
    }

    .section-head h2 {
      margin: 0;
      font-size: 18px;
    }

    .section-head span {
      color: var(--muted);
      font-size: 13px;
      font-weight: 500;
    }

    .pipeline {
      display: grid;
      grid-template-columns: repeat(8, minmax(130px, 1fr));
      gap: 20px;
      overflow-x: auto;
      padding: 2px 5px 10px;
    }

    .pipeline-card {
      position: relative;
      min-height: 170px;
      border-radius: 14px;
      padding: 16px 10px;
      text-align: center;
      border: 1.5px solid;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .pipeline-card:not(:last-child)::after {
      content: "\f054";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      position: absolute;
      top: 50%;
      right: -17px;
      transform: translateY(-50%);
      color: #111827;
      font-size: 14px;
    }

    .pipeline-card h3 {
      margin: 0 0 8px;
      font-size: 13px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .pipeline-card .count {
      font-size: 30px;
      font-weight: 800;
      margin-bottom: 18px;
    }

    .pipeline-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      min-height: 34px;
      padding: 8px 12px;
      border-radius: 10px;
      color: currentColor;
      background: rgba(255, 255, 255, .78);
      border: 1px solid currentColor;
      font-size: 12px;
      font-weight: 800;
      text-decoration: none;
      transition: .18s ease;
    }

    .pipeline-btn:hover {
      color: #fff;
      background: var(--pipeline-accent);
      border-color: var(--pipeline-accent);
      text-decoration: none;
    }

    .c-red { --pipeline-accent:#e11d48; color:#e11d48; border-color:#fb7185; background:#fff5f7; }
    .c-teal { --pipeline-accent:#0284c7; color:#0284c7; border-color:#38bdf8; background:#f0f9ff; }
    .c-cyan { --pipeline-accent:#0891b2; color:#0891b2; border-color:#22d3ee; background:#f2fdff; }
    .c-green { --pipeline-accent:#15803d; color:#15803d; border-color:#4ade80; background:#f3fff6; }
    .c-orange { --pipeline-accent:#c56a00; color:#c56a00; border-color:#f59e0b; background:#fff9ed; }
    .c-pink { --pipeline-accent:#db2777; color:#db2777; border-color:#f472b6; background:#fff5fb; }
    .c-purple { --pipeline-accent:#5b21b6; color:#5b21b6; border-color:#8b5cf6; background:#faf7ff; }
    .c-blue { --pipeline-accent:#1d4ed8; color:#1d4ed8; border-color:#60a5fa; background:#f5f9ff; }

    .projects-table-wrap {
      overflow-x: hidden;
    }

    .projects-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      min-width: 0;
    }

    .projects-table th,
    .projects-table td {
      padding: 13px 10px;
      border-bottom: 1px solid var(--line);
      text-align: center;
      vertical-align: middle;
      font-size: 12px;
    }

    .projects-table th:not(:first-child),
    .projects-table td:not(:first-child) {
      width: 86px;
      border-left: 1px solid var(--line);
      border-right: 1px solid var(--line);
    }

    .projects-table th {
      color: #6b7280;
      background: #fafafa;
      font-weight: 700;
    }

    .projects-table td:first-child,
    .projects-table th:first-child {
      text-align: left;
      width: 330px;
    }

    .project-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .project-thumb {
      width: 80px;
      height: 54px;
      object-fit: cover;
      border-radius: 10px;
      background: linear-gradient(135deg,#dbeafe,#dcfce7);
      border: 1px solid #dbe4f0;
    }

    .project-name {
      font-weight: 800;
      margin-bottom: 6px;
    }

    .project-meta {
      color: var(--muted);
      font-size: 11px;
    }

    .code-pill {
      display: inline-flex;
      padding: 3px 7px;
      border-radius: 6px;
      background: #eef2ff;
      color: #4338ca;
      font-weight: 700;
      margin-right: 5px;
    }

    .metric-number {
      font-size: 16px;
      font-weight: 800;
      display: block;
      margin-bottom: 3px;
    }

    .detail-btn {
      border: 1px solid #c7d2fe;
      background: #fff;
      color: #4f46e5;
      border-radius: 9px;
      padding: 7px 12px;
      font-weight: 700;
      cursor: pointer;
    }

    .table-total td {
      background: #fafafa;
      font-weight: 800;
    }

    .bottom-grid {
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr;
      gap: 16px;
    }

    .chart-box {
      height: 300px;
      position: relative;
    }

    .bar-chart {
      height: 210px;
      display: flex;
      align-items: end;
      gap: 12px;
      padding: 18px 8px 30px;
      border-left: 1px solid var(--line);
      border-bottom: 1px solid var(--line);
    }

    .bar-group {
      height: 100%;
      flex: 1;
      display: flex;
      align-items: end;
      gap: 4px;
      position: relative;
    }

    .bar {
      width: 50%;
      border-radius: 6px 6px 0 0;
      min-height: 8px;
    }

    .bar.target { background: #ddd6fe; }
    .bar.actual { background: #5b2cff; }

    .bar-label {
      position: absolute;
      bottom: -23px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 10px;
      color: #6b7280;
    }

    .legend {
      display:flex;
      justify-content:center;
      gap:18px;
      font-size:11px;
      color:#6b7280;
      margin-top:12px;
    }

    .legend i {
      width:10px;
      height:10px;
      display:inline-block;
      border-radius:3px;
      margin-right:5px;
    }

    .marketing-list, .activity-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .marketing-list,
    .bank-list {
      max-height: 360px;
      overflow-y: auto;
      padding-right: 4px;
    }

    .marketing-item, .activity-item {
      display: flex;
      align-items: center;
      gap: 11px;
      padding-bottom: 10px;
      border-bottom: 1px solid #f0f1f4;
    }

    .marketing-item:hover {
      background: #f8f9fa;
      border-radius: 8px;
      cursor: pointer;
    }

    .bank-item {
      display: grid;
      grid-template-columns: 28px 1fr 74px 54px;
      align-items: center;
      gap: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid #f0f1f4;
    }

    .rank {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #f3f4f6;
      display: grid;
      place-items: center;
      font-size: 11px;
      font-weight: 800;
    }

    .avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg,#c7d2fe,#fbcfe8);
      display: grid;
      place-items: center;
      color: #4f46e5;
      font-weight: 800;
      flex: 0 0 auto;
    }

    .item-main {
      flex: 1;
      min-width: 0;
    }

    .item-title {
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 3px;
    }

    .item-sub {
      color: var(--muted);
      font-size: 10px;
    }

    .item-value {
      color: var(--primary);
      font-weight: 800;
      font-size: 12px;
    }

    .activity-icon {
      width: 32px;
      height: 32px;
      border-radius: 9px;
      display: grid;
      place-items: center;
      color:#fff;
      flex: 0 0 auto;
    }

    .activity-time {
      color: var(--muted);
      font-size: 10px;
      white-space: nowrap;
    }

    @media (max-width: 1250px) {
      .kpi-grid { grid-template-columns: repeat(2, 1fr); }
      .kpi-card:last-child { grid-column: span 2; }
      .bottom-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 700px) {
      .dashboard-shell { padding: 15px; }
      .topbar { flex-direction: column; }
      .title h1 { font-size: 26px; }
      .kpi-grid { grid-template-columns: 1fr; }
      .kpi-card:last-child { grid-column: auto; }
      .panel-body { padding: 15px; }
    }
  
      body.dark-mode .dashboard-shell {
        background: #111827;
        color: #e5e7eb;
      }

      body.dark-mode .panel,
      body.dark-mode .kpi-card,
      body.dark-mode .periode-filter-bar,
      body.dark-mode .collapse .card,
      body.dark-mode .icon-btn,
      body.dark-mode .date-btn,
      body.dark-mode .filter-btn {
        background: #1f2937 !important;
        border-color: #374151 !important;
        color: #e5e7eb;
      }

      body.dark-mode .title p,
      body.dark-mode .kpi-label,
      body.dark-mode .kpi-note,
      body.dark-mode .section-head span,
      body.dark-mode .project-meta,
      body.dark-mode .item-sub,
      body.dark-mode .activity-time {
        color: #a8b2c7;
      }

      /* Resume Proyek & Sumber Prospek Styles */
      .resume-tab-btn {
        padding: 7px 16px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        border-radius: 9999px;
        cursor: pointer;
        transition: all .2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }
      .resume-tab-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
      }
      .resume-tab-btn.active {
        background: #4f46e5;
        border-color: #4f46e5;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
      }
      .resume-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 16px;
      }
      .resume-kpi-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        transition: transform .15s ease, box-shadow .15s ease;
      }
      .resume-kpi-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      }
      .resume-proc-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
      }
      .resume-proc-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
      }
      .table-sumber-matrix {
        width: 100%;
        font-size: 12px;
        border-collapse: collapse;
      }
      .table-sumber-matrix th {
        background: #f8fafc;
        padding: 9px 8px;
        font-weight: 700;
        font-size: 11px;
        color: #475569;
        border: 1px solid #e2e8f0;
        text-align: center;
        white-space: nowrap;
      }
      .table-sumber-matrix td {
        padding: 8px 8px;
        border: 1px solid #e2e8f0;
        text-align: center;
        color: #334155;
      }
      .table-sumber-matrix tr:hover {
        background: #f8fafc;
      }
      .table-sumber-matrix .total-row {
        background: #eff6ff;
        font-weight: 800;
      }
      @media (max-width: 992px) {
        .resume-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .resume-proc-grid { grid-template-columns: repeat(2, 1fr); }
      }
      @media (max-width: 576px) {
        .resume-kpi-grid { grid-template-columns: 1fr; }
        .resume-proc-grid { grid-template-columns: 1fr; }
      }
      body.dark-mode .resume-tab-btn {
        background: #1f2937;
        border-color: #374151;
        color: #94a3b8;
      }
      body.dark-mode .resume-tab-btn.active {
        background: #6366f1;
        border-color: #6366f1;
        color: #ffffff;
      }
      body.dark-mode .resume-kpi-box,
      body.dark-mode .resume-proc-card {
        background: #1f2937 !important;
        border-color: #374151 !important;
        color: #e2e8f0;
      }
      body.dark-mode .table-sumber-matrix th {
        background: #111827 !important;
        border-color: #374151 !important;
        color: #94a3b8;
      }
      body.dark-mode .table-sumber-matrix td {
        border-color: #374151 !important;
        color: #e2e8f0;
      }
      body.dark-mode .table-sumber-matrix .total-row {
        background: #1e1b4b !important;
      }
    </style>

    <div class="content-wrapper">
  <main class="dashboard-shell">
    <div class="dashboard-wrap">

      <header class="topbar">
        <div class="title">
          <h1>Selamat datang, {{ $username ?? 'dev' }}</h1>
          <p>Ringkasan penjualan dan aktivitas: <strong class="text-primary">{{ $summaryMetrics['label_periode'] ?? 'Bulan Ini' }}</strong></p>
        </div>

        <div class="top-actions">
          <button class="icon-btn" onclick="Swal.fire('Notifikasi', 'Tidak ada notifikasi baru', 'info')"><i class="fa-regular fa-bell"></i></button>
          <button class="date-btn"><i class="fa-regular fa-calendar-days"></i> {{ Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}</button>
        </div>
      </header>

      <!-- Filter Bar Periode -->
      <section class="periode-filter-bar mb-3 p-3 rounded shadow-sm" style="background:#ffffff;border:1px solid #e5e7eb;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span style="font-weight:600;font-size:13px;color:#4b5563;"><i class="fa-solid fa-filter mr-1 text-primary"></i> Filter Periode:</span>
          
          <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('beranda.index', ['periode' => 'bulan_ini']) }}" class="btn {{ ($summaryMetrics['periode_filter'] ?? 'bulan_ini') === 'bulan_ini' ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }}">
              Bulan Ini
            </a>
            <a href="{{ route('beranda.index', ['periode' => 'bulan_kemarin']) }}" class="btn {{ ($summaryMetrics['periode_filter'] ?? '') === 'bulan_kemarin' ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }}">
              Bulan Kemarin
            </a>
            <button type="button" class="btn {{ ($summaryMetrics['periode_filter'] ?? '') === 'pilih_bulan' ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }}" data-toggle="collapse" data-target="#collapsePilihBulan">
              <i class="fa-regular fa-calendar mr-1"></i> Pilih Bulan <i class="fa-solid fa-caret-down ml-1"></i>
            </button>
            <button type="button" class="btn {{ ($summaryMetrics['periode_filter'] ?? '') === 'custom' ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }}" data-toggle="collapse" data-target="#collapseCustomTanggal">
              <i class="fa-regular fa-calendar-days mr-1"></i> Custom Tanggal <i class="fa-solid fa-caret-down ml-1"></i>
            </button>
            <a href="{{ route('beranda.index', ['periode' => 'semua']) }}" class="btn {{ ($summaryMetrics['periode_filter'] ?? '') === 'semua' ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }}">
              Semua Waktu
            </a>
          </div>
        </div>

        <div>
          <span class="badge badge-light px-3 py-2" style="font-size:12px;border:1px solid #d1d5db;border-radius:20px;color:#374151;">
            <i class="fa-solid fa-clock-rotate-left mr-1 text-info"></i> Aktif: <strong>{{ $summaryMetrics['label_periode'] ?? 'Bulan Ini' }}</strong>
          </span>
        </div>
      </section>

      <!-- Collapse Form: Pilih Bulan & Tahun -->
      <div class="collapse {{ ($summaryMetrics['periode_filter'] ?? '') === 'pilih_bulan' ? 'show' : '' }} mb-3" id="collapsePilihBulan">
        <div class="card card-body p-3 shadow-sm border-0" style="background:#f9fafb;border:1px solid #e5e7eb !important;">
          <form action="{{ route('beranda.index') }}" method="GET" class="form-inline d-flex flex-wrap align-items-center gap-2">
            <input type="hidden" name="periode" value="pilih_bulan">
            <label class="mr-2 font-weight-bold text-sm text-secondary">Bulan:</label>
            <select name="bulan" class="form-control form-control-sm mr-3" style="width:140px">
              @foreach($availableMonths as $num => $nama)
                <option value="{{ $num }}" {{ ($summaryMetrics['filter_bulan'] ?? Carbon\Carbon::now()->month) == $num ? 'selected' : '' }}>
                  {{ $nama }}
                </option>
              @endforeach
            </select>

            <label class="mr-2 font-weight-bold text-sm text-secondary">Tahun:</label>
            <select name="tahun" class="form-control form-control-sm mr-3" style="width:110px">
              @foreach($availableYears as $year)
                <option value="{{ $year }}" {{ ($summaryMetrics['filter_tahun'] ?? Carbon\Carbon::now()->year) == $year ? 'selected' : '' }}>
                  {{ $year }}
                </option>
              @endforeach
            </select>

            <button type="submit" class="btn btn-sm btn-primary px-3">
              <i class="fa-solid fa-magnifying-glass mr-1"></i> Terapkan
            </button>
          </form>
        </div>
      </div>

      <!-- Collapse Form: Custom Rentang Tanggal -->
      <div class="collapse {{ ($summaryMetrics['periode_filter'] ?? '') === 'custom' ? 'show' : '' }} mb-3" id="collapseCustomTanggal">
        <div class="card card-body p-3 shadow-sm border-0" style="background:#f9fafb;border:1px solid #e5e7eb !important;">
          <form action="{{ route('beranda.index') }}" method="GET" class="form-inline d-flex flex-wrap align-items-center gap-2">
            <input type="hidden" name="periode" value="custom">
            <label class="mr-2 font-weight-bold text-sm text-secondary">Dari Tanggal:</label>
            <input type="date" name="start_date" value="{{ $summaryMetrics['custom_start'] ?? Carbon\Carbon::now()->startOfMonth()->toDateString() }}" class="form-control form-control-sm mr-3" required>

            <label class="mr-2 font-weight-bold text-sm text-secondary">Sampai Tanggal:</label>
            <input type="date" name="end_date" value="{{ $summaryMetrics['custom_end'] ?? Carbon\Carbon::now()->endOfMonth()->toDateString() }}" class="form-control form-control-sm mr-3" required>

            <button type="submit" class="btn btn-sm btn-primary px-3">
              <i class="fa-solid fa-magnifying-glass mr-1"></i> Terapkan
            </button>
          </form>
        </div>
      </div>

      <section class="kpi-grid">
        <article class="kpi-card">
          <div class="kpi-icon blue"><i class="fa-solid fa-building"></i></div>
          <div>
            <div class="kpi-label">Jumlah Project</div>
            <div class="kpi-value" style="color:#2563eb">{{ $summaryMetrics['jumlah_project'] ?? 0 }}</div>
          </div>
        </article>

        <article class="kpi-card">
          <div class="kpi-icon green"><i class="fa-solid fa-layer-group"></i></div>
          <div>
            <div class="kpi-label">Total Unit</div>
            <div class="kpi-value" style="color:#16a34a">{{ $summaryMetrics['total_unit'] ?? 0 }}</div>
            <div class="kpi-note"><span style="color:#2563eb;font-weight:600">{{ $summaryMetrics['unit_terjual'] ?? 0 }} Terjual</span> · {{ $summaryMetrics['unit_ready'] ?? 0 }} Ready</div>
          </div>
        </article>

        <article class="kpi-card">
          <div class="kpi-icon orange"><i class="fa-solid fa-wallet"></i></div>
          <div>
            <div class="kpi-label">Booking Fee / Bulanan</div>
            <div class="kpi-value money" style="color:#f97316">Rp {{ number_format($summaryMetrics['booking_fee_periode'] ?? 0, 0, ',', '.') }}</div>
            <div class="kpi-note">{{ $summaryMetrics['customer_periode'] ?? 0 }} customer terdaftar ({{ $summaryMetrics['label_periode'] ?? 'Bulan Ini' }})</div>
          </div>
        </article>

        <article class="kpi-card">
          <div class="kpi-icon purple"><i class="fa-regular fa-credit-card"></i></div>
          <div>
            <div class="kpi-label">Piutang</div>
            <div class="kpi-value money" style="color:#5b2cff">Rp {{ number_format($summaryMetrics['piutang'] ?? 0, 0, ',', '.') }}</div>
            <div class="kpi-note">Total {{ $summaryMetrics['piutang_customer'] ?? 0 }} customer</div>
          </div>
        </article>

        <article class="kpi-card">
          <div class="kpi-icon red"><i class="fa-regular fa-clock"></i></div>
          <div>
            <div class="kpi-label">Tagihan Tempo</div>
            <div class="kpi-value" style="color:#e11d48">{{ $summaryMetrics['tagihan_tempo_customer'] ?? 0 }} Customer</div>
            <div class="kpi-note">Total Rp {{ number_format($summaryMetrics['tagihan_tempo_total'] ?? 0, 0, ',', '.') }}</div>
          </div>
        </article>
      </section>

      <section class="panel">
        <div class="panel-body">
          <div class="section-head">
            <h2>Pipeline Penjualan <span>(Semua Project)</span></h2>
          </div>

          <div class="pipeline">
            <article class="pipeline-card c-red">
              <h3>Booking</h3>
              <div class="count">{{ $pipelineCounts['booking'] ?? 0 }}</div>
              <a class="pipeline-btn" href="{{ route('pengajuan-hold.index') }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-card c-teal">
              <h3>Pemberkasan Marketing</h3>
              <div class="count">{{ $pipelineCounts['marketing'] ?? 0 }}</div>
              <a class="pipeline-btn" href="{{ route('customer.index', ['id_status_progres' => 11]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-card c-cyan">
              <h3>Proses Admin</h3>
              <div class="count">{{ $pipelineCounts['sppr'] ?? 0 }}</div>
              <a class="pipeline-btn" href="{{ route('customer.index', ['id_status_progres' => 10]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-card c-green">
              <h3>Proses Bank</h3>
              <div class="count">{{ $pipelineCounts['wawancara'] ?? 0 }}</div>
              <a class="pipeline-btn" href="{{ route('customer.index', ['id_status_progres' => 7]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-card c-orange">
              <h3>SP3K</h3>
              <div class="count">{{ $pipelineCounts['acc_bank'] ?? 0 }}</div>
              <a class="pipeline-btn" href="{{ route('customer.index', ['id_status_progres' => 4]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-card c-pink">
              <h3>PPJB</h3>
              <div class="count">{{ $pipelineCounts['ppjb'] ?? 0 }}</div>
              <a class="pipeline-btn" href="{{ route('ppjb.index') }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-card c-purple">
              <h3>Akad</h3>
              <div class="count">{{ $pipelineCounts['akad'] ?? 0 }}</div>
              <a class="pipeline-btn" href="{{ route('customer.index', ['id_status_progres' => 3]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-card c-blue">
              <h3>BAST</h3>
              <div class="count">{{ $pipelineCounts['bast'] ?? 0 }}</div>
              <a class="pipeline-btn" href="{{ route('bast.index') }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>
          </div>

        </div>
      </section>

      <section class="panel">
        <div class="panel-body">
          <div class="section-head">
            <h2>Statistik per Project / Perumahan</h2>
          </div>

          <div class="projects-table-wrap">
            <table class="projects-table">
              <thead>
                <tr>
                  <th>Project / Perumahan</th>
                  <th>Booking</th>
                  <th>Pemberkasan Marketing</th>
                  <th>Proses Admin</th>
                  <th>Proses Bank</th>
                  <th>SP3K</th>
                  <th>PPJB</th>
                  <th>Akad</th>
                  <th>BAST</th>
                  <th style="width:85px;background:#f3f4f6">Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($projectStats as $project)
                  <tr>
                    <td>
                      <div class="project-info">
                        <div class="project-thumb"></div>
                        <div>
                          <div class="project-name">{{ $project['nama'] }}</div>
                          <div class="project-meta"><span class="code-pill">{{ $project['kode'] }}</span>{{ $project['total_unit'] }} Unit</div>
                        </div>
                      </div>
                    </td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 2]) }}" style="text-decoration:none"><span class="metric-number" style="color:#e11d48">{{ $project['booking'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 11]) }}" style="text-decoration:none"><span class="metric-number" style="color:#0284c7">{{ $project['marketing'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 10]) }}" style="text-decoration:none"><span class="metric-number" style="color:#0891b2">{{ $project['sppr'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 7]) }}" style="text-decoration:none"><span class="metric-number" style="color:#15803d">{{ $project['wawancara'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 4]) }}" style="text-decoration:none"><span class="metric-number" style="color:#c56a00">{{ $project['acc_bank'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 6]) }}" style="text-decoration:none"><span class="metric-number" style="color:#db2777">{{ $project['ppjb'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 3]) }}" style="text-decoration:none"><span class="metric-number" style="color:#5b21b6">{{ $project['akad'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 5]) }}" style="text-decoration:none"><span class="metric-number" style="color:#1d4ed8">{{ $project['bast'] }}</span></a></td>
                    <td style="background:#f9fafb"><a href="{{ route('customer.index', ['id_lokasi' => $project['id']]) }}" style="text-decoration:none"><span class="metric-number" style="color:#111827;font-weight:900">{{ $project['terjual'] }}</span></a></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center text-muted">Belum ada data project.</td>
                  </tr>
                @endforelse

                <tr class="table-total">
                  <td>Total Keseluruhan</td>
                  <td>{{ $projectTotals['booking'] ?? 0 }}</td>
                  <td>{{ $projectTotals['marketing'] ?? 0 }}</td>
                  <td>{{ $projectTotals['sppr'] ?? 0 }}</td>
                  <td>{{ $projectTotals['wawancara'] ?? 0 }}</td>
                  <td>{{ $projectTotals['acc_bank'] ?? 0 }}</td>
                  <td>{{ $projectTotals['ppjb'] ?? 0 }}</td>
                  <td>{{ $projectTotals['akad'] ?? 0 }}</td>
                  <td>{{ $projectTotals['bast'] ?? 0 }}</td>
                  <td style="color:#2563eb;font-weight:900">{{ $projectTotals['terjual'] ?? 0 }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- RESUME TIAP PROYEK (Executive Project Resume) -->
      <section class="panel" style="margin-top:16px">
        <div class="panel-body">
          <div class="section-head" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
            <div>
              <h2 style="margin:0;font-size:18px;font-weight:800;color:#1e293b">
                <i class="fa-solid fa-chart-pie text-primary mr-1"></i> Resume Tiap Proyek
              </h2>
              <span style="font-size:12px;color:#64748b">Ringkasan unit, realisasi penjualan, sisa stok, dan rincian progres fisik perumahan</span>
            </div>

            <div class="project-resume-tabs" style="display:flex;gap:8px;flex-wrap:wrap">
              @foreach($projectResumes as $key => $res)
                <button type="button" class="resume-tab-btn {{ $loop->first ? 'active' : '' }}" data-target="resume-pane-{{ $key }}">
                  <i class="fa-solid {{ $key == 'all' ? 'fa-layer-group' : 'fa-city' }} mr-1"></i> {{ $res['short_name'] }}
                </button>
              @endforeach
            </div>
          </div>

          @foreach($projectResumes as $key => $res)
            <div id="resume-pane-{{ $key }}" class="resume-pane" style="{{ $loop->first ? '' : 'display:none;' }}">
              <!-- Header info banner -->
              <div style="background:linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%);border:1px solid #bae6fd;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
                <div>
                  <h4 style="margin:0;font-size:15px;font-weight:800;color:#0f172a">
                    {{ $res['nama'] }} <span class="badge bg-primary" style="font-size:11px;margin-left:6px">{{ $res['badge'] }}</span>
                  </h4>
                  <small style="color:#475569">{{ $res['catatan'] }}</small>
                </div>
                <div style="display:flex;gap:16px;align-items:center">
                  <div style="text-align:right">
                    <span style="font-size:11px;color:#64748b;font-weight:600;display:block">Realisasi Terjual</span>
                    <strong style="font-size:16px;color:#16a34a">{{ $res['persentase_terjual'] }}%</strong>
                  </div>
                  <div style="width:120px;height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                    <div style="width:{{ $res['persentase_terjual'] }}%;height:100%;background:#16a34a;border-radius:4px"></div>
                  </div>
                </div>
              </div>

              <!-- 4 Utama KPI Box -->
              <div class="resume-kpi-grid">
                <div class="resume-kpi-box" style="border-left:4px solid #3b82f6">
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase">Total Unit</span>
                    <i class="fa-solid fa-cubes text-primary" style="font-size:16px;opacity:0.8"></i>
                  </div>
                  <div style="font-size:24px;font-weight:900;color:#1e293b;margin-top:6px">{{ $res['total_unit'] }} <small style="font-size:12px;font-weight:600;color:#64748b">Unit</small></div>
                  <span style="font-size:11px;color:#64748b">Kapasitas Peta Siteplan</span>
                </div>

                <div class="resume-kpi-box" style="border-left:4px solid #16a34a">
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase">Total Terjual</span>
                    <span class="badge bg-success" style="font-size:10px">{{ $res['persentase_terjual'] }}%</span>
                  </div>
                  <div style="font-size:24px;font-weight:900;color:#16a34a;margin-top:6px">{{ $res['total_terjual'] }} <small style="font-size:12px;font-weight:600;color:#64748b">Unit</small></div>
                  <span style="font-size:11px;color:#64748b">KPR Akad ({{ $res['kpr_akad'] }}) · Cash ({{ $res['terjual_cash'] }})</span>
                </div>

                <div class="resume-kpi-box" style="border-left:4px solid #eab308">
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase">Sedang On Proses</span>
                    <span class="badge bg-warning text-dark" style="font-size:10px">{{ $res['persentase_proses'] }}%</span>
                  </div>
                  <div style="font-size:24px;font-weight:900;color:#ca8a04;margin-top:6px">{{ $res['on_proses'] }} <small style="font-size:12px;font-weight:600;color:#64748b">Unit</small></div>
                  <span style="font-size:11px;color:#64748b">Fisik 80%-100%: <strong>{{ $res['total_fisik_proses'] }} Unit</strong></span>
                </div>

                <div class="resume-kpi-box" style="border-left:4px solid #ef4444">
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase">Sisa Unit Tersedia</span>
                    <span class="badge bg-danger" style="font-size:10px">{{ $res['persentase_sisa'] }}%</span>
                  </div>
                  <div style="font-size:24px;font-weight:900;color:#dc2626;margin-top:6px">{{ $res['sisa_unit'] }} <small style="font-size:12px;font-weight:600;color:#64748b">Unit</small></div>
                  <span style="font-size:11px;color:#64748b">Siap Dipasarkan / Booking</span>
                </div>
              </div>

              <!-- Rincian Unit On Proses & Progres Fisik -->
              <div style="margin-top:12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                  <span style="font-size:13px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:0.5px">
                    <i class="fa-solid fa-list-check mr-1 text-primary"></i> Rincian Unit Proses & Kesiapan Bangunan (80% - 100%)
                  </span>
                  <span style="font-size:11px;color:#64748b">Total Unit Proses: <strong>{{ $res['total_unit_proses'] }} Unit</strong> | Fisik Siap: <strong>{{ $res['total_fisik_proses'] }} Unit</strong></span>
                </div>

                <div class="resume-proc-grid">
                  @foreach($res['proses_items'] as $item)
                    <div class="resume-proc-card">
                      <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px;display:flex;align-items:center;justify-content:center;gap:5px">
                        <i class="fa-solid {{ $item['icon'] }}" style="color:{{ $item['color'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                      </div>
                      <div style="font-size:20px;font-weight:900;color:#1e293b;margin:4px 0">
                        {{ $item['unit'] }} <small style="font-size:11px;font-weight:600;color:#64748b">Unit</small>
                      </div>
                      <div style="font-size:10px;font-weight:700;color:#15803d;background:#dcfce7;border-radius:4px;padding:2px 6px;display:inline-block">
                        Fisik 80-100%: <strong>{{ $item['fisik'] }}</strong>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>

            </div>
          @endforeach
        </div>
      </section>

      <section class="bottom-grid" style="grid-template-columns: 1fr 1fr">
        <article class="panel">
          <div class="panel-body">
            <div class="section-head">
              <h2>Grafik Penjualan Bulanan <span>(Semua Project)</span></h2>
              <div style="display:flex;gap:10px;align-items:center">
                <div>
                  <label style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block">Tahun</label>
                  <select id="filterTahun" class="form-control" style="width:120px">
                    @foreach($availableYears as $year)
                      <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block">Status</label>
                  <select id="filterStatus" class="form-control" style="width:170px">
                    <option value="semua" selected>Semua Status</option>
                    <option value="marketing">Pemberkasan Marketing</option>
                    <option value="sppr">Proses Admin</option>
                    <option value="wawancara">Proses Bank</option>
                    <option value="sp3k">SP3K</option>
                    <option value="akad">Akad</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="chart-box">
              <canvas id="salesChart"></canvas>
            </div>
          </div>
        </article>

        <article class="panel">
          <div class="panel-body">
            <div class="section-head">
              <h2>Penjualan Marketing</h2>
            </div>

            <div class="marketing-list">
              @forelse ($marketingStats as $index => $marketing)
                <a href="{{ route('beranda.detail-customer-marketing', $marketing['id']) }}" class="marketing-item" style="text-decoration:none;color:inherit">
                  <span class="rank">{{ $index + 1 }}</span>
                  <span class="avatar">{{ $marketing['inisial'] }}</span>
                  <div class="item-main">
                    <div class="item-title">{{ $marketing['nama'] }}</div>
                    <div class="item-sub">{{ $marketing['kode'] }} · Marketing</div>
                  </div>
                  <div class="item-value">{{ $marketing['jumlah'] }} Unit</div>
                </a>
              @empty
                <div class="item-sub">Belum ada data marketing.</div>
              @endforelse
            </div>
          </div>
        </article>
      </section>

      <section class="panel" style="margin-top:16px">
        <div class="panel-body">
          <div class="section-head"><h2>Statistik Penggunaan Bank</h2></div>

          <div class="bank-list" style="max-height:250px">
            @forelse ($bankStats as $index => $bank)
              <div class="bank-item">
                <span class="rank">{{ $index + 1 }}</span>
                <div class="item-main">
                  <div class="item-title">{{ $bank['nama'] }}</div>
                  <div class="item-sub">Bank KPR</div>
                </div>
                <div class="item-value">{{ $bank['jumlah'] }} Nasabah</div>
                <div class="item-value">{{ $bank['persentase'] }}%</div>
              </div>
            @empty
              <div class="item-sub">Belum ada penggunaan bank.</div>
            @endforelse
          </div>
        </div>
      </section>

      <section class="panel" style="margin-top:16px">
        <div class="panel-body">
          <div class="section-head">
            <h2>Grafik Admin Pemberkasan</h2>
            <div style="display:flex;gap:10px;align-items:center">
              <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block">Tahun</label>
                <select id="filterTahunAdmin" class="form-control" style="width:120px">
                  @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block">Status</label>
                <select id="filterStatusAdmin" class="form-control" style="width:150px">
                  <option value="semua" selected>Semua</option>
                  <option value="wawancara">Proses Bank</option>
                  <option value="sp3k">SP3K</option>
                  <option value="akad">Akad</option>
                </select>
              </div>
            </div>
          </div>

          <div class="chart-box" style="height:350px">
            <canvas id="adminPemberkasanChart"></canvas>
          </div>
        </div>
      </section>

      <!-- REKAP DP & GRAFIK SUMBER PROSPEK (Sesuai Gambar 2 + Interaktif & Filter Bulan) -->
      <section class="panel" style="margin-top:16px">
        <div class="panel-body">
          <div class="section-head" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
            <div>
              <h2 style="margin:0;font-size:18px;font-weight:800;color:#1e293b">
                <i class="fa-solid fa-bullhorn text-primary mr-1"></i> Rekap DP & Grafik Sumber Prospek (Tahun 2026)
              </h2>
              <span style="font-size:12px;color:#64748b">Analisis efektivitas kanal promosi dan performa sumber prospek tahun 2026</span>
            </div>

            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
              <!-- Filter Bulan -->
              <div style="display:flex;align-items:center;gap:6px">
                <label style="font-size:12px;font-weight:700;color:#475569;margin:0">Bulan:</label>
                <select id="filterBulanSumber" class="form-control" style="width:170px;height:36px;font-size:13px;border-radius:8px">
                  <option value="semua" selected>Semua Bulan (YTD)</option>
                  <option value="1">Januari</option>
                  <option value="2">Februari</option>
                  <option value="3">Maret</option>
                  <option value="4">April</option>
                  <option value="5">Mei</option>
                  <option value="6">Juni</option>
                  <option value="7">Juli</option>
                  <option value="8">Agustus</option>
                  <option value="9">September</option>
                  <option value="10">Oktober</option>
                  <option value="11">November</option>
                  <option value="12">Desember</option>
                </select>
              </div>

              <!-- View Switcher Toggle -->
              <div class="btn-group" role="group" style="box-shadow:0 1px 3px rgba(0,0,0,0.08);border-radius:8px;overflow:hidden">
                <button type="button" class="btn btn-sm btn-primary" id="btnToggleChart" style="padding:6px 14px;font-size:12px;font-weight:700">
                  <i class="fa-solid fa-chart-column mr-1"></i> Grafik Batang
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleTable" style="padding:6px 14px;font-size:12px;font-weight:700">
                  <i class="fa-solid fa-table-cells mr-1"></i> Tabel Rekap
                </button>
              </div>
            </div>
          </div>

          <!-- Highlight Metric Badges -->
          <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:10px;margin-bottom:16px">
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px">
              <span style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase">Total Prospek (Periode)</span>
              <div style="font-size:20px;font-weight:900;color:#1e293b" id="spBadgeTotal">{{ $yoySummary['total_2026'] }} <small style="font-size:11px;font-weight:600;color:#64748b">Unit</small></div>
              <span style="font-size:10px;color:#16a34a;font-weight:600" id="spBadgeLabel">Tahun 2026 (Semua Bulan)</span>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px">
              <span style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase">Sumber Tertinggi</span>
              <div style="font-size:17px;font-weight:900;color:#2563eb" id="spBadgeTop">-</div>
              <span style="font-size:10px;color:#64748b">Penyumbang Terbesar</span>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px">
              <span style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase">Rata-rata per Bulan</span>
              <div style="font-size:20px;font-weight:900;color:#059669">{{ $yoySummary['rata_2026'] }} <small style="font-size:11px;font-weight:600;color:#64748b">Unit/Bln</small></div>
              <span style="font-size:10px;color:#64748b">Berdasarkan Bulan Aktif</span>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px">
              <span style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase">Pertumbuhan YoY (vs 2025)</span>
              <div style="font-size:20px;font-weight:900;color:#16a34a">+{{ $yoySummary['growth'] }} <small style="font-size:11px;font-weight:600;color:#16a34a">(+{{ $yoySummary['growth_pct'] }}%)</small></div>
              <span style="font-size:10px;color:#16a34a;font-weight:600"><i class="fa-solid fa-arrow-trend-up mr-1"></i> {{ $yoySummary['total_2025'] }} (2025) &rarr; {{ $yoySummary['total_2026'] }} (2026)</span>
            </div>
          </div>

          <!-- Chart View Container -->
          <div id="wrapperChartSumber" class="chart-box" style="height:350px">
            <canvas id="sumberProspekChart"></canvas>
          </div>

          <!-- Table View Container (Persis seperti Gambar 2, Modern & Responsif) -->
          <div id="wrapperTableSumber" style="display:none;overflow-x:auto;margin-top:10px">
            <table class="table-sumber-matrix">
              <thead>
                <tr style="background:#e0f2fe">
                  <th rowspan="2" style="width:36px">NO</th>
                  <th rowspan="2" style="text-align:left;min-width:160px">MARKETING / SUMBER</th>
                  <th colspan="12" style="background:#bae6fd;color:#0369a1">BULAN TAHUN 2026</th>
                  <th rowspan="2" style="background:#fed7aa;color:#9a3412">TOTAL</th>
                  <th rowspan="2" style="background:#fef08a;color:#854d0e">RATA-RATA</th>
                  <th colspan="4" style="background:#fbcfe8;color:#9d174d">YEAR ON YEAR (2025 vs 2026)</th>
                </tr>
                <tr>
                  <th>JAN</th><th>FEB</th><th>MAR</th><th>APR</th><th>MEI</th><th>JUN</th>
                  <th>JUL</th><th>AUG</th><th>SEP</th><th>OKT</th><th>NOV</th><th>DES</th>
                  <th style="background:#fdf2f8">2025</th>
                  <th style="background:#fdf2f8">2026</th>
                  <th style="background:#fdf2f8">GROWTH</th>
                  <th style="background:#fdf2f8">RATA-RATA</th>
                </tr>
              </thead>
              <tbody>
                @php $no = 1; @endphp
                @foreach($sumberMatrix as $srcName => $data)
                  <tr>
                    <td>{{ $no++ }}</td>
                    <td style="text-align:left;font-weight:700">
                      {{ $srcName }}
                    </td>
                    <td>{{ $data['jan'] ?: '-' }}</td>
                    <td>{{ $data['feb'] ?: '-' }}</td>
                    <td>{{ $data['mar'] ?: '-' }}</td>
                    <td>{{ $data['apr'] ?: '-' }}</td>
                    <td>{{ $data['mei'] ?: '-' }}</td>
                    <td>{{ $data['jun'] ?: '-' }}</td>
                    <td style="{{ $data['jul'] > 0 ? 'background:#f0fdf4;font-weight:700' : '' }}">{{ $data['jul'] ?: '-' }}</td>
                    <td>{{ $data['aug'] ?: '-' }}</td>
                    <td>{{ $data['sep'] ?: '-' }}</td>
                    <td>{{ $data['okt'] ?: '-' }}</td>
                    <td>{{ $data['nov'] ?: '-' }}</td>
                    <td>{{ $data['des'] ?: '-' }}</td>
                    <td style="font-weight:800;color:#0f172a;background:#fff7ed">{{ $data['y2026'] }}</td>
                    <td style="font-weight:700;color:#0f172a;background:#fefce8">{{ number_format($data['rata_rata'], 1) }}</td>
                    <td>{{ $data['y2025'] }}</td>
                    <td style="font-weight:700">{{ $data['y2026'] }}</td>
                    <td style="font-weight:800;color:{{ $data['growth'] >= 0 ? '#16a34a' : '#dc2626' }}">
                      {{ $data['growth'] > 0 ? '+' : '' }}{{ $data['growth'] }}
                    </td>
                    <td style="font-weight:700;color:{{ $data['growth_rata'] >= 0 ? '#16a34a' : '#dc2626' }}">
                      {{ $data['growth_rata'] > 0 ? '+' : '' }}{{ number_format($data['growth_rata'], 1) }}
                    </td>
                  </tr>
                @endforeach
                <tr class="total-row">
                  <td colspan="2" style="text-align:center;font-weight:900">TOTAL</td>
                  @foreach(range(1, 12) as $m)
                    <td style="font-weight:900;color:#1e40af">{{ $monthlyTotals[$m] ?? 0 }}</td>
                  @endforeach
                  <td style="font-weight:900;color:#c2410c;background:#fed7aa">{{ $yoySummary['total_2026'] }}</td>
                  <td style="font-weight:900;color:#854d0e;background:#fef08a">{{ $yoySummary['rata_2026'] }}</td>
                  <td style="font-weight:900">{{ $yoySummary['total_2025'] }}</td>
                  <td style="font-weight:900">{{ $yoySummary['total_2026'] }}</td>
                  <td style="font-weight:900;color:#16a34a">+{{ $yoySummary['growth'] }}</td>
                  <td style="font-weight:900;color:#16a34a">+{{ $yoySummary['growth_rata'] }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </div>
  </main>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        let salesChart;
        const labelsBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        function initChart(data) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            if (salesChart) salesChart.destroy();

            salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labelsBulan,
                    datasets: [{
                        label: 'Penjualan',
                        data: data,
                        backgroundColor: 'rgba(91, 44, 255, 0.7)',
                        borderColor: 'rgba(91, 44, 255, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function(evt, elements) {
                        if (elements.length > 0) {
                            var index = elements[0].index;
                            var bulan = index + 1;
                            var tahun = $('#filterTahun').val();
                            var status = $('#filterStatus').val();
                            var url = '{{ route("beranda.detail-grafik") }}?tahun=' + tahun + '&bulan=' + bulan + '&status=' + status;
                            window.open(url, '_blank');
                        }
                    },
                    onHover: function(evt, elements) {
                        evt.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.parsed.y + ' unit';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, precision: 0 },
                            grid: { color: '#f0f1f4' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        function loadChartData() {
            const tahun = $('#filterTahun').val();
            const status = $('#filterStatus').val();

            $.ajax({
                url: '{{ route("beranda.chart-data") }}',
                type: 'GET',
                data: { tahun: tahun, status: status },
                success: function(response) {
                    initChart(response.data);
                }
            });
        }

        $(document).ready(function() {
            $('#filterTahun').select2({
                theme: "bootstrap4",
                minimumResultsForSearch: Infinity,
            });
            $('#filterStatus').select2({
                theme: "bootstrap4",
                minimumResultsForSearch: Infinity,
            });
            $('#filterTahunAdmin').select2({
                theme: "bootstrap4",
                minimumResultsForSearch: Infinity,
            });
            $('#filterStatusAdmin').select2({
                theme: "bootstrap4",
                minimumResultsForSearch: Infinity,
            });
            $('#filterBulanSumber').select2({
                theme: "bootstrap4",
                minimumResultsForSearch: Infinity,
            });

            // Resume Proyek Tabs
            $(document).on('click', '.resume-tab-btn', function() {
                $('.resume-tab-btn').removeClass('active');
                $(this).addClass('active');
                var target = $(this).data('target');
                $('.resume-pane').hide();
                $('#' + target).fadeIn(200);
            });

            // Sumber Prospek Toggle Chart / Table
            $('#btnToggleChart').on('click', function() {
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnToggleTable').removeClass('btn-primary').addClass('btn-outline-primary');
                $('#wrapperChartSumber').show();
                $('#wrapperTableSumber').hide();
            });

            $('#btnToggleTable').on('click', function() {
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnToggleChart').removeClass('btn-primary').addClass('btn-outline-primary');
                $('#wrapperChartSumber').hide();
                $('#wrapperTableSumber').fadeIn(200);
            });

            loadChartData();

            $('#filterTahun, #filterStatus').on('change', function() {
                loadChartData();
            });

            loadAdminPemberkasanChart();
            $('#filterTahunAdmin, #filterStatusAdmin').on('change', function() {
                loadAdminPemberkasanChart();
            });

            loadSumberProspekChart();
            $('#filterBulanSumber').on('change', function() {
                loadSumberProspekChart();
            });
        });

        let sumberProspekChart;
        const spColors = [
            'rgba(59, 130, 246, 0.75)',
            'rgba(239, 68, 68, 0.75)',
            'rgba(34, 197, 94, 0.75)',
            'rgba(249, 115, 22, 0.75)',
            'rgba(168, 85, 247, 0.75)',
            'rgba(236, 72, 153, 0.75)',
            'rgba(20, 184, 166, 0.75)',
            'rgba(234, 179, 8, 0.75)',
            'rgba(99, 102, 241, 0.75)',
        ];
        const spBorders = spColors.map(c => c.replace('0.75', '1'));

        function loadSumberProspekChart() {
            const bulan = $('#filterBulanSumber').val();
            $.ajax({
                url: '{{ route("beranda.sumber-prospek-data") }}',
                type: 'GET',
                data: { bulan: bulan },
                success: function(response) {
                    renderSumberProspekChart(response.labels, response.data);
                    if (response.total !== undefined) {
                        $('#spBadgeTotal').html(response.total + ' <small style="font-size:11px;font-weight:600;color:#64748b">Unit</small>');
                    }
                    if (response.top_source !== undefined) {
                        $('#spBadgeTop').text(response.top_source);
                    }
                    if (response.periode_label !== undefined) {
                        $('#spBadgeLabel').text(response.periode_label);
                    }
                }
            });
        }

        function renderSumberProspekChart(labels, data) {
            const ctx = document.getElementById('sumberProspekChart').getContext('2d');
            if (sumberProspekChart) sumberProspekChart.destroy();

            sumberProspekChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah',
                        data: data,
                        backgroundColor: spColors,
                        borderColor: spBorders,
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.parsed.y + ' data';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, precision: 0 },
                            grid: { color: '#f0f1f4' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 0,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }

        let adminPemberkasanChart;
        const apColors = [
            'rgba(59, 130, 246, 0.7)',
            'rgba(239, 68, 68, 0.7)',
            'rgba(34, 197, 94, 0.7)',
            'rgba(249, 115, 22, 0.7)',
            'rgba(168, 85, 247, 0.7)',
            'rgba(236, 72, 153, 0.7)',
            'rgba(20, 184, 166, 0.7)',
            'rgba(234, 179, 8, 0.7)',
        ];
        const apBorders = apColors.map(c => c.replace('0.7', '1'));

        function loadAdminPemberkasanChart() {
            const tahun = $('#filterTahunAdmin').val();
            const status = $('#filterStatusAdmin').val();

            $.ajax({
                url: '{{ route("beranda.admin-pemberkasan-data") }}',
                type: 'GET',
                data: { tahun: tahun, status: status },
                success: function(response) {
                    renderAdminPemberkasanChart(response.labels, response.data, response.ids);
                }
            });
        }

        function renderAdminPemberkasanChart(labels, data, ids) {
            const ctx = document.getElementById('adminPemberkasanChart').getContext('2d');
            if (adminPemberkasanChart) adminPemberkasanChart.destroy();

            adminPemberkasanChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah',
                        data: data,
                        backgroundColor: apColors.slice(0, labels.length),
                        borderColor: apBorders.slice(0, labels.length),
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function(evt, elements) {
                        if (elements.length > 0) {
                            var index = elements[0].index;
                            var id = ids[index];
                            if (id) {
                                window.location.href = '{{ route("beranda.detail-customer-admin-pemberkasan", "__ID__") }}'.replace('__ID__', id);
                            }
                        }
                    },
                    onHover: function(evt, elements) {
                        evt.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.parsed.y + ' unit';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, precision: 0 },
                            grid: { color: '#f0f1f4' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 0,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }


    </script>
    @endpush
@endsection



