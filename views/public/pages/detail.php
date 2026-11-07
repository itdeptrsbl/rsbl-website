<?php
// Menentukan jenis konten detail dan menangani konten yang tidak ditemukan.

$item =
    query('SELECT * FROM contents WHERE id=? AND active=1', [(int) ($_GET['id'] ?? 0)])[0] ?? null;
if (!$item) {
    http_response_code(404);
}
?>
<?php if ($item && in_array($item['kind'], informationKinds(), true)): ?>
<?php require APP_ROOT . '/views/public/information/detail.php'; ?>
<?php else: ?>
<section class="container section narrow">
    <a class="text-link" href="index.php">← Beranda</a>
    <p class="eyebrow"><?= e($item['subtitle'] ?? 'INFORMASI') ?></p>
    <h1><?= e($item['title'] ?? 'Halaman tidak ditemukan') ?></h1>
    <?php if ($item && $item['image']): ?>
    <img class="detail-image" src="<?= e(photo($item['image'])) ?>" alt="<?= e($item['title']) ?>">
    <?php endif; ?>
    <p class="article-body"><?= nl2br(e($item['body'] ?? 'Informasi tidak tersedia.')) ?></p>
    <?php if (!empty($item['link'])): ?>
    <a class="button" href="<?= e($item['link']) ?>">Informasi lebih lanjut ↗</a>
    <?php endif; ?>
</section>
<?php endif; ?>
