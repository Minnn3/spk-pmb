<?php

namespace App\Policies;

use App\Models\DataPendaftar;
use App\Models\User;

class DataPendaftarPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat halaman daftar & impor data historis.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail satu data historis.
     */
    public function view(User $user, DataPendaftar $dataPendaftar): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat menambah data historis atau mengimpor Excel.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat mengubah data historis.
     */
    public function update(User $user, DataPendaftar $dataPendaftar): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat menghapus data historis.
     */
    public function delete(User $user, DataPendaftar $dataPendaftar): bool
    {
        return $user->isAdmin();
    }
}

