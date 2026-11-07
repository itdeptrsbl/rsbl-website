<?php
// Satu foto Rawat Jalan dengan ukuran dan posisi sesuai pengaturan master.

$display = json_decode(setting('slide_display_' . $photo['id'], '{}'), true) ?: []; ?>
<a class="outpatient-photo" href="<?= e(
    photo($photo['image']),
) ?>" target="_blank" rel="noopener" aria-label="Lihat foto <?= $i + 1 ?> ukuran penuh">
    <img data-slide-image data-display="<?= e(json_encode($display)) ?>" src="<?= e(
    photo($photo['image']),
) ?>" alt="<?= e($photo['title'] ?: 'Fasilitas Rawat Jalan ' . ($i + 1)) ?>" <?= $i > 0
    ? 'loading="lazy"'
    : '' ?>>
</a>
