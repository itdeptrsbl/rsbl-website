<?php
// Master video sejarah, pemimpin, masa jabatan, dan struktur organisasi.

$historyRows = query('SELECT * FROM contents WHERE kind=? ORDER BY sort_order,id', ['history']);
$historyVideoRow = [];
$organizationRow = [];
$historyLeaders = [];
foreach ($historyRows as $historyRow) {
    $type = historyType((int) $historyRow['id']);
    if ($type === 'video' && !$historyVideoRow) {
        $historyVideoRow = $historyRow;
    }
    if ($type === 'organization' && !$organizationRow) {
        $organizationRow = $historyRow;
    }
    if ($type === 'leader') {
        $historyLeaders[] = $historyRow;
    }
}
if ($error && ($_POST['history_type'] ?? '') === 'video') {
    $historyVideoRow = $_POST;
}
if ($error && ($_POST['history_type'] ?? '') === 'organization') {
    $organizationRow = $_POST;
}
?>
<section class="panel">
    <h2>Sejarah</h2>
    <form method="post" enctype="multipart/form-data">
        <?= csrf() ?>
        <input type="hidden" name="action" value="content_save">
        <input type="hidden" name="history_type" value="video">
        <input type="hidden" name="id" value="<?= (int) ($historyVideoRow['id'] ?? 0) ?>">
        <input type="hidden" name="title" value="Sejarah">
        <input type="hidden" name="active" value="1">
        <label>
            Video sejarah (MP4 / WebM, maks. 20 MB)
            <input type="file" name="video_file" accept="video/mp4,video/webm" <?= empty(
                $historyVideoRow['link']
            )
                ? 'required'
                : '' ?>>
        </label>
        <?php if (!empty($historyVideoRow['link'])): ?>
        <p class="muted">Video sudah tersimpan. Pilih file baru hanya untuk menggantinya.</p>
        <?php endif; ?>
        <label>
            Teks di bawah video (opsional)
            <textarea name="body" rows="6" maxlength="50000"><?= e(
                $historyVideoRow['body'] ?? '',
            ) ?></textarea>
        </label>
        <button class="button">Simpan sejarah</button>
    </form>
</section>
<section class="panel">
    <div class="admin-leader-heading">
        <h2>Kepemimpinan</h2>
        <a class="button small" href="index.php?page=admin&amp;tab=contents&amp;kind=history&amp;new_leader=1#new-leader">+ Tambah pemimpin</a>
    </div>
    <p class="muted">Ubah data melalui nama pemimpin. Foto maksimal 20 MB. Kosongkan tanggal akhir jika masih menjabat.</p>
    <?php
    $leaderEditors = $historyLeaders;
    if (
        isset($_GET['new_leader']) ||
        ($error && ($_POST['history_type'] ?? '') === 'leader' && empty($_POST['id']))
    ) {
        $leaderEditors[] = [];
    }
    foreach ($leaderEditors as $leader):

        $isNew = empty($leader['id']);
        $failed =
            $error &&
            ($_POST['history_type'] ?? '') === 'leader' &&
            (int) ($_POST['id'] ?? 0) === (int) ($leader['id'] ?? 0);
        if ($failed) {
            $leader = array_replace($leader, $_POST);
            $pendingPhoto =
                $_SESSION['leader_uploads'][$_POST['leader_upload_token'] ?? ''] ?? null;
            if ($pendingPhoto && (int) $pendingPhoto['id'] === (int) ($leader['id'] ?? 0)) {
                $leader['image'] = $pendingPhoto['path'];
            }
        }
        $leaderUploadToken = $failed
            ? $_POST['leader_upload_token'] ?? bin2hex(random_bytes(16))
            : bin2hex(random_bytes(16));
        ?>
    <details class="admin-leader-editor" <?= $isNew ? 'id="new-leader"' : '' ?> <?= $isNew ||
 $failed
     ? 'open'
     : '' ?>>
        <summary>
            <?php if (!empty($leader['image'])): ?>
            <img class="admin-leader-avatar" src="<?= e(photo($leader['image'])) ?>" alt="">
            <?php endif; ?>
            <span><?= e($leader['title'] ?? 'Pemimpin baru') ?></span>
        </summary>
        <form method="post" enctype="multipart/form-data">
            <?= csrf() ?>
            <input type="hidden" name="action" value="content_save">
            <input type="hidden" name="history_type" value="leader">
            <input type="hidden" name="leader_upload_token" value="<?= e($leaderUploadToken) ?>">
            <input type="hidden" name="id" value="<?= (int) ($leader['id'] ?? 0) ?>">
            <label>
                Foto pemimpin (JPG / PNG / WebP, maks. 20 MB)
                <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" <?= empty(
                    $leader['image']
                ) && !$failed
                    ? 'required'
                    : '' ?>>
            </label>
            <?php if ($failed): ?>
            <p class="notice error"><?php
            echo e($error);
            if (
                !empty($leader['image'])
            ): ?> Foto tetap tersimpan. Perbaiki isian lalu simpan kembali tanpa mengunggah ulang.<?php endif;
            ?></p>
            <?php endif; ?>
<?php $photoSize = $failed
    ? $_POST['leader_photo_zoom'] ?? 100
    : setting('history_leader_zoom_' . (int) ($leader['id'] ?? 0), '100'); ?>
<?php if (!in_array((int) $photoSize, range(100, 200, 10), true)) {
    $photoSize = 100;
} ?>
            <div class="leader-size-control" data-leader-size>
                <p>Zoom foto di dalam bingkai</p>
                <input type="hidden" name="leader_photo_zoom" value="<?= (int) $photoSize ?>">
                <?php foreach (['x' => 50, 'y' => 30] as $axis => $defaultPosition): ?>
                <input type="hidden" name="leader_photo_<?= $axis ?>" value="<?= (int) ($failed
    ? $_POST['leader_photo_' . $axis] ?? $defaultPosition
    : setting(
        'history_leader_' . $axis . '_' . (int) ($leader['id'] ?? 0),
        (string) $defaultPosition,
    )) ?>">
                <?php endforeach; ?>
                <div class="leader-size-buttons">
                    <button type="button" data-size-minus aria-label="Perkecil foto">&minus;</button>
                    <output aria-live="polite"><?= (int) $photoSize ?>%</output>
                    <button type="button" data-size-plus aria-label="Perbesar foto">+</button>
                </div>
                <div class="leader-photo-preview">
                    <div class="leader-photo-frame leader-zoom-<?= (int) $photoSize ?>" tabindex="0" role="group" aria-label="Posisi foto: seret foto atau gunakan tombol panah">
                        <img data-leader-preview src="<?= e(
                            photo($leader['image'] ?? ''),
                        ) ?>" alt="Preview foto pemimpin">
                    </div>
                </div>
                <small>Seret foto untuk mengatur posisinya, atau gunakan tombol panah saat bingkai dipilih. Perbesar zoom untuk ruang geser lebih luas. Klik Simpan pemimpin untuk menerapkan.</small>
            </div>
            <div class="form-grid"><?php
            field('title', 'Nama dokter / pemimpin', $leader['title'] ?? '');
            field('subtitle', 'Jabatan', $leader['subtitle'] ?? '');
            ?>
<?php
field('starts', 'Mulai menjabat (tahun / tanggal)', $leader['starts'] ?? '');
field(
    'ends',
    'Selesai menjabat (tahun / tanggal, kosong jika masih menjabat)',
    $leader['ends'] ?? '',
    'text',
    false,
);
?></div>
            <p class="muted">Boleh isi tahun saja (2007) atau tanggal lengkap (16 April 1995 / 16/04/1995). Tidak perlu melengkapi tanggal yang belum diketahui.</p>
            <label class="check">
                <input type="checkbox" name="active" <?= $leader['active'] ?? 1 ? 'checked' : '' ?>>
                Tampilkan pemimpin ini
            </label>
            <button class="button small">Simpan pemimpin</button>
        </form>
        <?php if (!$isNew): ?>
        <form method="post" data-confirm="Hapus pemimpin ini?">
            <?= csrf() ?>
            <input type="hidden" name="action" value="content_delete">
            <input type="hidden" name="id" value="<?= (int) $leader['id'] ?>">
            <button class="text-button danger">Hapus pemimpin</button>
        </form>
        <?php endif; ?>
    </details>
    <?php
    endforeach;
    ?>
<?php if (!$leaderEditors): ?>
    <p>Belum ada pemimpin. Gunakan tombol + Tambah pemimpin.</p>
    <?php endif; ?>
</section>
<script src="assets/js/admin/leader-photo-preview.js?v=<?= filemtime(
    APP_ROOT . '/assets/js/admin/leader-photo-preview.js',
) ?>" defer>
</script>
<section class="panel">
    <h2>Struktur Organisasi PT</h2>
    <form method="post" enctype="multipart/form-data">
        <?= csrf() ?>
        <input type="hidden" name="action" value="content_save">
        <input type="hidden" name="history_type" value="organization">
        <input type="hidden" name="id" value="<?= (int) ($organizationRow['id'] ?? 0) ?>">
        <input type="hidden" name="title" value="Struktur Organisasi PT">
        <input type="hidden" name="active" value="1">
        <label>
            Gambar struktur organisasi (JPG / PNG / WebP, maks. 20 MB)
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php if (!empty($organizationRow['image'])): ?>
        <img class="admin-partner-thumbnail" src="<?= e(
            photo($organizationRow['image']),
        ) ?>" alt="Struktur organisasi saat ini">
        <p class="muted">Kosongkan unggahan untuk mempertahankan gambar sebelumnya.</p>
        <?php endif; ?>
        <label>
            Teks (opsional)
            <textarea name="body" rows="5" maxlength="50000"><?= e(
                $organizationRow['body'] ?? '',
            ) ?></textarea>
        </label>
        <?php $organizationWidth =
            $_POST['organization_width'] ??
            setting('history_width_' . (int) ($organizationRow['id'] ?? 0), '100'); ?>
        <label>
            Ukuran gambar struktur organisasi
            <select name="organization_width">
                <?php foreach (range(20, 100, 10) as $width): ?>
                <option value="<?= $width ?>" <?= (string) $organizationWidth === (string) $width
    ? 'selected'
    : '' ?>><?= $width ?>% dari lebar konten<?= $width === 100 ? ' (penuh)' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="muted">Gambar tetap di tengah. Tinggi mengikuti proporsi gambar; ukuran otomatis menyesuaikan layar.</p>
        <button class="button">Simpan struktur organisasi</button>
    </form>
</section>
