@extends('layouts.app')

@section('title', 'Import Excel')
@section('page_title', 'Import Data Pendaftar via Excel')

@section('topbar_actions')
    <a href="{{ route('data-historis.index') }}" class="btn btn-ghost btn-sm">
        <x-icon name="Database" :size="16" :strokeWidth="2" />
        <span>Lihat Data Historis</span>
    </a>
@endsection

@section('content')
    <div class="content-narrow stack-lg">
        {{-- Langkah 1. Unduh Template (FR-DH-03) --}}
        <section class="card" aria-labelledby="headingLangkah1">
            <div class="card-header">
                <h2 id="headingLangkah1" class="card-title">
                    <x-icon name="FileText" :size="18" :strokeWidth="1.5" />
                    <span>Langkah 1. Unduh Template Excel</span>
                </h2>
                <span class="badge badge-neutral">Format .xlsx</span>
            </div>

            <p class="text-[13px] text-[#525252] mt-0 mb-4">
                Gunakan berkas template standar yang terdiri dari tiga kolom:
                <code class="font-mono text-[#1A1A1A]">Tahun</code> (4 digit),
                <code class="font-mono text-[#1A1A1A]">Bulan</code> (angka 1 sampai 12), dan
                <code class="font-mono text-[#1A1A1A]">Jumlah Pendaftar</code> (bilangan bulat 0 atau lebih).
            </p>

            <a href="{{ route('import-excel.template') }}" class="btn btn-ghost">
                <x-icon name="Download" :size="16" :strokeWidth="2" />
                <span>Unduh Template (.xlsx)</span>
            </a>
        </section>

        {{-- Langkah 2. Unggah Data (FR-DH-04 & FR-DH-06) --}}
        <section class="form-card" aria-labelledby="headingLangkah2">
            <div class="card-header">
                <h2 id="headingLangkah2" class="card-title">
                    <x-icon name="Upload" :size="18" :strokeWidth="1.5" />
                    <span>Langkah 2. Unggah dan Periksa Data</span>
                </h2>
            </div>

            <form method="POST" action="{{ route('import-excel.preview') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="form-group">
                    <label for="berkas_excel" class="form-label required">Pilih Berkas Excel (.xlsx)</label>
                    <label for="berkas_excel" class="upload-dropzone block">
                        <input
                            type="file"
                            id="berkas_excel"
                            name="berkas_excel"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            class="form-input {{ $errors->has('berkas_excel') ? 'error' : '' }}"
                            required
                        >
                        <div id="selectedFileInfo" class="form-hint mt-2">
                            Hanya berkas .xlsx dengan ukuran maksimal 2 MB (2.048 KB).
                        </div>
                    </label>
                    @error('berkas_excel')
                        <p class="form-error-message" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="mode_duplikat" class="form-label required">Bila Periode Tahun-Bulan Sudah Ada</label>
                    <select
                        id="mode_duplikat"
                        name="mode_duplikat"
                        class="form-input {{ $errors->has('mode_duplikat') ? 'error' : '' }}"
                    >
                        <option value="timpa" @selected(old('mode_duplikat', $pratinjau['mode'] ?? 'timpa') === 'timpa')>
                            Timpa — Ganti jumlah pendaftar lama dengan nilai baru dari berkas
                        </option>
                        <option value="lewati" @selected(old('mode_duplikat', $pratinjau['mode'] ?? 'timpa') === 'lewati')>
                            Lewati — Pertahankan nilai lama di database untuk periode yang sudah ada
                        </option>
                    </select>
                    @error('mode_duplikat')
                        <p class="form-error-message" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="Upload" :size="16" :strokeWidth="2" />
                        <span>Periksa Data</span>
                    </button>
                </div>
            </form>
        </section>

        {{-- Langkah 3. Pratinjau & Konfirmasi (FR-DH-04, FR-DH-05, FR-DH-06) --}}
        @if($pratinjau !== null)
            <section class="card" aria-labelledby="headingLangkah3">
                <div class="card-header">
                    <div>
                        <h2 id="headingLangkah3" class="card-title">Langkah 3. Pratinjau Hasil Pemeriksaan</h2>
                        <p class="text-[12px] text-[#A3A3A3] mt-0.5 mb-0">
                            Berkas: <span class="font-mono text-[#1A1A1A]">{{ $pratinjau['nama_file'] }}</span> —
                            Mode duplikat: <strong class="uppercase">{{ $pratinjau['mode'] }}</strong>
                        </p>
                    </div>
                </div>

                <div class="stack-md">
                    <div class="stats-grid">
                        <x-stat-card
                            label="Baris Valid"
                            :value="(string) $pratinjau['jumlah_valid']"
                            subtext="Siap diproses"
                            accent="green"
                        />

                        <x-stat-card
                            label="Menimpa Data Lama"
                            :value="(string) $pratinjau['jumlah_menimpa']"
                            :subtext="$pratinjau['mode'] === 'timpa' ? 'Akan diperbarui' : 'Akan dilewati'"
                            accent="amber"
                        />

                        <x-stat-card
                            label="Baris Tidak Valid"
                            :value="(string) $pratinjau['jumlah_tidak_valid']"
                            subtext="Otomatis dilewati"
                            accent="red"
                        />
                    </div>

                    @if($pratinjau['galat'] !== [])
                        <div class="table-card">
                            <div class="table-toolbar">
                                <div class="font-semibold text-[13px] text-[#DC2626]">
                                    Laporan Baris Tidak Valid ({{ count($pratinjau['galat']) }} temuan)
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="th-numeric">Nomor Baris</th>
                                            <th>Kolom</th>
                                            <th>Alasan Galat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pratinjau['galat'] as $itemGalat)
                                            <tr>
                                                <td class="td-numeric td-primary">{{ $itemGalat['baris'] }}</td>
                                                <td class="td-primary">{{ $itemGalat['kolom'] }}</td>
                                                <td>{{ $itemGalat['alasan'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="form-actions">
                        @if($pratinjau['jumlah_valid'] > 0)
                            <form method="POST" action="{{ route('import-excel.confirm') }}">
                                @csrf
                                <input type="hidden" name="mode_duplikat" value="{{ $pratinjau['mode'] }}">
                                <button type="submit" class="btn btn-primary">
                                    <x-icon name="Check" :size="16" :strokeWidth="2" />
                                    <span>Simpan {{ $pratinjau['jumlah_valid'] }} Baris Valid</span>
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('import-excel.cancel') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost">
                                <x-icon name="X" :size="16" :strokeWidth="2" />
                                <span>Batal</span>
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        @endif

        {{-- Riwayat Impor (FR-DH-09 — P2) --}}
        <section class="table-card" aria-labelledby="headingRiwayatImpor">
            <div class="table-toolbar">
                <h2 id="headingRiwayatImpor" class="card-title">Riwayat Impor Terakhir</h2>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Nama Berkas</th>
                            <th>Pengguna</th>
                            <th class="th-numeric">Berhasil</th>
                            <th class="th-numeric">Gagal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayatImpor as $riwayat)
                            <tr>
                                <td class="td-numeric">{{ $riwayat->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="td-primary">{{ $riwayat->nama_file }}</td>
                                <td>{{ $riwayat->pengguna?->name ?? 'Admin' }}</td>
                                <td class="td-numeric">
                                    <span class="badge badge-success">{{ $riwayat->baris_berhasil }}</span>
                                </td>
                                <td class="td-numeric">
                                    @if($riwayat->baris_gagal > 0)
                                        <span class="badge badge-danger">{{ $riwayat->baris_gagal }}</span>
                                    @else
                                        <span class="badge badge-neutral">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-[#A3A3A3]">
                                    Belum ada riwayat impor berkas Excel.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

