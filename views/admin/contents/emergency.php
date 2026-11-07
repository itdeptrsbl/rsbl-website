<?php
// Master penjelasan, kontak, fasilitas, dan foto IGD.

$emergency = emergencyContent();
if ($error && ($_POST['action'] ?? '') === 'emergency_save') {
    foreach ($emergency as $key => $value) {
        $emergency[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : $value;
    }
}
?>
<div class="notice">Perbarui kontak, jam layanan, penjelasan, dan fasilitas melalui formulir pertama. Foto dikelola terpisah di bawahnya. Nomor ambulans yang dikosongkan tidak ditampilkan; nomor IGD yang kosong menggunakan telepon rumah sakit.</div>
<form class="panel" method="post">
    <?= csrf() ?>
    <input type="hidden" name="action" value="emergency_save">
    <h2>Informasi &amp; kontak IGD</h2>
    <div class="form-grid"><?php field(
        'hours',
        'Jam layanan (contoh: 24 jam)',
        $emergency['hours'],
        'text',
        false,
    ); ?>
        <?php field('phone', 'Telepon khusus IGD (opsional)', $emergency['phone'], 'tel', false); ?>
        <?php field(
            'ambulance_phone',
            'Telepon ambulans (opsional)',
            $emergency['ambulance_phone'],
            'tel',
            false,
        ); ?></div>
    <?php foreach (
        [
            'intro' => ['Penjelasan singkat', 10000],
            'ambulance_note' => ['Informasi layanan ambulans (opsional)', 2000],
            'location' => ['Lokasi pintu masuk / petunjuk menuju IGD (opsional)', 2000],
            'arrival_note' => ['Pesan penting untuk pengunjung', 2000],
            'facilities' => ['Fasilitas IGD, satu poin per baris (opsional)', 10000],
        ]
        as $key => [$label, $limit]
    ): ?>
    <label>
        <?= e($label) ?>
        <textarea name="<?= $key ?>" rows="3" maxlength="<?= $limit ?>"><?= e(
    $emergency[$key],
) ?></textarea>
    </label>
    <?php endforeach; ?>
    <div class="actions">
        <button class="button">Simpan informasi IGD</button>
        <a href="index.php?page=emergency" target="_blank" rel="noopener">Lihat halaman IGD</a>
    </div>
</form>
<?php require APP_ROOT . '/views/admin/components/photo-gallery.php'; ?>
