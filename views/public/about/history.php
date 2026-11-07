<?php
// Sejarah rumah sakit, video, daftar pemimpin, dan struktur organisasi.

$historyItems = records('history');
$historyVideoItem = null;
$organizationItem = null;
$historyLeaders = [];
// Video selalu di atas; semua pemimpin dikumpulkan dalam satu bagian.
foreach (array_reverse($historyItems) as $item) {
    if (historyType((int) $item['id']) === 'video' && !$historyVideoItem) {
        $historyVideoItem = $item;
    }
    if (historyType((int) $item['id']) === 'organization' && !$organizationItem) {
        $organizationItem = $item;
    }
}
foreach ($historyItems as $item) {
    if (historyType((int) $item['id']) === 'leader') {
        $historyLeaders[] = $item;
    }
}
usort($historyLeaders, fn($a, $b) => strcmp($a['starts'], $b['starts']) ?: $a['id'] <=> $b['id']);
function historyDateLabel(string $date): string
{
    if (preg_match('/^[1-9][0-9]{3}$/', $date)) {
        return $date;
    }
    if ($date === '' || !validDate($date)) {
        return '';
    }
    $months = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
    ];
    return (int) substr($date, 8, 2) .
        ' ' .
        $months[(int) substr($date, 5, 2)] .
        ' ' .
        substr($date, 0, 4);
}
?>
<div class="history-page">
    <section class="container page-intro history-intro">
        <p class="eyebrow">TENTANG KAMI</p>
        <h1>Sejarah</h1>
        <div class="tab-row" aria-label="Tentang rumah sakit">
            <a class="selected" href="index.php?page=history" aria-current="page">Sejarah</a>
            <a href="index.php?page=vision">Visi &amp; Misi</a>
            <a href="index.php?page=quality">Laporan Mutu</a>
        </div>
    </section>
    <div class="container history-content">
        <?php if ($historyVideoItem): ?>
    <?php $video = historyVideo($historyVideoItem['link']); ?>
        <section class="history-block history-video-section" aria-label="Video sejarah rumah sakit">
            <?php if (($video['type'] ?? '') === 'youtube'): ?>
            <?php $videoId = basename(parse_url($video['url'], PHP_URL_PATH)); ?>
            <iframe class="history-video" src="<?= e(
                $video['url'] . '?autoplay=1&mute=1&loop=1&playlist=' . rawurlencode($videoId),
            ) ?>" title="Video sejarah rumah sakit" loading="lazy" allow="autoplay; fullscreen; encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
            </iframe>
            <?php elseif (($video['type'] ?? '') === 'file'): ?>
            <video class="history-video" controls autoplay muted loop playsinline preload="metadata">
                <source src="<?= e($video['url']) ?>">
                Browser Anda tidak mendukung pemutaran video.
            </video>
            <?php endif; ?>
        <?php if ($historyVideoItem['body'] !== ''): ?>
            <div class="history-copy doctor-history-text"><?= doctorHistory(
                $historyVideoItem['body'],
            ) ?></div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
<?php if ($historyLeaders): ?>
        <section class="history-block" aria-labelledby="history-leaders-heading">
            <h2 class="history-section-title" id="history-leaders-heading">Kepemimpinan</h2>
            <div class="history-leaders">
                <?php foreach ($historyLeaders as $leader): ?>
            <?php
            $photoSize = (int) setting('history_leader_zoom_' . (int) $leader['id'], '100');
            if (!in_array($photoSize, range(100, 200, 10), true)) {
                $photoSize = 100;
            }
            ?>
                <article class="history-leader">
                    <?php
                    $photoX = (int) setting('history_leader_x_' . (int) $leader['id'], '50');
                    $photoY = (int) setting('history_leader_y_' . (int) $leader['id'], '30');
                    ?>
                <?php if ($leader['image'] !== ''): ?>
                    <div class="leader-photo-frame leader-zoom-<?= $photoSize ?> leader-x-<?= $photoX ?> leader-y-<?= $photoY ?>">
                        <img src="<?= e(photo($leader['image'])) ?>" alt="<?= e(
    $leader['title'],
) ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <h3><?= e($leader['title']) ?></h3>
                    <?php if ($leader['subtitle'] !== ''): ?>
                    <p class="history-leader-role"><?= nl2br(e($leader['subtitle'])) ?></p>
                    <?php endif; ?>
                <?php if ($leader['starts'] !== ''): ?>
                    <p class="history-leader-tenure"><?= e(
                        historyDateLabel($leader['starts']),
                    ) ?> &ndash; <?= e(
     $leader['ends'] !== '' ? historyDateLabel($leader['ends']) : 'Sekarang',
 ) ?></p>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
<?php if (
    $organizationItem &&
    ($organizationItem['image'] !== '' || $organizationItem['body'] !== '')
): ?>
        <section class="history-block history-organization">
            <h2 class="history-section-title">Struktur Organisasi PT</h2>
            <?php
            $organizationWidth = (int) setting(
                'history_width_' . (int) $organizationItem['id'],
                '100',
            );
            if (!in_array($organizationWidth, range(20, 100, 10), true)) {
                $organizationWidth = 100;
            }
            ?>
        <?php if ($organizationItem['image'] !== ''): ?>
            <img class="history-image history-width-<?= $organizationWidth ?>" src="<?= e(
    photo($organizationItem['image']),
) ?>" alt="Struktur organisasi rumah sakit" loading="lazy">
            <?php endif; ?>
        <?php if ($organizationItem['body'] !== ''): ?>
            <div class="history-copy doctor-history-text"><?= doctorHistory(
                $organizationItem['body'],
            ) ?></div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
<?php if (!$historyVideoItem && !$historyLeaders && !$organizationItem): ?>
        <div class="panel pale">
            <p>Informasi sejarah belum tersedia.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
