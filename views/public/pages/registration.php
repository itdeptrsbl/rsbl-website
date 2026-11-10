<?php
// Form bertahap: kategori, kunjungan, data diri beserta jenis pasien, lalu pembayaran.
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
                ['Kategori', 'Kunjungan', 'Data diri', 'Pembayaran']
                as $index => $label
            ): ?>
            <li data-progress="<?= $index ?>"><span><?= $index + 1 ?></span><?= e($label) ?></li>
            <?php endforeach; ?>
        </ol>
        <form method="post" data-registration data-clinics-enabled="<?= $registrationClinicsEnabled
            ? '1'
            : '0' ?>" data-api-enabled="<?= $registrationApiEnabled
    ? '1'
    : '0' ?>" data-clinics-url="index.php?page=register&amp;registration_action=clinics">
            <?= csrf() ?>
            <?php if ($error): ?><p class="notice error" role="alert"><?= e(
    $error,
) ?></p><?php endif; ?>
            <noscript><p>Aktifkan JavaScript untuk menggunakan formulir bertahap ini.</p></noscript>
            <section data-step="0">
                <p class="eyebrow">LANGKAH 1 DARI 4</p>
                <h2>Kategori pendaftaran</h2>
                <p>Pilih kategori sesuai rencana kunjungan Anda.</p>
                <div class="registration-choices">
                    <label><input type="radio" name="payment" value="Umum" required><strong>Umum</strong></label>
                    <label><input type="radio" name="payment" value="BPJS" required><strong>BPJS Kesehatan</strong></label>
                </div>
                <div class="registration-jkn" data-jkn hidden>
                    <h3>Pendaftaran BPJS melalui Mobile JKN</h3>
                    <p>Silakan lanjutkan pendaftaran melalui aplikasi <strong>Mobile JKN</strong>. Pastikan status kepesertaan dan rujukan Anda sesuai ketentuan BPJS Kesehatan.</p>
                    <a class="text-link" href="index.php?page=contact">Butuh bantuan? Hubungi petugas</a>
                </div>
            </section>
            <section data-step="1" hidden>
                <p class="muted" data-clinics-status role="status" aria-live="polite"></p>
                <button type="button" class="text-link" data-clinics-retry hidden>Coba muat poli lagi</button>
                <p class="eyebrow">LANGKAH 2 DARI 4</p><h2>Pilih jadwal kunjungan</h2>
                <div class="form-grid">
                    <label>Poli tujuan<select name="clinic" required disabled><option value="">Pilih poli</option></select></label>
                    <label>Dokter<select name="doctor_id" required disabled><option value="">Pilih poli terlebih dahulu</option></select></label>
                    <div class="visit-date-field"><label for="visit-date">Tanggal kunjungan</label><input id="visit-date" type="date" name="visit_date" min="<?= date(
                        'Y-m-d',
                    ) ?>" max="<?= date(
    'Y-m-d',
    strtotime('+30 days'),
) ?>" required disabled><div data-visit-calendar hidden></div></div>
                    <label>Jadwal praktik<select name="session" required disabled><option value="">Pilih tanggal terlebih dahulu</option></select></label>
                </div>
                <p class="muted" data-schedule-status role="status"></p>
                <label>Catatan kunjungan (opsional)<textarea name="notes" rows="3" maxlength="1000" placeholder="Tuliskan catatan untuk petugas"></textarea></label>
            </section>
            <section data-step="2" hidden>
                <p class="eyebrow">LANGKAH 3 DARI 4</p><h2>Lengkapi data diri</h2>
                <p>Jenis pasien</p>
                <div class="registration-choices">
                    <label><input type="radio" name="patient_type" value="new" required><strong>Pasien baru</strong></label>
                    <label><input type="radio" name="patient_type" value="existing" required><strong>Pasien lama</strong></label>
                </div>
                <!-- MRN hanya isian pasien lama; tidak ada pencarian atau verifikasi API. -->
                <div data-existing hidden>
                    <label>Nomor rekam medis<input name="medical_record_number" maxlength="40" pattern="[A-Za-z0-9-]{1,40}" autocomplete="off" disabled></label>
                </div>
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
            </section>
            <section data-step="3" hidden>
                <p class="eyebrow">LANGKAH 4 DARI 4</p><h2>Jenis pembayaran</h2>
                <!-- Pribadi dan Lain-lain selalu tersedia; kategori penjamin lainnya mengikuti API. -->
                <label>Jenis pembayaran<select name="payment_category" required><option value="">Pilih jenis pembayaran</option><option value="Pribadi">Pribadi</option><option value="Lain-lain">Lain-lain</option></select></label>
                <p class="muted" data-payment-status role="status" aria-live="polite"></p>
                <button type="button" class="text-link" data-payment-retry hidden>Coba muat jenis pembayaran lagi</button>
                <!-- Pilihan penjamin diisi dari kategori master payer; data kontrak tidak ditampilkan. -->
                <div data-payer-fields hidden>
                    <label><span data-payer-label>Penjamin</span><select name="payer_code" disabled><option value="">Pilih penjamin</option></select></label>
                    <p class="muted" data-payer-status role="status" aria-live="polite"></p>
                    <button type="button" class="text-link" data-payer-retry hidden>Coba lagi</button>
                </div>
                <label class="check"><input type="checkbox" name="consent" value="1" required>Saya menyetujui penggunaan data pribadi untuk proses pendaftaran dan konfirmasi kunjungan.</label>
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
