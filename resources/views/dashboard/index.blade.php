@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('topbar_actions')
    <form method="GET" action="{{ route('dashboard.index') }}" class="flex items-center gap-2">
        <label for="tahun_target_dashboard" class="text-[12px] font-medium text-[#525252]">Tahun Target:</label>
        <select
            id="tahun_target_dashboard"
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

    @if($hasil['layak_hitung'])
        <a
            href="{{ route('dashboard.export', ['tahun_target' => $hasil['tahun_target']]) }}"
            class="btn btn-ghost btn-sm"
        >
            <x-icon name="Download" :size="16" :strokeWidth="2" />
            <span>Ekspor .xlsx</span>
        </a>
    @endif
@endsection

@section('content')
    @php
        $kesiapan = $hasil['kesiapan'];
        $parameter = $hasil['parameter'];
        $ringkasan = $hasil['ringkasan'];
        $keputusan = $hasil['keputusan'];
    @endphp

    <div class="dashboard-layout">
        {{-- Kolom Utama --}}
        <div class="stack-lg">
            @if(! $hasil['layak_hitung'])
                <div class="banner-card banner-danger" role="alert">
                    <div class="flex items-start gap-3">
                        <x-icon name="XCircle" :size="18" :strokeWidth="2" />
                        <div>
                            <div class="font-semibold text-[14px]">Data Historis Belum Layak Hitung</div>
                            <p class="text-[13px] mt-1 mb-0">{{ $kesiapan['alasan_tidak_layak'] }}</p>
                        </div>
                    </div>
                    @can('kelola-spk')
                        <div class="flex items-center gap-2">
                            <a href="{{ route('data-historis.index') }}" class="btn btn-ghost btn-sm">Kelola Data Historis</a>
                            <a href="{{ route('import-excel.index') }}" class="btn btn-primary btn-sm">Import Excel</a>
                        </div>
                    @endcan
                </div>
            @else
                @if($kesiapan['peringatan_24_bulan'])
                    <div class="banner-card banner-warning" role="status">
                        <div class="flex items-start gap-3">
                            <x-icon name="AlertTriangle" :size="18" :strokeWidth="2" />
                            <div>
                                <div class="font-semibold text-[13px]">Peringatan Jumlah Data Historis</div>
                                <p class="text-[12px] mt-0.5 mb-0">{{ $kesiapan['pesan_peringatan'] }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Baris 1: Empat Stat Card (FR-DB-02) --}}
                <div class="stats-grid">
                    <x-stat-card
                        label="Total Prediksi Pendaftar"
                        :value="number_format($ringkasan['total_prediksi_pendaftar'], 0, ',', '.')"
                        :subtext="'Tahun target ' . $hasil['tahun_target'] . ' (orang)'"
                        accent="default"
                    />

                    <x-stat-card
                        label="Bulan Puncak"
                        :value="$ringkasan['bulan_puncak'] . ' (' . number_format($ringkasan['nilai_bulan_puncak'], 0, ',', '.') . ')'"
                        subtext="Pendaftar bulanan tertinggi"
                        accent="default"
                    />

                    <x-stat-card
                        label="Total Estimasi Kas"
                        :value="'Rp ' . number_format($ringkasan['total_estimasi_kas'], 0, ',', '.')"
                        subtext="Proyeksi kas registrasi setahun"
                        accent="blue"
                    />

                    <x-stat-card
                        label="Kebutuhan Staf Tertinggi"
                        :value="$ringkasan['kebutuhan_staf_tertinggi'] . ' Staf'"
                        :subtext="'Puncak pada bulan ' . $ringkasan['bulan_staf_tertinggi']"
                        accent="default"
                    />
                </div>

                {{-- Baris 2: Grafik Garis Aktual dan Prediksi (FR-DB-03) --}}
                <section class="card" aria-labelledby="headingGrafikPrediksi">
                    <div class="card-header">
                        <h2 id="headingGrafikPrediksi" class="card-title">
                            <x-icon name="TrendingUp" :size="18" :strokeWidth="1.5" />
                            <span>Perbandingan Pendaftar Aktual dan Prediksi Tahun {{ $hasil['tahun_target'] }}</span>
                        </h2>
                        <a href="{{ route('perhitungan.index', ['tahun_target' => $hasil['tahun_target']]) }}" class="btn btn-ghost btn-xs">
                            <span>Telusuri Perhitungan</span>
                        </a>
                    </div>

                    <div class="chart-container">
                        <canvas
                            id="spkForecastChart"
                            data-chart-payload="{{ json_encode($hasil['seri_grafik']) }}"
                            aria-label="Grafik garis perbandingan pendaftar aktual dan prediksi"
                        ></canvas>
                    </div>
                </section>

                {{-- Baris 3: Tabel Rekomendasi Keputusan 12 Bulan (FR-DB-04) --}}
                <section class="table-card" aria-labelledby="headingTabelRekomendasi">
                    <div class="table-toolbar">
                        <div>
                            <h2 id="headingTabelRekomendasi" class="card-title">
                                Rekomendasi Strategi Promosi dan Alokasi Staf Tahun {{ $hasil['tahun_target'] }}
                            </h2>
                            <p class="text-[12px] text-[#A3A3A3] mt-0.5 mb-0">
                                Keputusan otomatis berdasarkan hasil prediksi Trend Moment + Indeks Musiman.
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th class="th-numeric">Y Akhir</th>
                                    <th>Status</th>
                                    <th class="th-numeric">Staf</th>
                                    <th class="th-numeric">Estimasi Kas</th>
                                    <th>Rekomendasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($keputusan as $baris)
                                    <tr>
                                        <td class="td-primary">
                                            {{ $baris['nama_bulan'] }}
                                            @if($baris['dipaksa_nol'])
                                                <span class="badge badge-danger ml-1" title="Nilai tren negatif dipaksa menjadi 0">Dipaksa 0</span>
                                            @endif
                                        </td>
                                        <td class="td-numeric td-primary">{{ number_format($baris['y_akhir'], 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge {{ $baris['badge_class'] }}">{{ $baris['status'] }}</span>
                                        </td>
                                        <td class="td-numeric" title="{{ $baris['rekomendasi_staf'] }}">
                                            {{ number_format($baris['kebutuhan_staf'], 0, ',', '.') }}
                                        </td>
                                        <td class="td-numeric">
                                            Rp {{ number_format($baris['estimasi_kas'], 0, ',', '.') }}
                                        </td>
                                        <td class="td-recommendation">
                                            <div>{{ $baris['rekomendasi_marketing'] }}</div>
                                            <div class="text-[12px] text-[#A3A3A3] mt-1">{{ $baris['rekomendasi_staf'] }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="td-numeric">{{ number_format($ringkasan['total_prediksi_pendaftar'], 0, ',', '.') }}</th>
                                    <th></th>
                                    <th class="td-numeric">Maks {{ number_format($ringkasan['kebutuhan_staf_tertinggi'], 0, ',', '.') }}</th>
                                    <th class="td-numeric">Rp {{ number_format($ringkasan['total_estimasi_kas'], 0, ',', '.') }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        {{-- Kolom Samping (320 px) — FR-DB-05 --}}
        <aside class="stack-lg" aria-label="Ringkasan Parameter dan Status Data">
            {{-- Samping 1: Parameter Aktif --}}
            <section class="card" aria-labelledby="headingParameterAktif">
                <div class="card-header">
                    <h2 id="headingParameterAktif" class="card-title">
                        <x-icon name="Settings" :size="18" :strokeWidth="1.5" />
                        <span>Parameter Aktif</span>
                    </h2>
                    @can('kelola-spk')
                        <a href="{{ route('pengaturan-spk.index') }}" class="btn btn-ghost btn-xs">Ubah</a>
                    @endcan
                </div>

                <div class="kv-list">
                    <div class="kv-row">
                        <span class="kv-label">Target minimum pendaftar</span>
                        <span class="kv-value">{{ number_format($parameter['target_minimum_pendaftar'], 0, ',', '.') }} orang/bulan</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label">Batas peak season</span>
                        <span class="kv-value">{{ number_format($parameter['batas_peak_season'], 0, ',', '.') }} orang/bulan</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label">Kapasitas layan staf</span>
                        <span class="kv-value">{{ number_format($parameter['kapasitas_layan_staf'], 0, ',', '.') }} pendaftar/staf/bulan</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label">Estimasi biaya pendaftaran</span>
                        <span class="kv-value">Rp {{ number_format($parameter['estimasi_biaya_pendaftaran'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </section>

            {{-- Samping 2: Status Data --}}
            <section class="card" aria-labelledby="headingStatusData">
                <div class="card-header">
                    <h2 id="headingStatusData" class="card-title">
                        <x-icon name="Database" :size="18" :strokeWidth="1.5" />
                        <span>Status Data</span>
                    </h2>
                    <span class="badge {{ $kesiapan['status_badge'] }}">{{ $kesiapan['status_label'] }}</span>
                </div>

                <div class="kv-list">
                    <div class="kv-row">
                        <span class="kv-label">Jumlah observasi</span>
                        <span class="kv-value">{{ number_format($kesiapan['jumlah_bulan'], 0, ',', '.') }} bulan</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label">Rentang periode</span>
                        <span class="kv-value">{{ $kesiapan['rentang_periode'] }}</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label">Bulan hilang</span>
                        <span class="kv-value">
                            @if($kesiapan['bulan_hilang'] === [])
                                Tidak ada
                            @else
                                {{ count($kesiapan['bulan_hilang']) }} bulan
                            @endif
                        </span>
                    </div>
                </div>

                @if($kesiapan['bulan_hilang'] !== [])
                    <div class="mt-3 pt-3 border-t border-[#E5E4E0] text-[12px] text-[#DC2626]">
                        Bulan hilang: {{ implode(', ', $kesiapan['bulan_hilang']) }}
                    </div>
                @endif
            </section>
        </aside>
    </div>
@endsection

