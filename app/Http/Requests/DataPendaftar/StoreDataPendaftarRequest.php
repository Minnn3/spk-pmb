<?php

namespace App\Http\Requests\DataPendaftar;

use App\Models\DataPendaftar;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataPendaftarRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki izin menambah data historis.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', DataPendaftar::class);
    }

    /**
     * Normalisasi nilai input sebelum divalidasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tahun' => $this->input('tahun') !== null ? trim((string) $this->input('tahun')) : null,
            'bulan' => $this->input('bulan') !== null ? trim((string) $this->input('bulan')) : null,
            'jumlah_pendaftar' => $this->input('jumlah_pendaftar') !== null ? trim((string) $this->input('jumlah_pendaftar')) : null,
        ]);
    }

    /**
     * Aturan validasi untuk penambahan data pendaftar bulanan (FR-DH-02).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tahun' => ['required', 'integer', 'digits:4', 'min:1900', 'max:2100'],
            'bulan' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('data_pendaftar', 'bulan')->where(function (Builder $query): void {
                    $query->where('tahun', (int) $this->input('tahun'));
                }),
            ],
            'jumlah_pendaftar' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Pesan galat validasi dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.integer' => 'Tahun harus berupa angka bulat.',
            'tahun.digits' => 'Tahun harus terdiri dari 4 digit.',
            'tahun.min' => 'Tahun tidak valid.',
            'tahun.max' => 'Tahun tidak valid.',
            'bulan.required' => 'Bulan wajib dipilih.',
            'bulan.integer' => 'Bulan harus berupa angka 1 sampai 12.',
            'bulan.between' => 'Bulan harus berada di antara 1 (Januari) sampai 12 (Desember).',
            'bulan.unique' => 'Data pendaftar untuk periode tahun dan bulan tersebut sudah terdaftar.',
            'jumlah_pendaftar.required' => 'Jumlah pendaftar wajib diisi.',
            'jumlah_pendaftar.integer' => 'Jumlah pendaftar harus berupa bilangan bulat.',
            'jumlah_pendaftar.min' => 'Jumlah pendaftar tidak boleh bernilai negatif (minimal 0).',
        ];
    }
}

