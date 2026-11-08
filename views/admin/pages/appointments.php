<?php
// Daftar permohonan pasien, filter status, catatan, dan tindakan admin.

$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['Menunggu verifikasi', 'Dikonfirmasi', 'Selesai', 'Dibatalkan'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}
$pageNo = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($pageNo - 1) * 25;
$where = $statusFilter ? ' WHERE a.status=?' : '';
$args = $statusFilter ? [$statusFilter] : [];
$total = (int) query('SELECT COUNT(*) AS total FROM appointments a' . $where, $args)[0]['total'];
$rows = query(
    'SELECT a.*, d.name AS doctor_name FROM appointments a LEFT JOIN doctors d ON d.id=a.doctor_id' .
        $where .
        ' ORDER BY a.id DESC LIMIT 25 OFFSET ' .
        $offset,
    $args,
);
?>
<div class="tab-row">
    <a href="index.php?page=admin&tab=appointments">Semua (<?= $total ?>)</a>
    <?php foreach ($validStatuses as $s): ?>
    <a class="<?= $s === $statusFilter
        ? 'selected'
        : '' ?>" href="index.php?page=admin&tab=appointments&status=<?= urlencode($s) ?>"><?= e(
    $s,
) ?></a>
    <?php endforeach; ?>
</div>
<?php if (!$rows): ?>
<div class="panel">Belum ada permohonan pada kategori ini.</div>
<?php endif; ?>
<?php foreach ($rows as $row): ?>
<article class="panel appointment">
    <div class="section-heading">
        <div>
            <p class="eyebrow"><?= e($row['reference']) ?></p>
            <h3><?= e($row['patient_name']) ?></h3>
        </div>
        <span class="badge"><?= e($row['status']) ?></span>
    </div>
    <p><?= e($row['doctor_name'] ?? 'Dokter tidak tersedia') ?> · <?= e(
     $row['visit_date'],
 ) ?> · <?= e($row['payment']) ?></p>
    <p>
        Telepon:
        <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $row['phone'])) ?>"><?= e(
    $row['phone'],
) ?></a>
        · Dikirim <?= e($row['created_at']) ?> WITA
    </p>
    <form method="post" enctype="multipart/form-data">
        <?= csrf() ?>
        <input type="hidden" name="action" value="appointment_update">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <label>
            Status
            <select name="status">
                <?php foreach ($validStatuses as $s): ?>
                <option <?= $s === $row['status'] ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Catatan internal petugas
            <textarea name="notes" rows="2" maxlength="5000"><?= e($row['notes']) ?></textarea>
        </label>
        <button class="button small">Simpan status</button>
    </form>
    <form method="post" enctype="multipart/form-data" class="delete-row" data-confirm="Hapus permanen data permohonan ini?">
        <?= csrf() ?>
        <input type="hidden" name="action" value="appointment_delete">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <button class="text-button danger">Hapus data permohonan</button>
    </form>
</article>
<?php endforeach; ?>
<div class="actions">
    <?php if ($pageNo > 1): ?>
    <a href="index.php?page=admin&tab=appointments&status=<?= urlencode(
        $statusFilter,
    ) ?>&p=<?= $pageNo - 1 ?>">← Sebelumnya</a>
    <?php endif; ?>
    <span>Halaman <?= $pageNo ?> · <?= $total ?> data</span>
    <?php if ($offset + 25 < $total): ?>
    <a href="index.php?page=admin&tab=appointments&status=<?= urlencode(
        $statusFilter,
    ) ?>&p=<?= $pageNo + 1 ?>">Berikutnya →</a>
    <?php endif; ?>
</div>
