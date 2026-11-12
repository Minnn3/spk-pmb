<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengaturanSpk\UpdatePengaturanSpkRequest;
use App\Models\PengaturanSpk;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PengaturanSpkController extends Controller
{
    /**
     * Tampilkan halaman Konfigurasi Parameter SPK (FR-KP-01, FR-KP-04).
     */
    public function index(): View
    {
        Gate::authorize('viewAny', PengaturanSpk::class);

        $pengaturan = PengaturanSpk::query()
            ->with('pengubah:id,name')
            ->orderBy('id')
            ->get()
            ->keyBy('kunci');

        $perubahanTerakhir = $pengaturan
            ->filter(static fn (PengaturanSpk $item): bool => $item->updated_at !== null)
            ->sortByDesc('updated_at')
            ->first();

        return view('pengaturan-spk.index', [
            'pengaturan' => $pengaturan,
            'defaults' => PengaturanSpk::DEFAULTS,
            'perubahanTerakhir' => $perubahanTerakhir,
        ]);
    }

    /**
     * Simpan perubahan empat parameter SPK setelah lolos validasi (FR-KP-02, FR-KP-04).
     */
    public function update(UpdatePengaturanSpkRequest $request): RedirectResponse
    {
        $tervalidasi = $request->validated();
        $userId = (int) $request->user()->id;

        DB::transaction(function () use ($tervalidasi, $userId): void {
            foreach (PengaturanSpk::DEFAULTS as $kunci => $konfigurasi) {
                if (! array_key_exists($kunci, $tervalidasi)) {
                    continue;
                }

                PengaturanSpk::query()->updateOrCreate(
                    ['kunci' => $kunci],
                    [
                        'label' => $konfigurasi['label'],
                        'nilai' => (int) $tervalidasi[$kunci],
                        'nilai_default' => $konfigurasi['nilai'],
                        'satuan' => $konfigurasi['satuan'],
                        'diubah_oleh' => $userId,
                    ]
                );
            }
        });

        return redirect()
            ->route('pengaturan-spk.index')
            ->with('toast_success', 'Parameter keputusan SPK berhasil disimpan dan langsung aktif.');
    }

    /**
     * Kembalikan keempat nilai parameter SPK ke nilai default awal (FR-KP-03, FR-KP-04).
     */
    public function resetDefault(Request $request): RedirectResponse
    {
        Gate::authorize('update', PengaturanSpk::class);

        $userId = (int) $request->user()->id;

        DB::transaction(function () use ($userId): void {
            foreach (PengaturanSpk::DEFAULTS as $kunci => $konfigurasi) {
                PengaturanSpk::query()->updateOrCreate(
                    ['kunci' => $kunci],
                    [
                        'label' => $konfigurasi['label'],
                        'nilai' => $konfigurasi['nilai'],
                        'nilai_default' => $konfigurasi['nilai'],
                        'satuan' => $konfigurasi['satuan'],
                        'diubah_oleh' => $userId,
                    ]
                );
            }
        });

        return redirect()
            ->route('pengaturan-spk.index')
            ->with('toast_success', 'Seluruh parameter SPK telah dikembalikan ke nilai default.');
    }
}

