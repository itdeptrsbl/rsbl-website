<?php
// Nilai awal identitas perusahaan dan pembuat ikon kontak serta sosial media.

// Penambahan pengaturan bersifat aditif: isi lama, akun, dan pendaftaran tidak diganti.
$brandingDefaults = [
    'company_name' => 'PT. Citra Ratna Nirmala',
    'company_image' => '',
    'accreditation_image' => '',
    'instagram' => 'https://www.instagram.com/bintanglaut_hospital',
    'facebook' => 'https://www.facebook.com/share/1AwDs2N41p/',
    'youtube' => 'https://youtube.com/@rs.bintanglaut',
    'tiktok' => 'https://www.tiktok.com/@rs.bintanglautpalopo',
];
foreach ($brandingDefaults as $key => $value) {
    if (!query('SELECT name FROM settings WHERE name=?', [$key])) {
        execute('INSERT INTO settings (name,value) VALUES (?,?)', [$key, $value]);
    }
}
// Memilih logo unggahan atau gambar identitas bawaan.
function brandingImage(string $key, string $fallback): string
{
    $value = setting($key);
    return $value !== '' ? photo($value) : $fallback;
}
// Ikon vektor kecil tetap tajam pada desktop maupun mobile.
// Menghasilkan ikon SVG kecil sesuai nama kontak atau sosial media.
function socialIcon(string $name): string
{
    $paths = [
        'phone' =>
            '<path d="M7 3H4a1 1 0 0 0-1 1c0 9 8 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 2a15 15 0 0 1-7-7l2-2z"/>',
        'whatsapp' =>
            '<path d="M21 11.5a9 9 0 0 1-13.5 8L3 21l1.5-4.5A9 9 0 1 1 21 11.5Z"/><path d="m8 7 2 3-1 1a8 8 0 0 0 4 4l1-1 3 2"/>',
        'instagram' =>
            '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".7"/>',
        'facebook' =>
            '<path d="M14 21v-8h3l.5-4H14V7c0-1 .5-2 2-2h2V2h-3c-4 0-5 2-5 5v2H7v4h3v8"/>',
        'youtube' => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 6 3-6 3z"/>',
        'tiktok' => '<path d="M14 3v12a4 4 0 1 1-4-4M14 3c1 4 3 5 6 5"/>',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' .
        ($paths[$name] ?? '') .
        '</svg>';
}
