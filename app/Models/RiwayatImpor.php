<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatImpor extends Model
{
    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'riwayat_impor';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nama_file',
        'total_baris',
        'baris_berhasil',
        'baris_gagal',
        'detail_galat',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'total_baris' => 'integer',
            'baris_berhasil' => 'integer',
            'baris_gagal' => 'integer',
            'detail_galat' => 'array',
        ];
    }

    /**
     * Relasi ke pengguna yang mengunggah file impor.
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Scope urutan terbaru.
     */
    public function scopeTerbaru(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}

