<?php
// Form bertahap: kategori, jenis pasien, jadwal kunjungan, dan identitas.
?>
<section class="container registration-page">
    <header class="registration-heading">
        <p class="eyebrow">PENDAFTARAN ONLINE</p>
        <h1>Rencanakan kunjungan Anda.</h1>
        <p>Pilih layanan dan lengkapi data diri dalam beberapa langkah.</p>
    </header>
    <div class="registration-shell">
        <ol class="registration-progress" aria-label="Tahapan pendaftaran">
            <?php foreach (
                ['Kategori', 'Jenis pasien', 'Kunjungan', 'Data diri']
                as $index => $label
            ): ?>
            <li data-progress="<?= $index ?>"><span><?= $index + 1 ?></span><?= e($label) ?></li>
            <?php endforeach; ?>
        </ol>
        <form method="post" data-registration data-doctors="<?= e(
            json_encode($registrationDoctors),
        ) ?>" data-selected-doctor="<?= (int) ($_GET['doctor_id'] ?? 0) ?>">
            <?= csrf() ?>
            <?php if ($error): ?><p class="notice error" role="alert"><?= e(
    $error,
) ?></p><?php endif; ?>
            <p class="registration-status">Pendaftaran online sedang disiapkan. Untuk mendaftar saat ini, silakan <a href="index.php?page=contact">hubungi petugas</a>.</p>
            <noscript><p>Aktifkan JavaScript untuk menggunakan formulir bertahap ini.</p></noscript>
            <section data-step="0">
                <p class="eyebrow">LANGKAH 1 DARI 4</p>
                <h2>Kategori pendaftaran</h2>
                <p>Pilih kategori sesuai rencana kunjungan Anda.</p>
                <div class="registration-choices">
                    <label><input type="radio" name="payment" value="Umum" required><strong>Umum</strong><small>Pendaftaran dengan biaya pribadi.</small></label>
                    <label><input type="radio" name="payment" value="BPJS" required><strong>BPJS Kesehatan</strong><small>Lanjutkan melalui Mobile JKN.</small></label>
                </div>
                <div class="registration-jkn" data-jkn hidden>
                    <h3>Pendaftaran BPJS melalui Mobile JKN</h3>
                    <p>Silakan lanjutkan pendaftaran melalui aplikasi <strong>Mobile JKN</strong>. Pastikan status kepesertaan dan rujukan Anda sesuai ketentuan BPJS Kesehatan.</p>
                    <a class="text-link" href="index.php?page=contact">Butuh bantuan? Hubungi petugas</a>
                </div>
            </section>
            <section data-step="1" hidden>
                <p class="eyebrow">LANGKAH 2 DARI 4</p><h2>Apakah Anda pernah berobat di sini?</h2>
                <div class="registration-choices">
                    <label><input type="radio" name="patient_type" value="new" required><strong>Pasien baru</strong><small>Belum memiliki nomor rekam medis.</small></label>
                    <label><input type="radio" name="patient_type" value="existing" required><strong>Pasien lama</strong><small>Sudah memiliki nomor rekam medis.</small></label>
                </div>
                <div data-existing hidden>
                    <div class="form-grid">
                        <label>Nomor rekam medis<input name="medical_record_number" maxlength="40" autocomplete="off"></label>
                        <label>Tanggal lahir untuk verifikasi<input type="date" name="verification_birth_date" max="<?= date(
                            'Y-m-d',
                        ) ?>"></label>
                    </div>
                    <button type="button" class="button secondary" data-lookup>Cari data pasien</button>
                    <p data-lookup-status role="status">Pencarian data pasien akan tersedia setelah integrasi Medinfras aktif.</p>
                </div>
            </section>
            <section data-step="2" hidden>
                <p class="eyebrow">LANGKAH 3 DARI 4</p><h2>Pilih jadwal kunjungan</h2>
                <div class="form-grid">
                    <label>Poli tujuan<select name="clinic" required><option value="">Pilih poli</option></select></label>
                    <label>Dokter<select name="doctor_id" required disabled><option value="">Pilih poli terlebih dahulu</option></select></label>
                    <label>Tanggal kunjungan<input type="date" name="visit_date" min="<?= date(
                        'Y-m-d',
                    ) ?>" max="<?= date(
    'Y-m-d',
    strtotime('+30 days'),
) ?>" required disabled></label>
                    <label>Jadwal praktik<select name="session" required disabled><option value="">Pilih tanggal terlebih dahulu</option></select></label>
                </div>
                <p class="muted" data-schedule-status role="status">Jadwal praktik website sebagai referensi. Ketersediaan kuota menunggu integrasi Medinfras.</p>
                <label>Catatan kunjungan (opsional)<textarea name="notes" rows="3" maxlength="1000" placeholder="Tuliskan catatan untuk petugas"></textarea></label>
            </section>
            <section data-step="3" hidden>
                <p class="eyebrow">LANGKAH 4 DARI 4</p><h2>Lengkapi data diri</h2>
                <div class="registration-summary" data-summary></div>
                <div class="form-grid">
                    <?php
                    // Definisi ringkas menjaga nama field konsisten untuk pemetaan API nanti.
                    $identityFields = [
                        ['first_name', 'Nama depan', 'text', true],
                        ['middle_name', 'Nama tengah (opsional)', 'text', false],
                        ['last_name', 'Nama belakang (opsional)', 'text', false],
                        ['patient_name', 'Nama lengkap (otomatis)', 'text', true],
                        ['birth_date', 'Tanggal lahir', 'date', true],
                        ['phone', 'Nomor HP / WhatsApp', 'tel', true],
                        ['email', 'Email (opsional)', 'email', false],
                        ['identity_number', 'Nomor identitas (opsional)', 'text', false],
                        ['birth_place', 'Tempat lahir (opsional)', 'text', false],
                        ['city', 'Kota / kabupaten (opsional)', 'text', false],
                        ['district', 'Kecamatan (opsional)', 'text', false],
                        ['village', 'Kelurahan / desa (opsional)', 'text', false],
                    ];
                    foreach ($identityFields as [$name, $label, $type, $required]): ?>
                    <label><?= e($label) ?><input name="<?= e($name) ?>" type="<?= e(
    $type,
) ?>" maxlength="150" <?= $required ? 'required' : '' ?> <?= $name === 'patient_name'
     ? 'readonly'
     : '' ?> <?= $type === 'date' ? 'max="' . date('Y-m-d') . '"' : '' ?>></label>
                    <?php endforeach;
                    ?>
                    <?php foreach (
                        [
                            'gender' => ['Jenis kelamin', ['Laki-laki', 'Perempuan']],
                            'identity_type' => [
                                'Jenis identitas (opsional)',
                                ['KTP', 'KIA', 'Paspor'],
                            ],
                            'religion' => [
                                'Agama (opsional)',
                                [
                                    'Islam',
                                    'Kristen',
                                    'Katolik',
                                    'Hindu',
                                    'Buddha',
                                    'Konghucu',
                                    'Lainnya',
                                ],
                            ],
                            'citizenship' => ['Kewarganegaraan (opsional)', ['WNI', 'WNA']],
                        ]
                        as $name => [$label, $options]
                    ): ?>
                    <label><?= e($label) ?><select name="<?= e($name) ?>" <?= $name === 'gender'
    ? 'required'
    : '' ?>><option value="">Pilih</option><?php foreach ($options as $option): ?><option><?= e(
    $option,
) ?></option><?php endforeach; ?></select></label>
                    <?php endforeach; ?>
                </div>
                <label>Alamat lengkap (opsional)<textarea name="address" rows="3" maxlength="1000" autocomplete="street-address"></textarea></label>
                <label class="check"><input type="checkbox" name="consent" value="1" required>Saya menyetujui penggunaan data pribadi untuk proses pendaftaran dan konfirmasi kunjungan.</label>
                <p class="muted">Pengiriman akan dibuka setelah integrasi Medinfras siap.</p>
            </section>
            <p class="registration-feedback" data-feedback role="status" aria-live="polite"></p>
            <div class="registration-actions">
                <button type="button" class="button secondary" data-back hidden>&larr; Kembali</button>
                <button type="button" class="button" data-next>Selanjutnya &rarr;</button>
                <button type="submit" class="button" data-submit hidden disabled>Pendaftaran belum tersedia</button>
            </div>
        </form>
    </div>
</section>
