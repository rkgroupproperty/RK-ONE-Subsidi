@extends('admin.layout_admin')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="font-weight-bold"><i class="fas fa-sync-alt mr-2 text-primary"></i> Sinkronisasi Data Excel / Google Sheets</h1>
                <a href="{{ route('beranda.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible shadow-sm">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Perhatian!</h5>
                    {{ session('error') }}
                </div>
            @endif

            <div class="row">
                <!-- Opsi 1: Data Terakhir (Rekomendasi) -->
                <div class="col-md-6 mb-4">
                    <div class="card card-outline card-success shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h3 class="card-title font-weight-bold text-success">
                                <i class="fas fa-check-circle mr-2"></i> Pilihan 1: Sinkronkan Data Saat Ini (Direkomendasikan)
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Data lengkap dari Google Spreadsheet <strong>DAFTAR NAMA KONSUMEN RK</strong> yang Anda berikan telah tersimpan rapi di sistem (mencakup data SP3K, seluruh perumahan 2024-2026, DP bulanan, dan histori pembatalan).
                            </p>
                            <div class="alert alert-info py-2 px-3 small border-0">
                                <i class="fas fa-info-circle mr-1"></i> Sistem akan otomatis membersihkan format tanggal, menautkan unit kavling, bank, dan status ke database.
                            </div>
                            <form action="{{ route('admin.sync-excel.process') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block font-weight-bold py-2 shadow-sm" onclick="return confirm('Mulai sinkronisasi seluruh data ke database sekarang?')">
                                    <i class="fas fa-play mr-1"></i> Jalankan Sinkronisasi Sekarang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Opsi 2: Live Sync Google Sheets -->
                <div class="col-md-6 mb-4">
                    <div class="card card-outline card-primary shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h3 class="card-title font-weight-bold text-primary">
                                <i class="fab fa-google-drive mr-2"></i> Pilihan 2: Tarik Langsung dari Google Spreadsheet (Live)
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Tarik data secara *live* langsung dari link Google Sheets Anda setiap kali ada pembaruan di spreadsheet.
                            </p>
                            <form action="{{ route('admin.sync-excel.process') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label class="small font-weight-bold">URL Google Spreadsheet:</label>
                                    <input type="url" name="google_sheet_url" class="form-control" 
                                           value="https://docs.google.com/spreadsheets/d/1TBwtGcu_3umU9IlhJW8eLyFcDzAL9ib2OrWFS6t1a6o/edit?gid=1360951222#gid=1360951222" required>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="fas fa-lock mr-1"></i> Catatan: Di Google Sheets, klik tombol <strong>Bagikan (Share)</strong> di kanan atas lalu setel akses ke <strong>"Siapa saja yang memiliki link dapat melihat"</strong> agar server dapat membaca datanya.
                                    </small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm">
                                    <i class="fas fa-cloud-download-alt mr-1"></i> Tarik Data dari Google Sheets
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Opsi 3: Upload File CSV Manual -->
                <div class="col-md-12">
                    <div class="card card-outline card-secondary shadow-sm">
                        <div class="card-header bg-light">
                            <h3 class="card-title font-weight-bold text-secondary">
                                <i class="fas fa-file-upload mr-2"></i> Pilihan 3: Upload File CSV Manual
                            </h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.sync-excel.process') }}" method="POST" enctype="multipart/form-data" class="row align-items-center">
                                @csrf
                                <div class="col-md-8 mb-2">
                                    <input type="file" name="file_csv" class="form-control-file border p-2 rounded w-100" accept=".csv" required>
                                    <small class="text-muted">Pilih file hasil download CSV dari Google Sheets (File &rarr; Download &rarr; Comma-separated values .csv).</small>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <button type="submit" class="btn btn-secondary btn-block font-weight-bold py-2">
                                        <i class="fas fa-upload mr-1"></i> Upload & Sinkronkan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection
