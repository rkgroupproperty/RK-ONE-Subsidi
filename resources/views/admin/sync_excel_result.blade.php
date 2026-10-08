@extends('admin.layout_admin')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="font-weight-bold"><i class="fas fa-sync-alt mr-2 text-primary"></i> Sinkronisasi Data Excel ke Database</h1>
                <a href="{{ route('beranda.index') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-home mr-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @php
                $status = $result['status'] ?? 'error';
                $stats  = $result['stats'] ?? [];
                $logs   = $result['logs'] ?? [];
            @endphp

            <div class="row">
                <div class="col-md-12">
                    @if($status === 'success')
                        <div class="alert alert-success alert-dismissible shadow-sm">
                            <h5><i class="icon fas fa-check-circle"></i> Sinkronisasi Berhasil!</h5>
                            Seluruh data master konsumen, kavling, SP3K, bank, dan marketing dari Excel telah berhasil disinkronkan ke database.
                        </div>
                    @else
                        <div class="alert alert-danger alert-dismissible shadow-sm">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Terjadi Kendala Sinkronisasi!</h5>
                            {{ $result['message'] ?? 'Proses sinkronisasi terhenti.' }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kartu Statistik -->
            <div class="row">
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm border">
                        <span class="info-box-icon bg-info"><i class="fas fa-map-marked-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Kavling Disinkron</span>
                            <span class="info-box-number font-weight-bold">{{ $stats['kavling_updated'] ?? 0 }} Unit</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm border">
                        <span class="info-box-icon bg-success"><i class="fas fa-user-plus"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Konsumen Baru Dibuat</span>
                            <span class="info-box-number font-weight-bold">{{ $stats['customer_created'] ?? 0 }} Konsumen</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm border">
                        <span class="info-box-icon bg-warning"><i class="fas fa-user-edit"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Konsumen Diperbarui</span>
                            <span class="info-box-number font-weight-bold">{{ $stats['customer_updated'] ?? 0 }} Konsumen</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm border">
                        <span class="info-box-icon bg-purple text-white" style="background:#8b5cf6 !important;"><i class="fas fa-file-contract"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Data SP3K Disinkron</span>
                            <span class="info-box-number font-weight-bold">{{ $stats['sp3k_created'] ?? 0 }} Data</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm border">
                        <span class="info-box-icon bg-info text-white" style="background:#0284c7 !important;"><i class="fas fa-user-tag"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pemberkasan Marketing</span>
                            <span class="info-box-number font-weight-bold">{{ $stats['mkt_synced'] ?? 0 }} Data</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Log Detail -->
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-terminal mr-2"></i> Log Aktivitas Sinkronisasi</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 420px; overflow-y: auto; background: #1e293b; color: #f8fafc; font-family: monospace; font-size: 13px; padding: 16px;">
                        @foreach($logs as $log)
                            <div>{{ $log }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('beranda.index') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left mr-1"></i> Lihat Data di Beranda
                    </a>
                    <a href="{{ route('admin.sync-excel') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-redo mr-1"></i> Jalankan Sinkronisasi Ulang
                    </a>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection
