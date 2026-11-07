<?php
// Isi beranda: layanan pilihan, dokter, informasi, mitra, dan popup.

require APP_ROOT . '/views/public/home/hero.php'; ?>
<div class="container quick-links">
    <a href="index.php?page=doctors">
        <span class="quick-icon">✚</span>
        <div>
            <strong>Dokter & Jadwal</strong>
            <small>Rencanakan kunjungan Anda</small>
        </div>
        <span>↗</span>
    </a>
    <a href="index.php?page=services">
        <span class="quick-icon">♡</span>
        <div>
            <strong>Layanan Kami</strong>
            <small>Perawatan sesuai kebutuhan</small>
        </div>
        <span>↗</span>
    </a>
    <a href="index.php?page=contact">
        <span class="quick-icon">⌖</span>
        <div>
            <strong>Lokasi & Kontak</strong>
            <small>Kami lebih dekat dari Anda</small>
        </div>
        <span>↗</span>
    </a>
</div>
<section class="section container">
    <div class="section-heading">
        <div>
            <p class="eyebrow">LAYANAN KESEHATAN</p>
            <h2>
                Perawatan yang Anda butuhkan.
                <br>
                Perhatian yang Anda harapkan.
            </h2>
        </div>
        <a class="text-link" href="index.php?page=services">Lihat semua layanan ↗</a>
    </div>
    <div class="grid four">
        <?php foreach (array_slice(records('service'), 0, 4) as $i => $item): ?>
        <a class="service-card" href="index.php?page=detail&id=<?= $item['id'] ?>">
            <span class="service-number">0<?= $i + 1 ?></span>
            <div class="service-icon"><?= ['✚', '⌂', '♡', '✧'][$i] ?></div>
            <h3><?= e($item['title']) ?></h3>
            <p><?= e($item['body']) ?></p>
            <span class="card-link">Selengkapnya ↗</span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<section class="soft-section doctor-showcase" data-doctor-carousel aria-label="Kenali tim dokter kami">
    <div class="container section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">KENALI TIM KAMI</p>
                <h2>
                    Temukan dokter,
                    <br>
                    mulai langkah sehat Anda.
                </h2>
            </div>
            <div>
                <p>Informasi dokter dan jadwal dalam satu tempat.</p>
                <a class="text-link" href="index.php?page=doctors">Cari dokter & jadwal ↗</a>
            </div>
        </div>
        <div class="doctor-carousel-shell">
            <button class="doctor-carousel-arrow doctor-carousel-prev" type="button" data-doctor-prev aria-label="Lihat dokter sebelumnya" aria-controls="home-doctor-track" hidden>&larr;</button>
            <div class="doctor-carousel-track" id="home-doctor-track" tabindex="0" role="region" aria-label="Pilihan dokter"><?php foreach (
                query('SELECT * FROM doctors WHERE active=1 ORDER BY id')
                as $d
            ):
                require APP_ROOT . '/views/components/cards/doctor.php';
            endforeach; ?></div>
            <button class="doctor-carousel-arrow doctor-carousel-next" type="button" data-doctor-next aria-label="Lihat dokter berikutnya" aria-controls="home-doctor-track" hidden>&rarr;</button>
        </div>
        <div class="doctor-showcase-more">
            <a class="button doctor-more-button" href="index.php?page=doctors">Lihat Selengkapnya</a>
        </div>
    </div>
</section>
<section class="section container">
    <div class="feature-banner">
        <div>
            <p class="eyebrow">LEBIH MUDAH DARI MANA SAJA</p>
            <h2>
                Rencanakan kunjungan,
                <br>
                tanpa harus datang dulu.
            </h2>
            <p>
                Ajukan pendaftaran online. Petugas kami akan memverifikasi
                <br>
                jadwal dan mengonfirmasi kunjungan Anda.
            </p>
            <a class="button light" href="index.php?page=register">Mulai Pendaftaran ↗</a>
        </div>
        <span class="banner-art" aria-hidden="true">✦</span>
    </div>
</section>
<section class="section container compact-top">
    <div class="section-heading">
        <div>
            <p class="eyebrow">RUANG INFORMASI</p>
            <h2>Kabar baik untuk hidup lebih sehat.</h2>
        </div>
        <a class="text-link" href="index.php?page=news">Semua informasi ↗</a>
    </div>
    <div class="grid three"><?php foreach (
        array_slice(array_merge(records('news'), records('event'), records('promo')), 0, 3)
        as $i => $item
    ):
        require APP_ROOT . '/views/components/cards/article.php';
    endforeach; ?></div>
</section>
<?php $partners = array_filter(records('partner'), fn($partner) => $partner['image'] !== ''); ?>
<?php if ($partners): ?>
<section class="container section home-partners" aria-labelledby="partners-heading">
    <div class="partner-heading">
        <h2 id="partners-heading"><?= e(setting('partner_heading', 'Mitra & Penjamin')) ?></h2>
    </div>
    <?php if (setting('partner_subtitle') !== ''): ?>
    <p class="partner-subtitle"><?= e(setting('partner_subtitle')) ?></p>
    <?php endif; ?>
    <div class="partner-carousel-shell">
        <button class="partner-arrow partner-prev" type="button" aria-label="Mitra sebelumnya" aria-controls="partner-logos" hidden>
        </button>
        <div class="home-partner-logos" id="partner-logos" role="region" aria-label="Logo mitra" tabindex="0">
            <?php foreach ($partners as $partner): ?>
            <figure>
                <img src="<?= e(photo($partner['image'])) ?>" alt="<?= e(
    $partner['title'],
) ?>" loading="lazy">
            </figure>
            <?php endforeach; ?>
        </div>
        <button class="partner-arrow partner-next" type="button" aria-label="Mitra berikutnya" aria-controls="partner-logos" hidden>
        </button>
    </div>
</section>
<?php endif; ?>
<?php
$popups = records('popup');
$popup = null;
foreach ($popups as $candidate) {
    if (
        ($candidate['starts'] === '' || $candidate['starts'] <= date('Y-m-d')) &&
        ($candidate['ends'] === '' || $candidate['ends'] >= date('Y-m-d'))
    ) {
        $popup = $candidate;
        break;
    }
}
if ($popup && setting('popup_enabled') === '1'): ?>
<dialog id="promo-dialog" data-key="<?= e(hash('sha256', json_encode($popup))) ?>">
    <button class="dialog-close" aria-label="Tutup informasi">×</button>
    <?php if ($popup['image']): ?>
    <img class="popup-image" src="<?= e(photo($popup['image'])) ?>" alt="<?= e($popup['title']) ?>">
    <?php endif; ?>
    <p class="eyebrow"><?= e($popup['subtitle']) ?></p>
    <h2><?= e($popup['title']) ?></h2>
    <p><?= nl2br(e($popup['body'])) ?></p>
    <a class="button" href="<?= e(
        $popup['link'] ?: 'index.php?page=register',
    ) ?>">Selengkapnya ↗</a>
</dialog>
<?php endif;
 ?>
