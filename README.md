# ParkirPro — Sistem Manajemen Parkir

Aplikasi PHP dan MySQL untuk mengelola kendaraan masuk/keluar, area dan kapasitas parkir, tarif, pengguna, transaksi, serta laporan pendapatan.

## Menjalankan di XAMPP

1. Salin folder proyek ke `C:\xampp2\htdocs\app-parkir`.
2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Buka phpMyAdmin di `http://localhost/phpmyadmin`.
4. Pilih **Import**, lalu impor `database/parkir.sql`. Skrip membuat database `parkir`, tabel, view laporan, akun awal, tarif, dan area contoh. Skrip menggunakan `IF NOT EXISTS`/`INSERT IGNORE` agar impor ulang tidak menghapus data.
5. Buka `http://localhost/app-parkir`.
6. Masuk menggunakan akun awal berikut:

   - Username: `admin`
   - Kata sandi: `admin123`
   - Username: `petugas`
   - Kata sandi: `Petugas123!`
   - Username: `owner`
   - Kata sandi: `Owner123!`

   Ganti kata sandi setiap akun setelah login pertama kali.

### Memperbarui database yang sudah digunakan

Jika database `parkir` sudah ada sebelum fitur barcode dan denah slot ditambahkan, **jangan impor ulang `parkir.sql`**. Impor `database/migration_barcode_denah.sql` satu kali melalui phpMyAdmin (pilih database `parkir`, buka tab **Import**, lalu pilih berkas migrasi). Migrasi akan menambahkan data slot parkir dan token barcode pada transaksi lama. Setelah migrasi berhasil, muat ulang aplikasi.

Instalasi baru cukup menggunakan `database/parkir.sql`; tidak perlu menjalankan migrasi.

Untuk menambahkan jenis kendaraan Bus dan Truk pada database yang sudah berjalan, impor `database/migration_bus_truk.sql` satu kali melalui phpMyAdmin. Tarif Bus dan Truk mengikuti tarif kategori **Lainnya**; nominal tarif yang sudah ada tidak diubah.

## Konfigurasi database

Default koneksi menggunakan `127.0.0.1`, database `parkir`, user `root`, dan kata sandi kosong (default XAMPP). Konfigurasi dapat diubah melalui environment variable `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS`, atau sesuaikan nilai di `config/database.php`.

## Peran pengguna

- **Admin**: dashboard, akun pengguna, tarif, area, data kendaraan, laporan transaksi dengan ekspor Excel/cetak PDF, serta log aktivitas.
- **Petugas**: kendaraan masuk/keluar, tiket dan struk, serta riwayat transaksi.
- **Owner**: dashboard pendapatan dan laporan dengan filter tanggal/jenis/status, ekspor laporan Excel, serta tampilan cetak untuk disimpan sebagai PDF.

Kata sandi disimpan menggunakan `password_hash`. Formulir perubahan data menggunakan token CSRF. Proses parkir mengunci data area dan transaksi untuk mengurangi risiko kapasitas berlebih atau transaksi keluar ganda.

## Catatan operasional

- Tarif dihitung per jam, dibulatkan ke atas, dengan minimum satu jam.
- Tarif transaksi disimpan sebagai snapshot sehingga perubahan tarif tidak mengubah transaksi lama.
- Sebelum dipakai pada lingkungan produksi, ganti kredensial database, ubah kata sandi admin bawaan, aktifkan HTTPS, dan lakukan backup database berkala.
- Tiket masuk menampilkan barcode Code 128. Pada halaman kendaraan keluar, petugas dapat memindainya dengan kamera atau memasukkan kode secara manual. Akses kamera memerlukan izin browser dan koneksi `localhost` atau HTTPS; CDN barcode/scanner memerlukan koneksi internet.
- Denah slot menampilkan ruang kosong dan terisi per area. Atur kapasitas area dari menu Admin → Area agar jumlah slot sesuai kapasitas.
- Halaman login menampilkan denah parkir publik yang diperbarui otomatis setiap 15 detik, dengan jumlah kendaraan terisi dan slot tersisa per area, ilustrasi kendaraan 3D tampak atas, daftar kendaraan aktif terbaru, serta banner promosi/informasi parkir. Daftar publik hanya menampilkan jenis, area, slot, dan waktu masuk—bukan plat nomor atau identitas pemilik.
- Kendaraan Motor, Bus, dan Truk memakai ilustrasi 3D tersendiri pada denah. Pada database lama, jalankan `database/migration_bus_truk.sql` sebelum mencatat kendaraan dengan jenis Bus atau Truk.
- Laporan Admin dan Owner dapat diunduh sebagai berkas Excel `.xls` berformat dan dicetak/disimpan menjadi PDF melalui dialog cetak browser. Ekspor memuat seluruh transaksi sesuai filter, tidak hanya 500 baris yang ditampilkan di halaman.
- Semua dashboard peran memiliki animasi masuk pada kartu ringkasan, grafik, denah, dan tabel; animasi mengikuti pengaturan reduce-motion perangkat.
- Denah Admin dan Petugas menampilkan tata letak tampak atas dengan area masuk/keluar, jalur kendaraan, dan mobil ilustratif pada slot mobil terisi. Pada layar ponsel, denah dapat digeser mendatar agar slot tetap terbaca.
