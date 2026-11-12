@extends('layouts.app')

@section('title', 'Data Historis')
@section('page_title', 'Manajemen Data Historis')

@section('topbar_actions')
    <a href="{{ route('data-historis.export') }}" class="btn btn-ghost btn-sm">
        <x-icon name="Download" :size="16" :strokeWidth="2" />
        <span>Ekspor .xlsx</span>
    </a>

    <a href="{{ route('import-excel.index') }}" class="btn btn-ghost btn-sm">
        <x-icon name="Upload" :size="16" :strokeWidth="2" />
        <span>Import Excel</span>
    </a>

    <button type="button" class="btn btn-primary btn-sm" data-modal-open="modalTambahData">
        <x-icon name="Plus" :size="16" :strokeWidth="2" />
        <span>Tambah Data</span>
    </button>
@endsection

@section('content')
    <div class="stack-lg">
        {{-- Banner Kesiapan Data (FR-DH-08) --}}
        <section class="banner-card {{ ! $kesiapan['layak_hitung'] ? 'banner-danger' : ($kesiapan['peringatan_24_bulan'] ? 'banner-warning' : '') }}" aria-label="Status Kesiapan Data Historis">
            <div class="stack-md w-full">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center flex-wrap gap-3">
                        <span class="badge {{ $kesiapan['status_badge'] }}">{{ $kesiapan['status_label'] }}</span>
                        <span class="text-[13px] font-medium text-[#1A1A1A]">
                            Total Observasi: <strong class="font-mono">{{ $kesiapan['jumlah_bulan'] }} bulan</strong>
                            ({{ $kesiapan['rentang_periode'] }})
                        </span>
                    </div>

                    @if($kesiapan['layak_hitung'])
                        <a href="{{ route('perhitungan.index') }}" class="btn btn-ghost btn-xs">
                            <span>Lihat Perhitungan</span>
                        </a>
                    @endif
                </div>

                @if(! $kesiapan['layak_hitung'])
                    <div class="text-[13px]">
                        {{ $kesiapan['alasan_tidak_layak'] }}
                    </div>
                @elseif($kesiapan['peringatan_24_bulan'])
                    <div class="text-[12px]">
                        {{ $kesiapan['pesan_peringatan'] }}
                    </div>
                @endif
            </div>
        </section>

        {{-- Jika terdapat galat validasi form tambah/ubah, tampilkan kartu form langsung agar mudah diperbaiki --}}
        @if($errors->any())
            <div class="banner-card banner-danger" role="alert">
                <div class="flex items-start gap-3">
                    <x-icon name="AlertTriangle" :size="18" :strokeWidth="2" />
                    <div>
                        <div class="font-semibold text-[13px]">Gagal Menyimpan Data Historis</div>
                        <ul class="mt-1 mb-0 pl-4 text-[12px]">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabel Data Historis dengan DataTables (FR-DH-07) --}}
        <section class="table-card" data-datatable data-page-size="12" aria-labelledby="headingTabelHistoris">
            <div class="table-toolbar">
                <div class="table-toolbar-group">
                    <input
                        type="search"
                        class="form-input btn-sm"
                        placeholder="Cari tahun, bulan, atau jumlah..."
                        data-dt-search
                        aria-label="Cari data historis"
                    >

                    <select
                        class="form-input btn-sm topbar-select"
                        data-dt-year
                        aria-label="Filter berdasarkan tahun"
                    >
                        <option value="">Semua Tahun</option>
                        @foreach($daftarTahun as $thn)
                            <option value="{{ $thn }}" @selected($tahunFilter === (int) $thn)>Tahun {{ $thn }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-[12px] text-[#A3A3A3]" id="headingTabelHistoris">
                    Urutan deret waktu kronologis (Tahun, Bulan)
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th data-dt-sort="number">No</th>
                            <th data-dt-sort="number">Tahun</th>
                            <th data-dt-sort="number">Bulan</th>
                            <th class="th-numeric" data-dt-sort="number">Jumlah Pendaftar (Y)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataPendaftar as $indeks => $item)
                            <tr data-dt-row data-year="{{ $item->tahun }}">
                                <td class="td-numeric" data-sort-value="{{ $indeks + 1 }}">{{ $indeks + 1 }}</td>
                                <td class="td-numeric" data-sort-value="{{ $item->tahun }}">{{ $item->tahun }}</td>
                                <td class="td-primary" data-sort-value="{{ $item->bulan }}">
                                    {{ $item->nama_bulan }} ({{ $item->bulan }})
                                </td>
                                <td class="td-numeric td-primary" data-sort-value="{{ $item->jumlah_pendaftar }}">
                                    {{ number_format($item->jumlah_pendaftar, 0, ',', '.') }}
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="table-action-btn"
                                            data-modal-open="modalEditData"
                                            data-action-url="{{ route('data-historis.update', $item) }}"
                                            data-tahun="{{ $item->tahun }}"
                                            data-bulan="{{ $item->bulan }}"
                                            data-jumlah="{{ $item->jumlah_pendaftar }}"
                                            title="Ubah periode {{ $item->label_periode }}"
                                        >
                                            <x-icon name="Pencil" :size="14" :strokeWidth="2" />
                                            <span>Edit</span>
                                        </button>

                                        <button
                                            type="button"
                                            class="table-action-btn danger"
                                            data-modal-open="modalHapusData"
                                            data-action-url="{{ route('data-historis.destroy', $item) }}"
                                            data-periode="{{ $item->label_periode }}"
                                            title="Hapus periode {{ $item->label_periode }}"
                                        >
                                            <x-icon name="Trash2" :size="14" :strokeWidth="2" />
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-[#A3A3A3]">
                                    Belum ada data pendaftar historis. Silakan tambah data manual atau unggah lewat Import Excel.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div data-dt-info>Menampilkan entri</div>
                <div class="pagination-controls" data-dt-pagination></div>
            </div>
        </section>
    </div>

    {{-- Modal Tambah Data Historis --}}
    <div id="modalTambahData" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="titleTambahData">
        <div class="modal">
            <div class="modal-header">
                <h2 id="titleTambahData" class="modal-title">Tambah Data Pendaftar Bulanan</h2>
                <button type="button" class="table-action-btn" data-modal-close aria-label="Tutup dialog">
                    <x-icon name="X" :size="18" :strokeWidth="2" />
                </button>
            </div>

            <form method="POST" action="{{ route('data-historis.store') }}" novalidate>
                @csrf

                <div class="form-grid">
                    <div class="form-group">
                        <label for="tambah_tahun" class="form-label required">Tahun</label>
                        <input
                            type="number"
                            id="tambah_tahun"
                            name="tahun"
                            value="{{ old('tahun', ($kesiapan['tahun_akhir'] ?? (int) date('Y'))) }}"
                            min="1900"
                            max="2100"
                            class="form-input form-input-mono {{ $errors->has('tahun') ? 'error' : '' }}"
                            required
                        >
                        <div class="form-hint">Format 4 digit (contoh: 2025)</div>
                    </div>

                    <div class="form-group">
                        <label for="tambah_bulan" class="form-label required">Bulan</label>
                        <select
                            id="tambah_bulan"
                            name="bulan"
                            class="form-input {{ $errors->has('bulan') ? 'error' : '' }}"
                            required
                        >
                            @foreach($namaBulanList as $angka => $nama)
                                <option value="{{ $angka }}" @selected((int) old('bulan', 1) === $angka)>
                                    {{ $angka }} — {{ $nama }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint">Pilih angka bulan 1 sampai 12</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="tambah_jumlah_pendaftar" class="form-label required">Jumlah Pendaftar (Y Aktual)</label>
                    <input
                        type="number"
                        id="tambah_jumlah_pendaftar"
                        name="jumlah_pendaftar"
                        value="{{ old('jumlah_pendaftar', 0) }}"
                        min="0"
                        class="form-input form-input-mono {{ $errors->has('jumlah_pendaftar') ? 'error' : '' }}"
                        required
                    >
                    <div class="form-hint">Bilangan bulat 0 atau lebih (orang/bulan)</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="Save" :size="16" :strokeWidth="2" />
                        <span>Simpan Data</span>
                    </button>
                    <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Data Historis --}}
    <div id="modalEditData" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="titleEditData">
        <div class="modal">
            <div class="modal-header">
                <h2 id="titleEditData" class="modal-title">Ubah Data Pendaftar Bulanan</h2>
                <button type="button" class="table-action-btn" data-modal-close aria-label="Tutup dialog">
                    <x-icon name="X" :size="18" :strokeWidth="2" />
                </button>
            </div>

            <form id="formEditData" method="POST" action="" novalidate>
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_tahun" class="form-label required">Tahun</label>
                        <input
                            type="number"
                            id="edit_tahun"
                            name="tahun"
                            min="1900"
                            max="2100"
                            class="form-input form-input-mono"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="edit_bulan" class="form-label required">Bulan</label>
                        <select id="edit_bulan" name="bulan" class="form-input" required>
                            @foreach($namaBulanList as $angka => $nama)
                                <option value="{{ $angka }}">{{ $angka }} — {{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_jumlah_pendaftar" class="form-label required">Jumlah Pendaftar (Y Aktual)</label>
                    <input
                        type="number"
                        id="edit_jumlah_pendaftar"
                        name="jumlah_pendaftar"
                        min="0"
                        class="form-input form-input-mono"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="Check" :size="16" :strokeWidth="2" />
                        <span>Simpan Perubahan</span>
                    </button>
                    <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Hapus Data Historis --}}
    <div id="modalHapusData" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="titleHapusData">
        <div class="modal">
            <div class="modal-header">
                <h2 id="titleHapusData" class="modal-title">Hapus Data Historis</h2>
                <button type="button" class="table-action-btn" data-modal-close aria-label="Tutup dialog">
                    <x-icon name="X" :size="18" :strokeWidth="2" />
                </button>
            </div>

            <p class="text-[13px] text-[#525252] mb-0">
                Apakah Anda yakin ingin menghapus data pendaftar periode <strong id="hapusPeriodeLabel" class="text-[#1A1A1A]"></strong>?
                Menghapus bulan di tengah rentang deret waktu akan membuat perhitungan tidak dapat dijalankan sampai bulan tersebut diisi kembali.
            </p>

            <form id="formHapusData" method="POST" action="">
                @csrf
                @method('DELETE')

                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">
                        <x-icon name="Trash2" :size="16" :strokeWidth="2" />
                        <span>Ya, Hapus Data</span>
                    </button>
                    <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                </div>
            </form>
        </div>
    </div>
@endsection

