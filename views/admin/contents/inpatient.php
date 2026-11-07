<?php
// Master pengantar Rawat Inap, jenis kamar, fasilitas, kapasitas, dan galeri.

$roomRows = query('SELECT * FROM contents WHERE kind=? ORDER BY sort_order,id', ['inpatient_room']);
$roomEdit = $edit;
if ($error && ($_POST['action'] ?? '') === 'inpatient_save') {
    $roomEdit = $_POST;
    $existingRoom =
        query('SELECT * FROM contents WHERE id=? AND kind=?', [
            (int) ($_POST['id'] ?? 0),
            'inpatient_room',
        ])[0] ?? [];
    $roomData = inpatientRoomData($existingRoom);
    $roomData = array_merge($roomData, [
        'description' => $_POST['description'] ?? '',
        'beds' => $_POST['beds'] ?? '',
        'cover_caption' => $_POST['cover_caption'] ?? '',
        'photo_captions' => $_POST['photo_captions'] ?? ($roomData['photo_captions'] ?? []),
        'photo_displays' => $_POST['photo_display'] ?? ($roomData['photo_displays'] ?? []),
        'facilities' => inpatientLines($_POST['facilities'] ?? ''),
    ]);
    $roomEdit['image'] = $existingRoom['image'] ?? '';
    $roomEdit['active'] = isset($_POST['active']) ? 1 : 0;
} else {
    $roomData = inpatientRoomData($roomEdit);
}
?>
<div class="notice">Kelola penjelasan halaman, daftar ruang perawatan, dan jenis kamar dari sini. Satu jenis kamar memiliki foto utama, galeri foto, kapasitas tempat tidur, dan daftar fasilitas. Data ketersediaan tempat tidur tidak ditampilkan otomatis.</div>
<form class="panel" method="post">
    <?= csrf() ?>
    <input type="hidden" name="action" value="inpatient_page">
    <h2>Pengaturan halaman Rawat Inap</h2>
    <label>
        Penjelasan singkat
        <textarea name="inpatient_intro" rows="4" maxlength="10000"><?= e(
            $_POST['inpatient_intro'] ??
                setting(
                    'inpatient_intro',
                    'Kenali pilihan ruang perawatan dan fasilitas untuk kenyamanan pasien serta keluarga. Hubungi petugas untuk informasi kamar dan persiapan rawat inap.',
                ),
        ) ?></textarea>
    </label>
    <label>
        Daftar ruang perawatan (satu nama per baris, opsional)
        <textarea name="inpatient_wards" rows="5" maxlength="10000" placeholder="Contoh: Ruang perawatan umum, ICU, Perinatologi"><?= e(
            $_POST['inpatient_wards'] ?? setting('inpatient_wards'),
        ) ?></textarea>
    </label>
    <button class="button">Simpan pengaturan halaman</button>
</form>
<div class="panel table-wrap">
    <div class="actions">
        <h2>Daftar jenis kamar</h2>
        <a class="button small" href="index.php?page=admin&amp;tab=contents&amp;kind=inpatient_room#room-form">+ Tambah jenis kamar</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Kamar</th>
                <th>Kapasitas per kamar</th>
                <th>Foto</th>
                <th>Urutan</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roomRows as $row): ?>
                <?php $rowData = inpatientRoomData($row); ?>
            <tr>
                <td><?= e($row['title']) ?></td>
                <td><?= !empty($rowData['beds'])
                    ? (int) $rowData['beds'] . ' tempat tidur'
                    : 'Tidak dicantumkan' ?></td>
                <td><?= ($row['image'] !== '' ? 1 : 0) + count($rowData['photos'] ?? []) ?></td>
                <td><?= (int) $row['sort_order'] ?></td>
                <td><?= $row['active'] ? 'Aktif' : 'Draft' ?></td>
                <td>
                    <div class="actions">
                        <a href="index.php?page=admin&amp;tab=contents&amp;kind=inpatient_room&amp;edit=<?= (int) $row[
                            'id'
                        ] ?>#room-form">Edit</a>
                        <form method="post" data-confirm="Hapus jenis kamar ini dari halaman Rawat Inap?">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="inpatient_delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="text-button danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$roomRows): ?>
            <tr>
                <td colspan="6">Belum ada jenis kamar. Tambahkan kamar dan foto asli rumah sakit melalui formulir di bawah.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<form class="panel" id="room-form" method="post" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="action" value="inpatient_save">
    <input type="hidden" name="id" value="<?= (int) ($roomEdit['id'] ?? 0) ?>">
    <input type="hidden" name="image" value="<?= e($roomEdit['image'] ?? '') ?>">
    <h2><?= empty($roomEdit['id']) ? 'Tambah jenis kamar' : 'Edit jenis kamar' ?></h2>
    <div class="form-grid">
        <?php field(
            'title',
            'Nama jenis kamar (contoh: VIP Suite, VIP, Kelas 1)',
            $roomEdit['title'] ?? '',
        ); ?>
        <label>
            Jumlah tempat tidur per kamar (opsional)
            <input type="number" name="beds" min="1" max="50" value="<?= e(
                $roomData['beds'] ?? '',
            ) ?>">
        </label>
    </div>
    <label>
        Deskripsi kamar (opsional)
        <textarea name="description" rows="3" maxlength="5000"><?= e(
            $roomData['description'] ?? '',
        ) ?></textarea>
    </label>
    <label>
        Fasilitas kamar (satu fasilitas per baris)
        <textarea name="facilities" rows="6" maxlength="10000" placeholder="Smart TV&#10;Kamar mandi&#10;Lemari penyimpanan"><?= e(
            implode(
                "
",
                $roomData['facilities'] ?? [],
            ),
        ) ?></textarea>
    </label>
    <label>
        Foto utama (JPG/PNG/WebP, maksimal 10 MB)
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
    </label>
    <?php field(
        'cover_caption',
        'Keterangan foto utama (contoh: Kamar Anak - Kelas 1)',
        $roomData['cover_caption'] ?? '',
        'text',
        false,
    ); ?>
    <p class="muted">Foto utama menjadi sampul jenis kamar. Unggah foto baru hanya jika ingin menggantinya.</p>
    <?php
    $edit = $roomEdit;
    require APP_ROOT . '/views/admin/components/photo-editor.php';
    ?>
    <h3>Tambah foto galeri</h3>
    <p class="muted">Klik + untuk menambahkan field foto baru. Setiap foto dapat diatur dan diberi keterangan sendiri, maksimal 10 MB per foto.</p>
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
    <p class="muted">Jumlah foto per kamar tidak dibatasi. Tambahkan foto bertahap dan simpan; foto lama tetap tersimpan. Maksimal 10 MB per foto. Centang foto yang ingin dihapus.</p>
    <div class="inpatient-master-photos inpatient-adjustable-photos">
        <?php foreach ($roomData['photos'] ?? [] as $photoIndex => $path): ?>
        <div>
            <div data-room-adjust data-field-prefix="photo_display[<?= $photoIndex ?>]" data-display="<?= e(
    json_encode($roomData['photo_displays'][$photoIndex] ?? ['fit' => 'contain']),
) ?>">
                <img src="<?= e(photo($path)) ?>" alt="Foto kamar <?= $photoIndex +
    1 ?>" draggable="false">
            </div>
            <label>
                Keterangan foto <?= $photoIndex + 1 ?>
                <input name="photo_captions[<?= $photoIndex ?>]" maxlength="255" value="<?= e(
    $roomData['photo_captions'][$photoIndex] ?? '',
) ?>" placeholder="Contoh: Kamar Anak - Kelas 2">
            </label>
            <label class="check">
                <input type="checkbox" name="remove_photos[]" value="<?= $photoIndex ?>">
                Hapus foto ini
            </label>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="form-grid">
        <label>
            Urutan tampil
            <input type="number" name="sort_order" min="1" value="<?= max(
                1,
                (int) ($roomEdit['sort_order'] ?? count($roomRows) + 1),
            ) ?>" required>
        </label>
    </div>
    <label class="check">
        <input type="checkbox" name="active" <?= $roomEdit['active'] ?? 1 ? 'checked' : '' ?>>
        Tampilkan jenis kamar
    </label>
    <div class="actions">
        <button class="button">Simpan jenis kamar</button>
        <a href="index.php?page=admin&amp;tab=contents&amp;kind=inpatient_room#room-form">Batal / tambah baru</a>
        <a href="index.php?page=inpatient" target="_blank" rel="noopener">Lihat halaman Rawat Inap</a>
    </div>
</form>
