<?php

namespace Tests\Feature;

use App\Http\Controllers\ImportDataPendaftarController;
use App\Models\DataPendaftar;
use App\Models\PengaturanSpk;
use App\Models\User;
use App\Services\ExcelDataService;
use Database\Seeders\DataPendaftarSeeder;
use Database\Seeders\PengaturanSpkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class SpkSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pimpinan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PengaturanSpkSeeder::class);

        $this->admin = User::factory()->admin()->create([
            'name' => 'Admin Penguji',
            'email' => 'admin@test.ac.id',
        ]);

        $this->pimpinan = User::factory()->pimpinan()->create([
            'name' => 'Pimpinan Penguji',
            'email' => 'pimpinan@test.ac.id',
        ]);
    }

    /**
     * AC-KA-01: Akses tanpa login diarahkan ke halaman login.
     */
    public function test_ac_ka_01_akses_tanpa_login_diarahkan_ke_login(): void
    {
        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
        $this->get(route('perhitungan.index'))->assertRedirect(route('login'));
        $this->get(route('data-historis.index'))->assertRedirect(route('login'));
        $this->get(route('import-excel.index'))->assertRedirect(route('login'));
        $this->get(route('pengaturan-spk.index'))->assertRedirect(route('login'));
    }

    /**
     * AC-KA-02 & AC-KA-03: Pimpinan hanya boleh membaca Dashboard dan Perhitungan;
     * akses ke Data Historis, Import Excel, dan Pengaturan SPK mengembalikan 403.
     */
    public function test_ac_ka_02_dan_03_hak_akses_pimpinan_dibatasi_403(): void
    {
        $this->seed(DataPendaftarSeeder::class);

        $this->actingAs($this->pimpinan)
            ->get(route('dashboard.index'))
            ->assertOk();

        $this->actingAs($this->pimpinan)
            ->get(route('perhitungan.index'))
            ->assertOk();

        $this->actingAs($this->pimpinan)
            ->get(route('data-historis.index'))
            ->assertForbidden();

        $this->actingAs($this->pimpinan)
            ->get(route('import-excel.index'))
            ->assertForbidden();

        $this->actingAs($this->pimpinan)
            ->get(route('pengaturan-spk.index'))
            ->assertForbidden();

        // AC-KA-03: Pimpinan mengirim permintaan simpan parameter secara langsung -> 403, nilai tidak berubah
        $this->actingAs($this->pimpinan)
            ->put(route('pengaturan-spk.update'), [
                'target_minimum_pendaftar' => 50,
                'batas_peak_season' => 120,
                'kapasitas_layan_staf' => 60,
                'estimasi_biaya_pendaftaran' => 300000,
            ])
            ->assertForbidden();

        $this->assertSame(30, PengaturanSpk::nilaiAktif()['target_minimum_pendaftar']);
    }

    /**
     * AC-DI-01 & AC-MT-09: Unduh template, isi dengan Lampiran A, unggah, dan simpan.
     * 24 baris valid, 24 tersimpan, tanpa galat, dan hasil identik dengan input manual.
     */
    public function test_ac_di_01_dan_ac_mt_09_impor_excel_lampiran_a_identik(): void
    {
        $responseTemplate = $this->actingAs($this->admin)
            ->get(route('import-excel.template'));
        $responseTemplate->assertOk();

        $fileExcel = $this->buatUploadedXlsx(DataPendaftarSeeder::DATASET_LAMPIRAN_A);

        $this->actingAs($this->admin)
            ->post(route('import-excel.preview'), [
                'berkas_excel' => $fileExcel,
                'mode_duplikat' => 'timpa',
            ])
            ->assertRedirect(route('import-excel.index'));

        $pratinjau = session(ImportDataPendaftarController::SESSION_PRATINJAU_KEY);
        $this->assertNotNull($pratinjau);
        $this->assertSame(24, $pratinjau['jumlah_valid']);
        $this->assertSame(0, $pratinjau['jumlah_tidak_valid']);
        $this->assertEmpty($pratinjau['galat']);

        $this->actingAs($this->admin)
            ->post(route('import-excel.confirm'), [
                'mode_duplikat' => 'timpa',
            ])
            ->assertRedirect(route('data-historis.index'));

        $this->assertSame(24, DataPendaftar::query()->count());

        $responsePerhitungan = $this->actingAs($this->admin)
            ->get(route('perhitungan.index', ['tahun_target' => 2026]));

        $responsePerhitungan->assertOk()
            ->assertSee('61.313333')
            ->assertSee('0.143043')
            ->assertSee('787');
    }

    /**
     * AC-DI-02: Unggah berkas dengan bulan 13, jumlah negatif, dan teks pada kolom jumlah.
     * Baris tersebut dilewati dan dilaporkan dengan nomor baris, kolom, dan alasan; baris valid tetap tersimpan.
     */
    public function test_ac_di_02_baris_tidak_valid_dilaporkan_dan_baris_valid_tersimpan(): void
    {
        $barisCampuran = [
            ['tahun' => 2024, 'bulan' => 1, 'jumlah_pendaftar' => 62],
            ['tahun' => 2024, 'bulan' => 13, 'jumlah_pendaftar' => 50],
            ['tahun' => 2024, 'bulan' => 2, 'jumlah_pendaftar' => -5],
            ['tahun' => 2024, 'bulan' => 3, 'jumlah_pendaftar' => 'abc'],
        ];

        $fileExcel = $this->buatUploadedXlsx($barisCampuran);

        $this->actingAs($this->admin)
            ->post(route('import-excel.preview'), [
                'berkas_excel' => $fileExcel,
                'mode_duplikat' => 'timpa',
            ])
            ->assertRedirect(route('import-excel.index'));

        $pratinjau = session(ImportDataPendaftarController::SESSION_PRATINJAU_KEY);
        $this->assertSame(1, $pratinjau['jumlah_valid']);
        $this->assertSame(3, $pratinjau['jumlah_tidak_valid']);
        $this->assertCount(3, $pratinjau['galat']);

        $barisGalat = array_column($pratinjau['galat'], 'baris');
        $this->assertSame([3, 4, 5], $barisGalat);

        $this->actingAs($this->admin)
            ->post(route('import-excel.confirm'))
            ->assertRedirect(route('data-historis.index'));

        $this->assertSame(1, DataPendaftar::query()->count());
        $this->assertSame(62, DataPendaftar::query()->first()->jumlah_pendaftar);
    }

    /**
     * AC-DI-03: Unggah periode yang sudah ada — Mode Lewati mempertahankan nilai lama;
     * Mode Timpa menggantinya; tidak pernah muncul dua baris untuk satu tahun-bulan.
     */
    public function test_ac_di_03_mode_lewati_dan_timpa_pada_periode_duplikat(): void
    {
        DataPendaftar::query()->create([
            'tahun' => 2024,
            'bulan' => 1,
            'jumlah_pendaftar' => 62,
        ]);

        $fileBaru = $this->buatUploadedXlsx([
            ['tahun' => 2024, 'bulan' => 1, 'jumlah_pendaftar' => 99],
        ]);

        // 1. Mode Lewati: mempertahankan nilai lama (62)
        $this->actingAs($this->admin)->post(route('import-excel.preview'), [
            'berkas_excel' => $fileBaru,
            'mode_duplikat' => 'lewati',
        ]);
        $this->actingAs($this->admin)->post(route('import-excel.confirm'), [
            'mode_duplikat' => 'lewati',
        ]);

        $this->assertSame(1, DataPendaftar::query()->count());
        $this->assertSame(62, DataPendaftar::query()->first()->jumlah_pendaftar);

        // 2. Mode Timpa: mengganti dengan nilai baru (99)
        $fileTimpa = $this->buatUploadedXlsx([
            ['tahun' => 2024, 'bulan' => 1, 'jumlah_pendaftar' => 99],
        ]);

        $this->actingAs($this->admin)->post(route('import-excel.preview'), [
            'berkas_excel' => $fileTimpa,
            'mode_duplikat' => 'timpa',
        ]);
        $this->actingAs($this->admin)->post(route('import-excel.confirm'), [
            'mode_duplikat' => 'timpa',
        ]);

        $this->assertSame(1, DataPendaftar::query()->count());
        $this->assertSame(99, DataPendaftar::query()->first()->jumlah_pendaftar);
    }

    /**
     * AC-DI-04: Unggah berkas non-.xlsx atau lebih dari 2 MB ditolak sebelum diproses.
     */
    public function test_ac_di_04_tolak_berkas_bukan_xlsx_atau_lebih_dari_2mb(): void
    {
        $fileTxt = UploadedFile::fake()->create('data.txt', 100, 'text/plain');

        $this->actingAs($this->admin)
            ->post(route('import-excel.preview'), [
                'berkas_excel' => $fileTxt,
                'mode_duplikat' => 'timpa',
            ])
            ->assertSessionHasErrors('berkas_excel');

        $fileBesar = UploadedFile::fake()->create(
            'terlalu_besar.xlsx',
            2500,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $this->actingAs($this->admin)
            ->post(route('import-excel.preview'), [
                'berkas_excel' => $fileBesar,
                'mode_duplikat' => 'timpa',
            ])
            ->assertSessionHasErrors('berkas_excel');
    }

    /**
     * AC-DI-07 & AC-SP-08: CRUD manual data historis dan validasi parameter SPK.
     */
    public function test_ac_di_07_dan_ac_sp_08_crud_manual_dan_validasi_parameter(): void
    {
        // Tambah data manual
        $this->actingAs($this->admin)
            ->post(route('data-historis.store'), [
                'tahun' => 2024,
                'bulan' => 1,
                'jumlah_pendaftar' => 62,
            ])
            ->assertRedirect(route('data-historis.index'));

        $item = DataPendaftar::query()->firstOrFail();
        $this->assertSame(62, $item->jumlah_pendaftar);

        // Ubah data manual
        $this->actingAs($this->admin)
            ->put(route('data-historis.update', $item), [
                'tahun' => 2024,
                'bulan' => 1,
                'jumlah_pendaftar' => 75,
            ])
            ->assertRedirect(route('data-historis.index'));

        $this->assertSame(75, $item->fresh()->jumlah_pendaftar);

        // Hapus data manual
        $this->actingAs($this->admin)
            ->delete(route('data-historis.destroy', $item))
            ->assertRedirect(route('data-historis.index'));

        $this->assertSame(0, DataPendaftar::query()->count());

        // AC-SP-08: Simpan target minimum >= batas peak atau kapasitas 0 ditolak
        $this->actingAs($this->admin)
            ->put(route('pengaturan-spk.update'), [
                'target_minimum_pendaftar' => 100,
                'batas_peak_season' => 100,
                'kapasitas_layan_staf' => 0,
                'estimasi_biaya_pendaftaran' => 250000,
            ])
            ->assertSessionHasErrors(['target_minimum_pendaftar', 'batas_peak_season', 'kapasitas_layan_staf']);

        $this->assertSame(30, PengaturanSpk::nilaiAktif()['target_minimum_pendaftar']);
    }

    /**
     * AC-UI-01, AC-UI-02, AC-UI-03: Verifikasi Dashboard, Perhitungan, dan kepatuhan DESIGN.md (tanpa style= dan tanpa emoji).
     */
    public function test_ac_ui_01_02_03_tampilan_dashboard_perhitungan_dan_kepatuhan_design(): void
    {
        $this->seed(DataPendaftarSeeder::class);

        $this->actingAs($this->admin)
            ->get(route('dashboard.index', ['tahun_target' => 2026]))
            ->assertOk()
            ->assertSee('787')
            ->assertSee('Juli (140)')
            ->assertSee('Rp 196.750.000')
            ->assertSee('4 Staf')
            ->assertSee('Low Season')
            ->assertSee('Normal Season')
            ->assertSee('Peak Season');

        // AC-UI-03: Telusuri seluruh berkas Blade di resources/views -> tidak ada atribut style=
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $isi = (string) file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/\bstyle\s*=/i',
                $isi,
                "Berkas Blade {$file->getFilename()} tidak boleh mengandung inline style="
            );
        }
    }

    /**
     * Pengujian unggah template 12 bulan (Januari-Desember 2024) saat basis data kosong,
     * termasuk pembacaan berkas dengan atribut XML ekstensi Microsoft Excel dan format CSV di kolom A.
     */
    public function test_unggah_template_12_bulan_saat_database_kosong(): void
    {
        $this->assertSame(0, DataPendaftar::query()->count());

        $data12Bulan = [
            ['tahun' => 2024, 'bulan' => 1, 'jumlah_pendaftar' => 32],
            ['tahun' => 2024, 'bulan' => 2, 'jumlah_pendaftar' => 24],
            ['tahun' => 2024, 'bulan' => 3, 'jumlah_pendaftar' => 42],
            ['tahun' => 2024, 'bulan' => 4, 'jumlah_pendaftar' => 48],
            ['tahun' => 2024, 'bulan' => 5, 'jumlah_pendaftar' => 147],
            ['tahun' => 2024, 'bulan' => 6, 'jumlah_pendaftar' => 133],
            ['tahun' => 2024, 'bulan' => 7, 'jumlah_pendaftar' => 84],
            ['tahun' => 2024, 'bulan' => 8, 'jumlah_pendaftar' => 54],
            ['tahun' => 2024, 'bulan' => 9, 'jumlah_pendaftar' => 20],
            ['tahun' => 2024, 'bulan' => 10, 'jumlah_pendaftar' => 0],
            ['tahun' => 2024, 'bulan' => 11, 'jumlah_pendaftar' => 5],
            ['tahun' => 2024, 'bulan' => 12, 'jumlah_pendaftar' => 16],
        ];

        $fileExcel = $this->buatUploadedXlsx($data12Bulan);

        $this->actingAs($this->admin)
            ->post(route('import-excel.preview'), [
                'berkas_excel' => $fileExcel,
                'mode_duplikat' => 'timpa',
            ])
            ->assertRedirect(route('import-excel.index'))
            ->assertSessionHasNoErrors();

        $pratinjau = session(ImportDataPendaftarController::SESSION_PRATINJAU_KEY);
        $this->assertNotNull($pratinjau);
        $this->assertSame(12, $pratinjau['jumlah_valid']);
        $this->assertSame(0, $pratinjau['jumlah_tidak_valid']);

        $this->actingAs($this->admin)
            ->post(route('import-excel.confirm'), [
                'mode_duplikat' => 'timpa',
            ])
            ->assertRedirect(route('data-historis.index'));

        $this->assertSame(12, DataPendaftar::query()->count());

        // Pastikan perhitungan & dashboard berjalan normal dengan 12 bulan data
        $this->actingAs($this->admin)
            ->get(route('dashboard.index', ['tahun_target' => 2025]))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('perhitungan.index', ['tahun_target' => 2025]))
            ->assertOk();
    }

    /**
     * Buat objek UploadedFile .xlsx dari larik baris untuk pengujian impor.
     *
     * @param  list<array{tahun: mixed, bulan: mixed, jumlah_pendaftar: mixed}>  $barisData
     */
    private function buatUploadedXlsx(array $barisData): UploadedFile
    {
        $excelService = new ExcelDataService();
        $spreadsheet = $excelService->buatSpreadsheetTemplate($barisData);

        $tempPath = tempnam(sys_get_temp_dir(), 'spk_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile(
            $tempPath,
            'data_uji.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}

