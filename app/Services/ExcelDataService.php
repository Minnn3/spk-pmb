<?php

namespace App\Services;

use App\Models\DataPendaftar;
use App\Models\RiwayatImpor;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

class ExcelDataService
{
    /**
     * Unduh template Excel (.xlsx) dengan kolom Tahun, Bulan, Jumlah Pendaftar
     * dan dua baris contoh (FR-DH-03).
     */
    public function unduhTemplate(): StreamedResponse
    {
        $spreadsheet = $this->buatSpreadsheetTemplate();

        return $this->streamSpreadsheet($spreadsheet, 'template_data_pendaftar_pmb.xlsx');
    }

    /**
     * Buat objek Spreadsheet untuk template impor (dapat dipakai juga pada pengujian).
     *
     * @param  list<array{tahun: mixed, bulan: mixed, jumlah_pendaftar: mixed}>|null  $barisData
     */
    public function buatSpreadsheetTemplate(?array $barisData = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pendaftar');

        $sheet->setCellValue('A1', 'Tahun');
        $sheet->setCellValue('B1', 'Bulan');
        $sheet->setCellValue('C1', 'Jumlah Pendaftar');

        $contoh = $barisData ?? [
            ['tahun' => 2024, 'bulan' => 1, 'jumlah_pendaftar' => 62],
            ['tahun' => 2024, 'bulan' => 2, 'jumlah_pendaftar' => 48],
        ];

        $nomorBaris = 2;
        foreach ($contoh as $item) {
            $sheet->setCellValue("A{$nomorBaris}", $item['tahun']);
            $sheet->setCellValue("B{$nomorBaris}", $item['bulan']);
            $sheet->setCellValue("C{$nomorBaris}", $item['jumlah_pendaftar']);
            $nomorBaris++;
        }

        foreach (['A', 'B', 'C'] as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Ekspor seluruh data historis pendaftar ke berkas .xlsx (FR-DH-10).
     */
    public function eksporDataHistoris(): StreamedResponse
    {
        $data = DataPendaftar::query()->urutKronologis()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Historis PMB');

        $sheet->setCellValue('A1', 'Tahun');
        $sheet->setCellValue('B1', 'Bulan');
        $sheet->setCellValue('C1', 'Nama Bulan');
        $sheet->setCellValue('D1', 'Jumlah Pendaftar');

        $baris = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$baris}", $item->tahun);
            $sheet->setCellValue("B{$baris}", $item->bulan);
            $sheet->setCellValue("C{$baris}", $item->nama_bulan);
            $sheet->setCellValue("D{$baris}", $item->jumlah_pendaftar);
            $baris++;
        }

        foreach (['A', 'B', 'C', 'D'] as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        return $this->streamSpreadsheet($spreadsheet, 'data_historis_pendaftar_pmb.xlsx');
    }

    /**
     * Ekspor tabel rekomendasi keputusan 12 bulan ke berkas .xlsx (FR-DB-06).
     *
     * @param  list<array<string, mixed>>  $keputusan
     * @param  array<string, mixed>  $ringkasan
     */
    public function eksporRekomendasi(int $tahunTarget, array $keputusan, array $ringkasan): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Rekomendasi {$tahunTarget}");

        $sheet->setCellValue('A1', 'Bulan');
        $sheet->setCellValue('B1', 'Prediksi Pendaftar (Y Akhir)');
        $sheet->setCellValue('C1', 'Status Musim');
        $sheet->setCellValue('D1', 'Kebutuhan Staf');
        $sheet->setCellValue('E1', 'Estimasi Kas (Rp)');
        $sheet->setCellValue('F1', 'Rekomendasi Strategi Marketing');

        $baris = 2;
        foreach ($keputusan as $item) {
            $sheet->setCellValue("A{$baris}", $item['nama_bulan'] . ' ' . $item['tahun']);
            $sheet->setCellValue("B{$baris}", $item['y_akhir']);
            $sheet->setCellValue("C{$baris}", $item['status']);
            $sheet->setCellValue("D{$baris}", $item['kebutuhan_staf']);
            $sheet->setCellValue("E{$baris}", $item['estimasi_kas']);
            $sheet->setCellValue("F{$baris}", $item['rekomendasi_marketing']);
            $baris++;
        }

        $sheet->setCellValue("A{$baris}", 'Total');
        $sheet->setCellValue("B{$baris}", $ringkasan['total_prediksi_pendaftar'] ?? 0);
        $sheet->setCellValue("E{$baris}", $ringkasan['total_estimasi_kas'] ?? 0);

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        return $this->streamSpreadsheet($spreadsheet, "rekomendasi_spk_pmb_{$tahunTarget}.xlsx");
    }

    /**
     * Baca dan validasi tiap baris berkas .xlsx untuk tahap pratinjau (FR-DH-04, FR-DH-05, FR-DH-06).
     *
     * @return array{
     *     nama_file: string,
     *     mode: string,
     *     total_baris: int,
     *     jumlah_valid: int,
     *     jumlah_menimpa: int,
     *     jumlah_tidak_valid: int,
     *     baris_valid: list<array{
     *         baris: int,
     *         tahun: int,
     *         bulan: int,
     *         nama_bulan: string,
     *         jumlah_pendaftar: int,
     *         sudah_ada: bool,
     *         nilai_lama: int|null
     *     }>,
     *     galat: list<array{baris: int, kolom: string, alasan: string}>
     * }
     */
    public function analisisBerkas(string $filePath, string $namaFileAsli, string $mode = 'timpa'): array
    {
        $daftarBarisMentah = $this->bacaBarisXlsx($filePath);

        // Ambil data periode yang sudah ada di database untuk penanda menimpa/lewati
        $dataLama = DataPendaftar::query()
            ->select(['tahun', 'bulan', 'jumlah_pendaftar'])
            ->get()
            ->keyBy(static fn (DataPendaftar $d): string => sprintf('%04d-%02d', $d->tahun, $d->bulan));

        $barisValid = [];
        $galat = [];
        $periodeDiBerkas = [];
        $totalBarisData = 0;
        $jumlahMenimpa = 0;

        foreach ($daftarBarisMentah as $itemBaris) {
            $nomorBaris = $itemBaris['baris'];
            $mentahTahun = $itemBaris['a'];
            $mentahBulan = $itemBaris['b'];
            $mentahJumlah = $itemBaris['c'];

            // Lewati baris yang benar-benar kosong seluruh kolomnya
            if ($this->apakahBarisKosong($mentahTahun, $mentahBulan, $mentahJumlah)) {
                continue;
            }

            $totalBarisData++;
            $galatBarisIni = [];

            // 1. Validasi Tahun (4 digit bilangan bulat)
            $tahunValid = $this->validasiBilanganBulat($mentahTahun);
            if ($tahunValid === null || $tahunValid < 1900 || $tahunValid > 2100) {
                $galatBarisIni[] = [
                    'baris' => $nomorBaris,
                    'kolom' => 'Tahun',
                    'alasan' => 'Tahun wajib berupa angka 4 digit yang valid (contoh: 2025).',
                ];
            }

            // 2. Validasi Bulan (bilangan bulat 1 sampai 12)
            $bulanValid = $this->validasiBilanganBulat($mentahBulan);
            if ($bulanValid === null || $bulanValid < 1 || $bulanValid > 12) {
                $galatBarisIni[] = [
                    'baris' => $nomorBaris,
                    'kolom' => 'Bulan',
                    'alasan' => 'Bulan wajib berupa angka bulat antara 1 sampai 12.',
                ];
            }

            // 3. Validasi Jumlah Pendaftar (bilangan bulat >= 0)
            $jumlahValid = $this->validasiBilanganBulat($mentahJumlah);
            if ($jumlahValid === null) {
                $galatBarisIni[] = [
                    'baris' => $nomorBaris,
                    'kolom' => 'Jumlah Pendaftar',
                    'alasan' => 'Jumlah Pendaftar wajib berupa angka bulat (bukan teks atau kosong).',
                ];
            } elseif ($jumlahValid < 0) {
                $galatBarisIni[] = [
                    'baris' => $nomorBaris,
                    'kolom' => 'Jumlah Pendaftar',
                    'alasan' => 'Jumlah Pendaftar tidak boleh bernilai negatif (minimal 0).',
                ];
            }

            if ($galatBarisIni !== []) {
                foreach ($galatBarisIni as $itemGalat) {
                    $galat[] = $itemGalat;
                }

                continue;
            }

            $kunciPeriode = sprintf('%04d-%02d', $tahunValid, $bulanValid);

            // Cek duplikat di dalam berkas Excel yang sama
            if (isset($periodeDiBerkas[$kunciPeriode])) {
                $galat[] = [
                    'baris' => $nomorBaris,
                    'kolom' => 'Tahun & Bulan',
                    'alasan' => "Kombinasi periode {$kunciPeriode} duplikat dengan baris ke-{$periodeDiBerkas[$kunciPeriode]} di berkas ini.",
                ];

                continue;
            }

            $periodeDiBerkas[$kunciPeriode] = $nomorBaris;

            /** @var DataPendaftar|null $existing */
            $existing = $dataLama->get($kunciPeriode);
            $sudahAda = $existing !== null;

            if ($sudahAda) {
                $jumlahMenimpa++;
            }

            $barisValid[] = [
                'baris' => $nomorBaris,
                'tahun' => (int) $tahunValid,
                'bulan' => (int) $bulanValid,
                'nama_bulan' => DataPendaftar::namaBulan((int) $bulanValid),
                'jumlah_pendaftar' => (int) $jumlahValid,
                'sudah_ada' => $sudahAda,
                'nilai_lama' => $existing?->jumlah_pendaftar,
            ];
        }

        $jumlahValid = count($barisValid);
        $jumlahTidakValid = $totalBarisData - $jumlahValid;

        return [
            'nama_file' => $namaFileAsli,
            'mode' => in_array($mode, ['timpa', 'lewati'], true) ? $mode : 'timpa',
            'total_baris' => $totalBarisData,
            'jumlah_valid' => $jumlahValid,
            'jumlah_menimpa' => $jumlahMenimpa,
            'jumlah_tidak_valid' => $jumlahTidakValid,
            'baris_valid' => $barisValid,
            'galat' => $galat,
        ];
    }

    /**
     * Simpan baris valid dari pratinjau ke database dan catat riwayat impor (FR-DH-05, FR-DH-06, FR-DH-09).
     *
     * @param  array<string, mixed>  $pratinjau
     * @return array{disimpan: int, dilewati_duplikat: int, gagal: int, riwayat: RiwayatImpor}
     */
    public function simpanPratinjau(array $pratinjau, int $userId): array
    {
        $mode = (string) ($pratinjau['mode'] ?? 'timpa');
        /** @var list<array<string, mixed>> $barisValid */
        $barisValid = $pratinjau['baris_valid'] ?? [];
        /** @var list<array<string, mixed>> $galat */
        $galat = $pratinjau['galat'] ?? [];

        $disimpan = 0;
        $dilewatiDuplikat = 0;

        DB::transaction(function () use ($barisValid, $mode, &$disimpan, &$dilewatiDuplikat): void {
            foreach ($barisValid as $item) {
                $tahun = (int) $item['tahun'];
                $bulan = (int) $item['bulan'];
                $jumlah = (int) $item['jumlah_pendaftar'];

                $existing = DataPendaftar::query()
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->first();

                if ($existing !== null) {
                    if ($mode === 'lewati') {
                        $dilewatiDuplikat++;

                        continue;
                    }

                    $existing->update([
                        'jumlah_pendaftar' => $jumlah,
                    ]);
                    $disimpan++;
                } else {
                    DataPendaftar::query()->create([
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                        'jumlah_pendaftar' => $jumlah,
                    ]);
                    $disimpan++;
                }
            }
        });

        $barisGagal = (int) ($pratinjau['jumlah_tidak_valid'] ?? count($galat));

        $riwayat = RiwayatImpor::query()->create([
            'user_id' => $userId,
            'nama_file' => (string) ($pratinjau['nama_file'] ?? 'impor.xlsx'),
            'total_baris' => (int) ($pratinjau['total_baris'] ?? ($disimpan + $dilewatiDuplikat + $barisGagal)),
            'baris_berhasil' => $disimpan,
            'baris_gagal' => $barisGagal,
            'detail_galat' => $galat,
        ]);

        return [
            'disimpan' => $disimpan,
            'dilewati_duplikat' => $dilewatiDuplikat,
            'gagal' => $barisGagal,
            'riwayat' => $riwayat,
        ];
    }

    /**
     * Ekstrak baris data (mulai baris ke-2) dari berkas .xlsx.
     * Menggunakan PhpSpreadsheet dengan fallback pembacaan ZIP/XML langsung
     * untuk kompatibilitas penuh pada environment Windows terisolasi.
     *
     * @return list<array{baris: int, a: mixed, b: mixed, c: mixed}>
     */
    private function bacaBarisXlsx(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $barisTertinggi = $sheet->getHighestDataRow();

            $hasil = [];
            for ($nomorBaris = 2; $nomorBaris <= $barisTertinggi; $nomorBaris++) {
                $hasil[] = [
                    'baris' => $nomorBaris,
                    'a' => $sheet->getCell("A{$nomorBaris}")->getValue(),
                    'b' => $sheet->getCell("B{$nomorBaris}")->getValue(),
                    'c' => $sheet->getCell("C{$nomorBaris}")->getValue(),
                ];
            }

            return $hasil;
        } catch (Throwable) {
            return $this->bacaBarisDariZipXlsx($filePath);
        }
    }

    /**
     * Baca struktur XML worksheet pertama (.xlsx) secara langsung menggunakan ZipArchive.
     *
     * @return list<array{baris: int, a: mixed, b: mixed, c: mixed}>
     */
    private function bacaBarisDariZipXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Berkas .xlsx tidak dapat dibuka.');
        }

        $sharedStrings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent !== false) {
            $sharedXml = new SimpleXMLElement($sharedXmlContent);
            foreach ($sharedXml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } else {
                    $teksGabungan = '';
                    foreach ($si->r as $run) {
                        $teksGabungan .= (string) $run->t;
                    }
                    $sharedStrings[] = $teksGabungan;
                }
            }
        }

        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXmlContent === false) {
            throw new RuntimeException('Worksheet utama tidak ditemukan di dalam berkas .xlsx.');
        }

        $sheetXml = new SimpleXMLElement($sheetXmlContent);
        if (! isset($sheetXml->sheetData->row)) {
            return [];
        }

        $hasil = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $nomorBaris = (int) ($row['r'] ?? 0);
            if ($nomorBaris < 2) {
                continue;
            }

            $kolom = ['A' => null, 'B' => null, 'C' => null];

            foreach ($row->c as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $hurufKolom = preg_replace('/[^A-Z]/', '', strtoupper($ref)) ?: '';
                if (! array_key_exists($hurufKolom, $kolom)) {
                    continue;
                }

                $tipe = (string) ($cell['t'] ?? '');
                if ($tipe === 's') {
                    $indeksString = (int) ($cell->v ?? 0);
                    $kolom[$hurufKolom] = $sharedStrings[$indeksString] ?? '';
                } elseif ($tipe === 'inlineStr') {
                    $kolom[$hurufKolom] = isset($cell->is->t) ? (string) $cell->is->t : '';
                } else {
                    $kolom[$hurufKolom] = isset($cell->v) ? (string) $cell->v : null;
                }
            }

            $hasil[] = [
                'baris' => $nomorBaris,
                'a' => $kolom['A'],
                'b' => $kolom['B'],
                'c' => $kolom['C'],
            ];
        }

        return $hasil;
    }

    /**
     * Periksa apakah seluruh sel pada suatu baris kosong.
     */
    private function apakahBarisKosong(mixed $tahun, mixed $bulan, mixed $jumlah): bool
    {
        $kosongTahun = $tahun === null || trim((string) $tahun) === '';
        $kosongBulan = $bulan === null || trim((string) $bulan) === '';
        $kosongJumlah = $jumlah === null || trim((string) $jumlah) === '';

        return $kosongTahun && $kosongBulan && $kosongJumlah;
    }

    /**
     * Validasi bahwa nilai sel Excel merupakan bilangan bulat murni (bukan teks atau pecahan).
     */
    private function validasiBilanganBulat(mixed $nilai): ?int
    {
        if ($nilai === null || is_bool($nilai)) {
            return null;
        }

        if (is_int($nilai)) {
            return $nilai;
        }

        if (is_float($nilai)) {
            if (floor($nilai) !== $nilai) {
                return null;
            }

            return (int) $nilai;
        }

        $teks = trim((string) $nilai);
        if ($teks === '') {
            return null;
        }

        if (! preg_match('/^-?\d+$/', $teks)) {
            return null;
        }

        return (int) $teks;
    }

    /**
     * Kirim Spreadsheet sebagai unduhan berkas .xlsx.
     */
    private function streamSpreadsheet(Spreadsheet $spreadsheet, string $namaFile): StreamedResponse
    {
        return response()->streamDownload(static function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
