<?php
// Master visi, misi, janji, motto, dan nilai-nilai rumah sakit.

$visionData = $error && ($_POST['action'] ?? '') === 'vision_save' ? $_POST : visionContent(); ?>
<form method="post" class="panel">
    <?= csrf() ?>
    <input type="hidden" name="action" value="vision_save">
    <h2>Visi, Misi, Janji, Motto &amp; Nilai-Nilai</h2>
    <label>
        Visi
        <textarea name="vision" rows="4" maxlength="5000"><?= e(
            $visionData['vision'] ?? '',
        ) ?></textarea>
    </label>
    <?php
    $listKey = 'missions';
    $listLabel = 'Misi';
    require APP_ROOT . '/views/admin/components/vision-list.php';
    ?>
    <label>
        Janji
        <textarea name="promise" rows="3" maxlength="5000"><?= e(
            $visionData['promise'] ?? '',
        ) ?></textarea>
    </label>
    <label>
        Motto
        <textarea name="motto" rows="2" maxlength="5000"><?= e(
            $visionData['motto'] ?? '',
        ) ?></textarea>
    </label>
    <?php
    $listKey = 'values';
    $listLabel = 'Nilai-Nilai';
    require APP_ROOT . '/views/admin/components/vision-list.php';
    ?>
    <button class="button">Simpan semua</button>
</form>
<script src="assets/js/admin/vision-editor.js?v=<?= filemtime(
    APP_ROOT . '/assets/js/admin/vision-editor.js',
) ?>" defer>
</script>
