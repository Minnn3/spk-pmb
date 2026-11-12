<?php

namespace App\Http\Controllers;

use App\Services\ForecastService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PerhitunganController extends Controller
{
    /**
     * Tampilkan halaman Transparansi Perhitungan 5 Tahap (FR-PH-01 s/d FR-PH-09).
     */
    public function index(Request $request, ForecastService $forecastService): View
    {
        Gate::authorize('lihat-analisis');

        $tahunTarget = $request->filled('tahun_target')
            ? (int) $request->integer('tahun_target')
            : null;

        $hasil = $forecastService->hitung($tahunTarget);

        return view('perhitungan.index', [
            'hasil' => $hasil,
        ]);
    }
}

