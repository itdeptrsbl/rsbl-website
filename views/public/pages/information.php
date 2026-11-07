<?php
// Daftar informasi yang dipublikasikan, ditampilkan dari yang terbaru.
?><section class="page-intro container">
    <p class="eyebrow">RUANG INFORMASI</p>
    <h1>Informasi untuk Anda.</h1>
    <p class="lead">Berita, kegiatan, dan informasi layanan rumah sakit.</p>
</section>
<section class="section compact-top container">
    <div class="grid three">
        <?php
        $items = query(
            'SELECT * FROM contents WHERE active=1 AND kind IN (?,?,?,?) ORDER BY id DESC',
            informationKinds(),
        );
        foreach ($items as $i => $item) {
            require APP_ROOT . '/views/components/cards/article.php';
        }
        ?>
<?php if (!$items): ?>
        <p>Belum ada informasi yang dipublikasikan.</p>
        <?php endif; ?>
    </div>
</section>
