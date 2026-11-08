<?php
// Form profil dokter, riwayat, foto, serta jadwal praktik.

$edit = query('SELECT * FROM doctors WHERE id=?', [(int) ($_GET['edit'] ?? 0)])[0] ?? [];
$profile = doctorProfile((int) ($edit['id'] ?? 0));
if ($error && ($_POST['action'] ?? '') === 'doctor_save') {
    $edit = $_POST;
    $edit['days'] = implode(',', array_map('intval', $_POST['days'] ?? []));
    $edit['active'] = isset($_POST['active']) ? 1 : 0;
    $profile = $_POST;
}
?>
<form class="panel" method="post" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="action" value="doctor_save">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
    <h2><?= empty($edit['id']) ? 'Tambah' : 'Edit' ?> dokter</h2>
    <div class="form-grid"><?php
    field('name', 'Nama dokter', $edit['name'] ?? '');
    field('specialty', 'Poli', $edit['specialty'] ?? '');
    field('image', 'URL foto HTTPS (opsional)', $edit['image'] ?? '', 'text', false);
    ?></div>
    <label>
        Unggah foto dokter (JPG/PNG/WebP, maks. 25 MB)
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
    </label>
    <h3>Profil &amp; riwayat dokter</h3>
    <?php foreach (
        [
            'bio' => 'Tentang dokter',
            'education' => 'Riwayat pendidikan',
            'experience' => 'Pengalaman praktik',
            'training' => 'Pelatihan',
            'certifications' => 'Sertifikasi',
            'organizations' => 'Keanggotaan organisasi',
        ]
        as $key => $label
    ): ?>
    <div class="history-editor" data-history-editor>
        <label for="history-<?= $key ?>"><?= e($label) ?> (opsional)</label>
        <div class="history-editor-surface">
            <div class="history-toolbar" role="group" aria-label="Format paragraf <?= e($label) ?>">
                <span class="history-toolbar-label">Paragraf</span>
                <button type="button" data-list="bullet" aria-label="Daftar bullet" title="Daftar bullet" aria-controls="history-<?= $key ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="4" cy="6" r="1.5"/>
                        <circle cx="4" cy="12" r="1.5"/>
                        <circle cx="4" cy="18" r="1.5"/>
                        <path d="M9 6h12M9 12h12M9 18h12"/>
                    </svg>
                </button>
                <button type="button" data-list="number" aria-label="Daftar bernomor" title="Daftar bernomor" aria-controls="history-<?= $key ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <text x="1" y="8">1</text>
                        <text x="1" y="14">2</text>
                        <text x="1" y="20">3</text>
                        <path d="M9 6h12M9 12h12M9 18h12"/>
                    </svg>
                </button>
                <button type="button" data-list="plain" aria-label="Hapus format daftar" title="Hapus format daftar" aria-controls="history-<?= $key ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18"/>
                    </svg>
                </button>
            </div>
            <textarea id="history-<?= $key ?>" name="<?= $key ?>" rows="5" maxlength="10000" placeholder="Tulis riwayat di sini..."><?= e(
    $profile[$key] ?? '',
) ?></textarea>
        </div>
    </div>
    <?php endforeach; ?>
<?php require APP_ROOT . '/views/admin/components/doctor-schedule.php'; ?>
    <label class="check">
        <input type="checkbox" name="active" <?= $edit['active'] ?? 1 ? 'checked' : '' ?>>
        Dokter aktif
    </label>
    <div class="actions">
        <button class="button">Simpan dokter</button>
        <a href="index.php?page=admin&tab=doctors">Tambah baru</a>
    </div>
</form>
<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Dokter</th>
                <th>Poli</th>
                <th>Jam WITA</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (query('SELECT * FROM doctors ORDER BY name') as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['specialty']) ?></td>
                <td><?= e(doctorScheduleText($row)) ?></td>
                <td><?= $row['active'] ? 'Aktif' : 'Nonaktif' ?></td>
                <td>
                    <a href="index.php?page=admin&tab=doctors&edit=<?= $row['id'] ?>">Edit</a>
                    <form method="post" enctype="multipart/form-data" data-confirm="Nonaktifkan dokter ini?">
                        <?= csrf() ?>
                        <input type="hidden" name="action" value="doctor_archive">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button class="text-button danger">Nonaktifkan</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
