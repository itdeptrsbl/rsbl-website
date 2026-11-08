<?php
// Master judul, isi, banyak foto, video, dan status publikasi informasi.

if ($error && ($_POST['action'] ?? '') === 'information_save') {
    $edit = $_POST;
    $edit['active'] = isset($_POST['active']) ? 1 : 0;
}
$storedItem =
    query('SELECT * FROM contents WHERE id=? AND kind=?', [(int) ($edit['id'] ?? 0), $kind])[0] ??
    null;
$informationImages = $storedItem ? informationPhotos($storedItem) : [];
?>
<form class="panel" method="post" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="action" value="information_save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <h2><?= empty($edit['id']) ? 'Tambah informasi' : 'Edit informasi' ?></h2>
    <?php field('title', 'Judul informasi', $edit['title'] ?? ''); ?>
    <h3>Foto sampul</h3>
    <label>
        Unggah foto sampul (JPG/PNG/WebP/AVIF, maksimal 25 MB)
        <input type="file" name="information_cover" accept="image/jpeg,image/png,image/webp,image/avif">
    </label>
    <p class="muted" data-information-cover-status role="status" aria-live="polite"></p>
    <?php $coverDisplay =
        $_POST['information_cover_display'] ??
        ($storedItem ? informationCoverDisplay((int) $storedItem['id']) : ['fit' => 'cover']); ?>
    <div class="information-cover-editor" data-information-cover data-room-adjust data-field-prefix="information_cover_display" data-display="<?= e(
        json_encode($coverDisplay),
    ) ?>" <?= empty($storedItem['image']) ? 'hidden' : '' ?>>
        <img src="<?= e(
            !empty($storedItem['image'])
                ? photo($storedItem['image'])
                : 'assets/room-placeholder.svg',
        ) ?>" alt="Foto sampul informasi tersimpan">
    </div>
    <?php if (($storedItem['image'] ?? '') !== ''): ?>
    <label class="check">
        <input type="checkbox" name="remove_information_cover" <?= isset(
            $_POST['remove_information_cover'],
        )
            ? 'checked'
            : '' ?>>
        Hapus foto sampul (unggahan baru akan menggantikannya)
    </label>
    <?php endif; ?>
    <div class="history-editor" data-history-editor>
        <label for="information-body">Isi informasi</label>
        <div class="history-editor-surface">
            <div class="history-toolbar" role="group" aria-label="Format isi informasi">
                <span class="history-toolbar-label">Paragraf</span>
                <button type="button" data-list="bullet" title="Daftar bullet" aria-label="Daftar bullet">&bull;</button>
                <button type="button" data-list="number" title="Daftar angka" aria-label="Daftar angka">1.</button>
                <button type="button" data-list="plain" title="Hapus format daftar" aria-label="Hapus format daftar">T</button>
            </div>
            <textarea id="information-body" name="body" rows="10" maxlength="50000" required><?= e(
                $edit['body'] ?? '',
            ) ?></textarea>
        </div>
    </div>
    <h3>Video informasi (opsional)</h3>
    <label>
        Unggah video MP4 / WebM, maksimal 50 MB
        <input type="file" name="information_video" accept="video/mp4,video/webm">
    </label>
    <?php $savedVideo = $storedItem ? informationVideo((int) $storedItem['id']) : ''; ?>
    <?php if ($savedVideo !== ''): ?>
    <video class="information-video" controls preload="metadata" playsinline src="<?= e(
        $savedVideo,
    ) ?>">Browser Anda tidak mendukung pemutaran video.</video>
    <label class="check">
        <input type="checkbox" name="remove_information_video">
        Hapus video tersimpan
    </label>
    <?php endif; ?>
    <h3>Foto-foto informasi</h3>
    <div class="inpatient-master-photos inpatient-adjustable-photos">
        <?php foreach ($informationImages as $photoIndex => $photo): ?>
        <div>
            <div data-room-adjust data-field-prefix="photo_display[<?= $photoIndex ?>]" data-display="<?= e(
    json_encode(
        $_POST['photo_display'][$photoIndex] ?? ($photo['display'] ?? ['fit' => 'contain']),
    ),
) ?>">
                <img src="<?= e(photo($photo['src'])) ?>" alt="Foto informasi <?= $photoIndex +
    1 ?>" draggable="false">
            </div>
            <label>
                Keterangan foto <?= $photoIndex + 1 ?>
                <input name="photo_captions[<?= $photoIndex ?>]" maxlength="255" value="<?= e(
    $_POST['photo_captions'][$photoIndex] ?? $photo['caption'],
) ?>">
            </label>
            <label class="check">
                <input type="checkbox" name="remove_photos[]" value="<?= $photoIndex ?>">
                Hapus foto
            </label>
        </div>
        <?php endforeach; ?>
    </div>
    <div data-room-upload-list><?php
    $uploadIndex = 0;
    require APP_ROOT . '/views/admin/components/upload-row.php';
    ?></div>
    <template data-room-upload-template><?php
    $uploadIndex = '__INDEX__';
    require APP_ROOT . '/views/admin/components/upload-row.php';
    ?></template>
    <script src="assets/js/admin/inpatient-photo-editor.js?v=<?= filemtime(
        APP_ROOT . '/assets/js/admin/inpatient-photo-editor.js',
    ) ?>" defer>
    </script>
    <label class="check">
        <input type="checkbox" name="active" <?= $edit['active'] ?? 1 ? 'checked' : '' ?>>
        Publikasikan informasi (hapus centang untuk draft)
    </label>
    <div class="actions">
        <button class="button">Simpan informasi</button>
        <a href="index.php?page=admin&amp;tab=<?= $tab === 'information'
            ? 'information'
            : 'contents' ?>&amp;kind=<?= e($kind) ?>">Batal / tambah baru</a>
    </div>
</form>
<div class="panel table-wrap">
    <h2>Daftar <?= e($kinds[$kind]) ?></h2>
    <table>
        <thead>
            <tr>
                <th>Judul</th>
                <th>Foto</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php $informationRows = query('SELECT * FROM contents WHERE kind=? ORDER BY id DESC', [
                $kind,
            ]); ?>
            <?php foreach ($informationRows as $row): ?>
            <tr>
                <td><?= e($row['title']) ?></td>
                <td><?= count(informationPhotos($row)) ?></td>
                <td><?= $row['active'] ? 'Aktif' : 'Draft' ?></td>
                <td>
                    <div class="actions">
                        <a href="index.php?page=admin&amp;tab=<?= $tab === 'information'
                            ? 'information'
                            : 'contents' ?>&amp;kind=<?= e($kind) ?>&amp;edit=<?= (int) $row[
    'id'
] ?>">Edit</a>
                        <?php if ($row['active']): ?>
                        <a href="index.php?page=detail&amp;id=<?= (int) $row[
                            'id'
                        ] ?>" target="_blank" rel="noopener">Lihat</a>
                        <?php endif; ?>
                        <form method="post" data-confirm="Hapus informasi ini?">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="content_delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="text-button danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$informationRows): ?>
            <tr>
                <td colspan="4">Belum ada informasi.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
