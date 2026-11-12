<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke riwayat impor yang dilakukan pengguna ini.
     */
    public function riwayatImpor(): HasMany
    {
        return $this->hasMany(RiwayatImpor::class, 'user_id', 'id');
    }

    /**
     * Relasi ke pengaturan SPK yang terakhir diubah oleh pengguna ini.
     */
    public function pengaturanDiubah(): HasMany
    {
        return $this->hasMany(PengaturanSpk::class, 'diubah_oleh', 'id');
    }

    /**
     * Periksa apakah pengguna memiliki peran admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Periksa apakah pengguna memiliki peran pimpinan.
     */
    public function isPimpinan(): bool
    {
        return $this->role === 'pimpinan';
    }
}
