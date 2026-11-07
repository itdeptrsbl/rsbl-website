<?php
// Satu field foto tambahan dengan tombol tambah dan keterangan.
?><div class="room-upload-row" data-room-upload-row data-upload-index="<?= e($uploadIndex) ?>">
    <div class="room-photo-file-row">
        <label>
            Foto tambahan
            <input type="file" name="room_photos[<?= e(
                $uploadIndex,
            ) ?>]" accept="image/jpeg,image/png,image/webp" data-room-file>
        </label>
        <button type="button" class="button small" data-add-room-field aria-label="Tambahkan field foto baru">+</button>
        <button type="button" class="text-button danger" data-remove-room-field>Hapus field</button>
    </div>
    <div data-room-file-preview>
    </div>
    <p class="muted" data-room-file-status aria-live="polite">
    </p>
    <label>
        Keterangan foto
        <input name="new_photo_captions[<?= e(
            $uploadIndex,
        ) ?>]" maxlength="255" placeholder="Keterangan foto (opsional)">
    </label>
</div>
