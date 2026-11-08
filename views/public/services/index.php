<?php
// Halaman utama fasilitas: galeri bertumpuk dan tujuh kategori layanan.

$facilitySlides = records('service_slide');
if (!$facilitySlides) {
    $facilitySlides = [
        [
            'id' => 0,
            'image' => setting('hero_image'),
            'title' => 'RS Bintang Laut',
        ],
    ];
}
?>
<section class="facilities-hero" aria-label="Galeri fasilitas dan layanan">
    <div class="facilities-hero-heading">
        <div class="container">
            <h1>Fasilitas dan Layanan</h1>
            <p>Hadir di setiap langkah perawatan Anda.</p>
        </div>
    </div>
    <div class="hospital-slideshow facilities-slideshow" data-facility-spotlight role="region" aria-roledescription="carousel" aria-label="Foto fasilitas rumah sakit">
        <div class="slide-stage">
            <?php foreach ($facilitySlides as $i => $slide): ?>
                <?php $display =
                    json_decode(setting('slide_display_' . $slide['id'], '{}'), true) ?: []; ?>
            <figure class="hospital-slide <?= $i === 0
                ? 'is-current'
                : '' ?>" data-spotlight-photo aria-label="<?= $i +
    1 .
    ' dari ' .
    count($facilitySlides) ?>">
                <div class="slide-photo-frame">
                    <img data-slide-image data-display="<?= e(json_encode($display)) ?>" src="<?= e(
    $slide['image'] ? photo($slide['image']) : 'assets/branding/doctors.jpg',
) ?>" alt="<?= e($slide['title'] ?: 'Fasilitas rumah sakit ' . ($i + 1)) ?>" <?= $i === 0
    ? 'fetchpriority="high"'
    : 'loading="lazy"' ?>>
                </div>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php if (count($facilitySlides) > 1): ?>
        <button class="slide-arrow slide-prev" data-prev aria-label="Foto sebelumnya">&lsaquo;</button>
        <button class="slide-arrow slide-next" data-next aria-label="Foto berikutnya">&rsaquo;</button>
        <div class="slide-toolbar">
            <div class="slide-dots" aria-label="Pilih foto fasilitas">
                <?php foreach ($facilitySlides as $i => $slide): ?>
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
</section>
<section class="container facilities-section" aria-labelledby="facilities-heading">
    <div class="facilities-intro">
        <div>
            <p class="eyebrow">UNTUK ANDA DAN KELUARGA</p>
            <h2 id="facilities-heading">
                Kenali fasilitas
                <br>
                dan layanan kami.
            </h2>
        </div>
        <p>Temukan pilihan layanan sesuai kebutuhan Anda. Hubungi petugas kami untuk informasi dan persiapan kunjungan.</p>
    </div>
    <div class="facilities-grid">
        <?php foreach (facilityCategories() as $slug => $category): ?>
        <article class="facility-card" id="<?= $slug ?>">
            <div class="facility-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="<?= e($category['icon']) ?>"/>
                </svg>
            </div>
            <p class="facility-caption"><?= e($category['caption']) ?></p>
            <h3>
                <a href="<?= e(facilityUrl($slug)) ?>"><?= e($category['title']) ?></a>
            </h3>
            <div class="facility-card-line" aria-hidden="true">
            </div>
        </article>
        <?php endforeach; ?>
        <aside class="facility-contact-card">
            <p class="eyebrow">KAMI SIAP MEMBANTU</p>
            <h3>
                Butuh informasi
                <br>
                lebih lanjut?
            </h3>
            <p>Diskusikan kebutuhan layanan Anda dengan petugas rumah sakit.</p>
            <a href="index.php?page=contact">
                Hubungi kami
                <span aria-hidden="true">&rarr;</span>
            </a>
        </aside>
    </div>
</section>
