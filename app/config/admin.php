<?php
// Daftar menu admin dan kategori konten yang boleh dikelola.

$modules = [
    'dashboard' => 'Ringkasan',
    'settings' => 'Pengaturan Website',
    'contents' => 'Konten Website',
    'home_content' => 'Beranda',
    'facilities' => 'Fasilitas & Layanan',
    'about_content' => 'Tentang Rumah Sakit',
    'extras' => 'Halaman Tambahan',
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

// Setiap kategori hanya memiliki satu lokasi pengelolaan.
$contentGroups = [
    'home_content' => ['slide', 'service', 'partner', 'popup'],
    'facilities' => array_merge(
        ['service_slide', 'outpatient_photo', 'clinic', 'inpatient_room', 'emergency_photo'],
        facilityPhotoKinds(),
    ),
    'about_content' => ['history', 'vision', 'quality'],
    'information' => informationKinds(),
    'extras' => ['page'],
];

$kinds['service'] = 'Kartu Layanan Beranda';
$kinds['news'] = 'Berita';
$kinds['page'] = 'Halaman Tambahan';
$kinds['popup'] = 'Popup Beranda';
