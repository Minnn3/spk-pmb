<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Buat akun awal Admin PMB dan Pimpinan / Dosen Penguji.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@spkpmb.ac.id'],
            [
                'name' => 'Admin PMB',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'pimpinan@spkpmb.ac.id'],
            [
                'name' => 'Pimpinan Kampus',
                'password' => Hash::make('password'),
                'role' => 'pimpinan',
                'email_verified_at' => now(),
            ]
        );
    }
}

