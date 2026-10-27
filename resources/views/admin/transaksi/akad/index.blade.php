@extends('admin.layout_admin')
@section('content')
    <style>
        .filter-period-toolbar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .filter-pill-nav {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 6px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
        }
        .filter-pill-nav .pill-btn {
            border: none;
            outline: none;
            background: transparent;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        .filter-pill-nav .pill-btn:hover {
            color: #1e293b;
            background: rgba(255,255,255,0.6);
        }
        .filter-pill-nav .pill-btn.active {
            background: #ffffff;
            color: #4f46e5;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .active-filter-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            border: 1px solid #c7d2fe;
        }
        .filter-panel-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .nav-view-mode {
            background: #e2e8f0;
            padding: 3px;
            border-radius: 8px;
            display: inline-flex;
            gap: 4px;
        }
        .nav-view-mode .btn-mode {
            border: none;
            background: transparent;
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            padding: 5px 14px;
            border-radius: 6px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .nav-view-mode .btn-mode.active {
            background: #ffffff;
            color: #4338ca;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }
    </style>

    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Toolbar Filter Periode -->
                <div class="filter-period-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-xs font-weight-bold text-uppercase text-muted mr-2" style="letter-spacing:0.5px">
                            <i class="fas fa-filter text-indigo mr-1"></i> Periode:
                        </span>
                        <div class="filter-pill-nav">
                            <button type="button" class="pill-btn active" data-filter="bulan_ini">Bulan Ini</button>
                            <button type="button" class="pill-btn" data-filter="bulan_kemarin">Bulan Kemarin</button>
                            <button type="button" class="pill-btn" data-filter="pilih_bulan">
                                <i class="far fa-calendar-alt mr-1"></i> Pilih Bulan <i class="fas fa-caret-down ml-1"></i>
                            </button>
                            <button type="button" class="pill-btn" data-filter="custom">
                                <i class="far fa-calendar mr-1"></i> Custom Tanggal <i class="fas fa-caret-down ml-1"></i>
                            </button>
                            <button type="button" class="pill-btn" data-filter="semua">Semua Data</button>
                        </div>
                    </div>

                    <div>
                        <span class="active-filter-badge" id="activeFilterLabel">
                            <i class="fas fa-history mr-1"></i> Menampilkan: <strong class="ml-1" id="filterStatusText">Bulan Ini ({{ \Carbon\Carbon::now()->translatedFormat('F Y') }})</strong>
                        </span>
                    </div>
                </div>

                <!-- Collapse Panel: Pilih Bulan & Tahun -->
                <div class="collapse" id="panelPilihBulan">
                    <div class="filter-panel-box">
                        <form id="formPilihBulan" class="form-inline d-flex flex-wrap align-items-center">
                            <label class="mr-2 font-weight-bold text-sm text-secondary">Bulan:</label>
                            <select id="selectBulan" class="form-control form-control-sm mr-3" style="width:140px;border-radius:6px">
                                @foreach($availableMonths as $num => $nama)
                                    <option value="{{ $num }}" {{ \Carbon\Carbon::now()->month == $num ? 'selected' : '' }}>
                                        {{ $nama }}
                                    </option>
                                @endforeach
                            </select>

                            <label class="mr-2 font-weight-bold text-sm text-secondary">Tahun:</label>
                            <select id="selectTahun" class="form-control form-control-sm mr-3" style="width:110px;border-radius:6px">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ \Carbon\Carbon::now()->year == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="submit" class="btn btn-sm btn-indigo text-white px-3" style="border-radius:6px">
                                <i class="fas fa-search mr-1"></i> Terapkan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Collapse Panel: Custom Tanggal -->
                <div class="collapse" id="panelCustomTanggal">
                    <div class="filter-panel-box">
                        <form id="formCustomTanggal" class="form-inline d-flex flex-wrap align-items-center">
                            <label class="mr-2 font-weight-bold text-sm text-secondary">Dari Tanggal:</label>
                            <input type="date" id="inputStartDate" class="form-control form-control-sm mr-3" style="border-radius:6px"
                                   value="{{ \Carbon\Carbon::now()->startOfMonth()->toDateString() }}" required>

                            <label class="mr-2 font-weight-bold text-sm text-secondary">Sampai Tanggal:</label>
                            <input type="date" id="inputEndDate" class="form-control form-control-sm mr-3" style="border-radius:6px"
                                   value="{{ \Carbon\Carbon::now()->endOfMonth()->toDateString() }}" required>

                            <button type="submit" class="btn btn-sm btn-indigo text-white px-3" style="border-radius:6px">
                                <i class="fas fa-search mr-1"></i> Terapkan
                            </button>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" style="border-radius:12px; overflow:hidden">
                            <div class="card-header bg-white p-3 border-bottom">
                                <div class="d-flex align-content-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center flex-wrap gap-3">
                                        <div class="d-flex align-items-center mr-3">
                                            <i class="fas fa-handshake text-indigo mr-2 fa-lg"></i>
                                            <h3 class="font-weight-bold text-lg mb-0" id="pageTitleText">Data Konsumen Akad</h3>
                                        </div>
                                        <!-- Mode Tab Switcher: Konsumen vs Jadwal -->
                                        <div class="nav-view-mode">
                                            <button type="button" class="btn-mode active" data-tab="konsumen">
                                                <i class="fas fa-users mr-1"></i> Konsumen Akad
                                            </button>
                                            <button type="button" class="btn-mode" data-tab="jadwal">
                                                <i class="fas fa-calendar-alt mr-1"></i> Jadwal Akad
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('export.transaksi.akad') }}" id="btnExportExcel" target="_blank" class="btn btn-sm btn-success mr-2 shadow-sm" style="border-radius:6px">
                                            <i class="fas fa-file-excel mr-1"></i> Excel
                                        </a>
                                        @if ($permissions['tambah'])
                                            <button class="btn btn-primary btn-sm shadow-sm" data-toggle="modal"
                                                data-target="#modalForm" style="border-radius:6px"><i class="fas fa-plus mr-1"></i>
                                                Tambah Jadwal / Konsumen Akad</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <!-- Filter Bar Proyek & Marketing (Khusus Tab Konsumen) -->
                                <div class="row mb-3 bg-light p-2 rounded mx-0 border align-items-center" id="containerFilterKonsumen">
                                    <div class="col-md-5 mb-2 mb-md-0">
                                        <label class="small font-weight-bold mb-1"><i class="fas fa-building mr-1 text-primary"></i> Filter Proyek / Perumahan:</label>
                                        <select id="filter_lokasi" class="form-control form-control-sm select2" style="border-radius:6px">
                                            <option value="">-- Semua Proyek --</option>
                                            @foreach ($lokasiList as $lok)
                                                <option value="{{ $lok->id }}">{{ $lok->nama_kavling }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5 mb-2 mb-md-0">
                                        <label class="small font-weight-bold mb-1"><i class="fas fa-user-tie mr-1 text-info"></i> Filter Marketing:</label>
                                        <select id="filter_marketing" class="form-control form-control-sm select2" style="border-radius:6px">
                                            <option value="">-- Semua Marketing --</option>
                                            @foreach ($marketingList as $mkt)
                                                <option value="{{ $mkt->id }}">{{ $mkt->nama_marketing }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 mt-md-4">
                                        <button type="button" id="btn-reset-filter" class="btn btn-sm btn-secondary btn-block" style="border-radius:6px">
                                            <i class="fas fa-redo mr-1"></i> Reset
                                        </button>
                                    </div>
                                </div>

                                <!-- Container Tabel 1: Konsumen Akad -->
                                <div id="tableKonsumenWrapper">
                                    <table class="table w-100 table-bordered table-striped data-table-konsumen small">
                                        <thead>
                                            <tr>
                                                <th width="3%" class="text-center">No</th>
                                                <th width="12%">Tanggal Akad</th>
                                                <th width="20%">Nama Konsumen</th>
                                                <th width="12%">No. Telp</th>
                                                <th width="12%">Marketing</th>
                                                <th width="16%">Lokasi & Kavling</th>
                                                <th width="12%">Status Akad</th>
                                                <th>Keterangan</th>
                                                <th class="text-center" width="8%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Container Tabel 2: Jadwal Akad -->
                                <div id="tableJadwalWrapper" style="display: none;">
                                    <table class="table w-100 table-bordered table-striped data-table-jadwal small">
                                        <thead>
                                            <tr>
                                                <th width="5%" class="text-center">No</th>
                                                <th width="20%">Jadwal Akad</th>
                                                <th width="15%" class="text-center">Total Peserta Akad</th>
                                                <th>Keterangan</th>
                                                <th class="text-center" width="15%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

    <!-- Modal Form Tambah Jadwal & Konsumen Akad -->
    <div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="modalFormLabel" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-indigo">
                    <h5 class="modal-title text-white font-weight-bold" id="modalFormLabel">
                        <i class="fas fa-handshake mr-2"></i>Form Jadwal & Konsumen Akad
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formData">
                    @csrf
                    <input type="hidden" id="primary_id" name="primary_id">
                    <div class="modal-body">
                        <div class="form-group row mb-3">
                            <label for="tgl_akad" class="col-sm-3 col-form-label font-weight-bold">Tanggal Akad <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" id="tgl_akad" class="form-control" name="tgl_akad"
                                    value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="keterangan" class="col-sm-3 col-form-label font-weight-bold">Keterangan / Bank <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" id="keterangan" class="form-control" name="keterangan" placeholder="Contoh: Bank BTN / Batch 1" required>
                            </div>
                        </div>

                        <div class="alert alert-light border p-2 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-plus text-primary mr-2 fa-lg"></i>
                                <span class="font-weight-bold text-dark">Pilih Konsumen Akad (Opsional / Langsung)</span>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Anda dapat langsung memilih satu atau beberapa konsumen untuk dimasukkan ke jadwal ini. Jika hanya ingin membuat jadwal saja, kolom ini dapat dikosongkan.
                            </small>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="id_customer" class="col-sm-3 col-form-label font-weight-bold">
                                Konsumen
                            </label>
                            <div class="col-sm-9">
                                <select id="id_customer" name="id_customer[]" class="form-control select2-modal" multiple="multiple" style="width: 100%;">
                                    @foreach($readyCustomers as $cust)
                                        @php
                                            $sttLabel = match($cust->id_status_progres) {
                                                4 => 'SP3K',
                                                7 => 'Proses Bank',
                                                10 => 'Proses Admin',
                                                11 => 'Pemberkasan',
                                                default => 'Proses'
                                            };
                                        @endphp
                                        <option value="{{ $cust->id }}">
                                            {{ $cust->nama_lengkap }} — {{ $cust->lokasi->nama_kavling ?? '-' }} [{{ $cust->kavling->kode_kavling ?? '-' }}] (Status: {{ $sttLabel }}{{ $cust->bankKpr ? ' - ' . $cust->bankKpr->nama : '' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="jenis_akad" class="col-sm-3 col-form-label font-weight-bold">Jenis Akad</label>
                            <div class="col-sm-9">
                                <select id="jenis_akad" name="jenis_akad" class="form-control">
                                    <option value="Akad KPR" selected>Akad KPR</option>
                                    <option value="Cash Keras">Cash Keras</option>
                                    <option value="AJB">AJB</option>
                                    <option value="PPJB">PPJB</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="status_akad" class="col-sm-3 col-form-label font-weight-bold">Status Kehadiran</label>
                            <div class="col-sm-9">
                                <select id="status_akad" name="status_akad" class="form-control">
                                    <option value="2" selected>Langsung Selesai Akad (Hadir / Masuk ke Data Konsumen Akad)</option>
                                    <option value="1">Baru Dijadwalkan (Belum Hadir)</option>
                                </select>
                                <small class="text-muted d-block mt-1">
                                    Pilih <strong>Langsung Selesai Akad</strong> agar konsumen otomatis tercatat Akad dan unit di Siteplan langsung berubah warna.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="button-text"><i class="fas fa-save mr-1"></i> Simpan Data Akad</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(function() {
            var permissions = @json($permissions);
            var showActionColumn = (permissions['edit'] == 1 || permissions['hapus'] == 1);

            var activeTab = 'konsumen';
            var currentFilter = 'bulan_ini';
            var filterBulan = '{{ \Carbon\Carbon::now()->month }}';
            var filterTahun = '{{ \Carbon\Carbon::now()->year }}';
            var customStart = '{{ \Carbon\Carbon::now()->startOfMonth()->toDateString() }}';
            var customEnd = '{{ \Carbon\Carbon::now()->endOfMonth()->toDateString() }}';
            var filterLokasi = '';
            var filterMarketing = '';

            // Cek URL params
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('filter')) {
                currentFilter = urlParams.get('filter');
                if (urlParams.has('bulan')) filterBulan = urlParams.get('bulan');
                if (urlParams.has('tahun')) filterTahun = urlParams.get('tahun');
                if (urlParams.has('start_date')) customStart = urlParams.get('start_date');
                if (urlParams.has('end_date')) customEnd = urlParams.get('end_date');

                if (currentFilter === 'pilih_bulan') {
                    $('#selectBulan').val(filterBulan);
                    $('#selectTahun').val(filterTahun);
                    $('#panelPilihBulan').collapse('show');
                } else if (currentFilter === 'custom') {
                    $('#inputStartDate').val(customStart);
                    $('#inputEndDate').val(customEnd);
                    $('#panelCustomTanggal').collapse('show');
                }
            }
            if (urlParams.has('tab')) {
                activeTab = urlParams.get('tab');
            }

            function updateFilterUI() {
                $('.filter-pill-nav .pill-btn').removeClass('active');
                $('.filter-pill-nav .pill-btn[data-filter="' + currentFilter + '"]').addClass('active');

                var label = 'Bulan Ini ({{ \Carbon\Carbon::now()->translatedFormat('F Y') }})';
                if (currentFilter === 'bulan_kemarin') {
                    label = 'Bulan Kemarin ({{ \Carbon\Carbon::now()->subMonth()->translatedFormat('F Y') }})';
                } else if (currentFilter === 'pilih_bulan') {
                    var namaBulan = $('#selectBulan option:selected').text().trim();
                    var tahun = $('#selectTahun').val();
                    label = 'Bulan ' + namaBulan + ' ' + tahun;
                } else if (currentFilter === 'custom') {
                    label = 'Rentang ' + customStart + ' s/d ' + customEnd;
                } else if (currentFilter === 'semua') {
                    label = 'Semua Data';
                }
                $('#filterStatusText').text(label);

                // Update Excel download link
                var exportUrl = new URL("{{ route('export.transaksi.akad') }}", window.location.origin);
                exportUrl.searchParams.set('tab', activeTab);
                exportUrl.searchParams.set('filter', currentFilter);
                if (currentFilter === 'pilih_bulan') {
                    exportUrl.searchParams.set('bulan', filterBulan);
                    exportUrl.searchParams.set('tahun', filterTahun);
                } else if (currentFilter === 'custom') {
                    exportUrl.searchParams.set('start_date', customStart);
                    exportUrl.searchParams.set('end_date', customEnd);
                }
                if (filterLokasi) exportUrl.searchParams.set('id_lokasi', filterLokasi);
                if (filterMarketing) exportUrl.searchParams.set('id_marketing', filterMarketing);
                $('#btnExportExcel').attr('href', exportUrl.toString());
            }

            // Inisialisasi DataTable Konsumen Akad
            var tableKonsumen = $('.data-table-konsumen').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                responsive: true,
                ajax: {
                    url: "{{ route('akad.index') }}",
                    data: function(d) {
                        d.tab = 'konsumen';
                        d.filter = currentFilter;
                        d.bulan = filterBulan;
                        d.tahun = filterTahun;
                        d.start_date = customStart;
                        d.end_date = customEnd;
                        d.id_lokasi = filterLokasi;
                        d.id_marketing = filterMarketing;
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'tgl_akad', name: 'tgl_akad', orderable: false, searchable: false },
                    { data: 'nama_lengkap', name: 'nama_lengkap', orderable: false, searchable: true },
                    { data: 'no_telp', name: 'no_telp', orderable: false, searchable: false },
                    { data: 'marketing', name: 'marketing', orderable: false, searchable: false },
                    { data: 'lokasi_kavling', name: 'lokasi_kavling', orderable: false, searchable: false },
                    { data: 'status_akad', name: 'status_akad', orderable: false, searchable: false },
                    { data: 'keterangan', name: 'keterangan', orderable: false, searchable: true },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }]
            });

            // Inisialisasi DataTable Jadwal Akad
            var tableJadwal = $('.data-table-jadwal').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                responsive: true,
                ajax: {
                    url: "{{ route('akad.index') }}",
                    data: function(d) {
                        d.tab = 'jadwal';
                        d.filter = currentFilter;
                        d.bulan = filterBulan;
                        d.tahun = filterTahun;
                        d.start_date = customStart;
                        d.end_date = customEnd;
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'tgl_akad', name: 'tgl_akad', orderable: false, searchable: false },
                    { data: 'total_akad', name: 'total_akad', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'keterangan', name: 'keterangan', orderable: false, searchable: true },
                    { data: 'action', name: 'action', orderable: false, searchable: false, visible: showActionColumn, className: 'text-center' }
                ],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }]
            });

            // Switch Tab Handler
            function switchTab(tab) {
                activeTab = tab;
                $('.nav-view-mode .btn-mode').removeClass('active');
                $('.nav-view-mode .btn-mode[data-tab="' + tab + '"]').addClass('active');

                if (tab === 'konsumen') {
                    $('#pageTitleText').text('Data Konsumen Akad');
                    $('#tableKonsumenWrapper').show();
                    $('#tableJadwalWrapper').hide();
                    $('#containerFilterKonsumen').show();
                    tableKonsumen.ajax.reload();
                } else {
                    $('#pageTitleText').text('Data Jadwal Akad');
                    $('#tableKonsumenWrapper').hide();
                    $('#tableJadwalWrapper').show();
                    $('#containerFilterKonsumen').hide();
                    tableJadwal.ajax.reload();
                }
                updateFilterUI();
            }

            $('.nav-view-mode .btn-mode').on('click', function() {
                var tab = $(this).data('tab');
                switchTab(tab);
            });

            if (activeTab === 'jadwal') {
                switchTab('jadwal');
            } else {
                updateFilterUI();
            }

            // Filter button click actions
            $('.filter-pill-nav .pill-btn').on('click', function(e) {
                var filter = $(this).data('filter');
                if (filter === 'pilih_bulan') {
                    $('#panelCustomTanggal').collapse('hide');
                    $('#panelPilihBulan').collapse('toggle');
                    return;
                }
                if (filter === 'custom') {
                    $('#panelPilihBulan').collapse('hide');
                    $('#panelCustomTanggal').collapse('toggle');
                    return;
                }

                $('#panelPilihBulan').collapse('hide');
                $('#panelCustomTanggal').collapse('hide');

                currentFilter = filter;
                updateFilterUI();
                if (activeTab === 'konsumen') {
                    tableKonsumen.ajax.reload();
                } else {
                    tableJadwal.ajax.reload();
                }
            });

            // Form submit: Pilih Bulan
            $('#formPilihBulan').on('submit', function(e) {
                e.preventDefault();
                currentFilter = 'pilih_bulan';
                filterBulan = $('#selectBulan').val();
                filterTahun = $('#selectTahun').val();
                updateFilterUI();
                if (activeTab === 'konsumen') {
                    tableKonsumen.ajax.reload();
                } else {
                    tableJadwal.ajax.reload();
                }
            });

            // Form submit: Custom Tanggal
            $('#formCustomTanggal').on('submit', function(e) {
                e.preventDefault();
                currentFilter = 'custom';
                customStart = $('#inputStartDate').val();
                customEnd = $('#inputEndDate').val();
                updateFilterUI();
                if (activeTab === 'konsumen') {
                    tableKonsumen.ajax.reload();
                } else {
                    tableJadwal.ajax.reload();
                }
            });

            // Filter Lokasi & Marketing
            $('#filter_lokasi').on('change', function() {
                filterLokasi = $(this).val();
                updateFilterUI();
                tableKonsumen.ajax.reload();
            });

            $('#filter_marketing').on('change', function() {
                filterMarketing = $(this).val();
                updateFilterUI();
                tableKonsumen.ajax.reload();
            });

            $('#btn-reset-filter').on('click', function() {
                $('#filter_lokasi').val('').trigger('change');
                $('#filter_marketing').val('').trigger('change');
                filterLokasi = '';
                filterMarketing = '';
                updateFilterUI();
                tableKonsumen.ajax.reload();
            });

            // Edit Jadwal Button
            $(document).on('click', '.edit-button', function() {
                var url = $(this).data('url');
                $.get(url, function(response) {
                    if (response.status === 'success') {
                        $('#primary_id').val(response.data.id);
                        $('#tgl_akad').val(response.data.tgl_akad);
                        $('#keterangan').val(response.data.keterangan);
                        $('#modalForm').modal('show');
                    }
                });
            });

            $('#modalForm').on('shown.bs.modal', function() {
                $('#id_customer').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $('#modalForm'),
                    placeholder: "-- Cari Nama Konsumen / Blok / Perumahan --",
                    allowClear: true
                });
            });

            $('#modalForm').on('hidden.bs.modal', function() {
                $('#formData')[0].reset();
                $('#primary_id').val('');
                $('#id_customer').val(null).trigger('change');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let submitBtn = $('#submitBtn');
                let spinner = submitBtn.find('.spinner-border');
                let btnText = submitBtn.find('.button-text');

                spinner.addClass('d-none');
                btnText.html('<i class="fas fa-save mr-1"></i> Simpan Data Akad');
                submitBtn.prop('disabled', false);
            });

            $('#formData').on('submit', function(e) {
                e.preventDefault();
                let submitBtn = $('#submitBtn');
                let spinner = submitBtn.find('.spinner-border');
                let btnText = submitBtn.find('.button-text');

                spinner.removeClass('d-none');
                btnText.text('Menyimpan...');
                submitBtn.prop('disabled', true);

                let id = $('#primary_id').val();
                let url = id ? '{{ route('akad.update', ['akad' => ':id']) }}'.replace(':id', id) : '{{ route('akad.store') }}';
                let method = id ? 'PUT' : 'POST';

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let formData = new FormData(this);
                formData.append('_method', method);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        $('#modalForm').modal('hide');
                        if (typeof audio !== 'undefined') {
                            try { audio.play(); } catch(e) {}
                        }
                        let msg = res && res.message ? res.message : "Data telah disimpan!";
                        toastr.success(msg, "BERHASIL", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });
                        tableJadwal.ajax.reload();
                        tableKonsumen.ajax.reload();
                    },
                    error: function(xhr) {
                        let msg = "Terjadi kesalahan saat menyimpan!";
                        if (xhr.status === 422) {
                            msg = "Ada inputan yang salah!";
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, val) {
                                let input = $('#' + key);
                                input.addClass('is-invalid');
                                input.parent().find('.invalid-feedback').remove();
                                input.parent().append(
                                    '<span class="invalid-feedback" role="alert"><strong>' +
                                    val[0] + '</strong></span>'
                                );
                            });
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (typeof audio !== 'undefined') {
                            try { audio.play(); } catch(e) {}
                        }
                        toastr.error(msg, "GAGAL!", {
                            progressBar: true,
                            timeOut: 4000,
                            positionClass: "toast-bottom-right",
                        });
                    },
                    complete: function() {
                        spinner.addClass('d-none');
                        btnText.html('<i class="fas fa-save mr-1"></i> Simpan Data Akad');
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            $(document).on('click', '.delete-button', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Data ini akan dihapus secara permanen!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<span class="swal-btn-text">Ya, Hapus</span>',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: false,
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger mx-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    preConfirm: () => {
                        return new Promise((resolve) => {
                            const confirmBtn = Swal.getConfirmButton();
                            const btnText = confirmBtn.querySelector('.swal-btn-text');

                            btnText.innerHTML = `<span class="spinner-border spinner-border-sm mx-2" role="status" aria-hidden="true"></span> Menghapus...`;
                            confirmBtn.disabled = true;

                            $.ajax({
                                url: form.attr('action'),
                                method: 'POST',
                                data: form.serialize(),
                                success: function() {
                                    if (typeof audio !== 'undefined') audio.play();
                                    toastr.success("Data telah dihapus!", "BERHASIL", {
                                        progressBar: true,
                                        timeOut: 3500,
                                        positionClass: "toast-bottom-right"
                                    });
                                    tableJadwal.ajax.reload(null, false);
                                    tableKonsumen.ajax.reload(null, false);
                                    Swal.close();
                                },
                                error: function() {
                                    if (typeof audio !== 'undefined') audio.play();
                                    toastr.error("Gagal menghapus data.", "GAGAL!", {
                                        progressBar: true,
                                        timeOut: 3500,
                                        positionClass: "toast-bottom-right"
                                    });
                                    btnText.innerHTML = `Ya, Hapus`;
                                    confirmBtn.disabled = false;
                                }
                            });
                        });
                    }
                });
            });
        });
    </script>
@endpush
