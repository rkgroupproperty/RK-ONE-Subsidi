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
                                    <h3 class="font-weight-bold text-lg">Data Proses Marketing</h3>
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('export.transaksi.proses-marketing') }}" target="_blank" class="btn btn-sm btn-success mr-2"><i class="fas fa-file-excel mr-1"></i> Excel</a>
                                        @if ($permissions['tambah'])
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                                data-target="#modalForm">
                                                <i class="fas fa-plus"></i> Tambah Proses Marketing
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th width="50px">No</th>
                                            <th>Nama</th>
                                            <th>Telp</th>
                                            <th>Blok / Unit</th>
                                            <th>Lokasi</th>
                                            <th>Status</th>
                                            <th width="120px">Action</th>
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

        <div class="modal fade" id="modalForm" tabindex="-1" role="dialog" data-focus="false"
            aria-labelledby="modalFormLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-indigo">
                        <h5 class="modal-title text-white font-weight-bold" id="modalFormLabel"></h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="formData" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="primary_id" name="primary_id">
                        <div class="modal-body">
                            <div class="form-group row">
                                <label for="id_customer" class="col-sm-3 col-form-label">Customer</label>
                                <div class="col-sm-9">
                                    <select name="id_customer" id="id_customer" class="form-control select-customer">
                                        <option value=""></option>
                                        @foreach ($customerList as $c)
                                            <option value="{{ $c->id }}">{{ $c->nama_lengkap }} ({{ $c->nik }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <hr>
                            <h6 class="font-weight-bold mb-3">1. Data Pribadi</h6>

                            <div class="form-group row">
                                <label for="nama_lengkap" class="col-sm-3 col-form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="nik" class="col-sm-3 col-form-label">NIK <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <input type="text" name="nik" id="nik" class="form-control">
                                </div>
                                <label for="no_telp" class="col-sm-2 col-form-label">No. Telp / WA <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input type="text" name="no_telp" id="no_telp" class="form-control">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="tempat_lahir" class="col-sm-3 col-form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control">
                                </div>
                                <label for="tgl_lahir" class="col-sm-2 col-form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input type="date" name="tgl_lahir" id="tgl_lahir" class="form-control">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="jenis_kelamin" class="col-sm-3 col-form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <select class="form-control select-jk" name="jenis_kelamin" id="jenis_kelamin">
                                        <option value=""></option>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                                <label for="email" class="col-sm-2 col-form-label">Email</label>
                                <div class="col-sm-4">
                                    <input type="text" name="email" id="email" class="form-control">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="npwp" class="col-sm-3 col-form-label">NPWP</label>
                                <div class="col-sm-3">
                                    <input type="text" name="npwp" id="npwp" class="form-control">
                                </div>
                                <label for="no_bpjs_kes" class="col-sm-2 col-form-label">No. BPJS Kes</label>
                                <div class="col-sm-4">
                                    <input type="text" name="no_bpjs_kes" id="no_bpjs_kes" class="form-control">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="pekerjaan" class="col-sm-3 col-form-label">Pekerjaan</label>
                                <div class="col-sm-3">
                                    <select class="form-control select-pekerjaan" name="pekerjaan" id="pekerjaan">
                                        <option value=""></option>
                                        <option value="Wiraswasta">Wiraswasta</option>
                                        <option value="Pegawai Swasta">Pegawai Swasta</option>
                                        <option value="ASN">ASN</option>
                                        <option value="TNI atau Polri">TNI atau Polri</option>
                                        <option value="Karyawan BUMN">Karyawan BUMN</option>
                                        <option value="Karyawan">Karyawan</option>
                                        <option value="Buruh">Buruh</option>
                                        <option value="Petani">Petani</option>
                                        <option value="Pedagang">Pedagang</option>
                                        <option value="Sopir">Sopir</option>
                                        <option value="Guru/Dosen">Guru/Dosen</option>
                                        <option value="Dokter">Dokter</option>
                                        <option value="Ibu Rumah Tangga">Ibu Rumah Tangga</option>
                                        <option value="Pensiunan">Pensiunan</option>
                                        <option value="Pelajar/Mahasiswa">Pelajar/Mahasiswa</option>
                                        <option value="Freelancer">Freelancer</option>
                                        <option value="Lain-lain">Lain-lain</option>
                                    </select>
                                </div>
                                <div class="col-sm-6" id="row-pekerjaan-lain" style="display: none;">
                                    <input type="text" class="form-control" name="pekerjaan_lain" id="pekerjaan_lain"
                                        placeholder="Pekerjaan lainnya">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="alamat_ktp" class="col-sm-3 col-form-label">Alamat KTP <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <textarea name="alamat_ktp" id="alamat_ktp" class="form-control" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="alamat_domisili" class="col-sm-3 col-form-label">Alamat Domisili <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <textarea name="alamat_domisili" id="alamat_domisili" class="form-control" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="status_pernikahan" class="col-sm-3 col-form-label">Status Pernikahan <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <select class="form-control select-status" name="status_pernikahan" id="status_pernikahan">
                                        <option value=""></option>
                                        <option value="Belum Menikah">Belum Menikah</option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                </div>
                            </div>

                            <div id="pasangan" style="display: none;">
                                <div class="form-group row">
                                    <label for="nama_p" class="col-sm-3 col-form-label">Nama Pasangan</label>
                                    <div class="col-sm-3">
                                        <input name="nama_p" id="nama_p" class="form-control" type="text">
                                    </div>
                                    <label for="nik_p" class="col-sm-2 col-form-label">NIK Pasangan</label>
                                    <div class="col-sm-4">
                                        <input name="nik_p" id="nik_p" class="form-control" type="text">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="nama_saudara" class="col-sm-3 col-form-label">Nama Saudara</label>
                                <div class="col-sm-3">
                                    <input name="nama_saudara" id="nama_saudara" class="form-control" type="text">
                                </div>
                                <label for="no_telp_saudara" class="col-sm-2 col-form-label">No. Telp Saudara</label>
                                <div class="col-sm-4">
                                    <input name="no_telp_saudara" id="no_telp_saudara" class="form-control" type="text">
                                </div>
                            </div>

                            <hr>
                            <h6 class="font-weight-bold mb-3">2. Data Blok / Unit</h6>

                            <div class="form-group row">
                                <label for="lokasi_display" class="col-sm-3 col-form-label">Lokasi Perumahan <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <input type="text" id="lokasi_display" class="form-control" readonly>
                                </div>
                                <label for="kode_kavling_display" class="col-sm-2 col-form-label">Blok / Unit <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input type="text" id="kode_kavling_display" class="form-control" readonly>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="total_harga_display" class="col-sm-3 col-form-label">Harga Jual</label>
                                <div class="col-sm-3">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp.</span>
                                        </div>
                                        <input type="text" id="total_harga_display" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6 class="font-weight-bold mb-3">3. Data Pembelian</h6>

                            <div class="form-group row">
                                <label for="id_marketing" class="col-sm-3 col-form-label">Marketing <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <select class="form-control select-marketing" name="id_marketing" id="id_marketing">
                                        <option value=""></option>
                                        <option value="0">Non Marketing</option>
                                        @foreach ($marketingList as $m)
                                            <option value="{{ $m->id }}">{{ $m->nama_marketing }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label for="sumber_prospek" class="col-sm-2 col-form-label">Sumber Prospek <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <select class="form-control select-sumber-prospek" name="sumber_prospek" id="sumber_prospek">
                                        <option value=""></option>
                                        <option value="Iklan Kantor">Iklan Kantor</option>
                                        <option value="Market Place FB">Market Place FB</option>
                                        <option value="Freelance">Freelance</option>
                                        <option value="Kanvasing">Kanvasing</option>
                                        <option value="Sosmed Pribadi">Sosmed Pribadi</option>
                                        <option value="Sosmed Kantor">Sosmed Kantor</option>
                                        <option value="Referensi">Referensi</option>
                                        <option value="WIC">WIC</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="jenis_perumahan" class="col-sm-3 col-form-label">Jenis Perumahan <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <select class="form-control select-jp" name="jenis_perumahan" id="jenis_perumahan">
                                        <option value=""></option>
                                        <option value="Subsidi">Subsidi</option>
                                        <option value="Komersil">Komersil</option>
                                    </select>
                                </div>
                                <label for="jenis_pembelian" class="col-sm-2 col-form-label">Jenis Pembelian <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <select class="form-control select-pembelian" name="jenis_pembelian" id="jenis_pembelian">
                                        <option value=""></option>
                                        <option value="Pembelian Cash">Pembelian Cash</option>
                                        <option value="Cash Bertahap">Cash Bertahap</option>
                                        <option value="KPR">KPR</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="besaran_dp" class="col-sm-3 col-form-label">Besaran DP <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp.</span>
                                        </div>
                                        <input name="besaran_dp" id="besaran_dp" class="form-control format-number" type="text">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="diskon" class="col-sm-3 col-form-label">Diskon</label>
                                <div class="col-sm-3">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp.</span>
                                        </div>
                                        <input name="diskon" id="diskon" class="form-control format-number" type="text">
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <h6 class="font-weight-bold mb-3">4. File Upload</h6>
                            <small class="text-muted d-block mb-2">Berkas yang sudah ada tidak perlu diunggah ulang.</small>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Foto Pemohon</label>
                                <div class="col-sm-3">
                                    <input name="foto_pemohon" id="foto_pemohon" type="file" accept=".jpg,.jpeg,.png"
                                        onchange="handleFileChange(this, 'preview_foto_pemohon')">
                                    <div id="preview_foto_pemohon" class="mt-1 d-none">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('foto_pemohon', 'preview_foto_pemohon')">Hapus</button>
                                    </div>
                                    <div id="existing_foto_pemohon" class="mt-1"></div>
                                </div>
                                <label class="col-sm-2 col-form-label">Foto KTP <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input name="foto_ktp" id="foto_ktp" type="file" accept=".jpg,.jpeg,.png"
                                        onchange="handleFileChange(this, 'preview_foto_ktp')">
                                    <div id="preview_foto_ktp" class="mt-1 d-none">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('foto_ktp', 'preview_foto_ktp')">Hapus</button>
                                    </div>
                                    <div id="existing_foto_ktp" class="mt-1"></div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Foto NPWP</label>
                                <div class="col-sm-3">
                                    <input name="foto_npwp" id="foto_npwp" type="file" accept=".jpg,.jpeg,.png"
                                        onchange="handleFileChange(this, 'preview_foto_npwp')">
                                    <div id="preview_foto_npwp" class="mt-1 d-none">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('foto_npwp', 'preview_foto_npwp')">Hapus</button>
                                    </div>
                                    <div id="existing_foto_npwp" class="mt-1"></div>
                                </div>
                                <label class="col-sm-2 col-form-label">Foto KK</label>
                                <div class="col-sm-4">
                                    <input name="foto_kk" id="foto_kk" type="file" accept=".jpg,.jpeg,.png"
                                        onchange="handleFileChange(this, 'preview_foto_kk')">
                                    <div id="preview_foto_kk" class="mt-1 d-none">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('foto_kk', 'preview_foto_kk')">Hapus</button>
                                    </div>
                                    <div id="existing_foto_kk" class="mt-1"></div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Foto BPJS</label>
                                <div class="col-sm-3">
                                    <input name="foto_bpjs" id="foto_bpjs" type="file" accept=".jpg,.jpeg,.png"
                                        onchange="handleFileChange(this, 'preview_foto_bpjs')">
                                    <div id="preview_foto_bpjs" class="mt-1 d-none">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('foto_bpjs', 'preview_foto_bpjs')">Hapus</button>
                                    </div>
                                    <div id="existing_foto_bpjs" class="mt-1"></div>
                                </div>
                                <label class="col-sm-2 col-form-label">Foto KTP Pasangan</label>
                                <div class="col-sm-4">
                                    <input name="foto_ktp_p" id="foto_ktp_p" type="file" accept=".jpg,.jpeg,.png"
                                        onchange="handleFileChange(this, 'preview_foto_ktp_p')">
                                    <div id="preview_foto_ktp_p" class="mt-1 d-none">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('foto_ktp_p', 'preview_foto_ktp_p')">Hapus</button>
                                    </div>
                                    <div id="existing_foto_ktp_p" class="mt-1"></div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Bukti Transfer</label>
                                <div class="col-sm-3">
                                    <input name="file_bukti" id="file_bukti" type="file" accept=".jpg,.jpeg,.png,.pdf"
                                        onchange="handleFileChange(this, 'preview_file_bukti')">
                                    <div id="preview_file_bukti" class="mt-1 d-none">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('file_bukti', 'preview_file_bukti')">Hapus</button>
                                    </div>
                                    <div id="existing_file_bukti" class="mt-1"></div>
                                </div>
                                <label class="col-sm-2 col-form-label">Bukti Proses Admin</label>
                                <div class="col-sm-4">
                                    <input name="file_sppr" id="file_sppr" type="file" accept=".jpg,.jpeg,.png,.pdf"
                                        onchange="handleFileChange(this, 'preview_file_sppr')">
                                    <div id="preview_file_sppr" class="mt-1 d-none">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('file_sppr', 'preview_file_sppr')">Hapus</button>
                                    </div>
                                    <div id="existing_file_sppr" class="mt-1"></div>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary ms-1" id="submitBtn">
                                <span class="spinner-border spinner-border-sm mx-1 d-none" role="status" aria-hidden="true"></span>
                                <span class="button-text">Simpan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" data-focus="false"
            aria-labelledby="previewModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-indigo">
                        <h5 class="modal-title text-white font-weight-bold" id="previewModalLabel">Preview File</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="modalPreviewImage" class="img-fluid" alt="Preview">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Keluar</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
@push('scripts')
    <script>
        var audio = new Audio('{{ asset('audio/notification.ogg') }}');
        var permissions = @json($permissions);
        var showActionColumn = (permissions['edit'] == 1);
        var berkasField = ['foto_pemohon', 'foto_ktp', 'foto_npwp', 'foto_kk', 'foto_bpjs', 'foto_ktp_p', 'file_bukti', 'file_sppr'];
        var berkasUrl = '{{ asset('assets/customer') }}/';

        $(document).on('click', '[data-target="#modalForm"]', function() {
            $('#modalFormLabel').text('Tambah Proses Marketing');
            $('#id_customer').val('').trigger('change').prop('disabled', false);
        });

        $(function() {
            $('.select-customer').select2({
                theme: "bootstrap4",
                width: '100%',
                placeholder: "Pilih Customer",
                dropdownParent: $('#modalForm'),
            });
            $('.select-jk, .select-status, .select-jp, .select-pembelian').select2({
                theme: "bootstrap4",
                width: '100%',
                dropdownParent: $('#modalForm'),
            });
            $('.select-pekerjaan, .select-marketing, .select-sumber-prospek').select2({
                theme: "bootstrap4",
                width: '100%',
                dropdownParent: $('#modalForm'),
            });

            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                ordering: false,
                responsive: true,
                ajax: "{{ route('proses-marketing.index') }}",
                columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                }, {
                    data: 'nama_lengkap',
                    name: 'nama_lengkap',
                    searchable: true
                }, {
                    data: 'no_telp',
                    name: 'no_telp',
                    searchable: true
                }, {
                    data: 'blok_unit',
                    name: 'blok_unit',
                    searchable: true
                }, {
                    data: 'lokasi_nama',
                    name: 'lokasi_nama',
                    searchable: true
                }, {
                    data: 'status_progres',
                    name: 'status_progres',
                    searchable: false
                }, {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    visible: showActionColumn,
                    className: 'text-center'
                }],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }]
            });
        });

        function formatNumber(num) {
            if (num === null || num === undefined || num === '') return '';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function setSelect(id, value) {
            let $el = $('#' + id);
            value = (value === null || value === undefined) ? '' : String(value);

            if (value && $el.find('option[value="' + value + '"]').length === 0) {
                $el.append($('<option>', { value: value, text: value }));
            }

            $el.val(value).trigger('change');
        }

        function isiForm(d) {
            $('#primary_id').val(d.id_customer);
            $('#nama_lengkap').val(d.nama_lengkap || '');
            $('#nik').val(d.nik || '');
            $('#tempat_lahir').val(d.tempat_lahir || '');
            $('#tgl_lahir').val(d.tgl_lahir || '');
            $('#no_telp').val(d.no_telp || '');
            $('#email').val(d.email || '');
            $('#npwp').val(d.npwp || '');
            $('#no_bpjs_kes').val(d.no_bpjs_kes || '');
            $('#alamat_ktp').val(d.alamat_ktp || '');
            $('#alamat_domisili').val(d.alamat_domisili || '');
            $('#nama_p').val(d.nama_p || '');
            $('#nik_p').val(d.nik_p || '');
            $('#nama_saudara').val(d.nama_saudara || '');
            $('#no_telp_saudara').val(d.no_telp_saudara || '');
            $('#besaran_dp').val(formatNumber(d.besaran_dp));
            $('#diskon').val(formatNumber(d.diskon));
            $('#lokasi_display').val(d.lokasi_nama || '');
            $('#kode_kavling_display').val(d.kode_kavling || '');
            $('#total_harga_display').val(formatNumber(d.total_harga));

            setSelect('jenis_kelamin', d.jenis_kelamin);
            setSelect('status_pernikahan', d.status_pernikahan);
            setSelect('id_marketing', d.id_marketing);
            setSelect('sumber_prospek', d.sumber_prospek);
            setSelect('jenis_perumahan', d.jenis_perumahan);
            setSelect('jenis_pembelian', d.jenis_pembelian);

            let pekerjaan = d.pekerjaan || '';
            let daftarPekerjaan = $('#pekerjaan option').map(function() {
                return $(this).val();
            }).get();

            if (pekerjaan && daftarPekerjaan.indexOf(pekerjaan) === -1) {
                $('#pekerjaan').append($('<option>', { value: pekerjaan, text: pekerjaan }));
            }
            setSelect('pekerjaan', pekerjaan);

            berkasField.forEach(function(field) {
                let $existing = $('#existing_' + field);
                $existing.empty();

                if (d.berkas && d.berkas[field]) {
                    $existing.html('<a href="' + berkasUrl + d.berkas[field] + '" target="_blank">' +
                        '<i class="fas fa-paperclip mr-1"></i>Lihat berkas saat ini</a>');
                }
            });

            togglePasangan();
            togglePekerjaanLain();
        }
        var pekerjaanAwal = $('#pekerjaan option').map(function() {
            return $(this).val();
        }).get();

        function togglePasangan() {
            if ($('#status_pernikahan').val() === 'Menikah') {
                $('#pasangan').fadeIn();
            } else {
                $('#pasangan').fadeOut();
                $('#nama_p, #nik_p').val('');
            }
        }

        function togglePekerjaanLain() {
            if ($('#pekerjaan').val() === 'Lain-lain') {
                $('#row-pekerjaan-lain').fadeIn();
            } else {
                $('#row-pekerjaan-lain').fadeOut();
                $('#pekerjaan_lain').val('');
            }
        }

        $(document).on('change', '#id_customer', function() {
            let id = $(this).val();
            if (!id) {
                return;
            }

            const url = '{{ route('proses-marketing.get-customer-detail', ':id') }}'.replace(':id', id);
            $.get(url, function(res) {
                if (res.status === 'success') {
                    isiForm(res.data);
                }
            });
        });

        $(document).on('click', '.edit-button', function() {
            var url = $(this).data('url');
            $.get(url, function(response) {
                if (response.status === 'success') {
                    $('#modalFormLabel').text('Edit Proses Marketing');
                    isiForm(response.data);
                    $('#id_customer').val(response.data.id_customer).trigger('change').prop('disabled', true);
                    $('#modalForm').modal('show');
                }
            });
        });

        $(document).on('change', '#status_pernikahan', function() {
            togglePasangan();
        });

        $(document).on('change', '#pekerjaan', function() {
            togglePekerjaanLain();
        });

        function handleFileChange(input, previewId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewDiv = document.getElementById(previewId);
                    const viewBtn = previewDiv.querySelector('button.btn-primary');
                    viewBtn.setAttribute('data-src', e.target.result);

                    input.style.display = 'none';
                    previewDiv.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        }

        function showPreview(el) {
            document.getElementById('modalPreviewImage').src = el.getAttribute('data-src');
            $('#previewModal').modal('show');
        }

        function clearFile(inputId, previewId) {
            const input = document.getElementById(inputId);
            input.value = '';
            input.style.display = 'block';
            document.getElementById(previewId).classList.add('d-none');
        }

        $('#modalForm').on('hidden.bs.modal', function() {
            $('#formData')[0].reset();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('#primary_id').val('');
            $('#id_customer').val('').trigger('change').prop('disabled', false);
            $('#lokasi_display, #kode_kavling_display, #total_harga_display').val('');

            berkasField.forEach(function(field) {
                $('#existing_' + field).empty();
                let preview = document.getElementById('preview_' + field);
                let input = document.getElementById(field);
                if (preview) preview.classList.add('d-none');
                if (input) input.style.display = 'block';
            });

            $('#pekerjaan option').each(function() {
                if (pekerjaanAwal.indexOf($(this).val()) === -1) {
                    $(this).remove();
                }
            });

            $('#jenis_kelamin, #status_pernikahan, #id_marketing, #sumber_prospek, #jenis_perumahan, #jenis_pembelian, #pekerjaan')
                .val('').trigger('change');
            togglePasangan();
            $('#row-pekerjaan-lain').hide();

            let submitBtn = $('#submitBtn');
            submitBtn.find('.spinner-border').addClass('d-none');
            submitBtn.find('.button-text').text('Simpan');
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
            let url = id ? '{{ route('proses-marketing.update', ['proses_marketing' => ':id']) }}'.replace(':id', id) :
                '{{ route('proses-marketing.store') }}';
            let method = id ? 'PUT' : 'POST';

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);

            if (id) {
                formData.set('id_customer', $('#id_customer').val());
            }

            formData.append('_method', method);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    $('#modalForm').modal('hide');
                    audio.play();
                    let msg = id ? "Data berhasil diupdate!" : "Data berhasil ditambahkan!";
                    toastr.success(msg, "BERHASIL", {
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
                        toastr.error("Gagal menyimpan data.", "GAGAL!", {
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
    </script>
@endpush