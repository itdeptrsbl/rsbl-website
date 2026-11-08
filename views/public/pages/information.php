<?php
// Semua kategori informasi memakai kartu dan halaman detail yang sama.
$informationCategories = [
    'news' => [
        'label' => 'Berita & Acara',
        'kinds' => ['news', 'event'],
        'empty' => 'Maaf, saat ini belum ada berita atau acara.',
    ],
    'promo' => [
        'label' => 'Promo',
        'kinds' => ['promo'],
        'empty' => 'Maaf, saat ini belum ada promo.',
    ],
    'career' => [
        'label' => 'Karier',
        'kinds' => ['career'],
        'empty' => 'Maaf, saat ini belum ada lowongan karier.',
    ],
];
$category = $_GET['category'] ?? 'news';
if (!is_string($category) || !isset($informationCategories[$category])) {
    $category = 'news';
}
$selectedCategory = $informationCategories[$category];
$placeholders = implode(',', array_fill(0, count($selectedCategory['kinds']), '?'));
$items = query(
    'SELECT * FROM contents WHERE active=1 AND kind IN (' . $placeholders . ') ORDER BY id DESC',
    $selectedCategory['kinds'],
);
?>
<section class="page-intro container">
    <p class="eyebrow">RUANG INFORMASI</p>
    <h1>Informasi untuk Anda.</h1>
    <p class="lead">Berita, kegiatan, promo layanan, dan peluang karier di rumah sakit.</p>
    <nav class="tab-row" aria-label="Kategori informasi">
        <?php foreach ($informationCategories as $key => $option): ?>
        <a href="index.php?page=news&amp;category=<?= e($key) ?>" class="<?= $category === $key
    ? 'selected'
    : '' ?>" <?= $category === $key ? 'aria-current="page"' : '' ?>><?= e($option['label']) ?></a>
        <?php endforeach; ?>
    </nav>
</section>
<section class="section compact-top container" aria-label="<?= e($selectedCategory['label']) ?>">
    <?php if ($category === 'career'): ?>
    <aside class="career-introduction" aria-labelledby="career-notice-title">
        <div class="career-notice-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Z"/><path d="m8 12 3 3 5-6"/></svg>
        </div>
        <div>
            <p class="career-notice-label">INFORMASI REKRUTMEN</p>
            <h2 id="career-notice-title">Waspadai penipuan rekrutmen.</h2>
            <p>Hati-hati terhadap pihak yang mengatasnamakan RS Bintang Laut. Pastikan informasi lowongan dan proses seleksi berasal dari kanal resmi rumah sakit.</p>
        </div>
    </aside>
    <?php endif; ?>
    <?php if ($items): ?>
    <div class="grid three">
        <?php foreach ($items as $i => $item): ?>
        <?php require APP_ROOT . '/views/components/cards/article.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="notice"><?= e($selectedCategory['empty']) ?></div>
    <?php endif; ?>
</section>
