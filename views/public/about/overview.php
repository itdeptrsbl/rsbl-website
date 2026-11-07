<?php
// Template ringkasan Tentang Kami yang disimpan untuk kebutuhan halaman umum.

$aboutPages = ['history' => 'Sejarah', 'vision' => 'Visi & Misi', 'quality' => 'Laporan Mutu'];
$aboutContent = records($page);
?>
<section class="container page-intro">
    <p class="eyebrow">TENTANG KAMI</p>
    <h1><?= e($aboutPages[$page]) ?></h1>
    <div class="tab-row" aria-label="Tentang rumah sakit">
        <?php foreach ($aboutPages as $slug => $label): ?>
        <a class="<?= $page === $slug
            ? 'selected'
            : '' ?>" href="index.php?page=<?= $slug ?>" <?= $page === $slug
    ? 'aria-current="page"'
    : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</section>
<section class="container section compact-top narrow">
    <?php if (!$aboutContent): ?>
    <div class="panel pale">
        <p>Informasi <?= e(strtolower($aboutPages[$page])) ?> belum tersedia.</p>
    </div>
    <?php endif; ?>
    <?php foreach ($aboutContent as $item): ?>
    <article class="panel">
        <?php if ($item['subtitle'] !== ''): ?>
        <p class="eyebrow"><?= e($item['subtitle']) ?></p>
        <?php endif; ?>
        <h2><?= e($item['title']) ?></h2>
        <?php if ($item['image'] !== ''): ?>
        <img class="detail-image" src="<?= e(photo($item['image'])) ?>" alt="<?= e(
    $item['title'],
) ?>" loading="lazy">
        <?php endif; ?>
        <div class="doctor-history-text"><?= doctorHistory($item['body']) ?></div>
        <?php if ($item['link'] !== ''): ?>
        <a class="text-link" href="<?= e($item['link']) ?>">Lihat informasi selengkapnya &rarr;</a>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</section>
