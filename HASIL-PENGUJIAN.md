# Hasil pengujian setelah penataan folder

Tanggal: 7 November 2026.

- Pemeriksaan sintaks seluruh PHP aplikasi/template dan JavaScript lulus melalui `npm.cmd run check`.
- Format PHP, CSS, JavaScript, dan konfigurasi lulus melalui `npm.cmd run format:check`.
- Tes integrasi `tests/smoke.py` lulus memakai PHP XAMPP, PDO SQLite, mbstring, dan fileinfo.
- Tes menggunakan salinan sementara; database dan media proyek asli tidak diubah.

Cakupan: instalasi, login/logout, CSRF, halaman publik/admin, URL CSS/JS bertingkat, CRUD konten, slideshow, master layanan, Rawat Jalan/poli, Rawat Inap/kamar, IGD, sejarah, visi, laporan mutu, dokter/jadwal, formulir pendaftaran, foto, video, draft, dan penolakan akses internal.

Pengujian ini tidak mencakup MySQL atau inspeksi visual langsung pada browser. Lakukan review desktop dan ponsel di lingkungan tujuan setelah deploy. Aplikasi tidak memiliki integrasi SIMRS atau pengiriman notifikasi otomatis.
