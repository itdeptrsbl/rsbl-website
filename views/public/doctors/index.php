<?php
// Daftar dokter, jadwal, profil, dan filter dokter berdasarkan poli.

$clinicFilter = null;
$clinicRequested = isset($_GET['clinic']);
$clinicSuffix = '';
if ($clinicRequested) {
    $clinicId = is_scalar($_GET['clinic']) ? (int) $_GET['clinic'] : 0;
    $clinicFilter =
        query('SELECT * FROM contents WHERE id=? AND kind=? AND active=1', [
            $clinicId,
            'clinic',
        ])[0] ?? null;
    $clinicSuffix = '&amp;clinic=' . $clinicId;
}
?>
<?php if ($page === 'doctor_profile'): ?>
<section class="container section">
    <a class="text-link" href="index.php?page=doctors">&larr; Daftar Dokter</a>
    <?php if (!$doctor): ?>
    <h1>Profil dokter tidak ditemukan.</h1>
    <p>Dokter tidak tersedia atau sudah tidak aktif.</p>
    <?php else: ?>
            <?php $profile = doctorProfile((int) $doctor['id']); ?>
    <div class="doctor-profile-layout">
        <aside>
            <img class="doctor-profile-photo" src="<?= e(photo($doctor['image'])) ?>" alt="<?= e(
    $doctor['name'],
) ?>">
            <?php $profileSessions = doctorSessions($doctor); ?>
                    <?php if ($profileSessions): ?>
            <div class="panel">
                <h3>Jadwal praktik</h3>
                <?php foreach ($profileSessions as $session): ?>
                <p>
                    <?= e(doctorDays(implode(',', $session['days']))) ?>
                    <br>
                    <?= e($session['start'] . ' - ' . $session['end']) ?> WITA
                </p>
                <?php endforeach; ?>
                <a class="button" href="index.php?page=register&amp;doctor_id=<?= (int) $doctor[
                    'id'
                ] ?>">Book Now &rarr;</a>
            </div>
            <?php endif; ?>
        </aside>
        <div>
            <p class="eyebrow"><?= e($doctor['specialty']) ?></p>
            <h1><?= e($doctor['name']) ?></h1>
            <?php $hasHistory = false; ?>
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
                        <?php if (trim($profile[$key] ?? '') !== ''): ?>
                            <?php $hasHistory = true; ?>
            <section class="doctor-history">
                <h2><?= e($label) ?></h2>
                <div class="doctor-history-text"><?= doctorHistory($profile[$key]) ?></div>
            </section>
            <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!$hasHistory): ?>
            <p>Informasi riwayat dokter belum tersedia.</p>
            <?php endif; ?>
            <a class="text-link" href="index.php?page=doctor_schedule">Lihat semua jadwal dokter &rarr;</a>
        </div>
    </div>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="page-intro container doctors-intro">
    <p class="eyebrow">TIM MEDIS</p>
    <h1><?= $page === 'doctors' ? 'Daftar Dokter' : 'Jadwal Dokter' ?></h1>
    <p class="lead"><?= $page === 'doctors'
        ? 'Kenali dokter dan riwayat profesional mereka.'
        : 'Pilih nama dokter untuk melihat profil, atau Book Now untuk mendaftar.' ?></p>
    <?php if ($clinicRequested): ?>
    <div class="notice">
        <?= $clinicFilter
            ? 'Menampilkan dokter: ' . e($clinicFilter['title'])
            : 'Poliklinik tidak tersedia.' ?>
        <a class="text-link" href="index.php?page=doctors">Lihat semua dokter</a>
        &middot;
        <a class="text-link" href="index.php?page=outpatient">Kembali ke Rawat Jalan</a>
    </div>
    <?php endif; ?>
    <div class="tab-row" aria-label="Halaman dokter">
        <a class="<?= $page === 'doctors'
            ? 'selected'
            : '' ?>" href="index.php?page=doctors<?= $clinicSuffix ?>">Daftar Dokter</a>
        <a class="<?= $page === 'doctor_schedule'
            ? 'selected'
            : '' ?>" href="index.php?page=doctor_schedule<?= $clinicSuffix ?>">Jadwal Dokter</a>
    </div>
    <label class="search-label">
        Cari dokter atau poliklinik
        <input id="doctor-search" type="search" placeholder="Nama dokter atau poliklinik...">
    </label>
</section>
<section class="container section compact-top <?= $page === 'doctor_schedule'
    ? 'doctor-schedule-container'
    : '' ?>">
    <?php if ($clinicRequested) {
        $doctorList = $clinicFilter
            ? query('SELECT * FROM doctors WHERE active=1 AND TRIM(specialty)=? ORDER BY name', [
                trim($clinicFilter['subtitle']),
            ])
            : [];
    } else {
        $doctorList = query('SELECT * FROM doctors WHERE active=1 ORDER BY name');
    } ?>
        <?php if ($page === 'doctor_schedule') {
            $doctorList = array_values(
                array_filter($doctorList, fn($d) => count(doctorSessions($d)) > 0),
            );
        } ?>
        <?php if ($page === 'doctors'): ?>
    <div class="grid three" id="doctors-list"><?php foreach ($doctorList as $d): ?>
                    <?php require APP_ROOT . '/views/components/cards/doctor.php'; ?>
                <?php endforeach; ?></div>
    <?php else: ?>
    <div class="panel table-wrap">
        <table class="doctor-schedule-table">
            <colgroup>
                <col style="width:32%">
                <col style="width:25%">
                <col style="width:19%">
                <col style="width:15%">
                <col style="width:9%">
            </colgroup>
            <thead>
                <tr>
                    <th>Dokter</th>
                    <th>Poliklinik</th>
                    <th>Hari praktik</th>
                    <th>Jam (WITA)</th>
                    <th>Pendaftaran</th>
                </tr>
            </thead>
            <tbody id="doctors-list">
                <?php foreach ($doctorList as $d): ?>
                <tr data-search="<?= e(strtolower($d['name'] . ' ' . $d['specialty'])) ?>">
                    <td>
                        <a class="text-link schedule-doctor" href="index.php?page=doctor_profile&amp;id=<?= (int) $d[
                            'id'
                        ] ?>">
                            <img class="schedule-doctor-avatar" src="<?= e(
                                photo($d['image']),
                            ) ?>" alt="" loading="lazy">
                            <span><?= e($d['name']) ?></span>
                        </a>
                    </td>
                    <td><?= e($d['specialty']) ?></td>
                    <td class="schedule-days">
                        <?php foreach (doctorSessions($d) as $session): ?>
                        <div class="schedule-session-value"><?= e(
                            doctorDays(implode(',', $session['days'])),
                        ) ?></div>
                        <?php endforeach; ?>
                    </td>
                    <td class="schedule-times">
                        <?php foreach (doctorSessions($d) as $session): ?>
                        <div class="schedule-session-value"><?= e(
                            $session['start'] . ' - ' . $session['end'],
                        ) ?> WITA</div>
                        <?php endforeach; ?>
                    </td>
                    <td class="schedule-book-cell">
                        <a class="schedule-book-link" href="index.php?page=register&amp;doctor_id=<?= (int) $d[
                            'id'
                        ] ?>">Book Now</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <p id="no-doctors" <?= $doctorList
        ? 'hidden'
        : '' ?>>Tidak ada dokter yang sesuai pencarian.</p>
</section>
<?php endif; ?>
