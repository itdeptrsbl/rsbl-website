<?php
// Halaman lengkap informasi dengan isi, video, dan galeri foto.

$informationImages = informationPhotos($item); ?>
<section class="container section narrow">
    <a class="text-link" href="index.php?page=news">&larr; Semua informasi</a>
    <p class="eyebrow">INFORMASI RUMAH SAKIT</p>
    <h1><?= e($item['title']) ?></h1>
    <div class="article-body information-body"><?= doctorHistory($item['body']) ?></div>
    <?php $video = informationVideo((int) $item['id']); ?>
    <?php if ($video !== ''): ?>
    <section class="information-video-section" aria-label="Video informasi">
        <video class="information-video" controls preload="metadata" playsinline src="<?= e(
            $video,
        ) ?>">
            Browser Anda tidak mendukung pemutaran video.
            <a href="<?= e($video) ?>">Buka video</a>
            .
        </video>
    </section>
    <?php endif; ?>
    <?php if ($informationImages): ?>
        <?php $first = $informationImages[0]; ?>
    <section class="facility-detail-gallery" data-room-gallery aria-label="Foto informasi">
        <div class="facility-detail-main">
            <a data-room-main-link href="<?= e(
                photo($first['src']),
            ) ?>" target="_blank" rel="noopener" aria-label="Lihat foto ukuran penuh">
                <img data-room-main data-slide-image data-display="<?= e(
                    json_encode($first['display'] ?? ['fit' => 'contain']),
                ) ?>" src="<?= e(photo($first['src'])) ?>" alt="<?= e(
    $first['caption'] ?: $item['title'],
) ?>">
            </a>
            <?php if (count($informationImages) > 1): ?>
            <button type="button" class="facility-detail-arrow gallery-prev" data-room-prev aria-label="Foto sebelumnya">&lsaquo;</button>
            <button type="button" class="facility-detail-arrow gallery-next" data-room-next aria-label="Foto berikutnya">&rsaquo;</button>
            <?php endif; ?>
        </div>
        <p class="facility-detail-caption" data-room-caption <?= $first['caption'] === ''
            ? 'hidden'
            : '' ?>><?= e($first['caption']) ?></p>
        <?php if (count($informationImages) > 1): ?>
        <div class="facility-detail-thumbs" role="group" aria-label="Pilih foto informasi">
            <?php foreach ($informationImages as $i => $image): ?>
            <button type="button" data-room-photo data-src="<?= e(
                photo($image['src']),
            ) ?>" data-caption="<?= e($image['caption']) ?>" data-display="<?= e(
    json_encode($image['display'] ?? ['fit' => 'contain']),
) ?>" aria-label="Foto <?= $i + 1 ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                <img src="<?= e(photo($image['src'])) ?>" alt="" loading="lazy">
            </button>
            <?php endforeach; ?>
        </div>
        <p class="facility-detail-count" data-room-status aria-live="polite">Foto 1 dari <?= count(
            $informationImages,
        ) ?></p>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    <?php if ($item['link'] !== ''): ?>
    <a class="button" href="<?= e($item['link']) ?>">Informasi lebih lanjut &rarr;</a>
    <?php endif; ?>
</section>
