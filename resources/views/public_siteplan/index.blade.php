@extends('layouts.app')

@section('title', 'Siteplan Penjualan - Dealaska')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif !important;
            background: url('{{ $bg ? asset("config_media/" . $bg->nama_file) : "" }}') no-repeat center center fixed !important;
            background-size: cover !important;
            min-height: 100vh;
        }

        .siteplan-container {
            max-width: 1250px;
            margin: 0 auto;
            padding: 25px 15px 40px;
        }

        .siteplan-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.18);
            overflow: hidden;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
        }

        .siteplan-header {
            background: linear-gradient(135deg, #0d3b66 0%, #1e5fa8 50%, #3a86ff 100%);
            padding: 24px 30px;
            text-align: center;
            position: relative;
        }

        .siteplan-header h4 {
            color: #fff;
            font-weight: 700;
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.5px;
        }

        .siteplan-header p {
            color: rgba(255, 255, 255, 0.85);
            margin: 6px 0 0;
            font-size: 0.85rem;
        }

        .siteplan-body {
            padding: 20px 25px 25px;
        }

        /* Tabs Styling */
        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
            gap: 5px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px 8px 0 0;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .nav-tabs .nav-link:hover {
            background-color: #f1f5f9;
            color: #1e5fa8;
        }

        .nav-tabs .nav-link.active {
            background-color: transparent;
            color: #1e5fa8;
            border-bottom: 3px solid #1e5fa8;
            font-weight: 700;
        }

        /* Action Toolbar below tabs */
        .siteplan-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 15px 0 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .siteplan-action-bar .btn-group-zoom {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .siteplan-action-bar button {
            font-weight: 600;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .siteplan-action-bar .zoom-level-badge {
            font-size: 0.8rem;
            font-weight: 700;
            background: #e2e8f0;
            color: #334155;
            padding: 7px 10px;
            border-radius: 6px;
            min-width: 48px;
            text-align: center;
        }

        /* SVG Canvas Container matching Admin */
        .svg-view-container {
            width: 100%;
            height: 72vh;
            min-height: 520px;
            overflow: hidden;
            position: relative;
            background: #ffffff;
            border: 2px solid #d1cfcf;
            border-radius: 8px;
        }

        .svg-view-container svg {
            width: 100%;
            height: 100%;
            display: block;
            cursor: grab;
            transition: transform 0.1s ease-out;
            touch-action: none;
            user-select: none;
            -webkit-user-drag: none;
        }

        /* Legend Modal / Dropdown */
        .legend {
            position: fixed;
            top: 140px;
            right: 35px;
            padding: 14px 16px;
            font-size: 13px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.22);
            width: 220px;
            z-index: 1060;
            border: 1px solid #e2e8f0;
            display: none;
        }

        .legend-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: #1e293b;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.82rem;
            font-weight: 500;
            color: #334155;
        }

        .legend-color {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            border-radius: 50%;
            border: 2px solid #000;
            flex-shrink: 0;
        }

        .toggle-btn {
            width: 100%;
            padding: 6px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            margin-top: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            transition: background 0.2s;
        }

        .toggle-btn:hover {
            background-color: #0056b3;
        }

        /* Popup Detail Kavling */
        #popupOverlay {
            position: fixed;
            z-index: 9999;
            display: none;
            pointer-events: none;
        }

        #popupBox {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            width: 300px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            position: relative;
            pointer-events: all;
            border: 1px solid #e5e7eb;
        }

        #popupClose {
            position: absolute;
            top: 10px;
            right: 12px;
            background: #f3f4f6;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }

        .popup-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1a5c30;
            margin-bottom: 15px;
        }

        .popup-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.8rem;
        }

        .popup-label {
            color: #6b7280;
        }

        .popup-value {
            font-weight: 600;
            color: #111827;
            text-align: right;
        }

        .popup-price {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .popup-price .value {
            font-size: 1rem;
            font-weight: 800;
            color: #16a34a;
        }

        .text-white-svg text {
            fill: #ffffff !important;
        }

        @media (max-width: 768px) {
            .siteplan-container {
                padding: 15px 10px;
            }
            .siteplan-header {
                padding: 18px 15px;
            }
            .siteplan-body {
                padding: 15px 10px;
            }
            .svg-view-container {
                height: 55vh;
                min-height: 400px;
            }
            .legend {
                top: 70px;
                right: 15px;
                width: 190px;
            }
            .show-btn {
                top: 70px;
                right: 15px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="siteplan-container">
        <div class="siteplan-card">
            {{-- Header --}}
            <div class="siteplan-header">
                <h4>Siteplan Penjualan</h4>
                <p>Silakan pilih lokasi perumahan dan klik pada kavling untuk melihat detail informasi.</p>
            </div>

            <div class="siteplan-body">
                {{-- Tabs Perumahan --}}
                <ul class="nav nav-tabs" id="siteplan-tabs" role="tablist">
                    @foreach ($lokasiKavling as $index => $kav)
                        <li class="nav-item">
                            <a class="nav-link {{ $index == 0 ? 'active' : '' }}" id="tab-{{ $kav->id }}"
                                data-toggle="pill" href="#pane-{{ $kav->id }}" role="tab">
                                {{ $kav->nama_kavling }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content" id="siteplan-tabContent">
                    @foreach ($lokasiKavling as $index => $kav)
                        <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" id="pane-{{ $kav->id }}"
                            role="tabpanel">

                            {{-- Action Toolbar: Zoom In, Zoom Out, Reset & Download Denah --}}
                            <div class="siteplan-action-bar">
                                <div class="btn-group-zoom flex-wrap">
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-zoom-in" title="Perbesar (Zoom In)">
                                        <i class="fas fa-search-plus mr-1"></i> Zoom In
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-zoom-out" title="Perkecil (Zoom Out)">
                                        <i class="fas fa-search-minus mr-1"></i> Zoom Out
                                    </button>
                                    <div class="zoom-level-badge">100%</div>
                                    <button type="button" class="btn btn-success btn-sm btn-zoom-reset reset-button" title="Kembalikan Tampilan Awal">
                                        <i class="fas fa-sync-alt mr-1"></i> Reset Siteplan
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm btn-show-legend font-weight-bold ml-1" onclick="toggleLegend()" title="Lihat Keterangan Status Kavling">
                                        <i class="fas fa-palette mr-1"></i> Keterangan Status
                                    </button>
                                    <a href="{{ route('public.siteplan.cetak.pdf', $kav->id) }}" target="_blank" class="btn btn-danger btn-sm ml-1" title="Unduh Denah dalam Format PDF">
                                        <i class="fas fa-file-pdf mr-1"></i> Download Denah PDF
                                    </a>
                                    <a href="{{ route('public.siteplan.cetak.jpg', $kav->id) }}" target="_blank" class="btn btn-primary btn-sm ml-1" title="Unduh Denah dalam Format JPG">
                                        <i class="fas fa-file-image mr-1"></i> Download Denah JPG
                                    </a>
                                </div>
                                <div class="text-muted small d-flex align-items-center flex-wrap">
                                    <span class="badge badge-light border text-dark py-1 px-2 mr-2" style="font-size: 0.8rem;">
                                        <i class="fas fa-calendar-alt text-primary mr-1"></i> Tanggal Unduh: <strong>{{ now()->translatedFormat('d/m/Y H:i') }} WIB</strong>
                                    </span>
                                    <span><i class="fas fa-info-circle mr-1 text-primary"></i> Klik kavling untuk info detail</span>
                                </div>
                            </div>

                            {{-- SVG View Container matching Admin --}}
                            <div class="svg-view-container svg-container" id="svg-container-{{ $kav->id }}">
                                {{-- SVG Render --}}
                                @if ($kav->masterSvg)
                                    {!! str_replace(['[[lebar]]', '[[tinggi]]'], ['100%', '100%'], $kav->masterSvg->header_svg) !!}

                                    @foreach ($kav->kavlingPeta as $pt)
                                        @php
                                            $warna = '#ffffff';

                                            if ($pt->customer) {
                                                $warna = $pt->customer->progres->warna ?? '#ffffff';
                                            } else {
                                                if ($pt->status == 1) {
                                                    $warna = '#42f202';
                                                }
                                            }
                                        @endphp

                                        <a href="javascript:void(0);" class="detail-button {{ $pt->siteplan_text_color === '#ffffff' ? 'text-white-svg' : '' }}"
                                            data-url="{{ route('public.siteplan.show', $pt->id) }}">
                                            {!! str_replace(
                                                ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                $pt->jenis_map == 'polygon' ? $kav->masterSvg->polygon_svg : $kav->masterSvg->path_svg,
                                            ) !!}
                                        </a>
                                    @endforeach

                                    {!! $kav->masterSvg->footer_svg !!}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Footer Copyright --}}
            <div class="text-center text-muted py-3" style="font-size: 0.85rem; border-top: 1px solid #f1f5f9;">
                <strong>Copyright &copy; 2026 di Kelola Tim Marcom RK GROUP Property</strong>
            </div>
        </div>
    </div>

    {{-- Floating Legend modal / dropdown --}}
    <div class="legend" id="legend" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
            <span class="legend-title font-weight-bold m-0" style="font-size: 13px;">Status Unit:</span>
            <button type="button" class="close text-muted p-0" onclick="toggleLegend()" style="font-size: 18px; line-height: 1; border:none; background:none;">&times;</button>
        </div>
        @foreach ($legend as $item)
            <div class="legend-item">
                <div class="legend-color" style="background-color: {{ $item->warna }}"></div>
                <span>{{ $item->status_progres }}</span>
            </div>
        @endforeach
        <button type="button" class="toggle-btn" onclick="toggleLegend()">Tutup</button>
    </div>

    {{-- Detail Popup --}}
    <div id="popupOverlay">
        <div id="popupBox">
            <button id="popupClose" onclick="closePopup()">&times;</button>
            <div class="popup-arrow"></div>

            <div class="popup-title">Detail Kavling</div>

            <div class="popup-row">
                <span class="popup-label">Perumahan</span>
                <span class="popup-value" id="p_nama_kavling">-</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Kode Kavling</span>
                <span class="popup-value" id="p_kode_kavling">-</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Tipe</span>
                <span class="popup-value"><span id="p_tipe_bangunan">-</span></span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Luas Tanah</span>
                <span class="popup-value"><span id="p_luas_tanah">-</span> m²</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Luas Bangunan</span>
                <span class="popup-value"><span id="p_luas_bangunan">-</span> m²</span>
            </div>

            <div class="popup-price">
                <span class="popup-label font-weight-bold">Total Harga</span>
                <span class="value"><span id="p_currency">Rp</span> <span id="p_hrg_jual">0</span></span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentTargetElement = null;
        let popupUpdateInterval = null;

        function toggleLegend() {
            var legend = document.getElementById("legend");
            if (!legend) return;
            if (legend.style.display === "none" || !legend.style.display) {
                legend.style.display = "block";
            } else {
                legend.style.display = "none";
            }
        }

        function updatePopupPosition() {
            if (!currentTargetElement || !$('#popupOverlay').is(':visible')) return;

            const targetRect = currentTargetElement.getBoundingClientRect();
            const popupBox = document.getElementById('popupBox');
            const popupWidth = popupBox.offsetWidth;
            const popupHeight = popupBox.offsetHeight;

            const targetCenterX = targetRect.left + (targetRect.width / 2);
            const targetCenterY = targetRect.top + (targetRect.height / 2);

            let left = targetCenterX - (popupWidth / 2);
            let top = targetCenterY - popupHeight - 25;

            const margin = 15;
            const winW = $(window).width();
            const winH = $(window).height();

            if (left < margin) left = margin;
            else if (left + popupWidth > winW - margin) left = winW - popupWidth - margin;

            if (top < margin) {
                top = targetCenterY + 25;
                $('.popup-arrow').css({
                    'bottom': 'auto', 'top': '-10px',
                    'border-top': 'none', 'border-bottom': '10px solid #fff'
                });
            } else {
                $('.popup-arrow').css({
                    'bottom': '-10px', 'top': 'auto',
                    'border-top': '10px solid #fff', 'border-bottom': 'none'
                });
            }

            $('#popupOverlay').css({ left: left + 'px', top: top + 'px' });
        }

        $(document).on('click', '.detail-button', function(e) {
            e.preventDefault();
            e.stopPropagation();

            let url = $(this).data('url');
            currentTargetElement = this;

            $.get(url, function(res) {
                if (res.success) {
                    $('#p_nama_kavling').text(res.data.lokasi.nama_kavling);
                    $('#p_kode_kavling').text(res.data.kode_kavling);
                    $('#p_tipe_bangunan').text(res.data.tipe_bangunan);
                    $('#p_luas_tanah').text(res.data.luas_tanah);
                    $('#p_luas_bangunan').text(res.data.luas_bangunan);
                    if (res.data.total_harga > 0) {
                        $('#p_hrg_jual').text(parseFloat(res.data.total_harga).toLocaleString('id-ID'));
                        $('#p_currency').show();
                        $('.popup-price').show();
                    } else {
                        $('#p_hrg_jual').text('Harga belum tersedia');
                        $('#p_currency').hide();
                    }

                    $('#popupOverlay').fadeIn(200, function() { updatePopupPosition(); });

                    if (popupUpdateInterval) clearInterval(popupUpdateInterval);
                    popupUpdateInterval = setInterval(updatePopupPosition, 50);
                }
            });
        });

        function closePopup() {
            $('#popupOverlay').fadeOut(200);
            currentTargetElement = null;
            if (popupUpdateInterval) clearInterval(popupUpdateInterval);
        }

        $(document).on('click', function(e) {
            if ($(e.target).closest('#popupBox').length === 0 && !$(e.target).hasClass('detail-button')) {
                closePopup();
            }
        });

        $(window).on('resize scroll', updatePopupPosition);
        $('a[data-toggle="pill"]').on('shown.bs.tab', closePopup);

        if (window.MutationObserver) {
            const observer = new MutationObserver(() => {
                if ($('#popupOverlay').is(':visible')) updatePopupPosition();
            });
            $(document).ready(() => {
                $('svg').each(function() {
                    observer.observe(this, { attributes: true, attributeFilter: ['transform', 'style'] });
                });
            });
        }
    </script>
    <script src="{{ asset('assets/svg_1.js') }}?v={{ time() }}"></script>
@endpush
