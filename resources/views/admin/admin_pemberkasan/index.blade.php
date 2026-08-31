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
                        <div class="card">
                            <div class="card-header p-3">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-lg">Admin Pemberkasan</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-striped data-table w-100">
                                    <thead>
                                        <tr>
                                            <th width="30px">No</th>
                                            <th>Customer</th>
                                            <th>Marketing</th>
                                            <th>Lokasi</th>
                                            <th>Status</th>
                                            <th>Admin</th>
                                            <th class="text-center" width="150px">Action</th>
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
    </div>

    <!-- Modal Setting Admin -->
    <div class="modal fade" id="modalSettingAdmin" tabindex="-1" role="dialog" aria-labelledby="modalSettingAdminLabel" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white font-weight-bold" id="modalSettingAdminLabel">Setting Admin Pemberkasan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formSettingAdmin">
                    @csrf
                    <input type="hidden" id="setting_id" name="id">
                    <div class="modal-body">
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Admin Pemberkasan</label>
                            <div class="col-sm-8">
                                <select class="form-control select-admin" name="id_admin_pemberkasan" id="id_admin_pemberkasan">
                                    <option value="">- Pilih Admin -</option>
                                    @foreach ($admins as $admin)
                                        <option value="{{ $admin->id }}">{{ $admin->username }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info ms-1" id="submitBtnSetting">
                            <span class="spinner-border spinner-border-sm mx-1 d-none" role="status" aria-hidden="true"></span>
                            <span class="button-text">Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const successMsg = sessionStorage.getItem('success');
            if (successMsg) {
                audio.play();
                toastr.success(successMsg, "BERHASIL", {
                    progressBar: true,
                    timeOut: 3500,
                    positionClass: "toast-bottom-right",
                });
                sessionStorage.removeItem('success');
            }
        });

        $(document).ready(function() {
            $('.select-admin').select2({
                theme: "bootstrap4",
                placeholder: "Pilih Admin",
                allowClear: true,
            });
        });

        var permissions = @json($permissions);

        $(function() {
            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                responsive: true,
                ordering: false,
                ajax: "{{ route('admin-pemberkasan.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'nama_lengkap',
                        name: 'nama_lengkap',
                        searchable: true
                    },
                    {
                        data: 'nama_marketing',
                        name: 'nama_marketing',
                    },
                    {
                        data: 'kode_kavling',
                        name: 'kode_kavling',
                    },
                    {
                        data: 'stt_reg',
                        name: 'stt_reg',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'admin_pemberkasan',
                        name: 'admin_pemberkasan',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        visible: permissions['edit'] == 1
                    }
                ],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }]
            });
        });

        $(document).on('click', '.setting-admin-button', function() {
            var id = $(this).data('id');
            var adminId = $(this).data('admin');

            $('#setting_id').val(id);
            $('#id_admin_pemberkasan').val(adminId).trigger('change');
            $('#modalSettingAdmin').modal('show');
        });

        $('#formSettingAdmin').on('submit', function(e) {
            e.preventDefault();

            let submitBtn = $('#submitBtnSetting');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            let formData = new FormData(this);

            $.ajax({
                url: '{{ route('admin-pemberkasan.set-admin') }}',
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#modalSettingAdmin').modal('hide');
                    audio.play();
                    toastr.success("Admin Pemberkasan berhasil disimpan!", "BERHASIL", {
                        progressBar: true,
                        timeOut: 3500,
                        positionClass: "toast-bottom-right",
                    });
                    $('.data-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        audio.play();
                        toastr.error("Ada inputan yang salah!", "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });

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
                    } else {
                        audio.play();
                        toastr.error("Gagal menyimpan data!", "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });
                    }
                    spinner.addClass('d-none');
                    btnText.text('Simpan');
                    submitBtn.prop('disabled', false);
                }
            });
        });

        $('#modalSettingAdmin').on('hidden.bs.modal', function() {
            $('#formSettingAdmin')[0].reset();
            $('#setting_id').val('');
            $('#id_admin_pemberkasan').val('').trigger('change');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let submitBtn = $('#submitBtnSetting');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.addClass('d-none');
            btnText.text('Simpan');
            submitBtn.prop('disabled', false);
        });
    </script>
@endpush
