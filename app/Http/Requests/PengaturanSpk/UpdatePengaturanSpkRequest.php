<?php

namespace App\Http\Requests\PengaturanSpk;

use App\Models\PengaturanSpk;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePengaturanSpkRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki izin mengubah parameter SPK (AC-KA-03).
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('update', PengaturanSpk::class);
    }

    /**
     * Normalisasi data parameter sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $fields = [
            'target_minimum_pendaftar',
            'batas_peak_season',
            'kapasitas_layan_staf',
            'estimasi_biaya_pendaftaran',
        ];

        $normalized = [];
        foreach ($fields as $field) {
            if ($this->has($field)) {
                $normalized[$field] = trim((string) $this->input($field));
            }
        }

        $this->merge($normalized);
    }

    /**
     * Aturan validasi parameter SPK sesuai bagian 1.6 poin 7, bagian 3.3, dan AC-SP-08.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_minimum_pendaftar' => ['required', 'integer', 'min:0', 'lt:batas_peak_season'],
            'batas_peak_season' => ['required', 'integer', 'gt:target_minimum_pendaftar'],
            'kapasitas_layan_staf' => ['required', 'integer', 'min:1'],
            'estimasi_biaya_pendaftaran' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Pesan kesalahan validasi berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_minimum_pendaftar.required' => 'Target minimum pendaftar wajib diisi.',
            'target_minimum_pendaftar.integer' => 'Target minimum pendaftar harus berupa bilangan bulat.',
            'target_minimum_pendaftar.min' => 'Target minimum pendaftar tidak boleh bernilai negatif (minimal 0).',
            'target_minimum_pendaftar.lt' => 'Target minimum pendaftar harus lebih kecil dari batas peak season.',
            'batas_peak_season.required' => 'Batas peak season wajib diisi.',
            'batas_peak_season.integer' => 'Batas peak season harus berupa bilangan bulat.',
            'batas_peak_season.gt' => 'Batas peak season harus lebih besar dari target minimum pendaftar.',
            'kapasitas_layan_staf.required' => 'Kapasitas layan staf wajib diisi.',
            'kapasitas_layan_staf.integer' => 'Kapasitas layan staf harus berupa bilangan bulat.',
            'kapasitas_layan_staf.min' => 'Kapasitas layan staf harus bernilai minimal 1 pendaftar/staf/bulan.',
            'estimasi_biaya_pendaftaran.required' => 'Estimasi biaya pendaftaran wajib diisi.',
            'estimasi_biaya_pendaftaran.integer' => 'Estimasi biaya pendaftaran harus berupa bilangan bulat.',
            'estimasi_biaya_pendaftaran.min' => 'Estimasi biaya pendaftaran tidak boleh bernilai negatif (minimal 0).',
        ];
    }
}

