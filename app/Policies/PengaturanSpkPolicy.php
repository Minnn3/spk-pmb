<?php

namespace App\Policies;

use App\Models\User;

class PengaturanSpkPolicy
{
    /**
     * Tentukan apakah pengguna dapat membuka halaman konfigurasi parameter SPK.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat menyimpan atau mereset parameter SPK.
     */
    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}

