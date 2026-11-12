<?php

namespace Database\Seeders;

use App\Models\PengaturanSpk;
use Illuminate\Database\Seeder;

class PengaturanSpkSeeder extends Seeder
{
    /**
     * Isi empat parameter standar SPK sesuai bagian 3.3 PRD.
     */
    public function run(): void
    {
        foreach (PengaturanSpk::DEFAULTS as $kunci => $konfigurasi) {
            PengaturanSpk::query()->updateOrCreate(
                ['kunci' => $kunci],
                [
                    'label' => $konfigurasi['label'],
                    'nilai' => $konfigurasi['nilai'],
                    'nilai_default' => $konfigurasi['nilai'],
                    'satuan' => $konfigurasi['satuan'],
                ]
            );
        }
    }
}

