@extends('admin.layout_admin')
@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" style="border-radius: 16px;">
                            <div class="card-header p-3 bg-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <div class="text-xs font-weight-bold text-uppercase text-primary mb-1">
                                            <i class="fas fa-building-columns mr-1"></i> Data Konsumen Bank Penyalur KPR
                                        </div>
                                        <h3 class="font-weight-bold text-lg mb-0 text-dark">
                                            Bank {{ $bank->nama }}
                                        </h3>
                                    </div>
                                    <a href="{{ route('beranda.index') }}" class="btn btn-sm btn-secondary font-weight-bold px-3 py-2" style="border-radius: 10px;">
                                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
                                    </a>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered w-100 table-striped data-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="5%" class="text-center">No</th>
                                                <th>Tanggal</th>
                                                <th>Nama Nasabah</th>
                                                <th>Marketing</th>
                                                <th>Perumahan & Blok</th>
                                                <th>Status Progres</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                responsive: true,
                ajax: "{{ route('beranda.detail-customer-bank.data', $bank->id) }}",
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center font-weight-bold'
                    },
                    {
                        data: 'tgl_terima',
                        name: 'tgl_terima',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama_lengkap',
                        name: 'nama_lengkap',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'id_marketing',
                        name: 'id_marketing',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id_lokasi',
                        name: 'id_lokasi',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id_status_progres',
                        name: 'id_status_progres',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    search: "Cari Nasabah:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ nasabah",
                    infoEmpty: "Tidak ada data nasabah untuk bank ini",
                    zeroRecords: "Data nasabah tidak ditemukan",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "Lanjut",
                        previous: "Sebelum"
                    }
                }
            });
        });
    </script>
    @endpush
@endsection
