<?php
// Menyimpan identitas, kontak, logo, dan pengaturan umum website.

foreach (
    [
        'logo_image' => 'logo_file',
        'hero_image' => 'hero_file',
        'company_image' => 'company_file',
        'accreditation_image' => 'accreditation_file',
    ]
    as $key => $field
) {
    $uploaded = uploadImage($field);
    if ($uploaded !== '') {
        execute('UPDATE settings SET value=? WHERE name=?', [$uploaded, $key]);
    }
    if (isset($_POST['clear_' . $key])) {
        execute('UPDATE settings SET value=? WHERE name=?', ['', $key]);
    }
}

$keys = [
    'name',
    'tagline',
    'hero_title',
    'hero_text',
    'address',
    'phone',
    'whatsapp',
    'about',
    'demo',
    'popup_enabled',
    'company_name',
    'instagram',
    'facebook',
    'youtube',
    'tiktok',
];

foreach ($keys as $key) {
    $value = trim($_POST[$key] ?? '');
    if (
        in_array($key, ['instagram', 'facebook', 'youtube', 'tiktok'], true) &&
        $value !== '' &&
        !preg_match('~^https://[^\s]+$~i', $value)
    ) {
        throw new RuntimeException('Tautan sosial harus dimulai dengan https://');
    }
    if (strlen($value) > 10000) {
        throw new RuntimeException('Isi pengaturan terlalu panjang.');
    }
    execute('UPDATE settings SET value=? WHERE name=?', [$value, $key]);
}

flash('Pengaturan website disimpan.');
