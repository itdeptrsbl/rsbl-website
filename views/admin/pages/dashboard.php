<?php
// Ringkasan jumlah data dan pintasan pekerjaan pengelola.
?>
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
        <a class="admin-shortcut" href="index.php?page=admin&tab=home_content&kind=slide">
            &#9639;
            <strong>Kelola slideshow beranda</strong>
        </a>
        <a class="admin-shortcut" href="index.php?page=admin&tab=home_content&kind=popup">
            ✦
            <strong>Ubah popup promo</strong>
        </a>
        <a class="admin-shortcut" href="index.php?page=admin&tab=doctors">
            ✚
            <strong>Perbarui jadwal dokter</strong>
        </a>
        <a class="admin-shortcut" href="index.php?page=admin&tab=appointments">
            ▤
            <strong>Periksa pendaftaran</strong>
        </a>
    </div>
</div>
