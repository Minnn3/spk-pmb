@extends('layouts.app')

@section('title', 'Perhitungan')
@section('page_title', 'Transparansi Perhitungan')

@section('topbar_actions')
    <form method="GET" action="{{ route('perhitungan.index') }}" class="flex items-center gap-2">
        <label for="tahun_target_perhitungan" class="text-[12px] font-medium text-[#525252]">Tahun Target:</label>
        <select
            id="tahun_target_perhitungan"
            name="tahun_target"
            class="form-input topbar-select"
            data-auto-submit="true"
        >
            @foreach($hasil['pilihan_tahun_target'] as $thn)
                <option value="{{ $thn }}" @selected((int) $hasil['tahun_target'] === (int) $thn)>
                    {{ $thn }}
                </option>
            @endforeach
        </select>
    </form>

    <button type="button" class="btn btn-ghost btn-sm" data-action="print">
        <x-icon name="Printer" :size="16" :strokeWidth="2" />
        <span>Cetak</span>
    </button>
@endsection

@section('content')
    @php
        $kesiapan = $hasil['kesiapan'];
    @endphp

    {{-- Header khusus mode cetak hitam-putih (DESIGN.md 5.12 & PRD 5.4) --}}
    <div class="print-header">
        <div>
            <h1 class="text-[20px] font-bold text-[#1A1A1A]">SPK Strategi Promosi dan Alokasi Staf PMB</h1>
            <p class="text-[12px] text-[#525252] mt-0.5 mb-0">
                Lampiran Audit Perhitungan Trend Moment &amp; Indeks Musiman — Tahun Target {{ $hasil['tahun_target'] }}
            </p>
        </div>
        <div class="text-right font-mono text-[11px] text-[#525252]">
            <div>REF-SPK-{{ $hasil['tahun_target'] }}</div>
            <div>Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="stack-lg">
        {{-- Banner Kesiapan Data --}}
        @if(! $hasil['layak_hitung'])
            <div class="banner-card banner-danger" role="alert">
                <div class="flex items-start gap-3">
                    <x-icon name="XCircle" :size="18" :strokeWidth="2" />
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $kesiapan['status_badge'] }}">{{ $kesiapan['status_label'] }}</span>
                            <span class="font-semibold text-[14px]">Perhitungan Tidak Dapat Dijalankan</span>
                        </div>
                        <p class="text-[13px] mt-1.5 mb-0">{{ $kesiapan['alasan_tidak_layak'] }}</p>
                    </div>
                </div>
                @can('kelola-spk')
                    <div class="flex items-center gap-2 no-print">
                        <a href="{{ route('data-historis.index') }}" class="btn btn-ghost btn-sm">Perbaiki Data Historis</a>
                    </div>
                @endcan
            </div>
        @else
            <div class="banner-card {{ $kesiapan['peringatan_24_bulan'] ? 'banner-warning' : '' }}" role="status">
                <div class="flex items-center flex-wrap gap-3">
                    <span class="badge {{ $kesiapan['status_badge'] }}">{{ $kesiapan['status_label'] }}</span>
                    <span class="text-[13px] text-[#1A1A1A]">
                        Dataset aktif: <strong>{{ $kesiapan['jumlah_bulan'] }} bulan</strong> ({{ $kesiapan['rentang_periode'] }}) —
                        Target prediksi: <strong>Tahun {{ $hasil['tahun_target'] }}</strong>.
                    </span>
                </div>
                @if($kesiapan['peringatan_24_bulan'])
                    <div class="text-[12px] w-full mt-1">
                        {{ $kesiapan['pesan_peringatan'] }}
                    </div>
                @endif
            </div>

            @php
                $tahap1 = $hasil['tahap1'];
                $tahap2 = $hasil['tahap2'];
                $tahap3 = $hasil['tahap3'];
                $tahap4 = $hasil['tahap4'];
            @endphp

            {{-- Tahap 1. Variabel (FR-PH-02) --}}
            <section class="table-card" data-datatable data-page-size="12" aria-labelledby="headingTahap1">
                <div class="table-toolbar">
                    <div>
                        <h2 id="headingTahap1" class="card-title">Tahap 1. Variabel Deret Waktu (X, Y, XY, X²)</h2>
                        <p class="text-[12px] text-[#A3A3A3] mt-0.5 mb-0">
                            Data diurutkan kronologis dari bulan paling awal (X = 0 sampai n − 1).
                        </p>
                    </div>
                    <div class="table-toolbar-group no-print">
                        <input
                            type="search"
                            class="form-input btn-sm"
                            placeholder="Cari tahun atau bulan..."
                            data-dt-search
                            aria-label="Cari pada tabel Tahap 1"
                        >
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th data-dt-sort="number">No</th>
                                <th data-dt-sort="number">Tahun</th>
                                <th data-dt-sort="number">Bulan</th>
                                <th class="th-numeric" data-dt-sort="number">X</th>
                                <th class="th-numeric" data-dt-sort="number">Y</th>
                                <th class="th-numeric" data-dt-sort="number">XY</th>
                                <th class="th-numeric" data-dt-sort="number">X²</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tahap1['baris'] as $baris)
                                <tr data-dt-row data-year="{{ $baris['tahun'] }}">
                                    <td class="td-numeric" data-sort-value="{{ $baris['no'] }}">{{ $baris['no'] }}</td>
                                    <td class="td-numeric" data-sort-value="{{ $baris['tahun'] }}">{{ $baris['tahun'] }}</td>
                                    <td class="td-primary" data-sort-value="{{ $baris['bulan'] }}">
                                        {{ $baris['nama_bulan'] }} ({{ $baris['bulan'] }})
                                    </td>
                                    <td class="td-numeric" data-sort-value="{{ $baris['x'] }}">{{ $baris['x'] }}</td>
                                    <td class="td-numeric td-primary" data-sort-value="{{ $baris['y'] }}">{{ $baris['y'] }}</td>
                                    <td class="td-numeric" data-sort-value="{{ $baris['xy'] }}">{{ $baris['xy'] }}</td>
                                    <td class="td-numeric" data-sort-value="{{ $baris['x2'] }}">{{ $baris['x2'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Total (n = {{ $tahap1['total']['n'] }})</th>
                                <th class="td-numeric">ΣX = {{ $tahap1['total']['sigma_x'] }}</th>
                                <th class="td-numeric">ΣY = {{ $tahap1['total']['sigma_y'] }}</th>
                                <th class="td-numeric">ΣXY = {{ $tahap1['total']['sigma_xy'] }}</th>
                                <th class="td-numeric">ΣX² = {{ $tahap1['total']['sigma_x2'] }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="table-footer no-print">
                    <div data-dt-info>Menampilkan entri</div>
                    <div class="pagination-controls" data-dt-pagination></div>
                </div>
            </section>

            {{-- Tahap 2. Koefisien Tren (FR-PH-03) --}}
            <section class="card" aria-labelledby="headingTahap2">
                <div class="card-header">
                    <h2 id="headingTahap2" class="card-title">Tahap 2. Koefisien Regresi Trend Moment (a dan b)</h2>
                    <span class="badge badge-neutral">Tanpa pembulatan antara</span>
                </div>

                <div class="stack-md">
                    <div>
                        <div class="text-[12px] font-semibold uppercase tracking-[0.04em] text-[#A3A3A3] mb-2">
                            1. Substitusi ke Dua Persamaan Normal Trend Moment
                        </div>
                        <div class="formula-block">
                            <div>Persamaan (1): ΣY  = n · a + b · ΣX   →  {{ $tahap2['sigma_y'] }} = {{ $tahap2['n'] }}a + {{ $tahap2['sigma_x'] }}b</div>
                            <div>Persamaan (2): ΣXY = a · ΣX + b · ΣX² →  {{ $tahap2['sigma_xy'] }} = {{ $tahap2['sigma_x'] }}a + {{ $tahap2['sigma_x2'] }}b</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-[12px] font-semibold uppercase tracking-[0.04em] text-[#A3A3A3] mb-2">
                            2. Langkah Eliminasi dan Substitusi
                        </div>
                        <div class="formula-block">
                            <div>Persamaan (1) × ΣX ({{ $tahap2['sigma_x'] }}): {{ $tahap2['persamaan_1_dikali_sigma_x']['kiri'] }} = {{ $tahap2['persamaan_1_dikali_sigma_x']['koef_a'] }}a + {{ $tahap2['persamaan_1_dikali_sigma_x']['koef_b'] }}b</div>
                            <div>Persamaan (2) × n  ({{ $tahap2['n'] }}):  {{ $tahap2['persamaan_2_dikali_n']['kiri'] }} = {{ $tahap2['persamaan_2_dikali_n']['koef_a'] }}a + {{ $tahap2['persamaan_2_dikali_n']['koef_b'] }}b</div>
                            <div>────────────────────────────────────────────────────────────────────────── (−)</div>
                            <div>b = (n·ΣXY − ΣX·ΣY) / (n·ΣX² − (ΣX)²) = {{ $tahap2['pembilang_b'] }} / {{ $tahap2['penyebut_b'] }} = {{ number_format($tahap2['b'], 6, '.', '') }}</div>
                            <div>a = (ΣY − b·ΣX) / n = ({{ $tahap2['sigma_y'] }} − ({{ number_format($tahap2['b'], 6, '.', '') }} × {{ $tahap2['sigma_x'] }})) / {{ $tahap2['n'] }} = {{ number_format($tahap2['a'], 6, '.', '') }}</div>
                            <div>Uji Persamaan (1): n·a + b·ΣX = {{ number_format($tahap2['verifikasi_sigma_y'], 6, '.', '') }} (ΣY = {{ $tahap2['sigma_y'] }})</div>
                        </div>
                    </div>

                    <div class="formula-result-box">
                        <div>
                            <div class="stat-label">Koefisien Dasar (a)</div>
                            <div class="font-mono text-[20px] font-bold text-[#1A1A1A]">{{ number_format($tahap2['a'], 6, '.', '') }}</div>
                        </div>
                        <div>
                            <div class="stat-label">Koefisien Kemiringan Tren (b)</div>
                            <div class="font-mono text-[20px] font-bold text-[#1A1A1A]">{{ number_format($tahap2['b'], 6, '.', '') }}</div>
                        </div>
                        <div>
                            <div class="stat-label">Persamaan Tren Akhir</div>
                            <div class="font-mono text-[16px] font-bold text-[#2563EB]">
                                Y_trend = {{ number_format($tahap2['a'], 6, '.', '') }} {{ $tahap2['b'] >= 0 ? '+' : '−' }} {{ number_format(abs($tahap2['b']), 6, '.', '') }}X
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Tahap 3. Indeks Musiman (FR-PH-04) --}}
            <section class="table-card" aria-labelledby="headingTahap3">
                <div class="table-toolbar">
                    <div>
                        <h2 id="headingTahap3" class="card-title">Tahap 3. Perhitungan Indeks Musiman (IM) 12 Bulan Kalender</h2>
                        <p class="text-[12px] text-[#A3A3A3] mt-0.5 mb-0">
                            Rata-rata total seluruh observasi: <span class="font-mono text-[#1A1A1A]">Ȳ = ΣY / n = {{ $tahap1['total']['sigma_y'] }} / {{ $tahap1['total']['n'] }} = {{ number_format($tahap3['y_bar'], 6, '.', '') }}</span>
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bulan Kalender</th>
                                <th class="th-numeric">Jumlah Observasi</th>
                                <th class="th-numeric">Rata-rata Bulan (Ȳ_m)</th>
                                <th class="th-numeric">Rata-rata Total (Ȳ)</th>
                                <th class="th-numeric">Indeks Musiman (IM_m)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tahap3['baris'] as $baris)
                                <tr>
                                    <td class="td-primary">{{ $baris['nama_bulan'] }}</td>
                                    <td class="td-numeric">{{ $baris['jumlah_observasi'] }}</td>
                                    <td class="td-numeric">{{ number_format($baris['rata_rata_bulan'], 2, '.', '') }}</td>
                                    <td class="td-numeric">{{ number_format($baris['y_bar'], 6, '.', '') }}</td>
                                    <td class="td-numeric td-primary">{{ number_format($baris['im'], 6, '.', '') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Tahap 4. Prediksi Akhir (FR-PH-05 & FR-PH-08) --}}
            @php
                $adaAktualTarget = collect($tahap4['baris'])->contains(static fn (array $b): bool => $b['y_aktual'] !== null);
            @endphp

            <section class="table-card" aria-labelledby="headingTahap4">
                <div class="table-toolbar">
                    <div>
                        <h2 id="headingTahap4" class="card-title">Tahap 4. Prediksi Akhir Tahun {{ $tahap4['tahun_target'] }}</h2>
                        <p class="text-[12px] text-[#A3A3A3] mt-0.5 mb-0">
                            Rumus: <span class="font-mono text-[#1A1A1A]">Y_akhir = max(0, ROUND(Y_trend × IM_m))</span> — pembulatan hanya dilakukan pada Y_akhir.
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th class="th-numeric">Indeks (X)</th>
                                <th class="th-numeric">Y Tren</th>
                                <th class="th-numeric">Indeks Musiman (IM)</th>
                                <th class="th-numeric">Y Akhir (Prediksi)</th>
                                @if($adaAktualTarget)
                                    <th class="th-numeric">Data Aktual</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tahap4['baris'] as $baris)
                                <tr>
                                    <td class="td-primary">
                                        {{ $baris['nama_bulan'] }} {{ $baris['tahun'] }}
                                        @if($baris['dipaksa_nol'])
                                            <span class="badge badge-danger ml-1">Dipaksa 0 (Tren Negatif)</span>
                                        @endif
                                    </td>
                                    <td class="td-numeric">{{ $baris['x'] }}</td>
                                    <td class="td-numeric">{{ number_format($baris['y_trend'], 4, '.', '') }}</td>
                                    <td class="td-numeric">{{ number_format($baris['im'], 6, '.', '') }}</td>
                                    <td class="td-numeric td-primary">{{ $baris['y_akhir'] }}</td>
                                    @if($adaAktualTarget)
                                        <td class="td-numeric">
                                            {{ $baris['y_aktual'] !== null ? $baris['y_aktual'] : '—' }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Total Prediksi Tahun {{ $tahap4['tahun_target'] }}</th>
                                <th class="td-numeric">{{ $tahap4['total_y_akhir'] }}</th>
                                @if($adaAktualTarget)
                                    <th></th>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection

