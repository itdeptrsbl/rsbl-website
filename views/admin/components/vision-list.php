<?php
// Editor daftar poin misi atau nilai yang dapat ditambah dan dihapus.
?><fieldset class="vision-list-editor" data-vision-list>
    <legend><?= e($listLabel) ?></legend>
    <div data-vision-items>
        <?php foreach ($visionData[$listKey] ?? [] as $listItem): ?>
        <div class="vision-editor-row">
            <label>
                Poin <?= e($listLabel) ?>
                <textarea name="<?= e($listKey) ?>[]" rows="2" maxlength="3000"><?= e(
    $listItem,
) ?></textarea>
            </label>
            <button class="text-button danger" type="button" data-remove-point>Hapus</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="button small" type="button" data-add-point>+ Tambah poin <?= e(
        $listLabel,
    ) ?></button>
    <p class="muted" data-list-status aria-live="polite">
    </p>
    <template>
        <div class="vision-editor-row">
            <label>
                Poin <?= e($listLabel) ?>
                <textarea name="<?= e($listKey) ?>[]" rows="2" maxlength="3000">
                </textarea>
            </label>
            <button class="text-button danger" type="button" data-remove-point>Hapus</button>
        </div>
    </template>
</fieldset>
