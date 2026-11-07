<?php
// Ringkasan jumlah data dan pintasan pekerjaan pengelola.
?><p>Selamat datang. Kelola informasi rumah sakit dan permohonan pasien dari sini.</p>
<div class="grid three stats">
    <?php foreach (
        [
            [
                'Permohonan masuk',
                (int) $db->query('SELECT COUNT(*) FROM appointments')->fetchColumn(),
            ],
            [
                'Menunggu verifikasi',
                (int) $db
                    ->query("SELECT COUNT(*) FROM appointments WHERE status='Menunggu verifikasi'")
                    ->fetchColumn(),
            ],
            [
                'Konten aktif',
                (int) $db->query('SELECT COUNT(*) FROM contents WHERE active=1')->fetchColumn(),
            ],
        ]
        as $stat
    ): ?>
    <div class="panel">
        <p><?= e($stat[0]) ?></p>
        <strong><?= $stat[1] ?></strong>
    </div>
    <?php endforeach; ?>
</div>
<div class="panel">
    <h2>Mulai mengelola</h2>
    <div class="grid three">
        <a class="admin-shortcut" href="index.php?page=admin&tab=contents&kind=slide">
            &#9639;
            <strong>Kelola slideshow beranda</strong>
            <small>Unggah foto dan atur urutan &rarr;</small>
        </a>
        <a class="admin-shortcut" href="index.php?page=admin&tab=contents&kind=popup">
            ✦
            <strong>Ubah popup promo</strong>
            <small>Atur isi dan periode tayang →</small>
        </a>
        <a class="admin-shortcut" href="index.php?page=admin&tab=doctors">
            ✚
            <strong>Perbarui jadwal dokter</strong>
            <small>Kelola hari dan jam praktik →</small>
        </a>
        <a class="admin-shortcut" href="index.php?page=admin&tab=appointments">
            ▤
            <strong>Periksa pendaftaran</strong>
            <small>Verifikasi permohonan masuk →</small>
        </a>
    </div>
</div>
<div class="notice">Sebelum dipublikasikan: ganti data contoh, verifikasi kontak dan jadwal, gunakan HTTPS, serta siapkan pencadangan database. Pendaftaran belum terhubung ke Medinfras.</div>
