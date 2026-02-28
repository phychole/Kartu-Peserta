# PSAJ - Generator Kartu Peserta & Nomor Bangku (PHP Native)

## Fitur
- Login admin
- Import peserta dari CSV (delimiter otomatis `;` atau `,`)
- Database: nomor peserta (unik), nama, kelas, ruang
- Cetak ke kertas F4 (210mm x 330mm)
- Ukuran kartu: 9 x 5.5 cm (10 kartu per halaman)

## Cara Install (Laragon)
1. Copy folder proyek ke: `laragon/www/psaj_kartu_ujian`
2. Buat database MySQL: `psaj_kartu_ujian`
3. Import SQL: `sql/install.sql`
4. Sesuaikan konfigurasi DB: `config/config.php`
5. Buka: `http://localhost/psaj_kartu_ujian/`

## Login Default
- username: `admin`
- password: `admin123`  (segera ubah hash di tabel `users` jika diperlukan)

## Logo
File logo sementara: `assets/logo-jatim.svg`  
Silakan ganti dengan logo resmi Provinsi Jawa Timur (SVG/PNG) **dengan nama file yang sama** agar otomatis tampil.

## Cetak
- Menu: Dashboard -> Cetak Kartu / Cetak Nomor Bangku
- Gunakan tombol **Print** pada halaman cetak
- Pastikan printer dialog: ukuran kertas **F4** dan skala **100%** (no fit-to-page).


## Catatan Folder
Project ini sudah **auto-detect BASE_URL**, jadi aman jika diletakkan di folder seperti `/kpsaj` tanpa perlu ubah kode.


## BASE_URL Otomatis
Jika ditempatkan di folder apa pun (mis: `/kpsaj`), sistem otomatis mendeteksi `BASE_URL`.
Include utama sekarang: `config/bootstrap.php`.
