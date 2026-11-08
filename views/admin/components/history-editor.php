<?php
// Isian pendukung untuk jenis konten sejarah yang dipilih.

$selectedType = $edit['history_type'] ?? historyType((int) ($edit['id'] ?? 0)); ?>
<div data-history-editor>
    <label>
        Jenis bagian
        <select name="history_type" data-history-type>
            <?php foreach (historyTypes() as $type => $label): ?>
            <option value="<?= e($type) ?>" <?= $type === $selectedType ? 'selected' : '' ?>><?= e(
    $label,
) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php field('title', 'Judul bagian / nama pemimpin', $edit['title'] ?? ''); ?>
    <div data-history-for="banner,timeline,leader,organization,text"><?php field(
        'subtitle',
        'Subjudul / jabatan dan masa kepemimpinan (opsional)',
        $edit['subtitle'] ?? '',
        'text',
        false,
    ); ?></div>
    <div data-history-for="banner,timeline,leader,organization,video,text">
        <label>
            Gambar / foto (JPG, PNG, WebP; maks. 25 MB)
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php if (!empty($edit['image'])): ?>
        <img class="admin-partner-thumbnail" src="<?= e(
            photo($edit['image']),
        ) ?>" alt="Pratinjau gambar saat ini">
        <?php endif; ?>
    </div>
    <div data-history-for="video">
        <label>
            Unggah video sejarah (MP4 / WebM; maks. 20 MB)
            <input type="file" name="video_file" accept="video/mp4,video/webm">
        </label>
        <?php if (!empty($edit['link'])): ?>
        <?php endif; ?>
    </div>
    <label>
        Teks tambahan (opsional)
        <textarea name="body" rows="6" maxlength="50000" placeholder="Untuk bagian video, teks ini tampil di bawah video. Bisa dikosongkan."><?= e(
            $edit['body'] ?? '',
        ) ?></textarea>
    </label>
    <?php field(
        'sort_order',
        'Urutan bagian (angka kecil di atas)',
        $edit['sort_order'] ?? 0,
        'number',
    ); ?>
    <label class="check">
        <input type="checkbox" name="active" <?= $edit['active'] ?? 1 ? 'checked' : '' ?>>
        Tampilkan di halaman Sejarah
    </label>
</div>
<script src="assets/js/admin/history-sections.js" defer>
</script>
