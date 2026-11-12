@extends('layouts.app')

@section('title', 'Pengaturan SPK')
@section('page_title', 'Konfigurasi Parameter SPK')

@section('topbar_actions')
    <a href="{{ route('dashboard.index') }}" class="btn btn-ghost btn-sm">
        <x-icon name="LayoutDashboard" :size="16" :strokeWidth="2" />
        <span>Buka Dashboard</span>
    </a>
@endsection

@section('content')
    <div class="content-narrow stack-lg">
        <section class="form-card" aria-labelledby="headingFormParameter">
            <div class="card-header">
                <div>
                    <h2 id="headingFormParameter" class="card-title">
                        <x-icon name="Settings" :size="18" :strokeWidth="1.5" />
                        <span>Parameter Keputusan SPK</span>
                    </h2>
                    <p class="text-[12px] text-[#A3A3A3] mt-0.5 mb-0">
                        Perubahan parameter langsung diterapkan pada perhitungan status musim, alokasi staf, dan estimasi kas di Dashboard.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('pengaturan-spk.update') }}" novalidate>
                @csrf
                @method('PUT')

                {{-- 1. target_minimum_pendaftar --}}
                @php
                    $itemMin = $pengaturan->get('target_minimum_pendaftar');
                    $valMin = old('target_minimum_pendaftar', $itemMin?->nilai ?? $defaults['target_minimum_pendaftar']['nilai']);
                @endphp
                <div class="form-group">
                    <label for="target_minimum_pendaftar" class="form-label required">
                        Target minimum pendaftar
                    </label>
                    <input
                        type="number"
                        id="target_minimum_pendaftar"
                        name="target_minimum_pendaftar"
                        value="{{ $valMin }}"
                        min="0"
                        class="form-input form-input-mono {{ $errors->has('target_minimum_pendaftar') ? 'error' : '' }}"
                        required
                    >
                    <div class="form-hint">
                        Satuan: <strong>orang/bulan</strong> (Default: {{ number_format($defaults['target_minimum_pendaftar']['nilai'], 0, ',', '.') }}).
                        Jika Y_akhir di bawah nilai ini, bulan berstatus <em>Low Season</em>. Wajib lebih kecil dari Batas peak season.
                    </div>
                    @error('target_minimum_pendaftar')
                        <p class="form-error-message" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 2. batas_peak_season --}}
                @php
                    $itemPeak = $pengaturan->get('batas_peak_season');
                    $valPeak = old('batas_peak_season', $itemPeak?->nilai ?? $defaults['batas_peak_season']['nilai']);
                @endphp
                <div class="form-group">
                    <label for="batas_peak_season" class="form-label required">
                        Batas peak season
                    </label>
                    <input
                        type="number"
                        id="batas_peak_season"
                        name="batas_peak_season"
                        value="{{ $valPeak }}"
                        min="1"
                        class="form-input form-input-mono {{ $errors->has('batas_peak_season') ? 'error' : '' }}"
                        required
                    >
                    <div class="form-hint">
                        Satuan: <strong>orang/bulan</strong> (Default: {{ number_format($defaults['batas_peak_season']['nilai'], 0, ',', '.') }}).
                        Jika Y_akhir sama dengan atau melebihi nilai ini, bulan berstatus <em>Peak Season</em>.
                    </div>
                    @error('batas_peak_season')
                        <p class="form-error-message" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 3. kapasitas_layan_staf --}}
                @php
                    $itemStaf = $pengaturan->get('kapasitas_layan_staf');
                    $valStaf = old('kapasitas_layan_staf', $itemStaf?->nilai ?? $defaults['kapasitas_layan_staf']['nilai']);
                @endphp
                <div class="form-group">
                    <label for="kapasitas_layan_staf" class="form-label required">
                        Kapasitas layan staf
                    </label>
                    <input
                        type="number"
                        id="kapasitas_layan_staf"
                        name="kapasitas_layan_staf"
                        value="{{ $valStaf }}"
                        min="1"
                        class="form-input form-input-mono {{ $errors->has('kapasitas_layan_staf') ? 'error' : '' }}"
                        required
                    >
                    <div class="form-hint">
                        Satuan: <strong>pendaftar/staf/bulan</strong> (Default: {{ number_format($defaults['kapasitas_layan_staf']['nilai'], 0, ',', '.') }}).
                        Dipakai pada rumus <span class="font-mono">CEIL(Y_akhir / kapasitas_layan_staf)</span>. Minimal 1.
                    </div>
                    @error('kapasitas_layan_staf')
                        <p class="form-error-message" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 4. estimasi_biaya_pendaftaran --}}
                @php
                    $itemBiaya = $pengaturan->get('estimasi_biaya_pendaftaran');
                    $valBiaya = old('estimasi_biaya_pendaftaran', $itemBiaya?->nilai ?? $defaults['estimasi_biaya_pendaftaran']['nilai']);
                @endphp
                <div class="form-group">
                    <label for="estimasi_biaya_pendaftaran" class="form-label required">
                        Estimasi biaya pendaftaran
                    </label>
                    <input
                        type="number"
                        id="estimasi_biaya_pendaftaran"
                        name="estimasi_biaya_pendaftaran"
                        value="{{ $valBiaya }}"
                        min="0"
                        class="form-input form-input-mono {{ $errors->has('estimasi_biaya_pendaftaran') ? 'error' : '' }}"
                        required
                    >
                    <div class="form-hint">
                        Satuan: <strong>Rupiah</strong> (Default: Rp {{ number_format($defaults['estimasi_biaya_pendaftaran']['nilai'], 0, ',', '.') }}).
                        Dipakai pada rumus <span class="font-mono">Y_akhir × estimasi_biaya_pendaftaran</span>.
                    </div>
                    @error('estimasi_biaya_pendaftaran')
                        <p class="form-error-message" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="Save" :size="16" :strokeWidth="2" />
                        <span>Simpan Parameter</span>
                    </button>

                    <button type="button" class="btn btn-ghost" data-modal-open="modalResetDefault">
                        <x-icon name="RefreshCw" :size="16" :strokeWidth="2" />
                        <span>Kembalikan ke Default</span>
                    </button>
                </div>
            </form>

            {{-- Informasi Audit Perubahan Terakhir (FR-KP-04) --}}
            @if($perubahanTerakhir !== null)
                <div class="mt-5 pt-4 border-t border-[#E5E4E0] text-[12px] text-[#A3A3A3] flex items-center justify-between flex-wrap gap-2">
                    <span>
                        Terakhir diperbarui oleh:
                        <strong class="text-[#525252]">{{ $perubahanTerakhir->pengubah?->name ?? 'Sistem Seeder' }}</strong>
                    </span>
                    <span class="font-mono">
                        {{ $perubahanTerakhir->updated_at?->format('d/m/Y H:i') }}
                    </span>
                </div>
            @endif
        </section>
    </div>

    {{-- Modal Konfirmasi Kembalikan ke Default (FR-KP-03) --}}
    <div id="modalResetDefault" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="titleResetDefault">
        <div class="modal">
            <div class="modal-header">
                <h2 id="titleResetDefault" class="modal-title">Kembalikan Parameter ke Default</h2>
                <button type="button" class="table-action-btn" data-modal-close aria-label="Tutup dialog">
                    <x-icon name="X" :size="18" :strokeWidth="2" />
                </button>
            </div>

            <p class="text-[13px] text-[#525252] mb-0">
                Apakah Anda yakin ingin memulihkan keempat parameter SPK ke nilai awal
                (Target Minimum: <span class="font-mono">30</span>,
                Batas Peak: <span class="font-mono">100</span>,
                Kapasitas Staf: <span class="font-mono">40</span>,
                Biaya Pendaftaran: <span class="font-mono">Rp 250.000</span>)?
            </p>

            <form method="POST" action="{{ route('pengaturan-spk.reset') }}">
                @csrf
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="RefreshCw" :size="16" :strokeWidth="2" />
                        <span>Ya, Pulihkan Default</span>
                    </button>
                    <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                </div>
            </form>
        </div>
    </div>
@endsection

