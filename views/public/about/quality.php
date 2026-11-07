<?php
// Galeri laporan mutu beserta pilihan memperbesar dan mengunduh gambar.

$qualityReports = array_values(
    array_filter(records('quality'), fn($report) => $report['image'] !== ''),
); ?>
<div class="quality-page">
    <section class="container page-intro">
        <p class="eyebrow">TENTANG KAMI</p>
        <h1>Laporan Mutu</h1>
        <div class="tab-row" aria-label="Tentang rumah sakit">
            <a href="index.php?page=history">Sejarah</a>
            <a href="index.php?page=vision">Visi &amp; Misi</a>
            <a class="selected" href="index.php?page=quality" aria-current="page">Laporan Mutu</a>
        </div>
    </section>
    <section class="container quality-gallery" aria-label="Galeri laporan mutu">
        <div class="quality-gallery-heading">
            <div>
                <p class="eyebrow">MUTU PELAYANAN</p>
                <h2>Laporan Mutu RS Bintang Laut Tahun 2026</h2>
            </div>
            <p>Pilih laporan untuk melihat gambar lebih besar dan membaca rinciannya.</p>
        </div>
        <?php if (!$qualityReports): ?>
        <div class="panel pale">
            <p>Laporan mutu belum tersedia.</p>
        </div>
        <?php endif; ?>
        <div class="quality-grid">
            <?php foreach ($qualityReports as $index => $report): ?>
            <article class="quality-card">
                <a class="quality-report-link" href="<?= e(
                    photo($report['image']),
                ) ?>" data-quality-report data-report-title="<?= e(
    $report['title'],
) ?>" aria-label="<?= e('Perbesar laporan: ' . $report['title']) ?>">
                    <div class="quality-image">
                        <img src="<?= e(photo($report['image'])) ?>" alt="<?= e(
    $report['title'],
) ?>" loading="lazy">
                        <span class="quality-expand" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M9 4H4v5m11-5h5v5M4 15v5h5m6 0h5v-5"/>
                            </svg>
                        </span>
                    </div>
                    <div class="quality-card-copy">
                        <span class="quality-number" aria-hidden="true"><?= sprintf(
                            '%02d',
                            $index + 1,
                        ) ?></span>
                        <div>
                            <h3><?= e($report['title']) ?></h3>
                            <span class="quality-view">
                                Lihat laporan
                                <span aria-hidden="true">&nearr;</span>
                            </span>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php if ($qualityReports): ?>
    <dialog class="quality-dialog" aria-labelledby="quality-dialog-title">
        <div class="quality-dialog-heading">
            <h2 id="quality-dialog-title">Laporan mutu</h2>
            <button type="button" data-quality-close aria-label="Tutup laporan">&times;</button>
        </div>
        <div class="quality-dialog-actions">
            <button type="button" data-quality-zoom aria-pressed="false">Perbesar gambar</button>
            <a data-quality-download download>Unduh gambar</a>
        </div>
        <div class="quality-dialog-image">
            <img alt="">
        </div>
    </dialog>
    <?php endif; ?>
</div>
