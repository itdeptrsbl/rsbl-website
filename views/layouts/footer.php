<?php
// Footer publik: kontak, navigasi tambahan, dan tautan masuk pengelola.
?></main>
<footer>
    <div class="container footer-grid">
        <div>
            <a class="brand" href="index.php">
                <?php if (setting('logo_image')): ?>
                <img class="brand-logo" src="<?= e(
                    photo(setting('logo_image')),
                ) ?>" alt="Logo rumah sakit">
                <?php else: ?>
                <span class="brand-symbol">✦</span>
                <?php endif; ?>
                <span>
                    <?= e(setting('name')) ?>
                    <small>MELAYANI DENGAN SEMANGAT KASIH</small>
                </span>
            </a>
            <p>
                Hadir untuk kesehatan Anda
                <br>
                dan keluarga di setiap langkah.
            </p>
        </div>
        <div>
            <h4>Jelajahi</h4>
            <a href="index.php?page=about">Tentang Kami</a>
            <a href="index.php?page=doctors">Dokter & Jadwal</a>
            <a href="index.php?page=services">Layanan</a>
            <a href="index.php?page=news">Berita & Acara</a>
        </div>
        <div>
            <h4>Untuk Pasien</h4>
            <a href="index.php?page=register">Pendaftaran Online</a>
            <a href="index.php?page=contact">Hubungi Kami</a>
            <?php foreach (array_slice(records('page'), 0, 2) as $f): ?>
            <a href="index.php?page=detail&id=<?= $f['id'] ?>"><?= e($f['title']) ?></a>
            <?php endforeach; ?>
        </div>
        <div>
            <h4>Kunjungi Kami</h4>
            <p><?= e(setting('address')) ?></p>
            <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', setting('phone'))) ?>"><?= e(
    setting('phone'),
) ?></a>
            <a href="https://wa.me/<?= e(
                preg_replace('/\D/', '', setting('whatsapp')),
            ) ?>">WhatsApp ↗</a>
        </div>
    </div>
    <div class="container footer-partners">
        <strong>Mitra &amp; Penjamin:</strong>
        <?php foreach (records('partner') as $partner): ?>
        <a href="index.php?page=detail&id=<?= $partner['id'] ?>"><?= e($partner['title']) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> <?= e(setting('name')) ?>. Seluruh hak dilindungi.</span>
        <a href="index.php?page=login">Login Pengelola</a>
    </div>
</footer>
<a class="floating-help" href="https://wa.me/<?= e(
    preg_replace('/\D/', '', setting('whatsapp')),
) ?>" aria-label="Hubungi rumah sakit lewat WhatsApp">
    ☏
    <span>Butuh bantuan?</span>
</a>
</body>
</html>
