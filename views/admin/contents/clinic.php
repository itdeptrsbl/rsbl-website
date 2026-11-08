<?php
// Master kartu poli dan penghubungnya ke field poliklinik dokter.
?><form class="panel" method="post" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="action" value="content_save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <h2><?= empty($edit['id']) ? 'Tambah poli' : 'Edit poli' ?></h2>
    <?php field('title', 'Nama kartu poli', $edit['title'] ?? ''); ?>
    <label>
        Poli penghubung (sesuai field poliklinik dokter)
        <input name="subtitle" list="doctor-specialties" maxlength="255" value="<?= e(
            $edit['subtitle'] ?? '',
        ) ?>" required>
    </label>
    <datalist id="doctor-specialties">
        <?php foreach (
            query('SELECT DISTINCT specialty FROM doctors ORDER BY specialty')
            as $specialty
        ): ?>
        <option value="<?= e($specialty['specialty']) ?>">
        </option>
        <?php endforeach; ?>
    </datalist>
    <label>
        Gambar kartu poli (JPG/PNG/WebP, maksimal 25 MB)
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
    </label>
    <input type="hidden" name="image" value="<?= e($edit['image'] ?? '') ?>">
    <?php require APP_ROOT . '/views/admin/components/photo-editor.php'; ?>
    <?php field('sort_order', 'Urutan tampil', $edit['sort_order'] ?? 0, 'number'); ?>
    <label class="check">
        <input type="checkbox" name="active" <?= $edit['active'] ?? 1 ? 'checked' : '' ?>>
        Tampilkan poli
    </label>
    <div class="actions">
        <button class="button">Simpan poli</button>
        <a href="index.php?page=admin&amp;tab=contents&amp;kind=clinic">Batal / tambah baru</a>
    </div>
</form>
<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Nama poli</th>
                <th>Poli penghubung</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (
                query('SELECT * FROM contents WHERE kind=? ORDER BY sort_order,id', ['clinic'])
                as $row
            ): ?>
            <tr>
                <td><?= e($row['title']) ?></td>
                <td><?= e($row['subtitle']) ?></td>
                <td><?= $row['active'] ? 'Aktif' : 'Draft' ?></td>
                <td>
                    <div class="actions">
                        <a href="index.php?page=admin&amp;tab=contents&amp;kind=clinic&amp;edit=<?= (int) $row[
                            'id'
                        ] ?>">Edit</a>
                        <form method="post" data-confirm="Hapus kartu poli ini? Data dokter tetap tersimpan.">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="content_delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="text-button danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
