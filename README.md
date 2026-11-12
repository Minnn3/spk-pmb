# SPK Perencanaan Strategi Promosi dan Alokasi Staf PMB

Sistem Pendukung Keputusan (SPK) berbasis web yang mengolah data historis pendaftar bulanan menjadi prediksi 12 bulan ke depan menggunakan kombinasi metode **Trend Moment** dan **Indeks Musiman**, lalu menerjemahkannya secara otomatis menjadi rekomendasi **strategi promosi marketing**, **alokasi jumlah staf CS/Panitia PMB**, dan **proyeksi kas registrasi** per bulan.

Seluruh tahapan perhitungan ditampilkan secara transparan (mulai dari tabel variabel $X, Y, XY, X^2$, substitusi & eliminasi persamaan regresi, perhitungan indeks musiman, hingga prediksi akhir) sehingga dapat diaudit dan divalidasi secara langsung.

---

## Fitur Utama

1. **Dashboard Eksekutif & Rekomendasi Keputusan**
   - Empat kartu metrik utama: Total Prediksi Pendaftar Setahun, Bulan Puncak, Total Estimasi Kas Registrasi, dan Kebutuhan Staf Tertinggi.
   - Grafik garis interaktif (*Chart.js*) yang membandingkan pendaftar aktual vs prediksi beserta garis ambang *Target Minimum* dan *Batas Peak Season*.
   - Tabel rekomendasi 12 bulan yang memuat status musim (*Low Season*, *Normal Season*, *Peak Season*), kebutuhan staf (`CEIL`), proyeksi kas, dan langkah promosi yang disarankan.
   - Ekspor tabel rekomendasi ke berkas Excel (`.xlsx`).

2. **Transparansi Perhitungan (Audit 5 Tahap)**
   - **Tahap 1 — Variabel Deret Waktu:** Tabel kronologis $X, Y, XY, X^2$ lengkap dengan baris total $\Sigma X, \Sigma Y, \Sigma XY, \Sigma X^2$.
   - **Tahap 2 — Koefisien Regresi Trend Moment:** Substitusi persamaan normal, langkah eliminasi untuk mendapatkan nilai $a$ dan $b$ (6 desimal), serta persamaan tren akhir $Y_{trend} = a + bX$.
   - **Tahap 3 — Indeks Musiman:** Rata-rata bulanan ($\bar{Y}_m$), rata-rata total ($\bar{Y}$), dan Indeks Musiman ($IM_m$) untuk 12 bulan kalender.
   - **Tahap 4 — Prediksi Akhir:** Perhitungan $Y_{akhir} = \max(0, \operatorname{ROUND}(Y_{trend} \times IM_m))$ pada tahun target terpilih.
   - Mode cetak hitam-putih (`@media print`) siap pakai untuk lampiran laporan.

3. **Manajemen Data Historis & Import Excel**
   - CRUD manual data pendaftar bulanan dengan validasi keunikan periode `(tahun, bulan)`.
   - Banner pendeteksi kesiapan deret waktu (mendeteksi celah bulan yang hilang serta batas minimum 12/24 bulan berurutan).
   - Unduh template standar `.xlsx`, unggah berkas `.xlsx` dengan pratinjau validasi baris demi baris, opsi penanganan periode duplikat (*Timpa* atau *Lewati*), riwayat impor, dan ekspor data historis.

4. **Konfigurasi Parameter SPK Dinamis**
   - Pengaturan 4 parameter keputusan tanpa menyentuh kode: `target_minimum_pendaftar`, `batas_peak_season`, `kapasitas_layan_staf`, dan `estimasi_biaya_pendaftaran`.
   - Fitur *Kembalikan ke Default* dan pencatatan audit pengguna/waktu perubahan terakhir.

5. **Manajemen Hak Akses (Role-Based Authorization)**
   - **Admin PMB (`admin`):** Akses penuh ke seluruh modul (kelola data historis, impor Excel, konfigurasi parameter SPK, dashboard, dan perhitungan).
   - **Pimpinan / Dosen Penguji (`pimpinan`):** Akses baca (*read-only*) ke halaman Dashboard dan Transparansi Perhitungan.

---

## Teknologi yang Digunakan

- **Backend:** PHP 8.4, Laravel 13
- **Database:** MySQL / MariaDB (`spkpmb`)
- **Frontend:** Blade, Vite, Tailwind CSS v4 (mengikuti *Universal Master Design System* di `DESIGN.md`)
- **Library:** `maatwebsite/excel` (`phpoffice/phpspreadsheet`), `chart.js`, `@fontsource/inter`, `@fontsource/jetbrains-mono`, Lucide SVG Icons
- **Testing:** PHPUnit 12 (Unit & Feature Tests terverifikasi dengan Dataset Uji Lampiran A)

---

## Instalasi & Menjalankan Proyek

1. **Clone repositori dan pasang dependensi:**
   ```bash
   composer install
   npm install
   ```

2. **Salin konfigurasi environment dan buat application key:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Atur koneksi database di `.env`:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=spkpmb
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Jalankan migrasi dan seeder (termasuk akun awal & dataset uji 24 bulan):**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Build aset frontend:**
   ```bash
   npm run build
   ```

6. **Jalankan server aplikasi:**
   ```bash
   php artisan serve
   ```

---

## Akun Demo Default

| Peran | Email | Kata Sandi | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Admin PMB** | `admin@spkpmb.ac.id` | `password` | Akses penuh (CRUD Data, Import Excel, Parameter SPK, Dashboard, Perhitungan) |
| **Pimpinan / Penguji** | `pimpinan@spkpmb.ac.id` | `password` | Baca saja (Dashboard & Transparansi Perhitungan) |

---

## Pengujian Otomatis

Untuk menjalankan seluruh pengujian unit (*Trend Moment*, *Indeks Musiman*, dan aturan keputusan SPK) serta pengujian fitur (*Import Excel*, CRUD, dan otorisasi):

```bash
php vendor/phpunit/phpunit/phpunit
```
