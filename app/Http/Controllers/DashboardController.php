<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSpk;
use App\Services\ExcelDataService;
use App\Services\ForecastService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman Dashboard Eksekutif dan Tabel Rekomendasi Keputusan (FR-DB-01 s/d FR-DB-05).
     */
    public function index(Request $request, ForecastService $forecastService): View
    {
        Gate::authorize('lihat-analisis');

        $tahunTarget = $request->filled('tahun_target')
            ? (int) $request->integer('tahun_target')
            : null;

        $hasil = $forecastService->hitung($tahunTarget);

        $daftarPengaturan = PengaturanSpk::query()
            ->with('pengubah:id,name')
            ->orderBy('id')
            ->get();

        return view('dashboard.index', [
            'hasil' => $hasil,
            'daftarPengaturan' => $daftarPengaturan,
        ]);
    }

    /**
     * Ekspor tabel rekomendasi keputusan ke berkas .xlsx (FR-DB-06).
     */
    public function exportRekomendasi(
        Request $request,
        ForecastService $forecastService,
        ExcelDataService $excelService
    ): StreamedResponse|RedirectResponse {
        Gate::authorize('lihat-analisis');

        $tahunTarget = $request->filled('tahun_target')
            ? (int) $request->integer('tahun_target')
            : null;

        $hasil = $forecastService->hitung($tahunTarget);

        if (! $hasil['layak_hitung']) {
            return redirect()
                ->route('dashboard.index')
                ->with('toast_danger', 'Data belum layak hitung sehingga rekomendasi belum dapat diekspor.');
        }

        return $excelService->eksporRekomendasi(
            (int) $hasil['tahun_target'],
            $hasil['keputusan'],
            $hasil['ringkasan']
        );
    }
}

