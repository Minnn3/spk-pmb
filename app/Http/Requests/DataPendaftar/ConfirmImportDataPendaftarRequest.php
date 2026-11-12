<?php

namespace App\Http\Requests\DataPendaftar;

use App\Models\DataPendaftar;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmImportDataPendaftarRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki izin menyimpan hasil pratinjau impor.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', DataPendaftar::class);
    }

    /**
     * Normalisasi input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('mode_duplikat')) {
            $this->merge([
                'mode_duplikat' => strtolower(trim((string) $this->input('mode_duplikat'))),
            ]);
        }
    }

    /**
     * Aturan validasi konfirmasi simpan impor.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mode_duplikat' => ['nullable', 'string', 'in:timpa,lewati'],
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
            'mode_duplikat.in' => 'Mode penanganan duplikat harus berupa Timpa atau Lewati.',
        ];
    }
}

