@extends('admin.layout_admin')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap');

  :root {
    --dash-bg: #f8fafc;
    --dash-surface: #ffffff;
    --dash-surface-glass: rgba(255, 255, 255, 0.88);
    --dash-card-border: rgba(226, 232, 240, 0.9);
    --dash-text-main: #0f172a;
    --dash-text-muted: #64748b;
    --dash-text-light: #94a3b8;
    
    /* Neon & Tech Accents */
    --neon-blue: #2563eb;
    --neon-blue-glow: rgba(37, 99, 235, 0.18);
    --neon-cyan: #06b6d4;
    --neon-cyan-glow: rgba(6, 182, 212, 0.18);
    --neon-purple: #7c3aed;
    --neon-purple-glow: rgba(124, 58, 237, 0.18);
    --neon-emerald: #10b981;
    --neon-emerald-glow: rgba(16, 185, 129, 0.18);
    --neon-amber: #f59e0b;
    --neon-amber-glow: rgba(245, 158, 11, 0.18);
    --neon-rose: #f43f5e;
    --neon-rose-glow: rgba(244, 63, 94, 0.18);

    --dash-radius-lg: 20px;
    --dash-radius-md: 14px;
    --dash-radius-sm: 10px;
    --dash-shadow-sm: 0 2px 8px -1px rgba(15, 23, 42, 0.04);
    --dash-shadow-md: 0 10px 25px -3px rgba(15, 23, 42, 0.05), 0 4px 10px -2px rgba(15, 23, 42, 0.02);
    --dash-shadow-glow: 0 12px 30px -4px rgba(99, 102, 241, 0.12);
  }

  * { box-sizing: border-box; }

  .dashboard-shell {
    margin: 0;
    font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
    background: radial-gradient(circle at 10% 8%, rgba(99, 102, 241, 0.035) 0%, transparent 45%),
                radial-gradient(circle at 90% 92%, rgba(6, 182, 212, 0.035) 0%, transparent 45%),
                var(--dash-bg);
    color: var(--dash-text-main);
    width: 100%;
    min-height: 100vh;
    padding: 26px 30px;
    letter-spacing: -0.015em;
  }

  .dashboard-wrap {
    max-width: 1600px;
    margin: 0 auto;
  }

  /* Futuristic Top Header */
  .topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding: 8px 4px;
  }

  .title-hub {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .title-hub .greeting-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #4f46e5;
    background: rgba(99, 102, 241, 0.08);
    padding: 4px 12px;
    border-radius: 9999px;
    width: fit-content;
    border: 1px solid rgba(99, 102, 241, 0.2);
  }

  .pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulseLive 2s infinite cubic-bezier(0.66, 0, 0, 1);
  }

  @keyframes pulseLive {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }

  .title-hub h1 {
    margin: 4px 0 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.2;
    color: var(--dash-text-main);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .title-hub p {
    margin: 2px 0 0;
    color: var(--dash-text-muted);
    font-size: 13.5px;
  }

  .top-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .btn-futuristic-sync {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff !important;
    border: none;
    border-radius: 12px;
    padding: 10px 18px;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.28);
    transition: all 0.25s ease;
    text-decoration: none !important;
    position: relative;
    overflow: hidden;
  }

  .btn-futuristic-sync::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(60deg, transparent, rgba(255,255,255,0.2), transparent);
    transform: rotate(30deg);
    transition: all 0.6s ease;
    opacity: 0;
  }

  .btn-futuristic-sync:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.42);
  }

  .btn-futuristic-sync:hover::after {
    opacity: 1;
    transform: rotate(30deg) translate(20%, 20%);
  }

  .icon-pill-btn, .date-pill-btn {
    background: var(--dash-surface);
    border: 1px solid var(--dash-card-border);
    color: #475569;
    border-radius: 12px;
    min-height: 42px;
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: var(--dash-shadow-sm);
    transition: all 0.2s ease;
  }

  .icon-pill-btn {
    width: 42px;
    padding: 0;
    justify-content: center;
    cursor: pointer;
  }

  .icon-pill-btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1e293b;
    transform: translateY(-1px);
  }

  .date-pill-btn {
    cursor: default;
    background: rgba(255, 255, 255, 0.9);
  }

  /* Filter Periode Bar - Glassmorphism */
  .periode-filter-container {
    background: var(--dash-surface-glass);
    backdrop-filter: blur(12px);
    border: 1px solid var(--dash-card-border);
    border-radius: var(--dash-radius-md);
    padding: 12px 18px;
    margin-bottom: 22px;
    box-shadow: var(--dash-shadow-sm);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
  }

  .filter-label-group {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .filter-label-group .indicator {
    font-size: 12px;
    font-weight: 700;
    color: var(--dash-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .filter-pill-nav {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 12px;
    gap: 3px;
  }

  .filter-pill-nav .pill-btn {
    border: none;
    background: transparent;
    color: #475569;
    padding: 6px 14px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
  }

  .filter-pill-nav .pill-btn:hover {
    color: #1e293b;
    background: rgba(255, 255, 255, 0.6);
  }

  .filter-pill-nav .pill-btn.active {
    background: #ffffff;
    color: #2563eb;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  }

  .active-filter-badge {
    background: rgba(37, 99, 235, 0.08);
    border: 1px solid rgba(37, 99, 235, 0.2);
    border-radius: 9999px;
    padding: 6px 14px;
    font-size: 12px;
    color: #1d4ed8;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  /* 5 KPI Metric Cards - Futuristic Cards */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 22px;
  }

  .kpi-card {
    background: var(--dash-surface-glass);
    backdrop-filter: blur(14px);
    border: 1px solid var(--dash-card-border);
    border-radius: var(--dash-radius-md);
    padding: 20px 18px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--dash-shadow-sm);
    min-height: 122px;
    min-width: 0;
    position: relative;
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: transparent;
    transition: all 0.3s ease;
  }

  .kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--dash-shadow-md);
    border-color: rgba(148, 163, 184, 0.4);
  }

  .kpi-card.kpi-blue:hover::before { background: linear-gradient(90deg, #38bdf8, #2563eb); }
  .kpi-card.kpi-green:hover::before { background: linear-gradient(90deg, #34d399, #10b981); }
  .kpi-card.kpi-orange:hover::before { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
  .kpi-card.kpi-purple:hover::before { background: linear-gradient(90deg, #a78bfa, #7c3aed); }
  .kpi-card.kpi-red:hover::before { background: linear-gradient(90deg, #fb7185, #f43f5e); }

  .kpi-icon-box {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    color: #ffffff;
    font-size: 20px;
    flex: 0 0 auto;
    position: relative;
  }

  .kpi-icon-box.blue {
    background: linear-gradient(135deg, #38bdf8 0%, #2563eb 100%);
    box-shadow: 0 6px 16px -2px rgba(37, 99, 235, 0.3);
  }
  .kpi-icon-box.green {
    background: linear-gradient(135deg, #34d399 0%, #059669 100%);
    box-shadow: 0 6px 16px -2px rgba(5, 150, 105, 0.3);
  }
  .kpi-icon-box.orange {
    background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
    box-shadow: 0 6px 16px -2px rgba(217, 119, 6, 0.3);
  }
  .kpi-icon-box.purple {
    background: linear-gradient(135deg, #a78bfa 0%, #6d28d9 100%);
    box-shadow: 0 6px 16px -2px rgba(109, 40, 217, 0.3);
  }
  .kpi-icon-box.red {
    background: linear-gradient(135deg, #fb7185 0%, #e11d48 100%);
    box-shadow: 0 6px 16px -2px rgba(225, 29, 72, 0.3);
  }

  .kpi-info {
    min-width: 0;
    flex: 1;
  }

  .kpi-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--dash-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .kpi-num {
    font-size: clamp(20px, 1.4vw, 25px);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 4px;
    color: var(--dash-text-main);
    letter-spacing: -0.02em;
  }

  .kpi-num.money {
    font-size: clamp(16px, 1.15vw, 21px);
    letter-spacing: -0.03em;
  }

  .kpi-desc {
    font-size: 11.5px;
    color: var(--dash-text-light);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Futuristic Panel Cards */
  .panel {
    background: var(--dash-surface-glass);
    backdrop-filter: blur(14px);
    border: 1px solid var(--dash-card-border);
    border-radius: var(--dash-radius-lg);
    box-shadow: var(--dash-shadow-sm);
    margin-bottom: 22px;
    overflow: hidden;
    transition: all 0.2s ease;
  }

  .panel-body {
    padding: 24px;
  }

  .section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
  }

  .section-head h2 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--dash-text-main);
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .section-head h2 span {
    color: var(--dash-text-muted);
    font-size: 13px;
    font-weight: 500;
  }

  /* Pipeline Penjualan - Connected Futuristic Stepper */
  .pipeline-track {
    display: grid;
    grid-template-columns: repeat(7, minmax(135px, 1fr));
    gap: 14px;
    overflow-x: auto;
    padding: 4px 4px 12px;
  }

  .pipeline-node {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--dash-radius-md);
    padding: 16px 12px;
    text-align: center;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 175px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .pipeline-node:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px -2px rgba(15, 23, 42, 0.08);
  }

  .pipeline-step-badge {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 6px;
    width: fit-content;
    margin: 0 auto 8px;
  }

  .pipeline-node h3 {
    margin: 0 0 10px;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    line-height: 1.3;
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .pipeline-node .count-display {
    font-size: 32px;
    font-weight: 900;
    letter-spacing: -0.03em;
    margin-bottom: 14px;
  }

  .pipeline-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 32px;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 700;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: 1px solid currentColor;
  }

  .pipeline-action-btn i {
    transition: transform 0.2s ease;
  }

  .pipeline-action-btn:hover i {
    transform: translateX(3px);
  }

  /* Node Color Variants */
  .p-booking { border-top: 3px solid #f43f5e; }
  .p-booking .pipeline-step-badge { background: #ffe4e6; color: #e11d48; }
  .p-booking .count-display { color: #e11d48; }
  .p-booking .pipeline-action-btn { color: #e11d48; background: #fff1f2; }
  .p-booking .pipeline-action-btn:hover { background: #e11d48; color: #fff; }

  .p-marketing { border-top: 3px solid #0284c7; }
  .p-marketing .pipeline-step-badge { background: #e0f2fe; color: #0284c7; }
  .p-marketing .count-display { color: #0284c7; }
  .p-marketing .pipeline-action-btn { color: #0284c7; background: #f0f9ff; }
  .p-marketing .pipeline-action-btn:hover { background: #0284c7; color: #fff; }

  .p-admin { border-top: 3px solid #0891b2; }
  .p-admin .pipeline-step-badge { background: #cffafe; color: #0891b2; }
  .p-admin .count-display { color: #0891b2; }
  .p-admin .pipeline-action-btn { color: #0891b2; background: #ecfeff; }
  .p-admin .pipeline-action-btn:hover { background: #0891b2; color: #fff; }

  .p-bank { border-top: 3px solid #10b981; }
  .p-bank .pipeline-step-badge { background: #d1fae5; color: #059669; }
  .p-bank .count-display { color: #059669; }
  .p-bank .pipeline-action-btn { color: #059669; background: #ecfdf5; }
  .p-bank .pipeline-action-btn:hover { background: #059669; color: #fff; }

  .p-sp3k { border-top: 3px solid #f59e0b; }
  .p-sp3k .pipeline-step-badge { background: #fef3c7; color: #d97706; }
  .p-sp3k .count-display { color: #d97706; }
  .p-sp3k .pipeline-action-btn { color: #d97706; background: #fffbeb; }
  .p-sp3k .pipeline-action-btn:hover { background: #d97706; color: #fff; }

  .p-ppjb { border-top: 3px solid #ec4899; }
  .p-ppjb .pipeline-step-badge { background: #fce7f3; color: #db2777; }
  .p-ppjb .count-display { color: #db2777; }
  .p-ppjb .pipeline-action-btn { color: #db2777; background: #fdf2f8; }
  .p-ppjb .pipeline-action-btn:hover { background: #db2777; color: #fff; }

  .p-akad { border-top: 3px solid #7c3aed; }
  .p-akad .pipeline-step-badge { background: #ede9fe; color: #6d28d9; }
  .p-akad .count-display { color: #6d28d9; }
  .p-akad .pipeline-action-btn { color: #6d28d9; background: #f5f3ff; }
  .p-akad .pipeline-action-btn:hover { background: #6d28d9; color: #fff; }

  .p-bast { border-top: 3px solid #3b82f6; }
  .p-bast .pipeline-step-badge { background: #dbeafe; color: #1d4ed8; }
  .p-bast .count-display { color: #1d4ed8; }
  .p-bast .pipeline-action-btn { color: #1d4ed8; background: #eff6ff; }
  .p-bast .pipeline-action-btn:hover { background: #1d4ed8; color: #fff; }

  /* Modern Projects Table */
  .projects-table-wrap {
    overflow-x: auto;
    border-radius: var(--dash-radius-md);
    border: 1px solid #e2e8f0;
  }

  .projects-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 880px;
  }

  .projects-table th {
    padding: 13px 12px;
    background: #f8fafc;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
    text-align: center;
  }

  .projects-table th:first-child {
    text-align: left;
    padding-left: 18px;
  }

  .projects-table td {
    padding: 14px 12px;
    border-bottom: 1px solid #f1f5f9;
    text-align: center;
    vertical-align: middle;
    font-size: 13px;
    background: #ffffff;
    transition: background 0.15s ease;
  }

  .projects-table tr:hover td {
    background: #f8fafc;
  }

  .projects-table td:first-child {
    text-align: left;
    padding-left: 18px;
  }

  .project-item-group {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .project-avatar-badge {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #4338ca;
    display: grid;
    place-items: center;
    font-size: 16px;
    flex: 0 0 auto;
    border: 1px solid #c7d2fe;
  }

  .project-name-text {
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 3px;
    font-size: 13.5px;
  }

  .project-tag-pill {
    display: inline-flex;
    padding: 2px 7px;
    border-radius: 5px;
    background: #e0f2fe;
    color: #0284c7;
    font-size: 10.5px;
    font-weight: 700;
    margin-right: 6px;
  }

  .table-metric-badge {
    font-weight: 800;
    font-size: 15px;
    transition: transform 0.15s ease;
    display: inline-block;
  }

  .table-metric-badge:hover {
    transform: scale(1.15);
  }

  .projects-table tr.table-total td {
    background: #f8fafc !important;
    font-weight: 900;
    color: #0f172a;
    border-top: 2px solid #cbd5e1;
    font-size: 13.5px;
  }

  /* Resume Proyek Modern Tabs & Cards */
  .resume-tab-btn {
    padding: 8px 18px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
  }

  .resume-tab-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #cbd5e1;
  }

  .resume-tab-btn.active {
    background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
    border-color: transparent;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
  }

  .resume-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px;
  }

  .resume-kpi-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--dash-radius-md);
    padding: 16px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
  }

  .resume-kpi-box:hover {
    transform: translateY(-2px);
    box-shadow: var(--dash-shadow-sm);
  }

  .resume-proc-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
  }

  .resume-proc-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 12px;
    text-align: center;
    transition: all 0.2s ease;
  }

  .resume-proc-card:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
  }

  /* Bottom Grid 2 Columns */
  .bottom-grid-2col {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 18px;
    margin-bottom: 22px;
  }

  .chart-canvas-box {
    height: 320px;
    position: relative;
  }

  /* Leaderboard Marketing & Bank Lists */
  .marketing-list, .bank-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 350px;
    overflow-y: auto;
    padding-right: 4px;
  }

  .marketing-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    transition: all 0.2s ease;
    text-decoration: none !important;
    color: inherit;
  }

  .marketing-item:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  }

  .rank-badge {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #475569;
    display: grid;
    place-items: center;
    font-size: 11px;
    font-weight: 800;
  }

  .rank-badge.top-1 { background: #fef08a; color: #854d0e; }
  .rank-badge.top-2 { background: #e2e8f0; color: #334155; }
  .rank-badge.top-3 { background: #fed7aa; color: #9a3412; }

  .user-avatar-initials {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #c7d2fe 0%, #a5b4fc 100%);
    color: #3730a3;
    display: grid;
    place-items: center;
    font-weight: 800;
    font-size: 13px;
    flex: 0 0 auto;
  }

  .bank-item-row {
    display: grid;
    grid-template-columns: 28px 1fr 90px 60px;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    transition: all 0.2s ease;
  }

  .bank-item-row:hover {
    background: #ffffff;
    border-color: #cbd5e1;
  }

  /* Table Sumber Matrix (YoY 2026 vs 2025) */
  .table-sumber-matrix {
    width: 100%;
    font-size: 12px;
    border-collapse: collapse;
  }

  .table-sumber-matrix th {
    background: #f8fafc;
    padding: 10px 8px;
    font-weight: 700;
    font-size: 11px;
    color: #475569;
    border: 1px solid #e2e8f0;
    text-align: center;
    white-space: nowrap;
  }

  .table-sumber-matrix td {
    padding: 9px 8px;
    border: 1px solid #e2e8f0;
    text-align: center;
    color: #334155;
    background: #ffffff;
  }

  .table-sumber-matrix tr:hover td {
    background: #f8fafc;
  }

  .table-sumber-matrix .total-row td {
    background: #eff6ff !important;
    font-weight: 800;
    border-top: 2px solid #bfdbfe;
  }

  /* Responsiveness */
  @media (max-width: 1300px) {
    .kpi-grid { grid-template-columns: repeat(3, 1fr); }
    .bottom-grid-2col { grid-template-columns: 1fr; }
  }

  @media (max-width: 992px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .resume-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .resume-proc-grid { grid-template-columns: repeat(2, 1fr); }
  }

  @media (max-width: 640px) {
    .dashboard-shell { padding: 14px; }
    .topbar { flex-direction: column; align-items: flex-start; }
    .kpi-grid { grid-template-columns: 1fr; }
    .resume-kpi-grid { grid-template-columns: 1fr; }
    .resume-proc-grid { grid-template-columns: 1fr; }
    .panel-body { padding: 16px; }
  }

  /* Dark Mode Ultra-Clean High-Tech */
  body.dark-mode .dashboard-shell {
    background: #090d16;
    color: #e2e8f0;
  }

  body.dark-mode .panel,
  body.dark-mode .kpi-card,
  body.dark-mode .periode-filter-container,
  body.dark-mode .pipeline-node,
  body.dark-mode .resume-kpi-box,
  body.dark-mode .marketing-item,
  body.dark-mode .bank-item-row {
    background: #111827 !important;
    border-color: #1f2937 !important;
    color: #e2e8f0 !important;
  }

  body.dark-mode .title-hub h1,
  body.dark-mode .kpi-num,
  body.dark-mode .project-name-text,
  body.dark-mode .pipeline-node h3 {
    color: #f1f5f9 !important;
  }

  body.dark-mode .projects-table th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-color: #1f2937 !important;
  }

  body.dark-mode .projects-table td {
    background: #111827 !important;
    border-color: #1f2937 !important;
    color: #cbd5e1 !important;
  }

  body.dark-mode .projects-table tr.table-total td {
    background: #1e293b !important;
    color: #f1f5f9 !important;
  }

  body.dark-mode .resume-proc-card {
    background: #0f172a !important;
    border-color: #1f2937 !important;
    color: #e2e8f0 !important;
  }

  body.dark-mode .table-sumber-matrix th {
    background: #0f172a !important;
    border-color: #1f2937 !important;
    color: #94a3b8 !important;
  }

  body.dark-mode .table-sumber-matrix td {
    background: #111827 !important;
    border-color: #1f2937 !important;
    color: #e2e8f0 !important;
  }

  body.dark-mode .table-sumber-matrix .total-row td {
    background: #1e1b4b !important;
  }

  body.dark-mode .filter-pill-nav {
    background: #1e293b;
  }

  body.dark-mode .filter-pill-nav .pill-btn {
    color: #94a3b8;
  }

  body.dark-mode .filter-pill-nav .pill-btn.active {
    background: #334155;
    color: #60a5fa;
  }
</style>

<div class="content-wrapper">
  <main class="dashboard-shell">
    <div class="dashboard-wrap">

      <!-- TOP HUB HEADER -->
      <header class="topbar">
        <div class="title-hub">
          <div class="greeting-badge">
            <span class="pulse-dot"></span> Real Estate Executive Hub
          </div>
          <h1>Selamat Datang, {{ $username ?? 'Admin' }}</h1>
          <p>Ringkasan performa penjualan dan monitoring unit aktif: <strong class="text-primary">{{ $summaryMetrics['label_periode'] ?? 'Bulan Ini' }}</strong></p>
        </div>

        <div class="top-actions">
          <a href="{{ route('admin.sync-excel') }}" class="btn-futuristic-sync" onclick="return confirm('Jalankan proses sinkronisasi data dari Excel ke database?')">
            <i class="fa-solid fa-cloud-arrow-down"></i> Sinkronkan Data Excel
          </a>
          <button class="icon-pill-btn" onclick="Swal.fire({title:'Status Sistem', text:'Sistem dan database beroperasi optimal', icon:'success', confirmButtonColor:'#2563eb'})" title="Status Notifikasi">
            <i class="fa-regular fa-bell"></i>
          </button>
          <div class="date-pill-btn">
            <i class="fa-regular fa-calendar-days text-primary"></i> {{ Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}
          </div>
        </div>
      </header>

      <!-- FILTER PERIODE BAR -->
      <section class="periode-filter-container">
        <div class="filter-label-group">
          <span class="indicator"><i class="fa-solid fa-sliders text-primary"></i> Filter Periode:</span>
          
          <nav class="filter-pill-nav">
            <a href="{{ route('beranda.index', ['periode' => 'bulan_ini']) }}" class="pill-btn {{ ($summaryMetrics['periode_filter'] ?? 'bulan_ini') === 'bulan_ini' ? 'active' : '' }}">
              Bulan Ini
            </a>
            <a href="{{ route('beranda.index', ['periode' => 'bulan_kemarin']) }}" class="pill-btn {{ ($summaryMetrics['periode_filter'] ?? '') === 'bulan_kemarin' ? 'active' : '' }}">
              Bulan Kemarin
            </a>
            <button type="button" class="pill-btn {{ ($summaryMetrics['periode_filter'] ?? '') === 'pilih_bulan' ? 'active' : '' }}" data-toggle="collapse" data-target="#collapsePilihBulan">
              <i class="fa-regular fa-calendar"></i> Pilih Bulan <i class="fa-solid fa-angle-down ml-1"></i>
            </button>
            <button type="button" class="pill-btn {{ ($summaryMetrics['periode_filter'] ?? '') === 'custom' ? 'active' : '' }}" data-toggle="collapse" data-target="#collapseCustomTanggal">
              <i class="fa-regular fa-calendar-days"></i> Custom Tanggal <i class="fa-solid fa-angle-down ml-1"></i>
            </button>
            <a href="{{ route('beranda.index', ['periode' => 'semua']) }}" class="pill-btn {{ ($summaryMetrics['periode_filter'] ?? '') === 'semua' ? 'active' : '' }}">
              Semua Waktu
            </a>
          </nav>
        </div>

        <div>
          <span class="active-filter-badge">
            <i class="fa-solid fa-clock-rotate-left"></i> Aktif: <strong>{{ $summaryMetrics['label_periode'] ?? 'Bulan Ini' }}</strong>
          </span>
        </div>
      </section>

      <!-- Collapse: Pilih Bulan & Tahun -->
      <div class="collapse {{ ($summaryMetrics['periode_filter'] ?? '') === 'pilih_bulan' ? 'show' : '' }} mb-3" id="collapsePilihBulan">
        <div class="panel p-3" style="margin-bottom:0">
          <form action="{{ route('beranda.index') }}" method="GET" class="form-inline d-flex flex-wrap align-items-center gap-2">
            <input type="hidden" name="periode" value="pilih_bulan">
            <label class="mr-2 font-weight-bold text-sm text-secondary">Bulan:</label>
            <select name="bulan" class="form-control form-control-sm mr-3" style="width:140px;border-radius:8px">
              @foreach($availableMonths as $num => $nama)
                <option value="{{ $num }}" {{ ($summaryMetrics['filter_bulan'] ?? Carbon\Carbon::now()->month) == $num ? 'selected' : '' }}>
                  {{ $nama }}
                </option>
              @endforeach
            </select>

            <label class="mr-2 font-weight-bold text-sm text-secondary">Tahun:</label>
            <select name="tahun" class="form-control form-control-sm mr-3" style="width:110px;border-radius:8px">
              @foreach($availableYears as $year)
                <option value="{{ $year }}" {{ ($summaryMetrics['filter_tahun'] ?? Carbon\Carbon::now()->year) == $year ? 'selected' : '' }}>
                  {{ $year }}
                </option>
              @endforeach
            </select>

            <button type="submit" class="btn btn-sm btn-primary px-3" style="border-radius:8px">
              <i class="fa-solid fa-magnifying-glass mr-1"></i> Terapkan
            </button>
          </form>
        </div>
      </div>

      <!-- Collapse: Custom Rentang Tanggal -->
      <div class="collapse {{ ($summaryMetrics['periode_filter'] ?? '') === 'custom' ? 'show' : '' }} mb-3" id="collapseCustomTanggal">
        <div class="panel p-3" style="margin-bottom:0">
          <form action="{{ route('beranda.index') }}" method="GET" class="form-inline d-flex flex-wrap align-items-center gap-2">
            <input type="hidden" name="periode" value="custom">
            <label class="mr-2 font-weight-bold text-sm text-secondary">Dari Tanggal:</label>
            <input type="date" name="start_date" value="{{ $summaryMetrics['custom_start'] ?? Carbon\Carbon::now()->startOfMonth()->toDateString() }}" class="form-control form-control-sm mr-3" style="border-radius:8px" required>

            <label class="mr-2 font-weight-bold text-sm text-secondary">Sampai Tanggal:</label>
            <input type="date" name="end_date" value="{{ $summaryMetrics['custom_end'] ?? Carbon\Carbon::now()->endOfMonth()->toDateString() }}" class="form-control form-control-sm mr-3" style="border-radius:8px" required>

            <button type="submit" class="btn btn-sm btn-primary px-3" style="border-radius:8px">
              <i class="fa-solid fa-magnifying-glass mr-1"></i> Terapkan
            </button>
          </form>
        </div>
      </div>

      <!-- 5 KPI METRICS CARDS -->
      <section class="kpi-grid">
        <article class="kpi-card kpi-blue">
          <div class="kpi-icon-box blue"><i class="fa-solid fa-city"></i></div>
          <div class="kpi-info">
            <div class="kpi-title">Jumlah Project</div>
            <div class="kpi-num" style="color:#2563eb">{{ $summaryMetrics['jumlah_project'] ?? 0 }}</div>
            <div class="kpi-desc">Perumahan Aktif</div>
          </div>
        </article>

        <article class="kpi-card kpi-green">
          <div class="kpi-icon-box green"><i class="fa-solid fa-cubes"></i></div>
          <div class="kpi-info">
            <div class="kpi-title">Total Unit</div>
            <div class="kpi-num" style="color:#10b981">{{ $summaryMetrics['total_unit'] ?? 0 }} <small style="font-size:12px;font-weight:700">Unit</small></div>
            <div class="kpi-desc">{{ $summaryMetrics['unit_ready'] ?? 0 }} Unit Ready Stok</div>
          </div>
        </article>

        <article class="kpi-card kpi-orange">
          <div class="kpi-icon-box orange"><i class="fa-solid fa-receipt"></i></div>
          <div class="kpi-info">
            <div class="kpi-title">Booking Fee / Periode</div>
            <div class="kpi-num money" style="color:#f59e0b">Rp {{ number_format($summaryMetrics['booking_fee_periode'] ?? 0, 0, ',', '.') }}</div>
            <div class="kpi-desc">{{ $summaryMetrics['customer_periode'] ?? 0 }} Customer ({{ $summaryMetrics['label_periode'] ?? 'Bulan Ini' }})</div>
          </div>
        </article>

        <article class="kpi-card kpi-purple">
          <div class="kpi-icon-box purple"><i class="fa-solid fa-house-circle-check"></i></div>
          <div class="kpi-info">
            <div class="kpi-title">Unit Terjual</div>
            <div class="kpi-num" style="color:#7c3aed">{{ $summaryMetrics['unit_terjual'] ?? 0 }} <small style="font-size:12px;font-weight:700">Unit</small></div>
            <div class="kpi-desc"><strong style="color:#10b981">{{ !empty($summaryMetrics['total_unit']) ? round((($summaryMetrics['unit_terjual'] ?? 0) / $summaryMetrics['total_unit']) * 100, 1) : 0 }}%</strong> dari Kapasitas Total</div>
          </div>
        </article>

        <article class="kpi-card kpi-red">
          <div class="kpi-icon-box red"><i class="fa-solid fa-business-time"></i></div>
          <div class="kpi-info">
            <div class="kpi-title">Tagihan Jatuh Tempo</div>
            <div class="kpi-num" style="color:#f43f5e">{{ $summaryMetrics['tagihan_tempo_customer'] ?? 0 }} <small style="font-size:12px;font-weight:700">Cust</small></div>
            <div class="kpi-desc">Total Rp {{ number_format($summaryMetrics['tagihan_tempo_total'] ?? 0, 0, ',', '.') }}</div>
          </div>
        </article>
      </section>

      <!-- PIPELINE PENJUALAN -->
      <section class="panel">
        <div class="panel-body">
          <div class="section-head">
            <h2>
              <i class="fa-solid fa-timeline text-primary"></i> Pipeline Penjualan <span>(Monitoring Alur Konsumen)</span>
            </h2>
          </div>

          <div class="pipeline-track">
            <article class="pipeline-node p-booking">
              <span class="pipeline-step-badge">Tahap 01</span>
              <h3>Booking</h3>
              <div class="count-display">{{ $pipelineCounts['booking'] ?? 0 }}</div>
              <a class="pipeline-action-btn" href="{{ route('pengajuan-hold.index') }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-node p-marketing">
              <span class="pipeline-step-badge">Tahap 02</span>
              <h3>Pemberkasan Marketing</h3>
              <div class="count-display">{{ $pipelineCounts['marketing'] ?? 0 }}</div>
              <a class="pipeline-action-btn" href="{{ route('customer.index', ['id_status_progres' => 11]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-node p-admin">
              <span class="pipeline-step-badge">Tahap 03</span>
              <h3>Proses Admin</h3>
              <div class="count-display">{{ $pipelineCounts['sppr'] ?? 0 }}</div>
              <a class="pipeline-action-btn" href="{{ route('customer.index', ['id_status_progres' => 10]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-node p-bank">
              <span class="pipeline-step-badge">Tahap 04</span>
              <h3>Proses Bank</h3>
              <div class="count-display">{{ $pipelineCounts['wawancara'] ?? 0 }}</div>
              <a class="pipeline-action-btn" href="{{ route('customer.index', ['id_status_progres' => 7]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-node p-sp3k">
              <span class="pipeline-step-badge">Tahap 05</span>
              <h3>SP3K Terbit</h3>
              <div class="count-display">{{ $pipelineCounts['acc_bank'] ?? 0 }}</div>
              <a class="pipeline-action-btn" href="{{ route('customer.index', ['id_status_progres' => 4]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-node p-ppjb">
              <span class="pipeline-step-badge">Tahap 06</span>
              <h3>PPJB</h3>
              <div class="count-display">{{ $pipelineCounts['ppjb'] ?? 0 }}</div>
              <a class="pipeline-action-btn" href="{{ route('ppjb.index') }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>

            <article class="pipeline-node p-akad">
              <span class="pipeline-step-badge">Tahap 07</span>
              <h3>Akad Kredit</h3>
              <div class="count-display">{{ $pipelineCounts['akad'] ?? 0 }}</div>
              <a class="pipeline-action-btn" href="{{ route('customer.index', ['id_status_progres' => 3]) }}">Buka Menu <i class="fa-solid fa-arrow-right"></i></a>
            </article>
          </div>
        </div>
      </section>

      <!-- STATISTIK PER PROJECT TABLE -->
      <section class="panel">
        <div class="panel-body">
          <div class="section-head">
            <h2>
              <i class="fa-solid fa-table-list text-primary"></i> Statistik per Project / Perumahan
            </h2>
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
                  <th style="background:#f1f5f9;color:#0f172a">Total Terjual</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($projectStats as $project)
                  <tr>
                    <td>
                      <div class="project-item-group">
                        <div class="project-avatar-badge">
                          <i class="fa-solid fa-building"></i>
                        </div>
                        <div>
                          <div class="project-name-text">{{ $project['nama'] }}</div>
                          <div style="font-size:11px;color:#64748b">
                            <span class="project-tag-pill">{{ $project['kode'] }}</span>{{ $project['total_unit'] }} Unit Kapasitas
                          </div>
                        </div>
                      </div>
                    </td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 2]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#e11d48">{{ $project['booking'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 11]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#0284c7">{{ $project['marketing'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 10]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#0891b2">{{ $project['sppr'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 7]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#059669">{{ $project['wawancara'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 4]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#d97706">{{ $project['acc_bank'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 6]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#db2777">{{ $project['ppjb'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 3]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#6d28d9">{{ $project['akad'] }}</span></a></td>
                    <td><a href="{{ route('customer.index', ['id_lokasi' => $project['id'], 'id_status_progres' => 5]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#1d4ed8">{{ $project['bast'] }}</span></a></td>
                    <td style="background:#f8fafc"><a href="{{ route('customer.index', ['id_lokasi' => $project['id']]) }}" style="text-decoration:none"><span class="table-metric-badge" style="color:#0f172a;font-size:16px">{{ $project['terjual'] }}</span></a></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center text-muted py-4">Belum ada data project terdaftar.</td>
                  </tr>
                @endforelse

                <tr class="table-total">
                  <td>Total Keseluruhan</td>
                  <td style="color:#e11d48">{{ $projectTotals['booking'] ?? 0 }}</td>
                  <td style="color:#0284c7">{{ $projectTotals['marketing'] ?? 0 }}</td>
                  <td style="color:#0891b2">{{ $projectTotals['sppr'] ?? 0 }}</td>
                  <td style="color:#059669">{{ $projectTotals['wawancara'] ?? 0 }}</td>
                  <td style="color:#d97706">{{ $projectTotals['acc_bank'] ?? 0 }}</td>
                  <td style="color:#db2777">{{ $projectTotals['ppjb'] ?? 0 }}</td>
                  <td style="color:#6d28d9">{{ $projectTotals['akad'] ?? 0 }}</td>
                  <td style="color:#1d4ed8">{{ $projectTotals['bast'] ?? 0 }}</td>
                  <td style="color:#2563eb;font-size:17px">{{ $projectTotals['terjual'] ?? 0 }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- RESUME TIAP PROYEK (Executive Project Resume) -->
      <section class="panel">
        <div class="panel-body">
          <div class="section-head" style="flex-wrap:wrap;gap:12px">
            <div>
              <h2>
                <i class="fa-solid fa-chart-pie text-primary"></i> Resume Tiap Proyek
              </h2>
              <span style="font-size:12px;color:#64748b">Ringkasan unit, realisasi penjualan, sisa stok, dan rincian progres fisik perumahan</span>
            </div>

            <div class="project-resume-tabs" style="display:flex;gap:8px;flex-wrap:wrap">
              @foreach($projectResumes as $key => $res)
                <button type="button" class="resume-tab-btn {{ $loop->first ? 'active' : '' }}" data-target="resume-pane-{{ $key }}">
                  <i class="fa-solid {{ $key == 'all' ? 'fa-layer-group' : 'fa-city' }}"></i> {{ $res['short_name'] }}
                </button>
              @endforeach
            </div>
          </div>

          @foreach($projectResumes as $key => $res)
            <div id="resume-pane-{{ $key }}" class="resume-pane" style="{{ $loop->first ? '' : 'display:none;' }}">
              <!-- Header info banner -->
              <div style="background:linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%);border:1px solid #bae6fd;border-radius:14px;padding:14px 18px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
                <div>
                  <h4 style="margin:0;font-size:15px;font-weight:800;color:#0f172a">
                    {{ $res['nama'] }} <span class="badge badge-primary px-2 py-1 ml-1" style="font-size:11px">{{ $res['badge'] }}</span>
                  </h4>
                  <small style="color:#475569">{{ $res['catatan'] }}</small>
                </div>
                <div style="display:flex;gap:16px;align-items:center">
                  <div style="text-align:right">
                    <span style="font-size:11px;color:#64748b;font-weight:700;display:block">Realisasi Terjual</span>
                    <strong style="font-size:17px;color:#10b981">{{ $res['persentase_terjual'] }}%</strong>
                  </div>
                  <div style="width:130px;height:9px;background:#cbd5e1;border-radius:99px;overflow:hidden">
                    <div style="width:{{ $res['persentase_terjual'] }}%;height:100%;background:#10b981;border-radius:99px"></div>
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
                  <span style="font-size:11px;color:#64748b">Kapasitas Siteplan Peta</span>
                </div>

                <div class="resume-kpi-box" style="border-left:4px solid #10b981">
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase">Total Terjual</span>
                    <span class="badge badge-success px-2 py-1" style="font-size:10px">{{ $res['persentase_terjual'] }}%</span>
                  </div>
                  <div style="font-size:24px;font-weight:900;color:#10b981;margin-top:6px">{{ $res['total_terjual'] }} <small style="font-size:12px;font-weight:600;color:#64748b">Unit</small></div>
                  <span style="font-size:11px;color:#64748b">KPR Akad ({{ $res['kpr_akad'] }}) · Cash ({{ $res['terjual_cash'] }})</span>
                </div>

                <div class="resume-kpi-box" style="border-left:4px solid #f59e0b">
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase">Sedang On Proses</span>
                    <span class="badge badge-warning px-2 py-1" style="font-size:10px">{{ $res['persentase_proses'] }}%</span>
                  </div>
                  <div style="font-size:24px;font-weight:900;color:#d97706;margin-top:6px">{{ $res['on_proses'] }} <small style="font-size:12px;font-weight:600;color:#64748b">Unit</small></div>
                  <span style="font-size:11px;color:#64748b">Fisik 80%-100%: <strong>{{ $res['total_fisik_proses'] }} Unit</strong></span>
                </div>

                <div class="resume-kpi-box" style="border-left:4px solid #ef4444">
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase">Sisa Unit Ready</span>
                    <span class="badge badge-danger px-2 py-1" style="font-size:10px">{{ $res['persentase_sisa'] }}%</span>
                  </div>
                  <div style="font-size:24px;font-weight:900;color:#dc2626;margin-top:6px">{{ $res['sisa_unit'] }} <small style="font-size:12px;font-weight:600;color:#64748b">Unit</small></div>
                  <span style="font-size:11px;color:#64748b">Siap Dipasarkan / Booking</span>
                </div>
              </div>

              <!-- Rincian Unit On Proses & Progres Fisik -->
              <div style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                  <span style="font-size:12px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:0.05em">
                    <i class="fa-solid fa-list-check mr-1 text-primary"></i> Rincian Unit Proses & Kesiapan Bangunan (80% - 100%)
                  </span>
                  <span style="font-size:11.5px;color:#64748b">Total Unit Proses: <strong>{{ $res['total_unit_proses'] }} Unit</strong> | Fisik Siap: <strong>{{ $res['total_fisik_proses'] }} Unit</strong></span>
                </div>

                <div class="resume-proc-grid">
                  @foreach($res['proses_items'] as $item)
                    <div class="resume-proc-card">
                      <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px;display:flex;align-items:center;justify-content:center;gap:5px">
                        <i class="fa-solid {{ $item['icon'] }}" style="color:{{ $item['color'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                      </div>
                      <div style="font-size:22px;font-weight:900;color:#1e293b;margin:4px 0">
                        {{ $item['unit'] }} <small style="font-size:11px;font-weight:600;color:#64748b">Unit</small>
                      </div>
                      <div style="font-size:10.5px;font-weight:700;color:#15803d;background:#dcfce7;border-radius:6px;padding:3px 8px;display:inline-block">
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

      <!-- BOTTOM GRID: GRAFIK PENJUALAN BULANAN & LEADERBOARD MARKETING -->
      <section class="bottom-grid-2col">
        <!-- Grafik Penjualan Bulanan -->
        <article class="panel" style="margin-bottom:0">
          <div class="panel-body">
            <div class="section-head">
              <h2><i class="fa-solid fa-chart-column text-primary"></i> Grafik Penjualan Bulanan</h2>
              <div style="display:flex;gap:10px;align-items:center">
                <div>
                  <label style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:2px;display:block">Tahun</label>
                  <select id="filterTahun" class="form-control form-control-sm" style="width:105px;border-radius:8px">
                    @foreach($availableYears as $year)
                      <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:2px;display:block">Status</label>
                  <select id="filterStatus" class="form-control form-control-sm" style="width:165px;border-radius:8px">
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

            <div class="chart-canvas-box">
              <canvas id="salesChart"></canvas>
            </div>
          </div>
        </article>

        <!-- Leaderboard Marketing -->
        <article class="panel" style="margin-bottom:0">
          <div class="panel-body">
            <div class="section-head">
              <h2><i class="fa-solid fa-trophy text-warning"></i> Penjualan Marketing</h2>
            </div>

            <div class="marketing-list">
              @forelse ($marketingStats as $index => $marketing)
                <a href="{{ route('beranda.detail-customer-marketing', $marketing['id']) }}" class="marketing-item">
                  <span class="rank-badge {{ $index == 0 ? 'top-1' : ($index == 1 ? 'top-2' : ($index == 2 ? 'top-3' : '')) }}">{{ $index + 1 }}</span>
                  <span class="user-avatar-initials">{{ $marketing['inisial'] }}</span>
                  <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:800;color:#0f172a;margin-bottom:2px">{{ $marketing['nama'] }}</div>
                    <div style="color:#64748b;font-size:11px">{{ $marketing['kode'] }} · Marketing</div>
                  </div>
                  <div style="color:#2563eb;font-weight:900;font-size:13px;background:rgba(37,99,235,0.08);padding:4px 10px;border-radius:8px">
                    {{ $marketing['jumlah'] }} Unit
                  </div>
                </a>
              @empty
                <div class="text-muted text-center py-4">Belum ada data marketing.</div>
              @endforelse
            </div>
          </div>
        </article>
      </section>

      <!-- STATISTIK PENGGUNAAN BANK -->
      <section class="panel">
        <div class="panel-body">
          <div class="section-head">
            <h2><i class="fa-solid fa-building-columns text-primary"></i> Statistik Penggunaan Bank</h2>
          </div>

          <div class="bank-list" style="max-height:260px">
            @forelse ($bankStats as $index => $bank)
              <div class="bank-item-row">
                <span class="rank-badge">{{ $index + 1 }}</span>
                <div style="min-width:0">
                  <div style="font-size:13px;font-weight:800;color:#0f172a">{{ $bank['nama'] }}</div>
                  <div style="color:#64748b;font-size:11px">Bank Penyalur KPR</div>
                </div>
                <div style="color:#0f172a;font-weight:800;font-size:13px;text-align:right">{{ $bank['jumlah'] }} Nasabah</div>
                <div style="text-align:right">
                  <span class="badge badge-light px-2 py-1 font-weight-bold" style="font-size:11px;border:1px solid #cbd5e1;border-radius:6px">{{ $bank['persentase'] }}%</span>
                </div>
              </div>
            @empty
              <div class="text-muted text-center py-4">Belum ada penggunaan bank.</div>
            @endforelse
          </div>
        </div>
      </section>

      <!-- GRAFIK ADMIN PEMBERKASAN -->
      <section class="panel">
        <div class="panel-body">
          <div class="section-head">
            <h2><i class="fa-solid fa-user-tie text-primary"></i> Grafik Admin Pemberkasan</h2>
            <div style="display:flex;gap:10px;align-items:center">
              <div>
                <label style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:2px;display:block">Tahun</label>
                <select id="filterTahunAdmin" class="form-control form-control-sm" style="width:105px;border-radius:8px">
                  @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:2px;display:block">Status</label>
                <select id="filterStatusAdmin" class="form-control form-control-sm" style="width:150px;border-radius:8px">
                  <option value="semua" selected>Semua</option>
                  <option value="wawancara">Proses Bank</option>
                  <option value="sp3k">SP3K</option>
                  <option value="akad">Akad</option>
                </select>
              </div>
            </div>
          </div>

          <div class="chart-canvas-box" style="height:350px">
            <canvas id="adminPemberkasanChart"></canvas>
          </div>
        </div>
      </section>

      <!-- REKAP DP & GRAFIK SUMBER PROSPEK (Tahun 2026) -->
      <section class="panel">
        <div class="panel-body">
          <div class="section-head" style="flex-wrap:wrap;gap:12px">
            <div>
              <h2><i class="fa-solid fa-bullhorn text-primary"></i> Rekap DP & Grafik Sumber Prospek (Tahun 2026)</h2>
              <span style="font-size:12px;color:#64748b">Analisis kanal promosi efektif dan performa sumber prospek tahun 2026</span>
            </div>

            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
              <!-- Filter Bulan -->
              <div style="display:flex;align-items:center;gap:6px">
                <label style="font-size:12px;font-weight:700;color:#475569;margin:0">Bulan:</label>
                <select id="filterBulanSumber" class="form-control form-control-sm" style="width:175px;height:36px;border-radius:8px">
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
              <div class="btn-group" role="group" style="box-shadow:0 1px 3px rgba(0,0,0,0.06);border-radius:8px;overflow:hidden">
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
          <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:12px;margin-bottom:18px">
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px">
              <span style="font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase">Total Prospek (Periode)</span>
              <div style="font-size:22px;font-weight:900;color:#0f172a" id="spBadgeTotal">{{ $yoySummary['total_2026'] }} <small style="font-size:11px;font-weight:600;color:#64748b">Unit</small></div>
              <span style="font-size:10.5px;color:#10b981;font-weight:700" id="spBadgeLabel">Tahun 2026 (Semua Bulan)</span>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px">
              <span style="font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase">Sumber Tertinggi</span>
              <div style="font-size:18px;font-weight:900;color:#2563eb" id="spBadgeTop">-</div>
              <span style="font-size:10.5px;color:#64748b">Penyumbang Terbesar</span>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px">
              <span style="font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase">Rata-rata per Bulan</span>
              <div style="font-size:22px;font-weight:900;color:#059669">{{ $yoySummary['rata_2026'] }} <small style="font-size:11px;font-weight:600;color:#64748b">Unit/Bln</small></div>
              <span style="font-size:10.5px;color:#64748b">Berdasarkan Bulan Aktif</span>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px">
              <span style="font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase">Pertumbuhan YoY (vs 2025)</span>
              <div style="font-size:22px;font-weight:900;color:#10b981">+{{ $yoySummary['growth'] }} <small style="font-size:11px;font-weight:600;color:#10b981">(+{{ $yoySummary['growth_pct'] }}%)</small></div>
              <span style="font-size:10.5px;color:#10b981;font-weight:700"><i class="fa-solid fa-arrow-trend-up mr-1"></i> {{ $yoySummary['total_2025'] }} (2025) &rarr; {{ $yoySummary['total_2026'] }} (2026)</span>
            </div>
          </div>

          <!-- Chart View Container -->
          <div id="wrapperChartSumber" class="chart-canvas-box" style="height:350px">
            <canvas id="sumberProspekChart"></canvas>
          </div>

          <!-- Table View Container -->
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
                    <td style="font-weight:800;color:{{ $data['growth'] >= 0 ? '#10b981' : '#dc2626' }}">
                      {{ $data['growth'] > 0 ? '+' : '' }}{{ $data['growth'] }}
                    </td>
                    <td style="font-weight:700;color:{{ $data['growth_rata'] >= 0 ? '#10b981' : '#dc2626' }}">
                      {{ $data['growth_rata'] > 0 ? '+' : '' }}{{ number_format($data['growth_rata'], 1) }}
                    </td>
                  </tr>
                @endforeach
                <tr class="total-row">
                  <td colspan="2" style="text-align:center;font-weight:900">TOTAL</td>
                  @foreach(range(1, 12) as $m)
                    <td style="font-weight:900;color:#1d4ed8">{{ $monthlyTotals[$m] ?? 0 }}</td>
                  @endforeach
                  <td style="font-weight:900;color:#c2410c;background:#fed7aa">{{ $yoySummary['total_2026'] }}</td>
                  <td style="font-weight:900;color:#854d0e;background:#fef08a">{{ $yoySummary['rata_2026'] }}</td>
                  <td style="font-weight:900">{{ $yoySummary['total_2025'] }}</td>
                  <td style="font-weight:900">{{ $yoySummary['total_2026'] }}</td>
                  <td style="font-weight:900;color:#10b981">+{{ $yoySummary['growth'] }}</td>
                  <td style="font-weight:900;color:#10b981">+{{ $yoySummary['growth_rata'] }}</td>
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

        // Create sleek gradient fill
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.9)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.25)');

        salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: [{
                    label: 'Penjualan',
                    data: data,
                    backgroundColor: gradient,
                    borderColor: '#3b82f6',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    barPercentage: 0.55,
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
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans' },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                return ctx.parsed.y + ' unit terjual';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0, font: { family: 'Plus Jakarta Sans' } },
                        grid: { color: 'rgba(226, 232, 240, 0.7)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', weight: '600' } }
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
    const spGradients = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
        '#8b5cf6', '#ec4899', '#06b6d4', '#eab308', '#6366f1'
    ];

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
                    backgroundColor: spGradients.slice(0, labels.length),
                    borderWidth: 0,
                    borderRadius: 8,
                    barPercentage: 0.6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans' },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                return ctx.parsed.y + ' data prospek';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0, font: { family: 'Plus Jakarta Sans' } },
                        grid: { color: 'rgba(226, 232, 240, 0.7)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }
                        }
                    }
                }
            }
        });
    }

    let adminPemberkasanChart;
    const apPalette = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
        '#8b5cf6', '#ec4899', '#06b6d4', '#eab308'
    ];

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
                    backgroundColor: apPalette.slice(0, labels.length),
                    borderWidth: 0,
                    borderRadius: 8,
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
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans' },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                return ctx.parsed.y + ' unit ditangani';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0, font: { family: 'Plus Jakarta Sans' } },
                        grid: { color: 'rgba(226, 232, 240, 0.7)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }
                        }
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection
