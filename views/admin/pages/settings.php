<?php
// Form identitas, kontak, logo, sosial media, dan pengaturan website.
?><form class="panel" method="post" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="action" value="settings">
    <h2>Identitas & kontak</h2>
    <div class="form-grid"><?php foreach (
        [
            'name' => 'Nama rumah sakit',
            'tagline' => 'Tagline',
            'address' => 'Alamat',
            'phone' => 'Telepon',
            'whatsapp' => 'Nomor WhatsApp (format 628...)',
        ]
        as $key => $label
    ) {
        field($key, $label, setting($key));
    } ?></div>
    <h2>Perusahaan & media sosial</h2>
    <div class="form-grid"><?php
    field('company_name', 'Nama perusahaan', setting('company_name'));
    foreach (
        [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
        ]
        as $key => $label
    ) {
        field($key, 'URL ' . $label, setting($key), 'url', false);
    }
    ?></div>
    <h2>Logo & gambar utama</h2>
    <p class="muted">Logo rumah sakit, perusahaan, dan akreditasi tampil berdampingan pada header. Foto slideshow dikelola di Master Konten &rarr; Slideshow Beranda.</p>
    <div class="form-grid">
        <img class="admin-logo-preview" src="<?= e(
            brandingImage('company_image', 'assets/branding/hospital.png'),
        ) ?>" alt="Logo perusahaan saat ini">
        <img class="admin-logo-preview" src="<?= e(
            brandingImage('accreditation_image', 'assets/branding/accreditation.jpg'),
        ) ?>" alt="Logo akreditasi saat ini">
    </div>
    <div class="form-grid">
        <label>
            Logo perusahaan (maks. 10 MB)
            <input type="file" name="company_file" accept="image/jpeg,image/png,image/webp">
        </label>
        <label>
            Logo akreditasi (maks. 10 MB)
            <input type="file" name="accreditation_file" accept="image/jpeg,image/png,image/webp">
        </label>
    </div>
    <label class="check">
        <input type="checkbox" name="clear_company_image">
        Gunakan kembali logo perusahaan bawaan
    </label>
    <label class="check">
        <input type="checkbox" name="clear_accreditation_image">
        Gunakan kembali logo akreditasi bawaan
    </label>
    <div class="form-grid">
        <label>
            Upload logo (JPG/PNG/WebP, maks. 10 MB)
            <input type="file" name="logo_file" accept="image/jpeg,image/png,image/webp">
        </label>
        <label>
            Upload gambar utama (maks. 10 MB)
            <input type="file" name="hero_file" accept="image/jpeg,image/png,image/webp">
        </label>
    </div>
    <label class="check">
        <input type="checkbox" name="clear_logo_image">
        Gunakan kembali logo rumah sakit bawaan
    </label>
    <label class="check">
        <input type="checkbox" name="clear_hero_image">
        Gunakan kembali foto dokter bawaan
    </label>
    <h2>Beranda & profil</h2>
    <?php foreach (
        [
            'hero_title' => 'Judul utama (Enter untuk baris baru)',
            'hero_text' => 'Deskripsi utama',
            'about' => 'Tentang rumah sakit',
        ]
        as $key => $label
    ): ?>
    <label>
        <?= $label ?>
        <textarea name="<?= $key ?>" rows="4" required><?= e(setting($key)) ?></textarea>
    </label>
    <?php endforeach; ?>
    <div class="form-grid">
        <label>
            Penanda data contoh
            <select name="demo">
                <option value="1" <?= setting('demo') === '1'
                    ? 'selected'
                    : '' ?>>Tampilkan</option>
                <option value="0" <?= setting('demo') === '0'
                    ? 'selected'
                    : '' ?>>Sembunyikan — setelah data diverifikasi</option>
            </select>
        </label>
        <label>
            Popup beranda
            <select name="popup_enabled">
                <option value="1" <?= setting('popup_enabled') === '1'
                    ? 'selected'
                    : '' ?>>Aktif</option>
                <option value="0" <?= setting('popup_enabled') === '0'
                    ? 'selected'
                    : '' ?>>Nonaktif</option>
            </select>
        </label>
    </div>
    <button class="button">Simpan pengaturan</button>
</form>
