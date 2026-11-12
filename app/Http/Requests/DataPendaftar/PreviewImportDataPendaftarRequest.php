<?php

namespace App\Http\Requests\DataPendaftar;

use App\Models\DataPendaftar;
use Closure;
use finfo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class PreviewImportDataPendaftarRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki izin mengunggah berkas Excel.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', DataPendaftar::class);
    }

    /**
     * Normalisasi input mode duplikat sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode_duplikat' => strtolower(trim((string) $this->input('mode_duplikat', 'timpa'))),
        ]);
    }

    /**
     * Aturan validasi unggahan Excel (.xlsx maksimal 2 MB) sesuai FR-DH-04 dan AC-DI-04.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'berkas_excel' => [
                'required',
                'file',
                'extensions:xlsx',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('Berkas yang diunggah tidak valid.');

                        return;
                    }

                    $ext = strtolower((string) $value->getClientOriginalExtension());
                    if ($ext !== 'xlsx') {
                        $fail('Format berkas harus berupa .xlsx (Excel Workbook).');

                        return;
                    }

                    $path = $value->getRealPath() ?: $value->getPathname();
                    if ($path === '' || ! is_file($path)) {
                        $fail('Berkas yang diunggah tidak ditemukan.');

                        return;
                    }

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = (string) @$finfo->file($path);

                    $allowedMimes = [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'application/zip',
                        'application/x-zip-compressed',
                        'application/octet-stream',
                        'text/plain',
                        'text/csv',
                        'inode/x-empty',
                    ];

                    if ($mime !== '' && ! in_array($mime, $allowedMimes, true)) {
                        $fail('Format berkas harus berupa .xlsx (Excel Workbook).');
                    }
                },
            ],
            'mode_duplikat' => ['required', 'string', 'in:timpa,lewati'],
        ];
    }

    /**
     * Pesan galat validasi unggahan dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'berkas_excel.required' => 'Silakan pilih berkas Excel (.xlsx) terlebih dahulu.',
            'berkas_excel.file' => 'Berkas yang diunggah tidak valid.',
            'berkas_excel.extensions' => 'Format berkas harus berupa .xlsx (Excel Workbook).',
            'berkas_excel.max' => 'Ukuran berkas maksimal adalah 2 MB (2.048 KB).',
            'mode_duplikat.required' => 'Mode penanganan periode duplikat wajib dipilih.',
            'mode_duplikat.in' => 'Mode penanganan duplikat harus berupa Timpa atau Lewati.',
        ];
    }
}
