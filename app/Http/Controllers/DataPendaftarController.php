<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataPendaftar\StoreDataPendaftarRequest;
use App\Http\Requests\DataPendaftar\UpdateDataPendaftarRequest;
use App\Models\DataPendaftar;
use App\Services\ExcelDataService;
use App\Services\ForecastService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPendaftarController extends Controller
{
    /**
     * Tampilkan halaman Manajemen Data Historis Pendaftar (FR-DH-01, FR-DH-07, FR-DH-08).
     */
    public function index(Request $request, ForecastService $forecastService): View
    {
        Gate::authorize('viewAny', DataPendaftar::class);

        $semuaData = DataPendaftar::query()->urutKronologis()->get();
        $kesiapan = $forecastService->periksaKesiapanData($semuaData);

        $daftarTahun = $semuaData
            ->pluck('tahun')
            ->unique()
            ->sort()
            ->values();

        $tahunFilter = $request->filled('tahun') ? (int) $request->integer('tahun') : null;

        $dataTabel = $tahunFilter !== null
            ? $semuaData->where('tahun', $tahunFilter)->values()
            : $semuaData;

        return view('data-historis.index', [
            'dataPendaftar' => $dataTabel,
            'daftarTahun' => $daftarTahun,
            'tahunFilter' => $tahunFilter,
            'kesiapan' => $kesiapan,
            'namaBulanList' => DataPendaftar::NAMA_BULAN,
        ]);
    }

    /**
     * Simpan data pendaftar bulanan baru (FR-DH-01, FR-DH-02).
     */
    public function store(StoreDataPendaftarRequest $request): RedirectResponse
    {
        $data = DataPendaftar::query()->create($request->validated());

        return redirect()
            ->route('data-historis.index')
            ->with('toast_success', 'Data periode ' . $data->label_periode . ' berhasil ditambahkan.');
    }

    /**
     * Perbarui data pendaftar bulanan yang sudah ada (FR-DH-01, FR-DH-02).
     */
    public function update(UpdateDataPendaftarRequest $request, DataPendaftar $dataPendaftar): RedirectResponse
    {
        $dataPendaftar->update($request->validated());

        return redirect()
            ->route('data-historis.index')
            ->with('toast_success', 'Data periode ' . $dataPendaftar->label_periode . ' berhasil diperbarui.');
    }

    /**
     * Hapus satu baris data pendaftar bulanan (FR-DH-01).
     */
    public function destroy(DataPendaftar $dataPendaftar): RedirectResponse
    {
        Gate::authorize('delete', $dataPendaftar);

        $labelPeriode = $dataPendaftar->label_periode;
        $dataPendaftar->delete();

        return redirect()
            ->route('data-historis.index')
            ->with('toast_success', 'Data periode ' . $labelPeriode . ' berhasil dihapus.');
    }

    /**
     * Ekspor seluruh data historis ke berkas .xlsx (FR-DH-10).
     */
    public function export(ExcelDataService $excelService): StreamedResponse
    {
        Gate::authorize('viewAny', DataPendaftar::class);

        return $excelService->eksporDataHistoris();
    }
}

