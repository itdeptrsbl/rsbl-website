<?php
// Memilih formulir master konten sesuai kategori yang sedang dibuka.
?>
<div class="admin-content-context">
    <nav class="tab-row admin-section-tabs" aria-label="Bagian <?= e($modules[$tab]) ?>">
        <?php foreach ($contentGroups[$tab] as $key): ?>
        <a class="<?= $kind === $key ? 'selected' : '' ?>" href="index.php?page=admin&amp;tab=<?= e(
    $tab,
) ?>&amp;kind=<?= e($key) ?>" <?= $kind === $key ? 'aria-current="page"' : '' ?>><?= e(
    $kinds[$key],
) ?></a>
        <?php endforeach; ?>
    </nav>
</div>

<?php
$edit =
    query('SELECT * FROM contents WHERE id=? AND kind=?', [(int) ($_GET['edit'] ?? 0), $kind])[0] ??
    [];
if ($error && ($_POST['action'] ?? '') === 'content_save') {
    $edit = $_POST;
}
?>
<?php if (in_array($kind, informationKinds(), true)): ?>
<?php require APP_ROOT . '/views/admin/contents/information.php'; ?>
<?php elseif (in_array($kind, facilityPhotoKinds(), true)): ?>
<?php require APP_ROOT . '/views/admin/contents/facility-detail.php'; ?>
<?php elseif ($kind === 'emergency_photo'): ?>
<?php require APP_ROOT . '/views/admin/contents/emergency.php'; ?>
<?php elseif ($kind === 'inpatient_room'): ?>
<?php require APP_ROOT . '/views/admin/contents/inpatient.php'; ?>
<?php elseif ($kind === 'outpatient_photo'): ?>
<?php require APP_ROOT . '/views/admin/contents/outpatient.php'; ?>
<?php elseif ($kind === 'clinic'): ?>
<?php require APP_ROOT . '/views/admin/contents/clinic.php'; ?>
<?php elseif ($kind === 'service_slide'): ?>
<?php require APP_ROOT . '/views/admin/components/photo-gallery.php'; ?>
<?php elseif ($kind === 'history'): ?>
<?php require APP_ROOT . '/views/admin/contents/history.php'; ?>
<?php elseif ($kind === 'vision'): ?>
<?php require APP_ROOT . '/views/admin/contents/vision.php'; ?>
<?php elseif ($kind === 'quality'): ?>
<?php require APP_ROOT . '/views/admin/contents/quality.php'; ?>
<?php else: ?>
<?php if ($kind === 'partner'): ?>
<form class="panel" method="post">
    <?= csrf() ?>
    <input type="hidden" name="action" value="partner_heading">
    <?php field(
        'partner_heading',
        'Judul bagian mitra di beranda',
        $_POST['partner_heading'] ?? setting('partner_heading', 'Mitra & Penjamin'),
    ); ?>
    <label>
        Subjudul bagian mitra (opsional)
        <textarea name="partner_subtitle" rows="2" maxlength="1000" placeholder="Kami bekerja sama dengan berbagai perusahaan untuk memberikan kemudahan, keamanan, dan kenyamanan bagi Anda."><?= e(
            $_POST['partner_subtitle'] ?? setting('partner_subtitle'),
        ) ?></textarea>
    </label>
    <button class="button">Simpan judul dan subjudul</button>
</form>
<?php endif; ?>
<div class="panel">
    <h2><?= empty($edit['id']) ? 'Tambah' : 'Edit' ?> <?= e($kinds[$kind]) ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?= csrf() ?>
        <input type="hidden" name="action" value="content_save">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
        <?php if ($kind === 'history'): ?>
<?php require APP_ROOT . '/views/admin/components/history-editor.php'; ?>
<?php else: ?>
        <div class="form-grid"><?php
        field(
            'title',
            $kind === 'slide'
                ? 'Judul (opsional)'
                : ($kind === 'popup'
                    ? 'Nama promosi (untuk pengelola)'
                    : 'Judul'),
            $edit['title'] ?? '',
            'text',
            $kind !== 'slide',
        );
        if (!in_array($kind, ['partner', 'service', 'popup'], true)) {
            field(
                'subtitle',
                'Label / subjudul (opsional)',
                $edit['subtitle'] ?? '',
                'text',
                false,
            );
        }
        ?></div>
        <?php if ($kind === 'popup'): ?>
        <p class="muted">Popup menampilkan gambar promosi saja. Unggah poster yang sudah berisi tulisan; gambar tampil utuh tanpa dipotong. Tautan tujuan dan tanggal tayang bersifat opsional.</p>
        <?php if (!empty($edit['image'])): ?>
        <img class="popup-admin-preview" src="<?= e(
            photo($edit['image']),
        ) ?>" alt="Pratinjau poster promosi">
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($kind !== 'service'): ?>
        <label>
            Unggah gambar (JPG/PNG/WebP, maks. 25 MB)
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php endif; ?>
        <?php if ($kind === 'slide') {
            require APP_ROOT . '/views/admin/components/photo-editor.php';
        } ?>
<?php if (!in_array($kind, ['partner', 'slide', 'popup'], true)): ?>
        <label>
            Isi konten
            <textarea name="body" rows="5" maxlength="50000" <?= $kind === 'slide'
                ? ''
                : 'required' ?>><?= e($edit['body'] ?? '') ?></textarea>
        </label>
        <?php endif; ?>
        <div class="form-grid"><?php
        if ($kind !== 'service') {
            field('image', 'URL gambar HTTPS (opsional)', $edit['image'] ?? '', 'text', false);
        }
        if ($kind !== 'partner') {
            if ($kind !== 'service') {
                field('link', 'Tautan tujuan (opsional)', $edit['link'] ?? '', 'text', false);
            }
            if ($kind === 'popup') {
                field(
                    'starts',
                    'Mulai tayang (khusus popup)',
                    $edit['starts'] ?? '',
                    'date',
                    false,
                );
                field('ends', 'Akhir tayang (khusus popup)', $edit['ends'] ?? '', 'date', false);
            }
            field(
                'sort_order',
                'Urutan tampil (angka kecil di depan)',
                $edit['sort_order'] ?? 0,
                'number',
            );
        }
        ?></div>
        <label class="check">
            <input type="checkbox" name="active" <?= $edit['active'] ?? 1 ? 'checked' : '' ?>>
            Aktif / dipublikasikan
        </label>
        
        <?php endif; ?>
        <div class="actions">
            <button class="button">Simpan konten</button>
            <a href="index.php?page=admin&tab=<?= e($tab) ?>&kind=<?= e(
    $kind,
) ?>">Batal / tambah baru</a>
        </div>
    </form>
</div>
<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Judul</th>
                <th>Status</th>
                <?php if ($kind !== 'partner'): ?>
                <th>Urutan</th>
                <?php endif; ?>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (
                query('SELECT * FROM contents WHERE kind=? ORDER BY sort_order,id DESC', [$kind])
                as $row
            ): ?>
            <tr>
                <td>
                    <?= e($row['title']) ?>
<?php if ($kind === 'history'): ?>
                    <small><?= e(historyTypes()[historyType((int) $row['id'])]) ?></small>
                    <?php endif; ?>
<?php if ($kind === 'partner' && $row['image']): ?>
                    <img class="admin-partner-thumbnail" src="<?= e(
                        photo($row['image']),
                    ) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                </td>
                <td><?= $row['active'] ? 'Aktif' : 'Draft' ?></td>
                <?php if ($kind !== 'partner'): ?>
                <td><?= $row['sort_order'] ?></td>
                <?php endif; ?>
                <td>
                    <div class="actions">
                        <a href="index.php?page=admin&tab=<?= e($tab) ?>&kind=<?= e(
    $kind,
) ?>&edit=<?= $row['id'] ?>">Edit</a>
                        <form method="post" enctype="multipart/form-data" data-confirm="Hapus konten ini?">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="content_delete">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button class="text-button danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
