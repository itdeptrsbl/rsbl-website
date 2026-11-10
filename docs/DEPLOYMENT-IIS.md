# Menjalankan website RSBL pada IIS

1. Aktifkan CGI dan IIS Management Console. Daftarkan `*.php` melalui
   FastCgiModule ke `C:\xampp\php\php-cgi.exe` yang sudah dipasang.
2. Salin proyek ke `C:\xampp\htdocs\rsbl-website`. Sertakan `web.config`
   di akar proyek. File ini berbeda dari `.htaccess` Apache dan wajib ikut.
3. Buat site RSBL-Website terpisah, binding HTTP 127.0.0.1 port 8081,
   host name kosong. Gunakan application pool khusus website, bukan pool BPJS.
4. Berikan identitas pool (misalnya `IIS AppPool\RSBL-Website`) izin
   Read & Execute pada proyek. Tambahkan Modify hanya untuk `storage` dan
   `assets\uploads`. SQLite memerlukan izin folder untuk journal database.
   Jangan memberikan Full Control kepada Everyone.
5. Pastikan php.ini PHP CGI mengaktifkan pdo_sqlite, mbstring, fileinfo,
   curl, openssl. Pastikan folder sesi dan temporary upload PHP dapat ditulis
   oleh identitas pool. `.user.ini` mengatur upload 50 MB dan POST 128 MB;
   request filtering IIS juga disesuaikan menjadi 128 MiB.
6. Buka `http://127.0.0.1:8081/` di server. Jika beranda belum dapat dibuka,
   coba `/index.php`. Jangan menjalankan installer ulang atau menghapus
   database ketika menerima error koneksi database.
7. Periksa `/storage/rsbl.sqlite`, `/config.php`, `/app/bootstrap.php`,
   `/views/layouts/header.php`, `/.git/config`, `/package.json`, dan
   `/assets/uploads/contoh.php`: semuanya harus ditolak (bukan HTTP 200).
   Pastikan foto, AVIF, CSS, JS dan video tetap dapat ditampilkan, login dan
   upload bekerja. Pengujian XML lokal tidak menggantikan pengujian IIS ini.
8. Jika error 500.19, catat kode error, Config File dan Config Source yang
   tampil lokal; jangan menghapus aturan perlindungan untuk mengatasinya.
   Beberapa bagian konfigurasi mungkin dikunci pada tingkat server.
9. Setelah uji lokal selesai, lanjutkan HTTPS/domain atau Cloudflare Tunnel.
   Cookie secure di config.php dan deteksi HTTPS origin/proxy perlu diverifikasi
   sesuai cara publikasi. Jangan expose aplikasi melalui Tunnel sebelum uji
   penolakan file internal, backup, dan akun pengelola diselesaikan.

`web.config` mewarisi handler PHP yang didaftarkan pada IIS; tidak memasang PHP
atau mengubah pengaturan global. Tidak memerlukan URL Rewrite karena navigasi
menggunakan query pada index.php. Jangan menerapkan config ini ke site BPJS.

Acuan Microsoft:
- https://learn.microsoft.com/en-us/iis/configuration/system.webserver/defaultdocument/
- https://learn.microsoft.com/en-us/iis/configuration/system.webserver/security/requestfiltering/
