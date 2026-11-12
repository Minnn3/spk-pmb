<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanSpk extends Model
{
    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'pengaturan_spk';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kunci',
        'label',
        'nilai',
        'nilai_default',
        'satuan',
        'diubah_oleh',
    ];

    /**
     * Nilai default standar sesuai spesifikasi PRD bagian 3.3.
     *
     * @var array<string, array{label: string, nilai: int, satuan: string}>
     */
    public const DEFAULTS = [
        'target_minimum_pendaftar' => [
            'label' => 'Target minimum pendaftar',
            'nilai' => 30,
            'satuan' => 'orang/bulan',
        ],
        'batas_peak_season' => [
            'label' => 'Batas peak season',
            'nilai' => 100,
            'satuan' => 'orang/bulan',
        ],
        'kapasitas_layan_staf' => [
            'label' => 'Kapasitas layan staf',
            'nilai' => 40,
            'satuan' => 'pendaftar/staf/bulan',
        ],
        'estimasi_biaya_pendaftaran' => [
            'label' => 'Estimasi biaya pendaftaran',
            'nilai' => 250000,
            'satuan' => 'Rupiah',
        ],
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nilai' => 'integer',
            'nilai_default' => 'integer',
            'diubah_oleh' => 'integer',
        ];
    }

    /**
     * Relasi ke pengguna yang terakhir mengubah parameter ini.
     */
    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'id');
    }

    /**
     * Scope untuk mengambil berdasarkan kunci parameter.
     */
    public function scopeDenganKunci(Builder $query, string $kunci): Builder
    {
        return $query->where('kunci', $kunci);
    }

    /**
     * Ambil seluruh parameter aktif sebagai array asosiatif [kunci => nilai],
     * dilengkapi nilai fallback jika belum di-seed.
     *
     * @return array{
     *     target_minimum_pendaftar: int,
     *     batas_peak_season: int,
     *     kapasitas_layan_staf: int,
     *     estimasi_biaya_pendaftaran: int
     * }
     */
    public static function nilaiAktif(): array
    {
        $tersimpan = self::query()->pluck('nilai', 'kunci')->all();

        return [
            'target_minimum_pendaftar' => isset($tersimpan['target_minimum_pendaftar'])
                ? (int) $tersimpan['target_minimum_pendaftar']
                : self::DEFAULTS['target_minimum_pendaftar']['nilai'],
            'batas_peak_season' => isset($tersimpan['batas_peak_season'])
                ? (int) $tersimpan['batas_peak_season']
                : self::DEFAULTS['batas_peak_season']['nilai'],
            'kapasitas_layan_staf' => isset($tersimpan['kapasitas_layan_staf'])
                ? (int) $tersimpan['kapasitas_layan_staf']
                : self::DEFAULTS['kapasitas_layan_staf']['nilai'],
            'estimasi_biaya_pendaftaran' => isset($tersimpan['estimasi_biaya_pendaftaran'])
                ? (int) $tersimpan['estimasi_biaya_pendaftaran']
                : self::DEFAULTS['estimasi_biaya_pendaftaran']['nilai'],
        ];
    }
}

