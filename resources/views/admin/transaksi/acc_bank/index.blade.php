@extends('admin.layout_admin')
@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-3">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-lg">Data SP3K</h3>
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('export.transaksi.acc-bank') }}" target="_blank" class="btn btn-sm btn-success mr-2"><i class="fas fa-file-excel mr-1"></i> Excel</a>
                                        @if ($permissions['tambah'])
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalTambahSp3k">
                                                <i class="fas fa-plus mr-1"></i> Tambah SP3K
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered w-100 small table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th width="3%">No</th>
                                            <th>Nama Customer</th>
                                            <th>Lokasi Rumah</th>
                                            <th>Bank KPR</th>
                                            <th>Harga Jual</th>
                                            <th>ACC Plafon</th>
                                            <th>Tgl SP3K</th>
                                            <th>Tgl Expired</th>
                                            <th>Sisa Hari</th>
                                            <th class="text-center" width="13%">Action</th>
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
        </section>

        <!-- Modal Tambah SP3K -->
        <div class="modal fade" id="modalTambahSp3k" tabindex="-1" role="dialog" aria-labelledby="modalTambahSp3kLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold" id="modalTambahSp3kLabel"><i class="fas fa-file-contract mr-2"></i> Input Data SP3K (ACC Bank)</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="formTambahSp3k" action="{{ route('sp3k.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="font-weight-bold">Pilih Customer <span class="text-danger">*</span></label>
                                    <select name="id_customer" id="sp3k_id_customer" class="form-control select2" style="width: 100%" required>
                                        <option value="">-- Pilih Customer --</option>
                                        @foreach ($customerList as $cust)
                                            <option value="{{ $cust->id }}">
                                                {{ $cust->nama_lengkap }} (Blok: {{ $cust->kavling->kode_kavling ?? '-' }} - {{ $cust->lokasi->nama_kavling ?? '-' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="card card-outline card-info bg-light mb-3" id="customerInfoCard" style="display: none;">
                                <div class="card-body p-2">
                                    <div class="row small">
                                        <div class="col-md-3"><strong>Lokasi:</strong> <span id="info_lokasi">-</span></div>
                                        <div class="col-md-3"><strong>Blok / Tipe:</strong> <span id="info_blok">-</span> (<span id="info_tipe">-</span>)</div>
                                        <div class="col-md-3"><strong>Harga Jual:</strong> <span class="text-primary font-weight-bold" id="info_harga">Rp 0</span></div>
                                        <div class="col-md-3"><strong>Sisa Bayar:</strong> <span class="text-danger font-weight-bold" id="info_sisa">Rp 0</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Bank KPR <span class="text-danger">*</span></label>
                                    <select name="id_bank_kpr" id="sp3k_id_bank_kpr" class="form-control select2" style="width: 100%" required>
                                        <option value="">-- Pilih Bank KPR --</option>
                                        @foreach ($bankKPRList as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Notaris <span class="text-danger">*</span></label>
                                    <select name="id_notaris" id="sp3k_id_notaris" class="form-control select2" style="width: 100%" required>
                                        <option value="">-- Pilih Notaris --</option>
                                        @foreach ($notarisList as $notaris)
                                            <option value="{{ $notaris->id }}">{{ $notaris->nama_notaris }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Nomor SP3K <span class="text-danger">*</span></label>
                                    <input type="text" name="no_sp3k" class="form-control" placeholder="Contoh: 045/SP3K/BTN/2026" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">ACC Plafon <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="text" name="acc_plafon" id="sp3k_acc_plafon" class="form-control rupiah" placeholder="0" required>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Tenor (Tahun) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="tenor" id="sp3k_tenor" class="form-control" min="1" max="35" value="20" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">Thn</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Tanggal Terbit SP3K <span class="text-danger">*</span></label>
                                    <input type="date" name="tgl_terbit_sp3k" id="sp3k_tgl_terbit" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Tanggal Expired (Otomatis 90 Hari)</label>
                                    <input type="date" id="sp3k_tgl_expired_display" class="form-control bg-light" readonly>
                                    <small class="text-muted">Masa berlaku SP3K otomatis dihitung 90 hari kalender sejak tanggal terbit.</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Lampiran SP3K (Opsional)</label>
                                    <input type="file" name="lampiran" class="form-control-file border p-1 rounded w-100" accept=".pdf,.jpg,.jpeg,.png">
                                    <small class="text-muted">Format: PDF, JPG, PNG (Maks 2MB)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Catatan ACC (Opsional)</label>
                                    <textarea name="catatan_acc" class="form-control" rows="2" placeholder="Catatan khusus dari bank / analis"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary" id="btnSubmitSp3k">
                                <i class="fas fa-save mr-1"></i> Simpan SP3K
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        var permissions = @json($permissions);
        var showActionColumn = (permissions['edit'] == 1 || permissions['hapus'] == 1);

        $(function() {
            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                ordering: false,
                responsive: true,
                ajax: "{{ route('sp3k.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'wawancara.customer.nama_lengkap',
                        name: 'wawancara.customer.nama_lengkap',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'lokasi_rumah',
                        name: 'lokasi_rumah',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'bankKPR',
                        name: 'bankKPR',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'harga_jual',
                        name: 'harga_jual',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'acc_plafon',
                        name: 'acc_plafon',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tgl_terbit_sp3k',
                        name: 'tgl_terbit_sp3k',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tgl_expired',
                        name: 'tgl_expired',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'sisa_hari',
                        name: 'sisa_hari',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        visible: showActionColumn,
                        searchable: false
                    }
                ],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }, ]
            });

            // Hitung tanggal expired 90 hari saat tanggal terbit berubah
            function updateExpiredDate() {
                var tglTerbitVal = $('#sp3k_tgl_terbit').val();
                if (tglTerbitVal) {
                    var d = new Date(tglTerbitVal);
                    d.setDate(d.getDate() + 90);
                    var yyyy = d.getFullYear();
                    var mm = String(d.getMonth() + 1).padStart(2, '0');
                    var dd = String(d.getDate()).padStart(2, '0');
                    $('#sp3k_tgl_expired_display').val(yyyy + '-' + mm + '-' + dd);
                }
            }
            $('#sp3k_tgl_terbit').on('change', updateExpiredDate);
            updateExpiredDate();

            // Auto format rupiah input
            $('#sp3k_acc_plafon').on('input', function() {
                var val = $(this).val().replace(/[^0-9]/g, '');
                if (val) {
                    $(this).val(new Intl.NumberFormat('id-ID').format(val));
                } else {
                    $(this).val('');
                }
            });

            // Customer change -> Auto-fill
            $('#sp3k_id_customer').on('change', function() {
                var customerId = $(this).val();
                if (!customerId) {
                    $('#customerInfoCard').hide();
                    return;
                }

                $.ajax({
                    url: "{{ url('admin/transaksi/acc-bank/customer-detail') }}/" + customerId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            var data = res.data;
                            $('#info_lokasi').text(data.lokasi);
                            $('#info_blok').text(data.blok);
                            $('#info_tipe').text(data.tipe);
                            $('#info_harga').text('Rp ' + data.harga_jual_fmt);
                            $('#info_sisa').text('Rp ' + data.sisa_bayar_fmt);
                            $('#customerInfoCard').slideDown();

                            if (data.id_bank_kpr) {
                                $('#sp3k_id_bank_kpr').val(data.id_bank_kpr).trigger('change');
                            }
                            if (!$('#sp3k_acc_plafon').val() && data.harga_jual) {
                                $('#sp3k_acc_plafon').val(new Intl.NumberFormat('id-ID').format(data.harga_jual));
                            }
                        }
                    }
                });
            });

            // Inisialisasi modal saat dibuka
            $('#modalTambahSp3k').on('shown.bs.modal', function () {
                if ($().select2) {
                    $('#sp3k_id_customer').select2({ dropdownParent: $('#modalTambahSp3k') });
                    $('#sp3k_id_bank_kpr').select2({ dropdownParent: $('#modalTambahSp3k') });
                    $('#sp3k_id_notaris').select2({ dropdownParent: $('#modalTambahSp3k') });
                }
            });

            // Submit Form Tambah SP3K via AJAX
            $('#formTambahSp3k').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                var formData = new FormData(form);
                var btn = $('#btnSubmitSp3k');
                var originalHtml = btn.html();

                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan...');

                $.ajax({
                    url: $(form).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        btn.prop('disabled', false).html(originalHtml);
                        $('#modalTambahSp3k').modal('hide');
                        form.reset();
                        $('#customerInfoCard').hide();
                        if ($().select2) {
                            $('#sp3k_id_customer').val('').trigger('change');
                            $('#sp3k_id_bank_kpr').val('').trigger('change');
                            $('#sp3k_id_notaris').val('').trigger('change');
                        }

                        toastr.success(response.message || "Data SP3K berhasil ditambahkan!", "BERHASIL", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right"
                        });

                        $('.data-table').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(originalHtml);
                        var errMsg = "Gagal menyimpan data SP3K.";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            var firstKey = Object.keys(errors)[0];
                            errMsg = errors[firstKey][0];
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }

                        toastr.error(errMsg, "GAGAL!", {
                            progressBar: true,
                            timeOut: 4500,
                            positionClass: "toast-bottom-right"
                        });
                    }
                });
            });
        });

        $(document).on('click', '.delete-button', function(e) {
            e.preventDefault();

            const form = $(this).closest('form');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'SP3K akan dibatalkan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<span class="swal-btn-text">Ya, Batalkan</span>',
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

                        btnText.innerHTML =
                            `<span class="spinner-border spinner-border-sm mx-2" role="status" aria-hidden="true"></span> Memproses...`;
                        confirmBtn.disabled = true;

                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: form.serialize(),
                            success: function() {
                                audio.play();
                                toastr.success("SP3K telah dibatalkan!",
                                "BERHASIL", {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: "toast-bottom-right"
                                });

                                $('.data-table').DataTable().ajax.reload(null,
                                    false);
                                Swal.close();
                            },
                            error: function() {
                                audio.play();
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
    </script>
@endpush
