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
                <img class="brand-logo" src="assets/branding/hospital.png" alt="Logo RS Bintang Laut">
                <?php endif; ?>
                <span>
                    <?= e(setting('name')) ?>
                    <small>Palopo | Sulawesi Selatan</small>
                </span>
            </a>
            <p>Melayani dengan Semangat Kasih</p>
        </div>
        <div>
            <h4>Jelajahi</h4>
            <a href="index.php?page=about">Tentang Kami</a>
            <a href="index.php?page=doctors">Dokter & Jadwal</a>
            <a href="index.php?page=services">Layanan</a>
            <a href="index.php?page=news">Berita & Acara</a>
            <a href="index.php?page=news&amp;category=promo">Promo</a>
            <a href="index.php?page=news&amp;category=career">Karier</a>
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
            <?php $footerMapQuery = rawurlencode(setting('name') . ', ' . setting('address')); ?>
            <iframe class="footer-map" title="Peta lokasi RS Bintang Laut" src="https://www.google.com/maps?q=<?= e(
                $footerMapQuery,
            ) ?>&amp;output=embed" width="360" height="190" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            <a class="text-link" href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(
                $footerMapQuery,
            ) ?>" target="_blank" rel="noopener noreferrer">Buka di Google Maps &nearr;</a>
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
<div class="floating-actions" aria-label="Akses cepat pasien">
<a class="floating-register" href="index.php?page=register">Daftar Online &nearr;</a>
<a class="floating-help" href="https://wa.me/<?= e(
    preg_replace('/\D/', '', setting('whatsapp')),
) ?>" aria-label="Hubungi rumah sakit lewat WhatsApp">
    ☏
    <span>Butuh bantuan?</span>
</a>
</div>
</body>
</html>
