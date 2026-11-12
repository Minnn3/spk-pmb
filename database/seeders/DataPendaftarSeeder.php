<?php

namespace Database\Seeders;

use App\Models\DataPendaftar;
use Illuminate\Database\Seeder;

class DataPendaftarSeeder extends Seeder
{
    /**
     * Dataset uji Lampiran A (24 bulan, Januari 2024 sampai Desember 2025).
     *
     * @var list<array{tahun: int, bulan: int, jumlah_pendaftar: int}>
     */
    public const DATASET_LAMPIRAN_A = [
        ['tahun' => 2024, 'bulan' => 1, 'jumlah_pendaftar' => 62],
        ['tahun' => 2024, 'bulan' => 2, 'jumlah_pendaftar' => 48],
        ['tahun' => 2024, 'bulan' => 3, 'jumlah_pendaftar' => 40],
        ['tahun' => 2024, 'bulan' => 4, 'jumlah_pendaftar' => 35],
        ['tahun' => 2024, 'bulan' => 5, 'jumlah_pendaftar' => 58],
        ['tahun' => 2024, 'bulan' => 6, 'jumlah_pendaftar' => 95],
        ['tahun' => 2024, 'bulan' => 7, 'jumlah_pendaftar' => 128],
        ['tahun' => 2024, 'bulan' => 8, 'jumlah_pendaftar' => 110],
        ['tahun' => 2024, 'bulan' => 9, 'jumlah_pendaftar' => 52],
        ['tahun' => 2024, 'bulan' => 10, 'jumlah_pendaftar' => 28],
        ['tahun' => 2024, 'bulan' => 11, 'jumlah_pendaftar' => 22],
        ['tahun' => 2024, 'bulan' => 12, 'jumlah_pendaftar' => 30],
        ['tahun' => 2025, 'bulan' => 1, 'jumlah_pendaftar' => 70],
        ['tahun' => 2025, 'bulan' => 2, 'jumlah_pendaftar' => 55],
        ['tahun' => 2025, 'bulan' => 3, 'jumlah_pendaftar' => 45],
        ['tahun' => 2025, 'bulan' => 4, 'jumlah_pendaftar' => 41],
        ['tahun' => 2025, 'bulan' => 5, 'jumlah_pendaftar' => 66],
        ['tahun' => 2025, 'bulan' => 6, 'jumlah_pendaftar' => 108],
        ['tahun' => 2025, 'bulan' => 7, 'jumlah_pendaftar' => 140],
        ['tahun' => 2025, 'bulan' => 8, 'jumlah_pendaftar' => 122],
        ['tahun' => 2025, 'bulan' => 9, 'jumlah_pendaftar' => 60],
        ['tahun' => 2025, 'bulan' => 10, 'jumlah_pendaftar' => 33],
        ['tahun' => 2025, 'bulan' => 11, 'jumlah_pendaftar' => 27],
        ['tahun' => 2025, 'bulan' => 12, 'jumlah_pendaftar' => 36],
    ];

    /**
     * Isi tabel data_pendaftar dengan dataset Lampiran A.
     */
    public function run(): void
    {
        foreach (self::DATASET_LAMPIRAN_A as $baris) {
            DataPendaftar::query()->updateOrCreate(
                [
                    'tahun' => $baris['tahun'],
                    'bulan' => $baris['bulan'],
                ],
                [
                    'jumlah_pendaftar' => $baris['jumlah_pendaftar'],
                ]
            );
        }
    }
}

