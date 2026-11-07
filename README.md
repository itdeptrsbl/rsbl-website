# Website RS Bintang Laut

Website PHP native untuk informasi rumah sakit, dokter dan jadwal, fasilitas layanan, informasi/berita dengan foto serta video, dan permohonan pendaftaran. Panel pengelola menyediakan master untuk setiap fitur.

## Menjalankan website

1. Tempatkan proyek di `C:\xampp\htdocs\rsbl-website`.
2. Aktifkan Apache di XAMPP, lalu buka `http://localhost/rsbl-website/`.
3. Pada instalasi pertama, buat akun admin. Jika database sudah ada, langsung gunakan website/login yang tersedia.
4. Alternatif server lokal: jalankan `start.bat`, lalu buka `http://localhost:8080`.

PHP 8.0+ diperlukan, dengan PDO SQLite (atau MySQL sesuai konfigurasi), mbstring, dan fileinfo. Website tidak memerlukan Node, Composer, atau kompilasi aset untuk dijalankan.

Konfigurasi database berada di `config.php`. SQLite disimpan di `storage/rsbl.sqlite`. Bila memakai MySQL, siapkan database dahulu dan ubah pengaturannya sebelum instalasi. Penataan folder tidak memindahkan data antar-database.

## Pengelolaan konten

- Master Konten mengelola foto beranda, fasilitas, Rawat Jalan dan poli, Rawat Inap dan kamar, IGD, Farmasi, Radiologi, Fisioterapi, Laboratorium, sejarah, visi, laporan mutu, dan mitra.
- Menu Informasi mengelola judul, isi, banyak foto dengan caption, dan video opsional.
- Dokter & Jadwal mengelola profil, riwayat profesional, dan beberapa sesi praktik per dokter.
- Form pendaftaran menggunakan empat langkah. Klien API Medinfras sudah disiapkan, tetapi pengiriman publik belum diaktifkan sampai koneksi vendor terverifikasi. Permohonan terdahulu tetap dapat dikelola pada panel Pendaftaran. Lihat [catatan integrasi Medinfras](docs/PENDAFTARAN-MEDINFRAS.md).
- Format gambar: JPG/PNG/WebP, maksimal 6000 piksel. Batas umum 10 MB; gambar sejarah dan laporan mutu 20 MB. Video Informasi MP4/WebM 50 MB, video sejarah 20 MB.
- `assets/uploads/` harus dapat ditulis oleh PHP. File lama tidak dihapus otomatis dari disk ketika tidak ditampilkan, agar tautan lain tetap aman.

## Struktur dan pengembangan

Lihat [panduan struktur proyek](docs/STRUKTUR-PROYEK.md) untuk lokasi controller, modul, template, aset, dan langkah menambahkan fitur. [Peta pemindahan file](docs/file-migration-map.json) memetakan path lama ke path baru.

```text
app/       bootstrap, konfigurasi admin, controller, aksi, modul, helper, database
views/     layout, autentikasi, halaman publik/admin, komponen bersama
assets/    CSS dan JavaScript menurut fungsi; branding dan unggahan
storage/   SQLite dan snapshot kode
tests/    tes integrasi
tools/    pemeriksaan sintaks
docs/     dokumentasi pengembangan
```

Formatter bersifat opsional untuk pengembang:

```powershell
npm.cmd ci
npm.cmd run format
npm.cmd run format:check
npm.cmd run check
```

Tes integrasi terisolasi:

```powershell
python tests/smoke.py --php C:\xampp\php\php.exe --extension C:\xampp\php\ext\php_pdo_sqlite.dll --extension C:\xampp\php\ext\php_mbstring.dll --extension C:\xampp\php\ext\php_fileinfo.dll
```

Pada hosting HTTPS, aktifkan `secure_cookie` dalam `config.php`. Folder internal memiliki proteksi Apache; server PHP bawaan memakai whitelist pada `router.php`. `node_modules/` dan perangkat pengembangan tidak diperlukan untuk menjalankan website di hosting.

## File yang disimpan di GitHub

Repository berisi source code, aset bawaan, dokumentasi dan tes. Database lokal,
foto/video hasil upload, backup, rahasia lingkungan dan `node_modules` diabaikan
oleh `.gitignore`. Clone baru memulai instalasi dengan database kosong; konten
master dan unggahan yang sudah ada perlu dipindahkan melalui backup privat
terpisah jika ingin menyalin tampilan serta isi website saat ini.
