<?php
// Daftar menu admin dan kategori konten yang boleh dikelola.

$modules = [
    'dashboard' => 'Ringkasan',
    'settings' => 'Pengaturan Website',
    'contents' => 'Master Konten',
    'information' => 'Informasi',
    'doctors' => 'Dokter & Jadwal',
    'appointments' => 'Pendaftaran',
    'account' => 'Akun Saya',
];

$kinds = [
    'slide' => 'Slideshow Beranda',
    'service_slide' => 'Foto Fasilitas & Layanan',
    'outpatient_photo' => 'Rawat Jalan',
    'inpatient_room' => 'Rawat Inap & Kamar',
    'emergency_photo' => 'Instalasi Gawat Darurat',
    'clinic' => 'Daftar Poli',
    'service' => 'Layanan',
    'news' => 'Informasi / Berita',
    'event' => 'Acara',
    'promo' => 'Promo',
    'popup' => 'Popup',
    'page' => 'Halaman Informasi',
    'partner' => 'Mitra',
    'history' => 'Sejarah',
    'vision' => 'Visi & Misi',
    'quality' => 'Laporan Mutu',
    'career' => 'Karier',
];

foreach (facilityPhotoPages() as $facilityPage) {
    $kinds[$facilityPage['kind']] = $facilityPage['title'];
}
