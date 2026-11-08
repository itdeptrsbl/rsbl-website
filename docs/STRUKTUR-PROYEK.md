# Struktur dan panduan pengembangan

Aplikasi memakai PHP native dan PDO. Tidak ada framework runtime, Composer, atau build frontend wajib. Node/Prettier dan Python/Black hanya dipakai untuk pengembangan.

## Susunan folder

```text
index.php                     Pintu masuk dan daftar route publik
config.php                    Konfigurasi koneksi, zona waktu, cookie
router.php                    Router server PHP bawaan dan whitelist aset
app/
  bootstrap.php               Inisialisasi APP_ROOT, sesi, header, database
  config/admin.php            Daftar modul admin dan kategori konten
  controllers/                Alur permintaan admin dan pendaftaran
  actions/admin/              Satu file untuk tiap aksi POST admin
  modules/                    Aturan bisnis per fitur
  support/                    Helper bersama dan identitas rumah sakit
  database/                   Skema dan data contoh instalasi
views/
  layouts/                    Header dan footer publik
  auth/                       Login dan instalasi
  public/
    dispatch.php              Pemilih template halaman publik
    pages/                    Halaman umum (beranda, kontak, pendaftaran, informasi)
    home/                     Komponen khusus beranda
    doctors/                  Daftar, jadwal, dan profil dokter
    services/                 Fasilitas, Rawat Jalan/Inap, IGD, layanan lain
    information/              Detail informasi dan galeri media
    about/                    Sejarah, visi, dan laporan mutu
  admin/
    layout.php                Kerangka panel dan navigasi
    pages/                    Ringkasan, pengaturan, konten, dokter, dll.
    contents/                 Form master menurut jenis konten
    components/               Editor foto, field unggah, dan sesi jadwal
  components/                 Kartu dan bagian tampilan yang digunakan ulang
assets/
  css/base.css                Aturan dasar dan komponen umum
  css/layout/                 Header dan ikon sosial
  css/pages/                  Aturan khusus halaman
  css/admin/                  Aturan editor dan panel pengelola
  css/components/             Komponen foto bersama
  css/archive/                Versi tampilan lama yang tidak dimuat
  js/core/                    Interaksi dasar
  js/layout/                  Menu navigasi
  js/pages/                   Perilaku halaman khusus
  js/admin/                   Editor dan isian dinamis pada master
  js/components/              Slideshow, galeri, carousel, foto
  uploads/                    Foto/video unggahan; URL lama dipertahankan
  branding/                   Logo dan media identitas
storage/                      Database SQLite dan snapshot kode sebelum penataan
tests/                       Tes integrasi terisolasi
tools/check.mjs              Pemeriksaan sintaks PHP dan JavaScript
```

Media statis lama di akar `assets/` sengaja dipertahankan agar URL yang sudah digunakan tidak putus. Media baru yang diunggah tetap disimpan di `assets/uploads/`. Penataan ini tidak memigrasikan atau mengganti data database.

## Alur permintaan

1. `index.php` memuat `app/bootstrap.php` dan memeriksa route yang diizinkan.
2. Bootstrap mendefinisikan `APP_ROOT`, memulai sesi, membuka koneksi, dan memuat helper.
3. Modul fitur dimuat oleh `index.php` sebelum controller/template memerlukannya.
4. Permintaan admin melewati `adminOnly()` dan `verifyCsrf()` sebelum aksi penyimpanan.
5. `app/controllers/admin.php` memilih file di `app/actions/admin/`, lalu menangani redirect dan error.
6. Template dipilih melalui dispatcher publik atau kerangka admin. Include file menggunakan `APP_ROOT`, bukan bergantung pada kedalaman folder.

## Lokasi perubahan yang paling sering

| Kebutuhan | Logika/data | Tampilan |
|---|---|---|
| Nama tab/kategori master | `app/config/admin.php` | `views/admin/layout.php` |
| Header/menu | `app/modules/services.php` untuk menu layanan | `views/layouts/header.php`, `assets/css/layout/header.css` |
| Slideshow/foto bersama | `app/actions/admin/content-save.php` | `views/admin/components/photo-editor.php`, `assets/js/components/slide-display.js` |
| Dokter dan jadwal | `app/modules/doctors.php`, `app/actions/admin/doctor-save.php` | `views/public/doctors/index.php`, `views/admin/pages/doctors.php` |
| Rawat Jalan/poli | `app/modules/services.php`, aksi konten admin | `views/public/services/outpatient.php`, `views/admin/contents/clinic.php` |
| Rawat Inap | `app/modules/inpatient.php` | `views/public/services/inpatient.php`, `views/admin/contents/inpatient.php` |
| IGD | `app/modules/emergency.php` | `views/public/services/emergency.php`, `views/admin/contents/emergency.php` |
| Farmasi/Radiologi/Fisioterapi/Lab | `app/modules/services.php` | `views/public/services/detail.php`, `views/admin/contents/facility-detail.php` |
| Informasi dan video | `app/modules/information.php` | `views/admin/contents/information.php`, `views/public/information/detail.php` |
| Pendaftaran | `app/controllers/registration.php` | `views/public/pages/registration.php` |
| Instalasi database | `app/database/schema.php`, `app/database/seed.php` | `views/auth/install.php` |

## Aturan penambahan fitur

- Letakkan validasi dan operasi database dalam modul/aksi, bukan di JavaScript. Query tampilan sederhana masih ada pada beberapa template warisan.
- Pertahankan autentikasi dan CSRF pada semua perubahan admin.
- Escape isi database dengan `e()` saat dirender. Konten editor saat ini disimpan sebagai teks, bukan HTML bebas.
- Jangan membentuk path include dari input pengguna. Gunakan daftar route/kategori yang diketahui.
- Untuk fitur baru, tambahkan route pada `index.php`, modul pada `app/modules/`, template pada folder fitur, dan aset pada folder terkait.
- Saat memindahkan aset, perbarui URL HTML, `filemtime()`, referensi JS/CSS, serta pengujian.
- Pertahankan urutan stylesheet yang sudah ada: CSS dasar, header/halaman, lalu komponen foto. Jangan menggabungkan aturan override tanpa pemeriksaan tampilan.
- Aset `header-layout.css` dalam folder archive adalah percobaan lama; tidak dimuat pada halaman aktif.

## Format dan verifikasi

```powershell
npm.cmd ci
npm.cmd run format
npm.cmd run format:check
npm.cmd run check
```

`PHP_BINARY` dapat diarahkan ke PHP XAMPP bila perintah `php` belum tersedia di PATH:

```powershell
$env:PHP_BINARY = 'C:\xampp\php\php.exe'
npm.cmd run check
```

Tes integrasi dijalankan pada salinan sementara dengan database kosong; database situs asli tidak disentuh:

```powershell
python tests/smoke.py --php C:\xampp\php\php.exe
```

Jika ekstensi PHP belum aktif:

```powershell
python tests/smoke.py --php C:\xampp\php\php.exe --extension C:\xampp\php\ext\php_pdo_sqlite.dll --extension C:\xampp\php\ext\php_mbstring.dll --extension C:\xampp\php\ext\php_fileinfo.dll
```

Tes juga memeriksa aset pada folder bertingkat, proteksi URL internal, login, editor, unggahan media, profil/jadwal dokter, pendaftaran, dan status publikasi. Validasi MySQL serta pemeriksaan visual desktop/ponsel tetap dilakukan di lingkungan tujuan.

Python dapat dirapikan memakai Black dari `requirements-dev.txt`: `python -m black tests/`. Konfigurasi indentasi umum ada di `.editorconfig`, PHP/CSS/JS di `.prettierrc.json`.

## Catatan di dalam kode

Setiap file kode aplikasi memiliki catatan singkat tentang perannya. Fungsi PHP/JavaScript dan helper tes diberi komentar tujuan; bagian validasi, penyimpanan, unggahan, serta CSS responsif diberi penanda seperlunya.

Komentar memakai `//` pada PHP/JavaScript, `/* ... */` pada CSS, dan `#` pada Python atau konfigurasi yang mendukungnya. Komentar template PHP tidak dicetak ke tampilan website. File JSON tidak diberi komentar karena sintaks JSON melarangnya: `package.json` menyimpan alat pengembangan dan perintah npm; `.prettierrc.json` mengatur formatter; `package-lock.json` mengunci versi dependency; `docs/file-migration-map.json` mencatat pemindahan file. Library di `node_modules/`, database, dan media unggahan tidak disunting untuk penambahan catatan ini.


## Susunan panel pengelola

Menu konten mengikuti bagian website: Beranda (slideshow, kartu layanan, mitra, popup), Fasilitas & Layanan (galeri, Rawat Jalan, poli, Rawat Inap, IGD, layanan penunjang), Tentang Rumah Sakit (sejarah, visi/misi, mutu), dan Informasi (berita, acara, promo, karier). Setiap kategori dikelola di satu kelompok. Halaman Tambahan hanya ditampilkan di sidebar bila ada data halaman mandiri. Konfigurasi kelompok berada di `app/config/admin.php`. Tautan lama `tab=contents&kind=...` tetap ditangani controller agar operasi simpan dan bookmark lama tetap berfungsi.
