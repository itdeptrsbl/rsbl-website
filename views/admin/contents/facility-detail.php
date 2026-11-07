<?php
// Master penjelasan dan foto untuk empat layanan sederhana.

foreach (facilityPhotoPages() as $slug => $facilityConfig) {
    if ($facilityConfig['kind'] === $kind) {
        $facilitySlug = $slug;
        $facilityTitle = $facilityConfig['title'];
        break;
    }
} ?>
<form class="panel" method="post">
    <?= csrf() ?>
    <input type="hidden" name="action" value="facility_intro">
    <input type="hidden" name="service_page" value="<?= e($facilitySlug) ?>">
    <h2>Halaman <?= e($facilityTitle) ?></h2>
    <p class="muted">Cukup isi penjelasan singkat dan unggah foto di bawah. Tidak perlu memasukkan jadwal, daftar tindakan, atau informasi tambahan.</p>
    <label>
        Penjelasan singkat (opsional)
        <textarea name="facility_intro" rows="5" maxlength="10000"><?= e(
            $_POST['facility_intro'] ?? setting('facility_intro_' . $facilitySlug),
        ) ?></textarea>
    </label>
    <div class="actions">
        <button class="button">Simpan penjelasan</button>
        <a href="index.php?page=<?= e(
            $facilitySlug,
        ) ?>" target="_blank" rel="noopener">Lihat halaman <?= e($facilityTitle) ?></a>
    </div>
</form>
<?php require APP_ROOT . '/views/admin/components/photo-gallery.php'; ?>
