<?php
// Form tambah/edit foto serta daftar foto untuk master layanan.

$photoLabels = [
    'outpatient_photo' => 'Rawat Jalan',
    'emergency_photo' => 'Instalasi Gawat Darurat',
    'service_slide' => 'Fasilitas dan Layanan',
];
foreach (facilityPhotoPages() as $facilityPage) {
    $photoLabels[$facilityPage['kind']] = $facilityPage['title'];
}
$photoKind = isset($photoLabels[$kind]) ? $kind : 'service_slide';
$photoLabel = $photoLabels[$photoKind];
?>
<div class="notice">Foto pada kategori ini tampil di galeri <?= e(
    $photoLabel,
) ?>. Cukup unggah foto, atur posisi, lalu simpan. <?= in_array(
    $photoKind,
    facilityPhotoKinds(),
    true,
)
    ? 'Foto urutan 1 menjadi foto utama; pengunjung dapat memilih foto lain melalui thumbnail atau panah.'
    : 'Tambahkan dua foto aktif atau lebih untuk pergantian otomatis.' ?></div>
<form class="panel" method="post" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="action" value="content_save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <h2><?= empty($edit['id']) ? 'Tambah foto fasilitas' : 'Edit foto fasilitas' ?></h2>
    <label>
        Foto fasilitas (JPG/PNG/WebP, maksimal 10 MB)
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" <?= empty(
            $edit['image']
        )
            ? 'required'
            : '' ?>>
    </label>
    <input type="hidden" name="image" value="<?= e($edit['image'] ?? '') ?>">
    <?php require APP_ROOT . '/views/admin/components/photo-editor.php'; ?>
    <div class="form-grid"><?php field(
        'title',
        'Keterangan foto (opsional, untuk aksesibilitas)',
        $edit['title'] ?? '',
        'text',
        false,
    ); ?>
        <?php field(
            'sort_order',
            in_array(
                $photoKind,
                array_merge(['outpatient_photo', 'emergency_photo'], facilityPhotoKinds()),
                true,
            )
                ? 'Urutan tampil (1 = foto utama paling besar)'
                : 'Urutan tampil',
            $edit['sort_order'] ?? 1,
            'number',
        ); ?></div>
    <label class="check">
        <input type="checkbox" name="active" <?= $edit['active'] ?? 1 ? 'checked' : '' ?>>
        Tampilkan foto
    </label>
    <div class="actions">
        <button class="button">Simpan foto</button>
        <a href="index.php?page=admin&amp;tab=contents&amp;kind=<?= e(
            $photoKind,
        ) ?>">Batal / tambah foto baru</a>
    </div>
</form>
<div class="panel table-wrap">
    <h2>Foto <?= e($photoLabel) ?></h2>
    <table>
        <thead>
            <tr>
                <th>Foto</th>
                <th>Keterangan</th>
                <th>Urutan</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $photoOrder = in_array(
                $photoKind,
                array_merge(['outpatient_photo', 'emergency_photo'], facilityPhotoKinds()),
                true,
            )
                ? 'CASE WHEN sort_order < 1 THEN 1 ELSE 0 END, sort_order, id DESC'
                : 'sort_order, id DESC';
            $photos = query('SELECT * FROM contents WHERE kind=? ORDER BY ' . $photoOrder, [
                $photoKind,
            ]);
            ?>
            <?php foreach ($photos as $row): ?>
            <tr>
                <td>
                    <img class="facility-master-thumb" src="<?= e(
                        photo($row['image']),
                    ) ?>" alt="<?= e($row['title'] ?: 'Foto fasilitas') ?>" width="120" height="60">
                </td>
                <td><?= e($row['title'] ?: 'Foto fasilitas') ?></td>
                <td><?= (int) $row['sort_order'] ?></td>
                <td><?= $row['active'] ? 'Aktif' : 'Draft' ?></td>
                <td>
                    <div class="actions">
                        <a href="index.php?page=admin&amp;tab=contents&amp;kind=<?= e(
                            $photoKind,
                        ) ?>&amp;edit=<?= (int) $row['id'] ?>">Edit</a>
                        <form method="post" data-confirm="Hapus foto fasilitas ini?">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="content_delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="text-button danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$photos): ?>
            <tr>
                <td colspan="5">Belum ada foto. Halaman menampilkan foto utama rumah sakit sementara.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
