<?php
// Master gambar laporan mutu, judul, dan status publikasinya.

$qualityEdit =
    query('SELECT * FROM contents WHERE id=? AND kind=?', [
        (int) ($_GET['edit'] ?? ($_POST['id'] ?? 0)),
        'quality',
    ])[0] ?? [];
if ($error && ($_POST['action'] ?? '') === 'content_save') {
    $qualityEdit = array_replace($qualityEdit, $_POST);
}
$qualityRows = query('SELECT * FROM contents WHERE kind=? ORDER BY id DESC', ['quality']);
?>
<form class="panel" method="post" enctype="multipart/form-data">
    <h2><?= empty($qualityEdit['id']) ? 'Tambah' : 'Edit' ?> laporan mutu</h2>
    <?= csrf() ?>
    <input type="hidden" name="action" value="content_save">
    <input type="hidden" name="id" value="<?= (int) ($qualityEdit['id'] ?? 0) ?>">
    <input type="hidden" name="active" value="1">
    <?php field('title', 'Judul laporan', $qualityEdit['title'] ?? ''); ?>
    <label>
        Gambar laporan (JPG / PNG / WebP, maks. 25 MB)
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" <?= empty(
            $qualityEdit['image']
        )
            ? 'required'
            : '' ?>>
    </label>
    <?php if (!empty($qualityEdit['image'])): ?>
    <img class="admin-quality-preview" src="<?= e(
        photo($qualityEdit['image']),
    ) ?>" alt="Gambar laporan saat ini">
    <?php endif; ?>
    <div class="actions">
        <button class="button">Simpan laporan</button>
        <a class="text-link" href="index.php?page=admin&amp;tab=contents&amp;kind=quality">Batal / tambah baru</a>
    </div>
</form>
<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Gambar</th>
                <th>Judul laporan</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($qualityRows as $report): ?>
            <tr>
                <td>
                    <?php if ($report['image'] !== ''): ?>
                    <img class="admin-partner-thumbnail" src="<?= e(
                        photo($report['image']),
                    ) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                </td>
                <td><?= e($report['title']) ?></td>
                <td>
                    <div class="actions">
                        <a class="text-link" href="index.php?page=admin&amp;tab=contents&amp;kind=quality&amp;edit=<?= (int) $report[
                            'id'
                        ] ?>">Edit</a>
                        <form method="post" data-confirm="Hapus laporan mutu ini?">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="content_delete">
                            <input type="hidden" name="id" value="<?= (int) $report['id'] ?>">
                            <button class="text-button danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
<?php if (!$qualityRows): ?>
            <tr>
                <td colspan="3">Belum ada laporan mutu.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
