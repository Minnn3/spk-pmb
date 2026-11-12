<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DataPendaftar extends Model
{
    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'data_pendaftar';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tahun',
        'bulan',
        'jumlah_pendaftar',
    ];

    /**
     * Daftar nama bulan dalam bahasa Indonesia.
     *
     * @var array<int, string>
     */
    public const NAMA_BULAN = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Konversi tipe atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'jumlah_pendaftar' => 'integer',
        ];
    }

    /**
     * Scope untuk mengurutkan deret waktu secara kronologis (tahun lalu bulan).
     */
    public function scopeUrutKronologis(Builder $query): Builder
    {
        return $query->orderBy('tahun', 'asc')->orderBy('bulan', 'asc');
    }

    /**
     * Scope untuk memfilter berdasarkan tahun tertentu.
     */
    public function scopePadaTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }

    /**
     * Scope pencarian berdasarkan tahun atau nama/angka bulan.
     */
    public function scopeCari(Builder $query, ?string $kataKunci): Builder
    {
        if ($kataKunci === null || trim($kataKunci) === '') {
            return $query;
        }

        $kataKunci = trim($kataKunci);
        $bulanCocok = [];

        foreach (self::NAMA_BULAN as $angka => $nama) {
            if (stripos($nama, $kataKunci) !== false) {
                $bulanCocok[] = $angka;
            }
        }

        return $query->where(function (Builder $q) use ($kataKunci, $bulanCocok) {
            if (is_numeric($kataKunci)) {
                $angka = (int) $kataKunci;
                $q->where('tahun', $angka)
                    ->orWhere('bulan', $angka)
                    ->orWhere('jumlah_pendaftar', $angka);
            }

            if ($bulanCocok !== []) {
                $q->orWhereIn('bulan', $bulanCocok);
            }
        });
    }

    /**
     * Ambil nama bulan dalam bahasa Indonesia untuk baris ini.
     */
    public function getNamaBulanAttribute(): string
    {
        return self::NAMA_BULAN[$this->bulan] ?? (string) $this->bulan;
    }

    /**
     * Ambil label periode lengkap (contoh: Januari 2024).
     */
    public function getLabelPeriodeAttribute(): string
    {
        return $this->nama_bulan . ' ' . $this->tahun;
    }

    /**
     * Ambil nama bulan statis dari angka 1-12.
     */
    public static function namaBulan(int $bulan): string
    {
        return self::NAMA_BULAN[$bulan] ?? (string) $bulan;
    }
}

