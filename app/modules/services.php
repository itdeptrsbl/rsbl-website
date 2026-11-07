<?php
// Kategori layanan, tautan halaman, dan penyimpanan deskripsi layanan sederhana.

// Daftar kategori digunakan bersama oleh navigasi dan halaman fasilitas.
// Daftar tujuh kategori untuk kartu dan menu fasilitas.
function facilityCategories(): array
{
    return [
        'rawat-jalan' => [
            'title' => 'Rawat Jalan',
            'caption' => 'Konsultasi & pemeriksaan',
            'icon' =>
                'M9 3v5a3 3 0 0 1-6 0V3M3 3H2m7 0h1M6 11v3a5 5 0 0 0 10 0v-2m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
        ],
        'rawat-inap' => [
            'title' => 'Rawat Inap',
            'caption' => 'Ruang & kenyamanan perawatan',
            'icon' => 'M3 18V7m0 8h18v3m0-3V9H9v6M3 11h6M6 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z',
        ],
        'rawat-darurat' => [
            'title' => 'Instalasi Gawat Darurat',
            'caption' => 'Penanganan kondisi darurat',
            'icon' => 'M3 12h4l3-7 4 14 3-7h4',
        ],
        'farmasi' => [
            'title' => 'Farmasi',
            'caption' => 'Informasi pelayanan obat',
            'icon' => 'm8 16 8-8M5 11l6-6a5 5 0 0 1 7 7l-6 6a5 5 0 0 1-7-7Z',
        ],
        'radiologi' => [
            'title' => 'Radiologi',
            'caption' => 'Pemeriksaan pencitraan',
            'icon' => 'M5 3h14v18H5ZM9 7l6 10m0-10L9 17M8 12h8',
        ],
        'fisioterapi' => [
            'title' => 'Fisioterapi',
            'caption' => 'Pemulihan & rehabilitasi',
            'icon' => 'M12 3a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM4 10l8-1 8 1m-8-1v6m0 0-5 6m5-6 5 6',
        ],
        'laboratorium' => [
            'title' => 'Laboratorium',
            'caption' => 'Pemeriksaan penunjang',
            'icon' => 'M9 3h6m-5 0v7L4 19a1 1 0 0 0 1 2h14a1 1 0 0 0 1-2l-6-9V3M7 15h10',
        ],
    ];
}

// Menentukan URL tujuan sesuai kategori fasilitas yang dipilih.
function facilityUrl(string $slug): string
{
    return match ($slug) {
        'rawat-jalan' => 'index.php?page=outpatient',
        'rawat-inap' => 'index.php?page=inpatient',
        'rawat-darurat' => 'index.php?page=emergency',
        'farmasi', 'radiologi', 'fisioterapi', 'laboratorium' => 'index.php?page=' . $slug,
        default => 'index.php?page=services#' . $slug,
    };
}

// Empat layanan sederhana berbagi tampilan dan master, dengan data terpisah.
// Konfigurasi empat layanan yang hanya memerlukan deskripsi dan galeri.
function facilityPhotoPages(): array
{
    return [
        'farmasi' => ['title' => 'Farmasi', 'kind' => 'pharmacy_photo'],
        'radiologi' => ['title' => 'Radiologi', 'kind' => 'radiology_photo'],
        'fisioterapi' => ['title' => 'Fisioterapi', 'kind' => 'physiotherapy_photo'],
        'laboratorium' => ['title' => 'Laboratorium', 'kind' => 'laboratory_photo'],
    ];
}

// Mengambil nama kategori foto untuk master layanan sederhana.
function facilityPhotoKinds(): array
{
    return array_column(facilityPhotoPages(), 'kind');
}

// Memvalidasi dan menyimpan penjelasan singkat satu layanan.
function saveFacilityIntro(): void
{
    $page = $_POST['service_page'] ?? '';
    $intro = $_POST['facility_intro'] ?? '';
    if (
        !is_string($page) ||
        !isset(facilityPhotoPages()[$page]) ||
        !is_string($intro) ||
        strlen($intro) > 10000
    ) {
        throw new RuntimeException('Layanan tidak valid atau penjelasan melebihi 10.000 karakter.');
    }
    $key = 'facility_intro_' . $page;
    if (query('SELECT name FROM settings WHERE name=?', [$key])) {
        execute('UPDATE settings SET value=? WHERE name=?', [trim($intro), $key]);
    } else {
        execute('INSERT INTO settings (name,value) VALUES (?,?)', [$key, trim($intro)]);
    }
    flash('Penjelasan layanan disimpan.');
}
