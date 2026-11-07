<?php
// Master penjelasan dan foto fasilitas Rawat Jalan.
?><form class="panel" method="post">
    <?= csrf() ?>
    <input type="hidden" name="action" value="outpatient_intro">
    <h2>Penjelasan Rawat Jalan</h2>
    <label>
        Penjelasan singkat
        <textarea name="outpatient_intro" rows="5" maxlength="10000"><?= e(
            $_POST['outpatient_intro'] ??
                setting(
                    'outpatient_intro',
                    'Pelayanan rawat jalan menyediakan konsultasi, pemeriksaan, dan penanganan sesuai kebutuhan pasien tanpa menjalani rawat inap.',
                ),
        ) ?></textarea>
    </label>
    <button class="button">Simpan penjelasan</button>
    <a class="text-link" href="index.php?page=admin&amp;tab=contents&amp;kind=clinic">Kelola daftar poli &rarr;</a>
</form>
<?php require APP_ROOT . '/views/admin/components/photo-gallery.php'; ?>
