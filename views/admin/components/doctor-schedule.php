<?php
// Editor beberapa sesi praktik dokter dengan hari dan jam terpisah.

$sessions =
    $error && isset($_POST['sessions']) ? (array) $_POST['sessions'] : doctorSessions($edit);
$dayLabels = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu',
];
?>
<section data-schedule-editor>
    <input type="hidden" name="schedule_editor" value="1">
    <h3>Jadwal praktik</h3>
    <div data-session-list><?php foreach ($sessions as $index => $session): ?>
            <?php require APP_ROOT . '/views/admin/components/doctor-session.php'; ?>
        <?php endforeach; ?></div>
    <button type="button" class="button small" data-add-session>+ Tambah sesi praktik</button>
    <p class="muted" data-session-message aria-live="polite">
    </p>
    <template data-session-template><?php
    $index = '__INDEX__';
    $session = ['days' => [], 'start' => '', 'end' => ''];
    ?>
        <?php require APP_ROOT . '/views/admin/components/doctor-session.php'; ?></template>
</section>
