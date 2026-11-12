<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataPendaftar\ConfirmImportDataPendaftarRequest;
use App\Http\Requests\DataPendaftar\PreviewImportDataPendaftarRequest;
use App\Models\DataPendaftar;
use App\Models\RiwayatImpor;
use App\Services\ExcelDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ImportDataPendaftarController extends Controller
{
    /**
     * Kunci session untuk menyimpan pratinjau unggahan sementara.
     */
    public const SESSION_PRATINJAU_KEY = 'spk_pratinjau_impor_excel';

    /**
     * Tampilkan halaman Import Excel beserta pratinjau dan riwayat impor (FR-DH-03 s/d FR-DH-09).
     */
    public function index(Request $request): View
    {
        Gate::authorize('create', DataPendaftar::class);

        $pratinjau = $request->session()->get(self::SESSION_PRATINJAU_KEY);

        $riwayatImpor = RiwayatImpor::query()
            ->with('pengguna:id,name')
            ->terbaru()
            ->paginate(10);

        return view('import-excel.index', [
            'pratinjau' => $pratinjau,
            'riwayatImpor' => $riwayatImpor,
        ]);
    }

    /**
     * Unduh berkas template Excel (.xlsx) (FR-DH-03).
     */
    public function downloadTemplate(ExcelDataService $excelService): StreamedResponse
    {
        Gate::authorize('create', DataPendaftar::class);

        return $excelService->unduhTemplate();
    }

    /**
     * Proses berkas Excel yang diunggah dan tampilkan pratinjau validasi (FR-DH-04, FR-DH-06).
     */
    public function preview(
        PreviewImportDataPendaftarRequest $request,
        ExcelDataService $excelService
    ): RedirectResponse {
        $berkas = $request->file('berkas_excel');
        $modeDuplikat = (string) $request->validated('mode_duplikat');

        try {
            $hasilPratinjau = $excelService->analisisBerkas(
                $berkas->getRealPath(),
                $berkas->getClientOriginalName(),
                $modeDuplikat
            );
        } catch (Throwable) {
            return back()
                ->withInput()
                ->withErrors([
                    'berkas_excel' => 'Berkas Excel tidak dapat dibaca. Pastikan berkas berformat .xlsx yang valid.',
                ]);
        }

        $request->session()->put(self::SESSION_PRATINJAU_KEY, $hasilPratinjau);

        return redirect()
            ->route('import-excel.index')
            ->with('toast_info', 'Pemeriksaan berkas selesai. Silakan tinjau ringkasan sebelum menyimpan.');
    }

    /**
     * Konfirmasi dan simpan seluruh baris valid dari pratinjau ke database (FR-DH-05, FR-DH-06).
     */
    public function confirm(
        ConfirmImportDataPendaftarRequest $request,
        ExcelDataService $excelService
    ): RedirectResponse {
        /** @var array<string, mixed>|null $pratinjau */
        $pratinjau = $request->session()->get(self::SESSION_PRATINJAU_KEY);

        if ($pratinjau === null) {
            return redirect()
                ->route('import-excel.index')
                ->with('toast_warning', 'Sesi pratinjau tidak ditemukan. Silakan unggah berkas kembali.');
        }

        if ($request->filled('mode_duplikat')) {
            $pratinjau['mode'] = (string) $request->validated('mode_duplikat');
        }

        $hasilSimpan = $excelService->simpanPratinjau($pratinjau, (int) $request->user()->id);
        $request->session()->forget(self::SESSION_PRATINJAU_KEY);

        return redirect()
            ->route('data-historis.index')
            ->with(
                'toast_success',
                "Impor selesai: {$hasilSimpan['disimpan']} baris valid berhasil disimpan."
            );
    }

    /**
     * Batalkan pratinjau impor yang sedang aktif.
     */
    public function cancel(Request $request): RedirectResponse
    {
        Gate::authorize('create', DataPendaftar::class);

        $request->session()->forget(self::SESSION_PRATINJAU_KEY);

        return redirect()
            ->route('import-excel.index')
            ->with('toast_info', 'Pratinjau impor dibatalkan.');
    }
}

