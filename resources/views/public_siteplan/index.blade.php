@extends('layouts.app')

@section('title', 'Siteplan Penjualan - RK GROUP Property')

@push('styles')
    <style>
        .legend {
            position: fixed;
            top: 80px;
            right: 30px;
            padding: 10px;
            font-size: 14px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.4);
            transition: right 0.3s ease;
            width: 200px;
            z-index: 1050;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            border-radius: 50%;
            border: 2px solid #000;
            flex-shrink: 0;
        }

        .toggle-btn {
            width: 100%;
            padding: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            margin-top: 10px;
            font-size: 13px;
        }

        .show-btn {
            position: fixed;
            top: 100px;
            right: 30px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.4);
            cursor: pointer;
            display: none;
            z-index: 1050;
        }

        .svg-container {
            width: 100%;
            height: 85vh;
            min-height: 600px;
            overflow: hidden;
            border: 2px solid #d1cfcf;
            position: relative;
            background: #ffffff;
        }

        /* Styling interaktif kavling - Opacity tebal & Multiply agar warna asli blok jelas namun nama blok & nomor unit di denah tetap terbaca tajam */
        polygon.kavling-poly,
        path.kavling-poly,
        polyline.kavling-poly,
        .svg-container svg polygon,
        .svg-container svg path,
        .svg-container svg polyline {
            transition: fill 0.2s ease, stroke 0.2s ease, filter 0.2s ease, fill-opacity 0.2s ease;
            vector-effect: non-scaling-stroke;
            fill-opacity: 0.78 !important;
            mix-blend-mode: multiply !important;
            stroke: rgba(0, 0, 0, 0.55) !important;
            stroke-width: 1px !important;
            cursor: pointer !important;
        }

        /* Unit belum terjual (putih #ffffff) dibuat transparan agar gambar denah & nomor lot terlihat jelas tanpa bertumpuk */
        polygon.kavling-poly[style*="#ffffff"],
        polygon.kavling-poly[style*="#FFFFFF"],
        polygon.kavling-poly[style*="fill:#ffffff"],
        polygon.kavling-poly[style*="fill: #ffffff"],
        path.kavling-poly[style*="#ffffff"],
        path.kavling-poly[style*="#FFFFFF"],
        path.kavling-poly[style*="fill:#ffffff"],
        path.kavling-poly[style*="fill: #ffffff"],
        polyline.kavling-poly[style*="#ffffff"],
        polyline.kavling-poly[style*="#FFFFFF"],
        polyline.kavling-poly[style*="fill:#ffffff"],
        polyline.kavling-poly[style*="fill: #ffffff"],
        .svg-container svg polygon[style*="#ffffff"],
        .svg-container svg polygon[style*="#FFFFFF"],
        .svg-container svg polygon[style*="fill:#ffffff"],
        .svg-container svg polygon[style*="fill: #ffffff"],
        .svg-container svg path[style*="#ffffff"],
        .svg-container svg path[style*="#FFFFFF"],
        .svg-container svg path[style*="fill:#ffffff"],
        .svg-container svg path[style*="fill: #ffffff"],
        .svg-container svg polyline[style*="#ffffff"],
        .svg-container svg polyline[style*="#FFFFFF"],
        .svg-container svg polyline[style*="fill:#ffffff"],
        .svg-container svg polyline[style*="fill: #ffffff"] {
            fill: transparent !important;
            fill-opacity: 0 !important;
            stroke: rgba(37, 99, 235, 0.35) !important;
            stroke-width: 1px !important;
            mix-blend-mode: normal !important;
        }

        /* Efek hover interaktif */
        polygon.kavling-poly:hover,
        path.kavling-poly:hover,
        polyline.kavling-poly:hover,
        .svg-container svg polygon:hover,
        .svg-container svg path:hover,
        .svg-container svg polyline:hover {
            fill-opacity: 0.25 !important;
            stroke: #1d4ed8 !important;
            stroke-width: 2.5px !important;
            filter: drop-shadow(0 0 6px rgba(37, 99, 235, 0.7));
            cursor: pointer !important;
        }

        .svg-container svg {
            width: 100%;
            height: 100%;
            cursor: grab;
            transition: transform 0.1s ease-out;
        }

        .siteplan-wrapper {
            padding: 15px 20px;
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
            width: 320px;
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
            width: 26px;
            height: 26px;
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
            font-size: 1rem;
            color: #1a5c30;
            margin-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }

        .popup-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.85rem;
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
    </style>
@endpush

@section('content')
    <div class="siteplan-wrapper">
        <div class="card card-primary card-outline card-outline-tabs">
            @php
                $activeLokasiId = request('id_lokasi') ?? ($lokasiKavling->first()->id ?? 1);
            @endphp
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
                    @foreach ($lokasiKavling as $index => $kav)
                        <li class="nav-item">
                            <a class="nav-link {{ $kav->id == $activeLokasiId ? 'active' : '' }}"
                                id="custom-tabs-four-{{ $kav->id }}-tab" data-toggle="pill"
                                href="#custom-tabs-four-{{ $kav->id }}" role="tab">
                                {{ $kav->nama_kavling }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="custom-tabs-four-tabContent">
                    @foreach ($lokasiKavling as $index => $kav)
                        <div class="tab-pane fade {{ $kav->id == $activeLokasiId ? 'show active' : '' }}"
                            id="custom-tabs-four-{{ $kav->id }}" role="tabpanel">

                            <a href="{{ route('public.siteplan.cetak.pdf', $kav->id) }}" target="_blank"
                                class="btn btn-danger btn-sm mr-1">
                                <i class="fas fa-file-pdf mr-1"></i> Cetak Denah PDF
                            </a>
                            <a href="{{ route('public.siteplan.cetak.jpg', $kav->id) }}" target="_blank"
                                class="btn btn-primary btn-sm">
                                <i class="fas fa-file-image mr-1"></i> Download Denah JPG
                            </a>

                            {{-- SVG Container khusus lokasi ini --}}
                            <div class="svg-container mt-3" id="svg-container-{{ $kav->id }}">
                                <button class="reset-button btn btn-success btn-sm"
                                    style="position: absolute; top: 10px; left: 10px; z-index: 10;">
                                    Reset Siteplan
                                </button>

                                {{-- SVG Header --}}
                                @if ($kav->masterSvg)
                                    {!! str_replace(['[[lebar]]', '[[tinggi]]'], ['100%', '100%'], $kav->masterSvg->header_svg) !!}
                                @endif

                                {{-- Loop kavling --}}
                                @foreach ($kav->kavlingPeta as $pt)
                                    @php
                                        $warna = '#ffffff';

                                        if ($pt->customer) {
                                            $warna = $pt->customer->progres->warna ?? '#ffffff';
                                        } elseif ($pt->status == 2) {
                                            $warna = '#42f202';
                                        }
                                    @endphp

                                    @if ($pt->jenis_map == 'polygon')
                                        <a href="javascript:void(0);" class="detail-button"
                                            data-url="{{ route('public.siteplan.show', $pt->id) }}"
                                            data-kavling="{{ $pt->kode_kavling }}"
                                            title="{{ $pt->kode_kavling }}">
                                            {!! str_replace(
                                                ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                $kav->masterSvg->polygon_svg,
                                            ) !!}
                                        </a>
                                    @elseif ($pt->jenis_map == 'polyline')
                                        <a href="javascript:void(0);" class="detail-button"
                                            data-url="{{ route('public.siteplan.show', $pt->id) }}"
                                            data-kavling="{{ $pt->kode_kavling }}"
                                            title="{{ $pt->kode_kavling }}">
                                            {!! str_replace(
                                                ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                $kav->masterSvg->polyline_svg ?? $kav->masterSvg->polygon_svg,
                                            ) !!}
                                        </a>
                                    @elseif ($pt->jenis_map == 'path')
                                        <a href="javascript:void(0);" class="detail-button"
                                            data-url="{{ route('public.siteplan.show', $pt->id) }}"
                                            data-kavling="{{ $pt->kode_kavling }}"
                                            title="{{ $pt->kode_kavling }}">
                                            {!! str_replace(
                                                ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                $kav->masterSvg->path_svg,
                                            ) !!}
                                        </a>
                                    @endif
                                @endforeach

                                {{-- SVG Footer --}}
                                @if ($kav->masterSvg)
                                    {!! $kav->masterSvg->footer_svg !!}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Footer Copyright --}}
        <div class="text-center text-muted py-2" style="font-size: 0.85rem;">
            <strong>Copyright &copy; 2026 di Kelola Tim Marcom RK GROUP Property</strong>
        </div>
    </div>

    <!-- Tombol show di luar legenda -->
    <button class="show-btn btn-xs" id="show-btn" onclick="toggleLegend()">Show</button>

    <!-- Tambahkan legenda -->
    <div class="legend" id="legend">
        @foreach ($legend as $item)
            <div class="legend-item">
                <div class="legend-color" style="background-color: {{ $item->warna }}"></div>
                {{ $item->status_progres }}
            </div>
        @endforeach

        <!-- Tombol hide -->
        <button class="toggle-btn" onclick="toggleLegend()">Hide</button>
    </div>

    {{-- Detail Popup Modal --}}
    <div id="popupOverlay">
        <div id="popupBox">
            <button id="popupClose" onclick="closePopup()">&times;</button>
            <div class="popup-title">Detail Data Kavling</div>

            <div class="popup-row">
                <span class="popup-label">Perumahan</span>
                <span class="popup-value" id="p_nama_kavling">-</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Kode Kavling</span>
                <span class="popup-value font-weight-bold" id="p_kode_kavling">-</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Tipe</span>
                <span class="popup-value" id="p_tipe_bangunan">-</span>
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
        function toggleLegend() {
            var legend = document.getElementById('legend');
            var showBtn = document.getElementById('show-btn');

            if (legend.style.right === '30px' || legend.style.right === '') {
                legend.style.right = '-300px';
                showBtn.style.display = 'block';
            } else {
                legend.style.right = '30px';
                showBtn.style.display = 'none';
            }
        }

        let currentTargetElement = null;
        let popupUpdateInterval = null;

        function updatePopupPosition() {
            if (!currentTargetElement || !$('#popupOverlay').is(':visible')) return;

            const targetRect = currentTargetElement.getBoundingClientRect();
            const popupBox = document.getElementById('popupBox');
            const popupWidth = popupBox.offsetWidth;
            const popupHeight = popupBox.offsetHeight;

            const targetCenterX = targetRect.left + (targetRect.width / 2);
            const targetCenterY = targetRect.top + (targetRect.height / 2);

            let left = targetCenterX - (popupWidth / 2);
            let top = targetCenterY - popupHeight - 20;

            const margin = 15;
            const winW = $(window).width();
            const winH = $(window).height();

            if (left < margin) left = margin;
            else if (left + popupWidth > winW - margin) left = winW - popupWidth - margin;

            if (top < margin) {
                top = targetCenterY + 20;
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
                    $('#p_nama_kavling').text(res.data.lokasi ? res.data.lokasi.nama_kavling : '-');
                    $('#p_kode_kavling').text(res.data.kode_kavling);
                    $('#p_tipe_bangunan').text(res.data.tipe_bangunan || '-');
                    $('#p_luas_tanah').text(res.data.luas_tanah || '-');
                    $('#p_luas_bangunan').text(res.data.luas_bangunan || '-');
                    if (res.data.total_harga > 0) {
                        $('#p_hrg_jual').text(parseFloat(res.data.total_harga).toLocaleString('id-ID'));
                        $('#p_currency').show();
                        $('.popup-price').show();
                    } else {
                        $('#p_hrg_jual').text('Hubungi Marketing');
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

        // Support direct hash tab switching (e.g., #custom-tabs-four-2)
        $(document).ready(function() {
            let hash = window.location.hash;
            if (hash) {
                let match = hash.match(/\d+/);
                if (match) {
                    let id = match[0];
                    if ($('#custom-tabs-four-' + id + '-tab').length) {
                        $('#custom-tabs-four-' + id + '-tab').tab('show');
                    }
                }
            }
        });
    </script>
    <script src="{{ asset('assets/svg_1.js') }}?v={{ time() }}"></script>
@endpush
