<?php
// Satu baris sesi praktik yang dapat ditambah melalui editor jadwal.
?><fieldset class="doctor-session" data-session>
    <legend>Sesi praktik</legend>
    <div class="day-checks">
        <?php foreach ($dayLabels as $day => $label): ?>
        <label class="check">
            <input type="checkbox" name="sessions[<?= e(
                $index,
            ) ?>][days][]" value="<?= $day ?>" <?= in_array($day, (array) ($session['days'] ?? []))
    ? 'checked'
    : '' ?>>
            <?= $label ?>
        </label>
        <?php endforeach; ?>
    </div>
    <div class="form-grid">
        <label>
            Jam mulai (WITA)
            <input type="time" name="sessions[<?= e($index) ?>][start]" value="<?= e(
    $session['start'] ?? '',
) ?>">
        </label>
        <label>
            Jam selesai (WITA)
            <input type="time" name="sessions[<?= e($index) ?>][end]" value="<?= e(
    $session['end'] ?? '',
) ?>">
        </label>
    </div>
    <button type="button" class="text-button danger" data-remove-session>Hapus sesi</button>
</fieldset>
