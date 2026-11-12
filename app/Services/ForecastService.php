<?php

namespace App\Services;

use App\Models\DataPendaftar;
use App\Models\PengaturanSpk;
use Illuminate\Support\Collection;

class ForecastService
{
    /**
     * Jalankan seluruh tahapan perhitungan Trend Moment, Indeks Musiman,
     * dan Keputusan SPK berdasarkan data di database atau koleksi yang diberikan.
     *
     * @param  int|null  $tahunTarget  Tahun yang ingin diprediksi (default: tahun terakhir + 1)
     * @param  Collection<int, DataPendaftar>|array<int, array{tahun: int, bulan: int, jumlah_pendaftar: int}>|null  $dataMasukan
     * @param  array<string, int>|null  $parameterMasukan
     * @return array<string, mixed>
     */
    public function hitung(?int $tahunTarget = null, Collection|array|null $dataMasukan = null, ?array $parameterMasukan = null): array
    {
        $koleksiData = $this->siapkanDataKronologis($dataMasukan);
        $parameter = $this->siapkanParameter($parameterMasukan);
        $kesiapan = $this->periksaKesiapanData($koleksiData);

        $tahunDefault = $kesiapan['tahun_akhir'] !== null
            ? $kesiapan['tahun_akhir'] + 1
            : (int) date('Y') + 1;

        $tahunTargetAktif = $tahunTarget ?? $tahunDefault;
        $daftarPilihanTahun = $this->buatPilihanTahunTarget($kesiapan['tahun_awal'], $kesiapan['tahun_akhir'], $tahunTargetAktif);

        if (! $kesiapan['layak_hitung']) {
            return [
                'layak_hitung' => false,
                'kesiapan' => $kesiapan,
                'parameter' => $parameter,
                'tahun_target' => $tahunTargetAktif,
                'pilihan_tahun_target' => $daftarPilihanTahun,
                'tahap1' => null,
                'tahap2' => null,
                'tahap3' => null,
                'tahap4' => null,
                'keputusan' => [],
                'ringkasan' => null,
                'seri_grafik' => null,
            ];
        }

        $tahap1 = $this->hitungTahap1Variabel($koleksiData);
        $tahap2 = $this->hitungTahap2KoefisienTren($tahap1);
        $tahap3 = $this->hitungTahap3IndeksMusiman($koleksiData, $tahap1['total']['sigma_y'], $tahap1['total']['n']);
        $tahap4 = $this->hitungTahap4PrediksiAkhir(
            $tahunTargetAktif,
            (int) $kesiapan['tahun_awal'],
            (int) $kesiapan['bulan_awal'],
            $tahap2['a'],
            $tahap2['b'],
            $tahap3['indeks_per_bulan'],
            $koleksiData
        );
        $keputusan = $this->hitungTahap5Keputusan($tahap4['baris'], $parameter);
        $ringkasan = $this->buatRingkasanDashboard($keputusan);
        $seriGrafik = $this->buatSeriGrafik($koleksiData, $keputusan, $parameter, $tahunTargetAktif);

        return [
            'layak_hitung' => true,
            'kesiapan' => $kesiapan,
            'parameter' => $parameter,
            'tahun_target' => $tahunTargetAktif,
            'pilihan_tahun_target' => $daftarPilihanTahun,
            'tahap1' => $tahap1,
            'tahap2' => $tahap2,
            'tahap3' => $tahap3,
            'tahap4' => $tahap4,
            'keputusan' => $keputusan,
            'ringkasan' => $ringkasan,
            'seri_grafik' => $seriGrafik,
        ];
    }

    /**
     * Periksa kelayakan deret waktu historis (minimal 12 bulan, tanpa bulan kosong).
     *
     * @param  Collection<int, array{tahun: int, bulan: int, jumlah_pendaftar: int}>|null  $koleksiData
     * @return array{
     *     jumlah_bulan: int,
     *     layak_hitung: bool,
     *     peringatan_24_bulan: bool,
     *     tahun_awal: int|null,
     *     bulan_awal: int|null,
     *     tahun_akhir: int|null,
     *     bulan_akhir: int|null,
     *     periode_awal: string|null,
     *     periode_akhir: string|null,
     *     rentang_periode: string,
     *     bulan_hilang: list<string>,
     *     alasan_tidak_layak: string|null,
     *      pesan_peringatan: string|null,
     *     status_badge: string,
     *     status_label: string
     * }
     */
    public function periksaKesiapanData(?Collection $koleksiData = null): array
    {
        $data = $koleksiData !== null
            ? $this->siapkanDataKronologis($koleksiData)
            : $this->siapkanDataKronologis(null);

        $jumlahBulan = $data->count();

        if ($jumlahBulan === 0) {
            return [
                'jumlah_bulan' => 0,
                'layak_hitung' => false,
                'peringatan_24_bulan' => false,
                'tahun_awal' => null,
                'bulan_awal' => null,
                'tahun_akhir' => null,
                'bulan_akhir' => null,
                'periode_awal' => null,
                'periode_akhir' => null,
                'rentang_periode' => 'Belum ada data',
                'bulan_hilang' => [],
                'alasan_tidak_layak' => 'Belum ada data historis pendaftar. Minimal dibutuhkan 12 bulan data berurutan.',
                'pesan_peringatan' => null,
                'status_badge' => 'badge-danger',
                'status_label' => 'Tidak layak hitung',
            ];
        }

        $awal = $data->first();
        $akhir = $data->last();

        $tahunAwal = (int) $awal['tahun'];
        $bulanAwal = (int) $awal['bulan'];
        $tahunAkhir = (int) $akhir['tahun'];
        $bulanAkhir = (int) $akhir['bulan'];

        $periodeAwal = DataPendaftar::namaBulan($bulanAwal) . ' ' . $tahunAwal;
        $periodeAkhir = DataPendaftar::namaBulan($bulanAkhir) . ' ' . $tahunAkhir;
        $rentangPeriode = $periodeAwal . ' – ' . $periodeAkhir;

        // Peta periode yang tersedia untuk mendeteksi celah bulan kosong
        $petaTersedia = [];
        foreach ($data as $item) {
            $kunci = sprintf('%04d-%02d', $item['tahun'], $item['bulan']);
            $petaTersedia[$kunci] = true;
        }

        $bulanHilang = [];
        $tahunIterasi = $tahunAwal;
        $bulanIterasi = $bulanAwal;

        while ($tahunIterasi < $tahunAkhir || ($tahunIterasi === $tahunAkhir && $bulanIterasi <= $bulanAkhir)) {
            $kunci = sprintf('%04d-%02d', $tahunIterasi, $bulanIterasi);
            if (! isset($petaTersedia[$kunci])) {
                $bulanHilang[] = DataPendaftar::namaBulan($bulanIterasi) . ' ' . $tahunIterasi;
            }

            $bulanIterasi++;
            if ($bulanIterasi > 12) {
                $bulanIterasi = 1;
                $tahunIterasi++;
            }
        }

        $alasanTidakLayak = null;

        if ($bulanHilang !== []) {
            $alasanTidakLayak = 'Data deret waktu tidak berurutan. Terdapat bulan yang hilang: ' . implode(', ', $bulanHilang) . '.';
        } elseif ($jumlahBulan < 12) {
            $alasanTidakLayak = "Jumlah data historis baru {$jumlahBulan} bulan. Minimal dibutuhkan 12 bulan berurutan untuk menjalankan perhitungan.";
        }

        $layakHitung = $alasanTidakLayak === null;
        $peringatan24Bulan = $layakHitung && $jumlahBulan < 24;
        $pesanPeringatan = $peringatan24Bulan
            ? "Data historis saat ini berjumlah {$jumlahBulan} bulan (di bawah rekomendasi 24 bulan). Perhitungan tetap dijalankan, namun beberapa bulan kalender baru memiliki 1 observasi untuk indeks musiman."
            : null;

        if (! $layakHitung) {
            $statusBadge = 'badge-danger';
            $statusLabel = 'Tidak layak hitung';
        } elseif ($peringatan24Bulan) {
            $statusBadge = 'badge-warning';
            $statusLabel = 'Kurang dari 24 bulan';
        } else {
            $statusBadge = 'badge-success';
            $statusLabel = 'Siap hitung';
        }

        return [
            'jumlah_bulan' => $jumlahBulan,
            'layak_hitung' => $layakHitung,
            'peringatan_24_bulan' => $peringatan24Bulan,
            'tahun_awal' => $tahunAwal,
            'bulan_awal' => $bulanAwal,
            'tahun_akhir' => $tahunAkhir,
            'bulan_akhir' => $bulanAkhir,
            'periode_awal' => $periodeAwal,
            'periode_akhir' => $periodeAkhir,
            'rentang_periode' => $rentangPeriode,
            'bulan_hilang' => $bulanHilang,
            'alasan_tidak_layak' => $alasanTidakLayak,
            'pesan_peringatan' => $pesanPeringatan,
            'status_badge' => $statusBadge,
            'status_label' => $statusLabel,
        ];
    }

    /**
     * Tahap 1: Hitung variabel X, Y, XY, X² untuk setiap baris dan totalnya.
     *
     * @param  Collection<int, array{tahun: int, bulan: int, jumlah_pendaftar: int}>  $data
     * @return array{
     *     baris: list<array{no: int, tahun: int, bulan: int, nama_bulan: string, x: int, y: int, xy: int, x2: int}>,
     *     total: array{n: int, sigma_x: int, sigma_y: int, sigma_xy: int, sigma_x2: int}
     * }
     */
    public function hitungTahap1Variabel(Collection $data): array
    {
        $baris = [];
        $sigmaX = 0;
        $sigmaY = 0;
        $sigmaXY = 0;
        $sigmaX2 = 0;

        foreach ($data->values() as $indeks => $item) {
            $x = $indeks;
            $y = (int) $item['jumlah_pendaftar'];
            $xy = $x * $y;
            $x2 = $x * $x;

            $sigmaX += $x;
            $sigmaY += $y;
            $sigmaXY += $xy;
            $sigmaX2 += $x2;

            $baris[] = [
                'no' => $indeks + 1,
                'tahun' => (int) $item['tahun'],
                'bulan' => (int) $item['bulan'],
                'nama_bulan' => DataPendaftar::namaBulan((int) $item['bulan']),
                'x' => $x,
                'y' => $y,
                'xy' => $xy,
                'x2' => $x2,
            ];
        }

        return [
            'baris' => $baris,
            'total' => [
                'n' => count($baris),
                'sigma_x' => $sigmaX,
                'sigma_y' => $sigmaY,
                'sigma_xy' => $sigmaXY,
                'sigma_x2' => $sigmaX2,
            ],
        ];
    }

    /**
     * Tahap 2: Hitung koefisien regresi Trend Moment (a dan b) beserta langkah eliminasi.
     *
     * @param  array{total: array{n: int, sigma_x: int, sigma_y: int, sigma_xy: int, sigma_x2: int}}  $tahap1
     * @return array{
     *     n: int,
     *     sigma_x: int,
     *     sigma_y: int,
     *     sigma_xy: int,
     *     sigma_x2: int,
     *     persamaan_1_dikali_sigma_x: array{kiri: int, koef_a: int, koef_b: int},
     *     persamaan_2_dikali_n: array{kiri: int, koef_a: int, koef_b: int},
     *     pembilang_b: int,
     *     penyebut_b: int,
     *     b: float,
     *     a: float,
     *     verifikasi_sigma_y: float
     * }
     */
    public function hitungTahap2KoefisienTren(array $tahap1): array
    {
        $n = $tahap1['total']['n'];
        $sigmaX = $tahap1['total']['sigma_x'];
        $sigmaY = $tahap1['total']['sigma_y'];
        $sigmaXY = $tahap1['total']['sigma_xy'];
        $sigmaX2 = $tahap1['total']['sigma_x2'];

        // Eliminasi a dengan mengalikan persamaan (1) dengan ΣX dan persamaan (2) dengan n
        $pers1Kiri = $sigmaY * $sigmaX;
        $pers1KoefA = $n * $sigmaX;
        $pers1KoefB = $sigmaX * $sigmaX;

        $pers2Kiri = $n * $sigmaXY;
        $pers2KoefA = $n * $sigmaX;
        $pers2KoefB = $n * $sigmaX2;

        $pembilangB = $pers2Kiri - $pers1Kiri;
        $penyebutB = $pers2KoefB - $pers1KoefB;

        $b = $penyebutB !== 0 ? ($pembilangB / $penyebutB) : 0.0;
        $a = $n !== 0 ? (($sigmaY - ($b * $sigmaX)) / $n) : 0.0;

        $verifikasiSigmaY = ($n * $a) + ($b * $sigmaX);

        return [
            'n' => $n,
            'sigma_x' => $sigmaX,
            'sigma_y' => $sigmaY,
            'sigma_xy' => $sigmaXY,
            'sigma_x2' => $sigmaX2,
            'persamaan_1_dikali_sigma_x' => [
                'kiri' => $pers1Kiri,
                'koef_a' => $pers1KoefA,
                'koef_b' => $pers1KoefB,
            ],
            'persamaan_2_dikali_n' => [
                'kiri' => $pers2Kiri,
                'koef_a' => $pers2KoefA,
                'koef_b' => $pers2KoefB,
            ],
            'pembilang_b' => $pembilangB,
            'penyebut_b' => $penyebutB,
            'b' => (float) $b,
            'a' => (float) $a,
            'verifikasi_sigma_y' => (float) $verifikasiSigmaY,
        ];
    }

    /**
     * Tahap 3: Hitung rata-rata total (Ȳ) dan Indeks Musiman (IM) untuk 12 bulan kalender.
     *
     * @param  Collection<int, array{tahun: int, bulan: int, jumlah_pendaftar: int}>  $data
     * @return array{
     *     y_bar: float,
     *     baris: list<array{bulan: int, nama_bulan: string, jumlah_observasi: int, total_bulan: int, rata_rata_bulan: float, y_bar: float, im: float}>,
     *     indeks_per_bulan: array<int, float>
     * }
     */
    public function hitungTahap3IndeksMusiman(Collection $data, int $sigmaY, int $n): array
    {
        $yBar = $n > 0 ? ($sigmaY / $n) : 0.0;
        $dikelompokkan = $data->groupBy('bulan');

        $baris = [];
        $indeksPerBulan = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            /** @var Collection<int, array{tahun: int, bulan: int, jumlah_pendaftar: int}> $grupBulan */
            $grupBulan = $dikelompokkan->get($bulan, collect());
            $jumlahObservasi = $grupBulan->count();
            $totalBulan = (int) $grupBulan->sum('jumlah_pendaftar');
            $rataRataBulan = $jumlahObservasi > 0 ? ($totalBulan / $jumlahObservasi) : 0.0;
            $im = $yBar > 0.0 ? ($rataRataBulan / $yBar) : 0.0;

            $indeksPerBulan[$bulan] = (float) $im;

            $baris[] = [
                'bulan' => $bulan,
                'nama_bulan' => DataPendaftar::namaBulan($bulan),
                'jumlah_observasi' => $jumlahObservasi,
                'total_bulan' => $totalBulan,
                'rata_rata_bulan' => (float) $rataRataBulan,
                'y_bar' => (float) $yBar,
                'im' => (float) $im,
            ];
        }

        return [
            'y_bar' => (float) $yBar,
            'baris' => $baris,
            'indeks_per_bulan' => $indeksPerBulan,
        ];
    }

    /**
     * Tahap 4: Hitung prediksi akhir (Y_akhir) untuk 12 bulan pada tahun target.
     * Pembulatan ROUND (half-up) hanya dilakukan satu kali pada Y_akhir.
     *
     * @param  array<int, float>  $indeksPerBulan
     * @param  Collection<int, array{tahun: int, bulan: int, jumlah_pendaftar: int}>  $dataAktual
     * @return array{
     *     tahun_target: int,
     *     baris: list<array{
     *         tahun: int,
     *         bulan: int,
     *         nama_bulan: string,
     *         x: int,
     *         y_trend: float,
     *         im: float,
     *         y_mentah: float,
     *         y_akhir: int,
     *         dipaksa_nol: bool,
     *         y_aktual: int|null
     *     }>,
     *     total_y_akhir: int
     * }
     */
    public function hitungTahap4PrediksiAkhir(
        int $tahunTarget,
        int $tahunAwal,
        int $bulanAwal,
        float $a,
        float $b,
        array $indeksPerBulan,
        Collection $dataAktual
    ): array {
        $petaAktual = [];
        foreach ($dataAktual as $item) {
            if ((int) $item['tahun'] === $tahunTarget) {
                $petaAktual[(int) $item['bulan']] = (int) $item['jumlah_pendaftar'];
            }
        }

        $baris = [];
        $totalYAkhir = 0;

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $x = (($tahunTarget - $tahunAwal) * 12) + ($bulan - $bulanAwal);
            $yTrend = $a + ($b * $x);
            $im = $indeksPerBulan[$bulan] ?? 0.0;
            $yMentah = $yTrend * $im;
            $yBulat = (int) round($yMentah, 0, PHP_ROUND_HALF_UP);

            $dipaksaNol = $yMentah < 0 || $yBulat < 0;
            $yAkhir = max(0, $yBulat);

            $totalYAkhir += $yAkhir;

            $baris[] = [
                'tahun' => $tahunTarget,
                'bulan' => $bulan,
                'nama_bulan' => DataPendaftar::namaBulan($bulan),
                'x' => $x,
                'y_trend' => (float) $yTrend,
                'im' => (float) $im,
                'y_mentah' => (float) $yMentah,
                'y_akhir' => $yAkhir,
                'dipaksa_nol' => $dipaksaNol,
                'y_aktual' => $petaAktual[$bulan] ?? null,
            ];
        }

        return [
            'tahun_target' => $tahunTarget,
            'baris' => $baris,
            'total_y_akhir' => $totalYAkhir,
        ];
    }

    /**
     * Tahap 5: Terjemahkan hasil prediksi 12 bulan menjadi keputusan SPK
     * (Status Musim, Strategi Marketing, Kebutuhan Staf, Estimasi Kas).
     *
     * @param  list<array<string, mixed>>  $barisPrediksi
     * @param  array{
     *     target_minimum_pendaftar: int,
     *     batas_peak_season: int,
     *     kapasitas_layan_staf: int,
     *     estimasi_biaya_pendaftaran: int
     * }  $parameter
     * @return list<array{
     *     tahun: int,
     *     bulan: int,
     *     nama_bulan: string,
     *     x: int,
     *     y_trend: float,
     *     im: float,
     *     y_akhir: int,
     *     dipaksa_nol: bool,
     *     y_aktual: int|null,
     *     status: string,
     *     badge_class: string,
     *     kebutuhan_staf: int,
     *     rekomendasi_staf: string,
     *     estimasi_kas: int,
     *     rekomendasi_marketing: string
     * }>
     */
    public function hitungTahap5Keputusan(array $barisPrediksi, array $parameter): array
    {
        $targetMin = (int) $parameter['target_minimum_pendaftar'];
        $batasPeak = (int) $parameter['batas_peak_season'];
        $kapasitasStaf = max(1, (int) $parameter['kapasitas_layan_staf']);
        $biayaPendaftaran = (int) $parameter['estimasi_biaya_pendaftaran'];

        $hasil = [];

        foreach ($barisPrediksi as $baris) {
            $yAkhir = (int) $baris['y_akhir'];
            $evaluasi = $this->evaluasiBulan($yAkhir, $targetMin, $batasPeak, $kapasitasStaf, $biayaPendaftaran);

            $hasil[] = array_merge($baris, $evaluasi);
        }

        return $hasil;
    }

    /**
     * Evaluasi satu nilai Y_akhir terhadap parameter SPK.
     *
     * @return array{
     *     status: string,
     *     badge_class: string,
     *     kebutuhan_staf: int,
     *     rekomendasi_staf: string,
     *     estimasi_kas: int,
     *     rekomendasi_marketing: string
     * }
     */
    public function evaluasiBulan(
        int $yAkhir,
        int $targetMinimum,
        int $batasPeak,
        int $kapasitasLayanStaf,
        int $estimasiBiayaPendaftaran
    ): array {
        if ($yAkhir < $targetMinimum) {
            $status = 'Low Season';
            $badgeClass = 'badge-warning';
        } elseif ($yAkhir >= $batasPeak) {
            $status = 'Peak Season';
            $badgeClass = 'badge-info';
        } else {
            $status = 'Normal Season';
            $badgeClass = 'badge-success';
        }

        $kebutuhanStaf = $yAkhir > 0
            ? (int) ceil($yAkhir / max(1, $kapasitasLayanStaf))
            : 0;

        $estimasiKas = $yAkhir * $estimasiBiayaPendaftaran;
        $rekomendasiMarketing = (string) __('spk.rekomendasi_marketing.' . $status);
        $rekomendasiStaf = (string) __('spk.rekomendasi_staf', ['jumlah' => $kebutuhanStaf]);

        return [
            'status' => $status,
            'badge_class' => $badgeClass,
            'kebutuhan_staf' => $kebutuhanStaf,
            'rekomendasi_staf' => $rekomendasiStaf,
            'estimasi_kas' => $estimasiKas,
            'rekomendasi_marketing' => $rekomendasiMarketing,
        ];
    }

    /**
     * Buat ringkasan 4 stat card untuk Dashboard Eksekutif (FR-DB-02).
     *
     * @param  list<array<string, mixed>>  $keputusan
     * @return array{
     *     total_prediksi_pendaftar: int,
     *     bulan_puncak: string,
     *     nilai_bulan_puncak: int,
     *     total_estimasi_kas: int,
     *     kebutuhan_staf_tertinggi: int,
     *     bulan_staf_tertinggi: string
     * }
     */
    public function buatRingkasanDashboard(array $keputusan): array
    {
        $totalPrediksi = 0;
        $totalKas = 0;
        $nilaiPuncak = -1;
        $bulanPuncak = '-';
        $stafTertinggi = 0;
        $bulanStafTertinggi = '-';

        foreach ($keputusan as $baris) {
            $yAkhir = (int) $baris['y_akhir'];
            $kas = (int) $baris['estimasi_kas'];
            $staf = (int) $baris['kebutuhan_staf'];

            $totalPrediksi += $yAkhir;
            $totalKas += $kas;

            if ($yAkhir > $nilaiPuncak) {
                $nilaiPuncak = $yAkhir;
                $bulanPuncak = (string) $baris['nama_bulan'];
            }

            if ($staf > $stafTertinggi) {
                $stafTertinggi = $staf;
                $bulanStafTertinggi = (string) $baris['nama_bulan'];
            }
        }

        return [
            'total_prediksi_pendaftar' => $totalPrediksi,
            'bulan_puncak' => $bulanPuncak,
            'nilai_bulan_puncak' => max(0, $nilaiPuncak),
            'total_estimasi_kas' => $totalKas,
            'kebutuhan_staf_tertinggi' => $stafTertinggi,
            'bulan_staf_tertinggi' => $bulanStafTertinggi,
        ];
    }

    /**
     * Siapkan struktur data untuk grafik garis Chart.js di Dashboard (FR-DB-03).
     *
     * @param  Collection<int, array{tahun: int, bulan: int, jumlah_pendaftar: int}>  $dataAktual
     * @param  list<array<string, mixed>>  $keputusan
     * @param  array<string, int>  $parameter
     * @return array{
     *     labels: list<string>,
     *     aktual_tahun_terakhir: list<int|null>,
     *     label_aktual: string,
     *     prediksi: list<int>,
     *     label_prediksi: string,
     *     target_minimum_pendaftar: int,
     *     batas_peak_season: int
     * }
     */
    private function buatSeriGrafik(Collection $dataAktual, array $keputusan, array $parameter, int $tahunTarget): array
    {
        $labels = array_values(DataPendaftar::NAMA_BULAN);
        $prediksi = array_map(static fn (array $b): int => (int) $b['y_akhir'], $keputusan);

        // Gunakan tahun target jika sudah ada data aktual di tahun target, atau tahun terakhir data historis sebagai pembanding
        $tahunAktualPembanding = $dataAktual->contains(fn (array $d): bool => $d['tahun'] === $tahunTarget)
            ? $tahunTarget
            : (int) ($dataAktual->last()['tahun'] ?? ($tahunTarget - 1));

        $petaAktual = [];
        foreach ($dataAktual as $item) {
            if ((int) $item['tahun'] === $tahunAktualPembanding) {
                $petaAktual[(int) $item['bulan']] = (int) $item['jumlah_pendaftar'];
            }
        }

        $aktualSeries = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $aktualSeries[] = $petaAktual[$bulan] ?? null;
        }

        return [
            'labels' => $labels,
            'aktual_tahun_terakhir' => $aktualSeries,
            'label_aktual' => 'Aktual ' . $tahunAktualPembanding,
            'prediksi' => $prediksi,
            'label_prediksi' => 'Prediksi ' . $tahunTarget,
            'target_minimum_pendaftar' => (int) $parameter['target_minimum_pendaftar'],
            'batas_peak_season' => (int) $parameter['batas_peak_season'],
        ];
    }

    /**
     * Normalisasi dan urutkan selalu data masukan secara kronologis (tahun ASC, bulan ASC).
     *
     * @param  Collection<int, mixed>|array<int, mixed>|null  $dataMasukan
     * @return Collection<int, array{tahun: int, bulan: int, jumlah_pendaftar: int}>
     */
    private function siapkanDataKronologis(Collection|array|null $dataMasukan): Collection
    {
        if ($dataMasukan === null) {
            $koleksi = DataPendaftar::query()
                ->select(['tahun', 'bulan', 'jumlah_pendaftar'])
                ->urutKronologis()
                ->get();
        } else {
            $koleksi = collect($dataMasukan);
        }

        return $koleksi
            ->map(static function (mixed $item): array {
                if ($item instanceof DataPendaftar) {
                    return [
                        'tahun' => (int) $item->tahun,
                        'bulan' => (int) $item->bulan,
                        'jumlah_pendaftar' => (int) $item->jumlah_pendaftar,
                    ];
                }

                return [
                    'tahun' => (int) ($item['tahun'] ?? 0),
                    'bulan' => (int) ($item['bulan'] ?? 0),
                    'jumlah_pendaftar' => (int) ($item['jumlah_pendaftar'] ?? 0),
                ];
            })
            ->sortBy([
                ['tahun', 'asc'],
                ['bulan', 'asc'],
            ])
            ->values();
    }

    /**
     * Siapkan parameter SPK dengan fallback ke database / default.
     *
     * @param  array<string, int>|null  $parameterMasukan
     * @return array{
     *     target_minimum_pendaftar: int,
     *     batas_peak_season: int,
     *     kapasitas_layan_staf: int,
     *     estimasi_biaya_pendaftaran: int
     * }
     */
    private function siapkanParameter(?array $parameterMasukan): array
    {
        if ($parameterMasukan === null) {
            return PengaturanSpk::nilaiAktif();
        }

        $defaults = PengaturanSpk::DEFAULTS;

        return [
            'target_minimum_pendaftar' => isset($parameterMasukan['target_minimum_pendaftar'])
                ? (int) $parameterMasukan['target_minimum_pendaftar']
                : $defaults['target_minimum_pendaftar']['nilai'],
            'batas_peak_season' => isset($parameterMasukan['batas_peak_season'])
                ? (int) $parameterMasukan['batas_peak_season']
                : $defaults['batas_peak_season']['nilai'],
            'kapasitas_layan_staf' => isset($parameterMasukan['kapasitas_layan_staf'])
                ? (int) $parameterMasukan['kapasitas_layan_staf']
                : $defaults['kapasitas_layan_staf']['nilai'],
            'estimasi_biaya_pendaftaran' => isset($parameterMasukan['estimasi_biaya_pendaftaran'])
                ? (int) $parameterMasukan['estimasi_biaya_pendaftaran']
                : $defaults['estimasi_biaya_pendaftaran']['nilai'],
        ];
    }

    /**
     * Buat daftar pilihan tahun target yang relevan untuk dropdown pemilih tahun.
     *
     * @return list<int>
     */
    private function buatPilihanTahunTarget(?int $tahunAwal, ?int $tahunAkhir, int $tahunTargetAktif): array
    {
        if ($tahunAwal === null || $tahunAkhir === null) {
            return [$tahunTargetAktif];
        }

        $mulai = min($tahunAwal + 1, $tahunTargetAktif);
        $sampai = max($tahunAkhir + 5, $tahunTargetAktif);

        $pilihan = [];
        for ($t = $mulai; $t <= $sampai; $t++) {
            $pilihan[] = $t;
        }

        return $pilihan;
    }
}

