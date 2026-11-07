<?php
// Memilih formulir master konten sesuai kategori yang sedang dibuka.

if (
    !in_array(
        $kind,
        array_merge(
            ['service_slide', 'outpatient_photo', 'clinic', 'inpatient_room', 'emergency_photo'],
            facilityPhotoKinds(),
            informationKinds(),
        ),
        true,
    )
): ?>
<div class="notice">
    Slideshow beranda: pilih kategori
    <strong>Slideshow Beranda</strong>
    , unggah foto lebar, isi judul dan urutan. Minimal dua slide aktif untuk menampilkan tombol navigasi dan pergantian otomatis. Rekomendasi foto: 1920 × 800 piksel; hindari menaruh tulisan pada foto.
</div>
<?php endif; ?>
<div class="tab-row">
    <?php foreach ($kinds as $key => $label): ?>
<?php if ($tab === 'information' && !in_array($key, informationKinds(), true)) {
    continue;
} ?>
    <a class="<?= $kind === $key
        ? 'selected'
        : '' ?>" href="index.php?page=admin&amp;tab=<?= $tab === 'information'
    ? 'information'
    : 'contents' ?>&amp;kind=<?= e($key) ?>"><?= $label ?></a>
    <?php endforeach; ?>
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
            $kind === 'slide' ? 'Judul (opsional)' : 'Judul',
            $edit['title'] ?? '',
            'text',
            $kind !== 'slide',
        );
        if ($kind !== 'partner') {
            field(
                'subtitle',
                'Label / subjudul (opsional)',
                $edit['subtitle'] ?? '',
                'text',
                false,
            );
        }
        ?></div>
        <label>
            Unggah gambar (JPG/PNG/WebP, maks. 10 MB)
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php if ($kind === 'slide') {
            require APP_ROOT . '/views/admin/components/photo-editor.php';
        } ?>
<?php if ($kind !== 'partner'): ?>
        <label>
            Isi konten
            <textarea name="body" rows="5" maxlength="50000" <?= $kind === 'slide'
                ? ''
                : 'required' ?>><?= e($edit['body'] ?? '') ?></textarea>
        </label>
        <?php endif; ?>
        <div class="form-grid"><?php
        field('image', 'URL gambar HTTPS (opsional)', $edit['image'] ?? '', 'text', false);
        if ($kind !== 'partner') {
            field('link', 'Tautan tujuan (opsional)', $edit['link'] ?? '', 'text', false);
            field('starts', 'Mulai tayang (khusus popup)', $edit['starts'] ?? '', 'date', false);
            field('ends', 'Akhir tayang (khusus popup)', $edit['ends'] ?? '', 'date', false);
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
        <p class="muted">Unggah gambar atau gunakan URL HTTPS. Unggahan baru diprioritaskan. Mitra aktif dengan gambar tampil di bagian paling bawah beranda. Periode tayang hanya berlaku untuk popup.</p>
        <?php endif; ?>
        <div class="actions">
            <button class="button">Simpan konten</button>
            <a href="index.php?page=admin&tab=contents&kind=<?= e($kind) ?>">Batal / tambah baru</a>
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
                        <a href="index.php?page=admin&tab=contents&kind=<?= e(
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
