<?php
// Kartu ringkasan informasi: sampul, judul, cuplikan, dan tautan detail.

$isInformation = in_array($item['kind'] ?? '', informationKinds(), true);
$cardPhotos = $isInformation ? informationPhotos($item) : [];
$coverImage = $item['image'];
$coverDisplay = $isInformation
    ? informationCoverDisplay((int) $item['id'])
    : ['fit' => 'cover', 'zoom' => 100, 'x' => 50, 'y' => 50];
?>
<article class="article-card">
    <a href="index.php?page=detail&amp;id=<?= (int) $item['id'] ?>">
        <div class="article-image tone-<?= $i % 3 ?>">
            <?php if ($coverImage !== ''): ?>
            <img data-slide-image data-display="<?= e(json_encode($coverDisplay)) ?>" src="<?= e(
    photo($coverImage),
) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
            <?php else: ?>
            <div class="information-no-photo" aria-hidden="true">Informasi Rumah Sakit</div>
            <?php endif; ?>
            <?php if (count($cardPhotos) > 1): ?>
            <span class="information-photo-count"><?= count($cardPhotos) ?> foto</span>
            <?php endif; ?>
        </div>
        <div class="article-content">
            <?php if ($item['subtitle'] !== ''): ?>
            <p class="eyebrow"><?= e($item['subtitle']) ?></p>
            <?php endif; ?>
            <h3><?= e($item['title']) ?></h3>
            <p><?= e(mb_substr($item['body'], 0, 110)) ?>&hellip;</p>
            <span class="text-link">Baca selengkapnya &rarr;</span>
        </div>
    </a>
</article>
