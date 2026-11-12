<?php

namespace Tests\Unit;

use App\Services\ForecastService;
use Database\Seeders\DataPendaftarSeeder;
use Tests\TestCase;

class ForecastServiceTest extends TestCase
{
    private ForecastService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ForecastService();
    }

    /**
     * AC-MT-01: Muat dataset Lampiran A (24 bulan, Jan 2024 sampai Des 2025).
     */
    public function test_ac_mt_01_besaran_tahap_1_sesuai_lampiran_a(): void
    {
        $hasil = $this->service->hitung(2026, DataPendaftarSeeder::DATASET_LAMPIRAN_A, [
            'target_minimum_pendaftar' => 30,
            'batas_peak_season' => 100,
            'kapasitas_layan_staf' => 40,
            'estimasi_biaya_pendaftaran' => 250000,
        ]);

        $this->assertTrue($hasil['layak_hitung']);
        $total = $hasil['tahap1']['total'];

        $this->assertSame(24, $total['n']);
        $this->assertSame(276, $total['sigma_x']);
        $this->assertSame(1511, $total['sigma_y']);
        $this->assertSame(17541, $total['sigma_xy']);
        $this->assertSame(4324, $total['sigma_x2']);
    }

    /**
     * AC-MT-02 & AC-MT-06: Hitung koefisien tren dan substitusi persamaan (1).
     */
    public function test_ac_mt_02_dan_06_koefisien_tren_dan_substitusi_persamaan(): void
    {
        $hasil = $this->service->hitung(2026, DataPendaftarSeeder::DATASET_LAMPIRAN_A, []);
        $tahap2 = $hasil['tahap2'];

        $this->assertEqualsWithDelta(61.313333, $tahap2['a'], 1e-6);
        $this->assertEqualsWithDelta(0.143043, $tahap2['b'], 1e-6);

        // AC-MT-06: Substitusi a dan b ke persamaan (1): n*a + b*ΣX == ΣY (1511)
        $substitusi = ($tahap2['n'] * $tahap2['a']) + ($tahap2['b'] * $tahap2['sigma_x']);
        $this->assertEqualsWithDelta(1511.0, $substitusi, 1e-6);
    }

    /**
     * AC-MT-03 & AC-MT-04: Rata-rata total Ȳ dan Indeks Musiman 12 bulan.
     */
    public function test_ac_mt_03_dan_04_rata_rata_total_dan_indeks_musiman(): void
    {
        $hasil = $this->service->hitung(2026, DataPendaftarSeeder::DATASET_LAMPIRAN_A, []);
        $tahap3 = $hasil['tahap3'];

        $this->assertEqualsWithDelta(62.958333, $tahap3['y_bar'], 1e-6);

        $imHarapan = [
            1 => 1.048312,
            2 => 0.818001,
            3 => 0.675050,
            4 => 0.603574,
            5 => 0.984778,
            6 => 1.612177,
            7 => 2.128392,
            8 => 1.842488,
            9 => 0.889477,
            10 => 0.484447,
            11 => 0.389146,
            12 => 0.524156,
        ];

        foreach ($imHarapan as $bulan => $nilaiHarapan) {
            $this->assertEqualsWithDelta(
                $nilaiHarapan,
                $tahap3['indeks_per_bulan'][$bulan],
                1e-6,
                "IM bulan ke-{$bulan} tidak cocok"
            );
        }
    }

    /**
     * AC-MT-05: Hitung prediksi tahun 2026 (Y akhir Jan sampai Des dan total 787).
     */
    public function test_ac_mt_05_prediksi_tahun_2026_sesuai_lampiran_a(): void
    {
        $hasil = $this->service->hitung(2026, DataPendaftarSeeder::DATASET_LAMPIRAN_A, []);
        $tahap4 = $hasil['tahap4'];

        $yAkhirHarapan = [68, 53, 44, 39, 64, 106, 140, 121, 59, 32, 26, 35];
        $yAkhirAktual = array_map(static fn (array $b): int => $b['y_akhir'], $tahap4['baris']);

        $this->assertSame($yAkhirHarapan, $yAkhirAktual);
        $this->assertSame(787, $tahap4['total_y_akhir']);
    }

    /**
     * AC-MT-07 & AC-MT-08: Urutan acak memberikan hasil yang sama persis.
     */
    public function test_ac_mt_08_urutan_acak_menghasilkan_nilai_identik(): void
    {
        $dataAcak = DataPendaftarSeeder::DATASET_LAMPIRAN_A;
        shuffle($dataAcak);

        $hasilNormal = $this->service->hitung(2026, DataPendaftarSeeder::DATASET_LAMPIRAN_A, []);
        $hasilAcak = $this->service->hitung(2026, $dataAcak, []);

        $this->assertSame($hasilNormal['tahap1']['total'], $hasilAcak['tahap1']['total']);
        $this->assertSame($hasilNormal['tahap4']['total_y_akhir'], $hasilAcak['tahap4']['total_y_akhir']);
    }

    /**
     * AC-MT-10: Dataset menurun linear 12 bulan dengan Y = 110, 100, 90, ..., 0.
     * Y tren bulan target negatif; seluruh Y akhir bernilai 0 dan tiap baris diberi penanda.
     */
    public function test_ac_mt_10_tren_menurun_tajam_dipaksa_nol_dengan_penanda(): void
    {
        $dataMenurun = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $dataMenurun[] = [
                'tahun' => 2025,
                'bulan' => $bulan,
                'jumlah_pendaftar' => 110 - (($bulan - 1) * 10),
            ];
        }

        $hasil = $this->service->hitung(2026, $dataMenurun, []);

        $this->assertTrue($hasil['layak_hitung']);

        foreach ($hasil['tahap4']['baris'] as $baris) {
            $this->assertLessThan(0, $baris['y_trend']);
            $this->assertSame(0, $baris['y_akhir']);
            if ($baris['im'] > 0) {
                $this->assertTrue($baris['dipaksa_nol']);
            }
        }
    }

    /**
     * AC-SP-01, AC-SP-02, AC-SP-03, AC-SP-04, AC-SP-05, AC-SP-06, AC-SP-07, AC-SP-09:
     * Pengujian aturan keputusan SPK.
     */
    public function test_ac_sp_logika_keputusan_dan_parameter_dinamis(): void
    {
        // AC-SP-01 & AC-SP-02: batas Low, Normal, Peak
        $eval29 = $this->service->evaluasiBulan(29, 30, 100, 40, 250000);
        $eval30 = $this->service->evaluasiBulan(30, 30, 100, 40, 250000);
        $eval99 = $this->service->evaluasiBulan(99, 30, 100, 40, 250000);
        $eval100 = $this->service->evaluasiBulan(100, 30, 100, 40, 250000);

        $this->assertSame('Low Season', $eval29['status']);
        $this->assertSame('Normal Season', $eval30['status']);
        $this->assertSame('Normal Season', $eval99['status']);
        $this->assertSame('Peak Season', $eval100['status']);

        // AC-SP-03: Kebutuhan staf untuk Y akhir = 0, 40, 41, 106, 140 -> 0, 1, 2, 3, 4
        $this->assertSame(0, $this->service->evaluasiBulan(0, 30, 100, 40, 250000)['kebutuhan_staf']);
        $this->assertSame(1, $this->service->evaluasiBulan(40, 30, 100, 40, 250000)['kebutuhan_staf']);
        $this->assertSame(2, $this->service->evaluasiBulan(41, 30, 100, 40, 250000)['kebutuhan_staf']);
        $this->assertSame(3, $this->service->evaluasiBulan(106, 30, 100, 40, 250000)['kebutuhan_staf']);
        $this->assertSame(4, $this->service->evaluasiBulan(140, 30, 100, 40, 250000)['kebutuhan_staf']);

        // AC-SP-04 & AC-SP-05: Dataset Lampiran A dengan parameter default
        $hasil = $this->service->hitung(2026, DataPendaftarSeeder::DATASET_LAMPIRAN_A, [
            'target_minimum_pendaftar' => 30,
            'batas_peak_season' => 100,
            'kapasitas_layan_staf' => 40,
            'estimasi_biaya_pendaftaran' => 250000,
        ]);

        $keputusan = collect($hasil['keputusan'])->keyBy('bulan');
        $this->assertSame(17000000, $keputusan[1]['estimasi_kas']);
        $this->assertSame(35000000, $keputusan[7]['estimasi_kas']);
        $this->assertSame(196750000, $hasil['ringkasan']['total_estimasi_kas']);

        // Peak pada Juni (6), Juli (7), Agustus (8); Low hanya November (11)
        foreach ($keputusan as $bulan => $item) {
            if (in_array($bulan, [6, 7, 8], true)) {
                $this->assertSame('Peak Season', $item['status']);
            } elseif ($bulan === 11) {
                $this->assertSame('Low Season', $item['status']);
            } else {
                $this->assertSame('Normal Season', $item['status']);
            }
        }

        // AC-SP-06: Ubah target_minimum_pendaftar menjadi 40 -> Low pada April (4), Oktober (10), November (11), Desember (12)
        $hasilTarget40 = $this->service->hitung(2026, DataPendaftarSeeder::DATASET_LAMPIRAN_A, [
            'target_minimum_pendaftar' => 40,
            'batas_peak_season' => 100,
            'kapasitas_layan_staf' => 40,
            'estimasi_biaya_pendaftaran' => 250000,
        ]);
        $bulanLow = collect($hasilTarget40['keputusan'])
            ->where('status', 'Low Season')
            ->pluck('bulan')
            ->values()
            ->all();
        $this->assertSame([4, 10, 11, 12], $bulanLow);

        // AC-SP-07: Ubah kapasitas_layan_staf menjadi 50 -> Staf Juli = 3
        $hasilStaf50 = $this->service->hitung(2026, DataPendaftarSeeder::DATASET_LAMPIRAN_A, [
            'target_minimum_pendaftar' => 30,
            'batas_peak_season' => 100,
            'kapasitas_layan_staf' => 50,
            'estimasi_biaya_pendaftaran' => 250000,
        ]);
        $stafJuli = collect($hasilStaf50['keputusan'])->firstWhere('bulan', 7)['kebutuhan_staf'];
        $this->assertSame(3, $stafJuli);
    }

    /**
     * AC-DI-05 & AC-DI-06: Deteksi bulan hilang dan jumlah bulan < 12 atau 12..23.
     */
    public function test_ac_di_05_dan_06_deteksi_bulan_hilang_dan_jumlah_bulan(): void
    {
        // Hapus Maret 2025 (bulan 3 tahun 2025)
        $dataTanpaMaret = array_values(array_filter(
            DataPendaftarSeeder::DATASET_LAMPIRAN_A,
            static fn (array $b): bool => ! ($b['tahun'] === 2025 && $b['bulan'] === 3)
        ));

        $hasilBolong = $this->service->hitung(2026, $dataTanpaMaret, []);
        $this->assertFalse($hasilBolong['layak_hitung']);
        $this->assertContains('Maret 2025', $hasilBolong['kesiapan']['bulan_hilang']);
        $this->assertStringContainsString('Maret 2025', (string) $hasilBolong['kesiapan']['alasan_tidak_layak']);

        // Kurang dari 12 bulan (10 bulan)
        $data10Bulan = array_slice(DataPendaftarSeeder::DATASET_LAMPIRAN_A, 0, 10);
        $hasil10Bulan = $this->service->hitung(2026, $data10Bulan, []);
        $this->assertFalse($hasil10Bulan['layak_hitung']);

        // 15 bulan (antara 12 sampai 23): dihitung dengan peringatan
        $data15Bulan = array_slice(DataPendaftarSeeder::DATASET_LAMPIRAN_A, 0, 15);
        $hasil15Bulan = $this->service->hitung(2026, $data15Bulan, []);
        $this->assertTrue($hasil15Bulan['layak_hitung']);
        $this->assertTrue($hasil15Bulan['kesiapan']['peringatan_24_bulan']);
    }

    /**
     * Uji Performa (PRD 1.5 & 3.5): Waktu kalkulasi 120 bulan data di bawah 5 detik.
     */
    public function test_performa_kalkulasi_120_bulan_di_bawah_5_detik(): void
    {
        $data120Bulan = [];
        for ($i = 0; $i < 120; $i++) {
            $tahun = 2016 + intdiv($i, 12);
            $bulan = ($i % 12) + 1;
            $data120Bulan[] = [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'jumlah_pendaftar' => 50 + (($i * 7) % 90),
            ];
        }

        $mulai = microtime(true);
        $hasil = $this->service->hitung(2026, $data120Bulan, []);
        $durasiDetik = microtime(true) - $mulai;

        $this->assertTrue($hasil['layak_hitung']);
        $this->assertSame(120, $hasil['tahap1']['total']['n']);
        $this->assertLessThan(5.0, $durasiDetik);
    }
}
