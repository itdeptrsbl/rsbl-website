<?php
// Editor pratinjau foto: mode tampilan, perbesaran, dan posisi gambar.

$display = json_decode(setting('slide_display_' . (int) ($edit['id'] ?? 0), '{}'), true) ?: [];
if (in_array($kind, ['clinic', 'inpatient_room'], true)) {
    $display += ['fit' => 'contain'];
}
if ($error && ($_POST['action'] ?? '') === 'content_save') {
    $display = [
        'fit' => $_POST['slide_fit'] ?? 'cover',
        'zoom' => $_POST['slide_zoom'] ?? 100,
        'x' => $_POST['slide_x'] ?? 50,
        'y' => $_POST['slide_y'] ?? 50,
    ];
}
?>
<fieldset class="slide-editor" data-slide-editor data-photo-kind="<?= e($kind) ?>">
    <legend>Sesuaikan tampilan foto</legend>
    <div class="form-grid">
        <label>
            Mode gambar
            <select name="slide_fit">
                <option value="cover" <?= ($display['fit'] ?? 'cover') === 'cover'
                    ? 'selected'
                    : '' ?>>Penuhi area (sebagian foto dapat terpotong)</option>
                <option value="contain" <?= ($display['fit'] ?? '') === 'contain'
                    ? 'selected'
                    : '' ?>>Tampilkan foto utuh</option>
            </select>
        </label>
        <label>
            Pratinjau layar
            <select data-preview-size>
                <option value="desktop">Desktop</option>
                <option value="tablet">Tablet</option>
                <option value="mobile">Ponsel</option>
            </select>
        </label>
    </div>
    <?php foreach (['zoom' => 100, 'x' => 50, 'y' => 50] as $key => $default): ?>
    <input type="hidden" name="slide_<?= $key ?>" value="<?= (int) ($display[$key] ?? $default) ?>">
    <?php endforeach; ?>
    <div class="slide-edit-toolbar">
        <div class="slide-zoom-controls" role="group" aria-label="Perbesaran foto">
            <button type="button" data-zoom-step="-5" aria-label="Perkecil foto">&minus;</button>
            <output data-value="zoom" aria-live="polite"><?= (int) ($display['zoom'] ??
                100) ?>%</output>
            <button type="button" data-zoom-step="5" aria-label="Perbesar foto">+</button>
        </div>
        <button type="button" class="button small" data-fit-photo>Pas-kan foto utuh</button>
        <button type="button" class="text-button" data-reset-display>Kembalikan posisi awal</button>
    </div>
    <p class="muted" id="slide-drag-help"></p>
    <div class="slide-preview <?= $kind === 'clinic'
        ? 'clinic-preview'
        : '' ?>" data-preview-frame tabindex="0" role="group" aria-label="Atur posisi foto" aria-describedby="slide-drag-help">
        <img data-preview-image draggable="false" src="<?= e(
            !empty($edit['image'])
                ? photo($edit['image'])
                : ($kind === 'inpatient_room'
                    ? 'assets/room-placeholder.svg'
                    : 'assets/branding/doctors.jpg'),
        ) ?>" alt="Pratinjau posisi foto slide">
    </div>
    <p class="muted" data-preview-message aria-live="polite">
    </p>
</fieldset>
