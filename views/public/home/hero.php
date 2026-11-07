<?php
// Slideshow beranda, slogan rumah sakit, dan tombol layanan utama.

$heroSlides = records('slide');
if (!$heroSlides) {
    $heroSlides = [
        [
            'id' => 0,
            'title' => 'Pelayanan dalam kasih',
            'subtitle' => 'RS Bintang Laut',
            'image' => setting('hero_image'),
            'link' => '',
        ],
    ];
}
?>
<section class="wide-hero" aria-label="Galeri rumah sakit">
    <div class="hospital-slideshow" data-slideshow role="region" aria-roledescription="carousel" aria-label="Foto dokter dan fasilitas">
        <div class="slide-stage">
            <?php foreach ($heroSlides as $i => $slide): ?>
                <?php $display =
                    json_decode(setting('slide_display_' . $slide['id'], '{}'), true) ?: []; ?>
            <figure class="hospital-slide <?= $i === 0 ? 'is-current' : '' ?>" data-slide <?= $i > 0
    ? 'hidden'
    : '' ?> aria-label="<?= $i + 1 . ' dari ' . count($heroSlides) ?>">
                <div class="slide-photo-frame">
                    <img data-slide-image data-display="<?= e(json_encode($display)) ?>" src="<?= e(
    $slide['image'] ? photo($slide['image']) : 'assets/branding/doctors.jpg',
) ?>" alt="<?= e($slide['title'] ?: 'Foto rumah sakit ' . ($i + 1)) ?>" <?= $i === 0
    ? 'fetchpriority="high"'
    : 'loading="lazy"' ?>>
                </div>
                <?php if (
                    $slide['title'] !== '' ||
                    $slide['subtitle'] !== '' ||
                    !empty($slide['link'])
                ): ?>
                <figcaption>
                    <?php if ($slide['subtitle'] !== ''): ?>
                    <span><?= e($slide['subtitle']) ?></span>
                    <?php endif; ?>
                            <?php if ($slide['title'] !== ''): ?>
                    <strong><?= e($slide['title']) ?></strong>
                    <?php endif; ?>
                            <?php if (!empty($slide['link'])): ?>
                    <a href="<?= e($slide['link']) ?>">Lihat selengkapnya &rarr;</a>
                    <?php endif; ?>
                </figcaption>
                <?php endif; ?>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php if (count($heroSlides) > 1): ?>
        <button class="slide-arrow slide-prev" data-prev aria-label="Foto sebelumnya">&lsaquo;</button>
        <button class="slide-arrow slide-next" data-next aria-label="Foto berikutnya">&rsaquo;</button>
        <div class="slide-toolbar">
            <div class="slide-dots" aria-label="Pilih foto">
                <?php foreach ($heroSlides as $i => $slide): ?>
                <button data-slide-to="<?= $i ?>" aria-label="Tampilkan foto <?= $i +
    1 ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <span class="sr-only" data-slide-status aria-live="polite">
        </span>
    </div>
    <div class="container hero-introduction">
        <div class="hero-motto">
            <h1><?= nl2br(e(setting('hero_title'))) ?></h1>
            <p class="hero-motto-translation"><?= e(setting('tagline')) ?></p>
        </div>
        <div class="hero-intro-description">
            <p><?= e(setting('hero_text')) ?></p>
            <div class="actions">
                <a class="button" href="index.php?page=register">Daftar Online &nearr;</a>
                <a class="button button-outline" href="index.php?page=doctors">Temukan Dokter &rarr;</a>
            </div>
        </div>
    </div>
</section>
