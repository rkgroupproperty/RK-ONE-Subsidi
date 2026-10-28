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
                            <div class="card-header p-3 bg-indigo text-white">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-lg">Verifikasi Data Booking</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Tanggal</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->tgl_booking_formatted }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Nama Lengkap</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->nama_lengkap }}" class="form-control"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">NIK</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->nik }}" class="form-control"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Jenis
                                                Kelamin</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->jenis_kelamin }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Tempat Lahir</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->tempat_lahir }}" class="form-control"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Tanggal
                                                Lahir</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->tgl_lahir_formatted }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Alamat</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->alamat }}" class="form-control"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">NPWP</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->npwp }}" class="form-control"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="no_bpjs_kes" class="col-sm-4 col-form-label">No. BPJS Kes</label>
                                            <div class="col-sm-8">
                                                <input type="text" id="no_bpjs_kes" value="{{ $data->no_bpjs_kes }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Email</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->email }}" class="form-control"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Nomor
                                                Telepon</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->no_telp }}" class="form-control"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Nama
                                                Saudara</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->nama_saudara }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">No. Telp
                                                Saudara</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->no_telp_saudara }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label">Nama Marketing</label>
                                            <div class="col-sm-8">
                                                <input type="text"
                                                    value="{{ $data->marketing->nama_marketing ?? '-' }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label">Lokasi Perumahan</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->lokasi->nama_kavling ?? '-' }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label">Blok Kavling</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->kavling->kode_kavling ?? '-' }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        @foreach ($data->rincian_biaya as $item)
                                            @if (($item['nilai'] ?? 0) > 0)
                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label">{{ $item['nama'] ?? '-' }}</label>
                                                    <div class="col-sm-8">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">Rp.</span>
                                                            </div>
                                                            <input type="text"
                                                                value="{{ number_format($item['nilai'], 0, ',', '.') }}"
                                                                class="form-control" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label"><strong>Total Harga</strong></label>
                                            <div class="col-sm-8">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp.</span>
                                                    </div>
                                                    <input type="text"
                                                        value="{{ number_format($data->total_harga, 0, ',', '.') }}"
                                                        class="form-control" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Jenis
                                                Pembayaran</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->jenis_pembayaran }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Jenis
                                                Perumahan</label>
                                            <div class="col-sm-8">
                                                <input type="text" value="{{ $data->jenis_perumahan }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Booking
                                                Fee</label>
                                            <div class="col-sm-8">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp.</span>
                                                    </div>
                                                    <input type="text" name="booking_fee" id="booking_fee"
                                                        value="{{ number_format((float) $data->booking_fee, 0, ',', '.') }}"
                                                        class="form-control" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto KTP</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_ktp)
                                                        <img src="{{ asset('assets/booking/' . $data->foto_ktp) }}"
                                                            style="max-width: 100%; max-height: 100%;">
                                                    @else
                                                        <span style="color: #6c757d;">Tidak ada file</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto NPWP</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_npwp)
                                                        <img src="{{ asset('assets/booking/' . $data->foto_npwp) }}"
                                                            style="max-width: 100%; max-height: 100%;">
                                                    @else
                                                        <span style="color: #6c757d;">Tidak ada file</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto KK</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_kk)
                                                        <img src="{{ asset('assets/booking/' . $data->foto_kk) }}"
                                                            style="max-width: 100%; max-height: 100%;">
                                                    @else
                                                        <span style="color: #6c757d;">Tidak ada file</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto BPJS</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_bpjs)
                                                        <img src="{{ asset('assets/booking/' . $data->foto_bpjs) }}"
                                                            style="max-width: 100%; max-height: 100%;">
                                                    @else
                                                        <span style="color: #6c757d;">Tidak ada file</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto KTP Pasangan</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_ktp_p)
                                                        <img src="{{ asset('assets/booking/' . $data->foto_ktp_p) }}"
                                                            style="max-width: 100%; max-height: 100%;">
                                                    @else
                                                        <span style="color: #6c757d;">Tidak ada file</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Bukti Transfer</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->file_bukti)
                                                        <img src="{{ asset('assets/booking/' . $data->file_bukti) }}"
                                                            style="max-width: 100%; max-height: 100%;">
                                                    @else
                                                        <span style="color: #6c757d;">Tidak ada file</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto Pemohon</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_pemohon)
                                                        <img src="{{ asset('assets/booking/' . $data->foto_pemohon) }}"
                                                            style="max-width: 100%; max-height: 100%;">
                                                    @else
                                                        <span style="color: #6c757d;">Tidak ada file</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <form id="formData">
                                    @csrf
                                    <input type="hidden" id="primary_id" name="primary_id"
                                        value="{{ $data->id }}">
                                    <input type="hidden" name="booking_fee" id="booking_fee"
                                        value="{{ $data->booking_fee }}">

                                    <div class="form-group row">
                                        <label for="stt_reg" class="col-sm-2 col-form-label">Status Verifikasi</label>
                                        <div class="col-sm-4">
                                            <select name="stt_reg" id="stt_reg" class="form-control select-status">
                                                <option value=""></option>
                                                <option value="1" {{ $data->stt_reg == 1 ? 'selected' : '' }}>Pending
                                                </option>
                                                <option value="2" {{ $data->stt_reg == 2 ? 'selected' : '' }}>
                                                    Disetujui
                                                </option>
                                                <option value="3" {{ $data->stt_reg == 3 ? 'selected' : '' }}>Ditolak
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Metode Bayar</label>
                                        <div class="col-sm-3">
                                            <select class="form-select select-metode-bayar" name="id_metode_bayar"
                                                id="id_metode_bayar">
                                                <option value=""></option>
                                                @foreach ($metodeBayarList as $item)
                                                    <option value="{{ $item->id }}">{{ $item->jenis_bayar }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label for="id_bank" class="col-sm-2 col-form-label">Rekening Pembayaran</label>
                                        <div class="col-sm-3">
                                            <select class="form-select select-bank" name="id_bank" id="id_bank">
                                                <option value=""></option>
                                                @foreach ($bankList as $item)
                                                    <option value="{{ $item->id }}">{{ $item->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="jenis_pembelian" class="col-sm-2 col-form-label">Jenis
                                            Pembelian</label>
                                        <div class="col-sm-3">
                                            <select name="jenis_pembelian" id="jenis_pembelian"
                                                class="form-control select-pembelian" required>
                                                <option value=""></option>
                                                <option value="Pembelian Cash"
                                                    {{ $data->jenis_pembelian == 'Pembelian Cash' ? 'selected' : '' }}>
                                                    Pembelian Cash</option>
                                                <option value="Cash Bertahap"
                                                    {{ $data->jenis_pembelian == 'Cash Bertahap' ? 'selected' : '' }}>Cash
                                                    Bertahap</option>
                                                <option value="KPR"
                                                    {{ $data->jenis_pembelian == 'KPR' ? 'selected' : '' }}>KPR</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="sumber_prospek" class="col-sm-2 col-form-label">Sumber
                                            Prospek</label>
                                        <div class="col-sm-3">
                                            <input type="text" id="sumber_prospek"
                                                value="{{ $data->sumber_prospek ?? '-' }}"
                                                class="form-control" readonly>
                                        </div>
                                    </div>

                                    <!-- CASH ==================================> -->
                                    <hr class="hr-transaksi" style="display: none;">
                                    <div id="trx_cash" style="display: none;">
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Atas Nama Surat</label>
                                            <div class="col-sm-3">
                                                <input name="an_surat_cash" id="an_surat_cash" class="form-control"
                                                    type="text"
                                                    value="{{ $data->an_surat_cash ?? $data->nama_lengkap }}">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CASH BERTAHAP ==================================> -->
                                    <hr class="hr-transaksi" style="display: none;">
                                    <div id="trx_cash_bertahap" style="display: none;">
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Termin (x)</label>
                                            <div class="col-sm-2">
                                                <div class="input-group">
                                                    <input name="termin_x_cash_b" id="termin_x_cash_b"
                                                        class="form-control format-number"
                                                        value="{{ isset($data->termin_x_cash_b) && $data->termin_x_cash_b != 0 ? number_format($data->termin_x_cash_b, 0, ',', '.') : 60 }}">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">Bulan</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SECTION PROSES ADMIN & RINCIAN PEMBAYARAN ==================================> -->
                                    <div id="section_proses_admin">
                                        <hr>
                                        <h5 class="font-weight-bold text-indigo mb-3">
                                            <i class="fas fa-file-invoice-dollar mr-2"></i>Data Penjualan & Rincian Pembayaran
                                        </h5>

                                        <div class="form-group row">
                                            <label for="id_admin_pemberkasan" class="col-sm-2 col-form-label">Admin Pemberkasan</label>
                                            <div class="col-sm-4">
                                                <select name="id_admin_pemberkasan" id="id_admin_pemberkasan" class="form-control select-admin-pemberkasan">
                                                    <option value="">-- Pilih Admin --</option>
                                                    @foreach ($adminPemberkasanList as $adm)
                                                        <option value="{{ $adm->id }}" {{ ($data->id_admin_pemberkasan == $adm->id) ? 'selected' : '' }}>
                                                            {{ $adm->nama_lengkap }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="promo" class="col-sm-2 col-form-label">Promo</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="promo" id="promo" class="form-control" placeholder="Promo yang berlaku">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="perubahan_posisi" class="col-sm-2 col-form-label">Perubahan Posisi</label>
                                            <div class="col-sm-8">
                                                <textarea name="perubahan_posisi" id="perubahan_posisi" class="form-control" rows="2" placeholder="Catatan perubahan posisi (opsional)"></textarea>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="harga_jual" class="col-sm-2 col-form-label">Harga Jual</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="harga_jual" id="harga_jual" class="form-control rupiah"
                                                        value="{{ number_format((float) ($data->total_harga ?: ($data->kavling->hrg_jual ?? 0)), 0, ',', '.') }}">
                                                </div>
                                            </div>
                                            <label for="asumsi_plafon_kpr" class="col-sm-2 col-form-label">Asumsi Plafon KPR</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="asumsi_plafon_kpr" id="asumsi_plafon_kpr" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="biaya_surat_surat" class="col-sm-2 col-form-label">Biaya Surat-surat</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="biaya_surat_surat" id="biaya_surat_surat" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="biaya_kelebihan_tanah" class="col-sm-2 col-form-label">Biaya Kelebihan Tanah <small class="text-muted">(opsional)</small></label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="biaya_kelebihan_tanah" id="biaya_kelebihan_tanah" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                            <label for="biaya_sudut" class="col-sm-2 col-form-label">Biaya Sudut <small class="text-muted">(opsional)</small></label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="biaya_sudut" id="biaya_sudut" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="biaya_lain_lain" class="col-sm-2 col-form-label">Biaya Lain-lain <small class="text-muted">(opsional)</small></label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="biaya_lain_lain" id="biaya_lain_lain" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                            <label for="cicilan_per_bulan" class="col-sm-2 col-form-label">Cicilan per Bulan</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="cicilan_per_bulan" id="cicilan_per_bulan" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                        </div>

                                        <hr>
                                        <h6 class="font-weight-bold text-dark mb-3">
                                            <i class="fas fa-coins mr-2 text-warning"></i>Rincian Pembayaran
                                        </h6>

                                        <!-- Booking Fee -->
                                        <div class="form-group row">
                                            <label for="jumlah_booking_fee" class="col-sm-2 col-form-label">Booking Fee</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" id="jumlah_booking_fee_display" class="form-control rupiah"
                                                        value="{{ number_format((float) $data->booking_fee, 0, ',', '.') }}" readonly style="background-color: #e9ecef; font-weight: bold;">
                                                    <input type="hidden" name="jumlah_booking_fee" id="jumlah_booking_fee" value="{{ (int)$data->booking_fee }}">
                                                </div>
                                                <small class="text-success"><i class="fas fa-check-circle mr-1"></i>Sudah dibayar (memotong Total DP)</small>
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" name="keterangan_booking" id="keterangan_booking" class="form-control" placeholder="Keterangan booking (opsional)">
                                            </div>
                                        </div>

                                        <!-- DP (Down Payment) dengan auto-potong Booking Fee -->
                                        <div class="form-group row bg-light p-3 rounded mb-3 border">
                                            <label for="total_dp" class="col-sm-2 col-form-label font-weight-bold">Total DP</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                                                    <input type="text" name="total_dp" id="total_dp" class="form-control rupiah font-weight-bold" placeholder="0">
                                                </div>
                                                <small class="text-muted">Masukkan total DP yang disepakati</small>
                                            </div>
                                            <div class="col-sm-5">
                                                <div class="card border-info mb-2 shadow-sm">
                                                    <div class="card-body p-2 bg-white">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="text-muted small">Total DP:</span>
                                                            <span class="font-weight-bold small" id="disp_total_dp">Rp 0</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center mb-1 text-danger">
                                                            <span class="small"><i class="fas fa-minus-circle mr-1"></i>Potongan Booking Fee:</span>
                                                            <span class="font-weight-bold small" id="disp_potongan_bf">- Rp {{ number_format((float)$data->booking_fee, 0, ',', '.') }}</span>
                                                        </div>
                                                        <div class="dropdown-divider my-1"></div>
                                                        <div class="d-flex justify-content-between align-items-center text-success">
                                                            <span class="font-weight-bold">Sisa DP yang Harus Dibayar:</span>
                                                            <span class="font-weight-bold h6 mb-0" id="disp_sisa_dp">Rp 0</span>
                                                        </div>
                                                        <input type="hidden" name="nominal_dp" id="nominal_dp" value="0">
                                                    </div>
                                                </div>
                                                <input type="text" name="keterangan_dp" id="keterangan_dp" class="form-control" placeholder="Keterangan DP (opsional)">
                                            </div>
                                        </div>

                                        <!-- Biaya Posisi Unit -->
                                        <div class="form-group row">
                                            <label for="nominal_biaya_posisi_unit" class="col-sm-2 col-form-label">Biaya Posisi Unit</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="nominal_biaya_posisi_unit" id="nominal_biaya_posisi_unit" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" name="keterangan_posisi_unit" id="keterangan_posisi_unit" class="form-control" placeholder="Keterangan posisi unit (opsional)">
                                            </div>
                                        </div>

                                        <!-- Biaya KPR -->
                                        <div class="form-group row">
                                            <label for="nominal_biaya_kpr" class="col-sm-2 col-form-label">Biaya KPR</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="nominal_biaya_kpr" id="nominal_biaya_kpr" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" name="keterangan_kpr" id="keterangan_kpr" class="form-control" placeholder="Keterangan KPR (opsional)">
                                            </div>
                                        </div>

                                        <!-- Blokir Angsuran -->
                                        <div class="form-group row">
                                            <label for="nominal_blokir_angsuran" class="col-sm-2 col-form-label">Blokir Angsuran</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="nominal_blokir_angsuran" id="nominal_blokir_angsuran" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" name="keterangan_blokir_angsuran" id="keterangan_blokir_angsuran" class="form-control" placeholder="Keterangan blokir angsuran (opsional)">
                                            </div>
                                        </div>

                                        <!-- Biaya Materai -->
                                        <div class="form-group row">
                                            <label for="nominal_biaya_materai" class="col-sm-2 col-form-label">Biaya Materai</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="nominal_biaya_materai" id="nominal_biaya_materai" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" name="keterangan_materai" id="keterangan_materai" class="form-control" placeholder="Keterangan materai (opsional)">
                                            </div>
                                        </div>

                                        <!-- Biaya Buka Tabungan -->
                                        <div class="form-group row">
                                            <label for="nominal_biaya_buka_tabungan" class="col-sm-2 col-form-label">Biaya Buka Tabungan</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="nominal_biaya_buka_tabungan" id="nominal_biaya_buka_tabungan" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" name="keterangan_tabungan" id="keterangan_tabungan" class="form-control" placeholder="Keterangan tabungan (opsional)">
                                            </div>
                                        </div>

                                        <!-- Biaya Peningkatan SHM -->
                                        <div class="form-group row">
                                            <label for="peningkatan_mutu" class="col-sm-2 col-form-label">Biaya Peningkatan SHM</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                                    <input type="text" name="peningkatan_mutu" id="peningkatan_mutu" class="form-control rupiah" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" name="keterangan_shm" id="keterangan_shm" class="form-control" placeholder="Keterangan SHM (opsional)">
                                            </div>
                                        </div>

                                        <!-- Total Yang Harus Dibayar Box -->
                                        <div class="alert alert-info d-flex justify-content-between align-items-center mt-3 mb-4">
                                            <div>
                                                <h6 class="font-weight-bold mb-0"><i class="fas fa-calculator mr-2"></i>Total Kewajiban Pembayaran (Setelah Potong Booking Fee):</h6>
                                                <small class="text-muted">Total dari Sisa DP + Biaya Posisi + Biaya KPR + Blokir Angsuran + Materai + Tabungan + SHM + Kelebihan Tanah + Sudut + Lain-lain</small>
                                            </div>
                                            <div class="text-right">
                                                <span class="h4 font-weight-bold text-primary mb-0" id="disp_total_kewajiban">Rp 0</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer text-center">
                                        <a href="{{ route('pengajuan-hold.index') }}" class="btn btn-danger">Kembali</a>

                                        <button type="submit" class="btn btn-primary ms-1" id="submitBtn">
                                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                                aria-hidden="true"></span>
                                            <span class="button-text">Simpan</span>
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
@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select-status').select2({
                theme: "bootstrap4",
                minimumResultsForSearch: Infinity,
            });

            $('.select-jenis').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Jenis Pembelian',
                minimumResultsForSearch: Infinity,
            });

            $('.select-bank').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Bank',
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            $('.select-metode-bayar').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Metode Bayar',
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            $('.select-admin-pemberkasan').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Admin Pemberkasan',
                width: '100%'
            });

            $('.select-pembelian').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Jenis Pembelian',
                minimumResultsForSearch: Infinity,
            });
        });

        $(document).ready(function() {
            function hideAllTransactionForms() {
                $('#trx_cash').hide();
                $('#trx_cash_bertahap').hide();
                $('.hr-transaksi').hide();
            }

            $('#jenis_pembelian').on('change', function() {
                let selected = $(this).val();
                hideAllTransactionForms();

                switch (selected) {
                    case 'Pembelian Cash':
                        $('#trx_cash').prev('.hr-transaksi').show();
                        $('#trx_cash').show();
                        break;
                    case 'Cash Bertahap':
                        $('#trx_cash_bertahap').prev('.hr-transaksi').show();
                        $('#trx_cash_bertahap').show();
                        break;
                }
            });

            $('#jenis_pembelian').trigger('change');

            const bookingFeeVal = {{ (int)($data->booking_fee ?? 0) }};

            function formatRupiah(angka) {
                var number_string = angka.replace(/[^,\d]/g, '').toString(),
                    split = number_string.split(','),
                    sisa = split[0].length % 3,
                    rupiah = split[0].substr(0, sisa),
                    ribuan = split[0].substr(sisa).match(/\d{3}/gi);

                if (ribuan) {
                    separator = sisa ? '.' : '';
                    rupiah += separator + ribuan.join('.');
                }
                return split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            }

            function cleanNumber(str) {
                if (!str) return 0;
                return parseInt(String(str).replace(/[^0-9]/g, '')) || 0;
            }

            function calculateTotals() {
                let totalDp = cleanNumber($('#total_dp').val());
                let sisaDp = Math.max(0, totalDp - bookingFeeVal);

                $('#disp_total_dp').text('Rp ' + formatRupiah(totalDp.toString()));
                $('#disp_sisa_dp').text('Rp ' + formatRupiah(sisaDp.toString()));
                $('#nominal_dp').val(sisaDp);

                // Hitung total kewajiban
                let totalKewajiban = sisaDp
                    + cleanNumber($('#nominal_biaya_posisi_unit').val())
                    + cleanNumber($('#nominal_biaya_kpr').val())
                    + cleanNumber($('#nominal_blokir_angsuran').val())
                    + cleanNumber($('#nominal_biaya_materai').val())
                    + cleanNumber($('#nominal_biaya_buka_tabungan').val())
                    + cleanNumber($('#peningkatan_mutu').val())
                    + cleanNumber($('#biaya_kelebihan_tanah').val())
                    + cleanNumber($('#biaya_sudut').val())
                    + cleanNumber($('#biaya_lain_lain').val())
                    + cleanNumber($('#biaya_surat_surat').val());

                $('#disp_total_kewajiban').text('Rp ' + formatRupiah(totalKewajiban.toString()));
            }

            $(document).on('keyup input change', '.rupiah', function() {
                let val = $(this).val();
                if (val && !$(this).is('#jumlah_booking_fee_display')) {
                    $(this).val(formatRupiah(val));
                }
                calculateTotals();
            });

            // Toggle visibility section_proses_admin berdasarkan stt_reg
            $('#stt_reg').on('change', function() {
                let stt = $(this).val();
                if (stt == '2') {
                    $('#section_proses_admin').slideDown(250);
                } else {
                    $('#section_proses_admin').slideUp(250);
                }
            });

            if ($('#stt_reg').val() == '2') {
                $('#section_proses_admin').show();
            } else {
                $('#section_proses_admin').hide();
            }

            calculateTotals();
        });


        var audio = new Audio('{{ asset('audio/notification.ogg') }}');

        $('#formData').on('submit', function(e) {
            e.preventDefault();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('readonly', true);

            let id = '{{ $data->id }}';
            let url = '{{ route('pengajuan-hold.verifikasi.simpan', ['id' => ':id']) }}'.replace(':id', id);
            let method = 'POST';

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
                success: function() {
                    sessionStorage.setItem('success', 'Verifikasi Booking Berhasil!');
                    window.location.href = "{{ route('pengajuan-hold.index') }}";
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
                        toastr.error('Terjadi Kesalahan', "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });
                    }
                    spinner.addClass('d-none');
                    btnText.text('Simpan');
                    submitBtn.prop('readonly', false);
                }
            });
        });
    </script>
@endpush
