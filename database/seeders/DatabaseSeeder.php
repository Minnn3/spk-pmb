<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Jalankan seluruh seeder aplikasi SPK PMB.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PengaturanSpkSeeder::class,
            DataPendaftarSeeder::class,
        ]);
    }
}
