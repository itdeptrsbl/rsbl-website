<?php
// Mengisi data contoh untuk instalasi pertama, bukan untuk data operasional.

// Konten awal hanya untuk meninjau desain. Data layanan dan jadwal harus diverifikasi RS.
$settings = [
    'name' => 'RS Bintang Laut',
    'tagline' => 'Melayangi dengan Semangat Kasih',
    'hero_title' => 'Servire In Caritate',
    'hero_text' =>
        'Temukan layanan kesehatan dan informasi dokter dengan lebih mudah. Kami hadir untuk menemani setiap langkah perawatan Anda.',
    'address' => 'Jl. K.H.M. Kasim No. 5, Palopo, Sulawesi Selatan',
    'phone' => '+6247121302',
    'whatsapp' => '6285394117711',
    'about' =>
        'Rumah Sakit Bintang Laut hadir di Palopo dengan semangat melayani dalam kasih. Kami berupaya menghadirkan pelayanan yang ramah, nyaman, dan berpusat pada pasien.',
    'logo_image' => '',
    'hero_image' => '',
    'demo' => '1',
    'popup_enabled' => '1',
];
foreach ($settings as $k => $v) {
    execute('INSERT INTO settings (name,value) VALUES (?,?)', [$k, str_replace('\\n', "\n", $v)]);
}
$items = [
    [
        'service',
        'Rawat Jalan',
        'Konsultasi & pemeriksaan',
        'Kenali pilihan poliklinik dan konsultasikan kebutuhan kesehatan Anda.',
        '01',
    ],
    [
        'service',
        'Rawat Inap',
        'Kenyamanan dalam perawatan',
        'Informasi ruang perawatan dan fasilitas untuk pasien dan keluarga.',
        '02',
    ],
    [
        'service',
        'Instalasi Gawat Darurat',
        'Bantuan saat dibutuhkan',
        'Untuk kondisi darurat, hubungi rumah sakit atau datang langsung ke IGD.',
        '03',
    ],
    [
        'service',
        'Medical Check Up',
        'Langkah kecil, manfaat besar',
        'Dapatkan informasi paket pemeriksaan kesehatan melalui petugas kami.',
        '04',
    ],
    [
        'news',
        'Mulai hidup sehat dari kebiasaan sederhana',
        'Artikel kesehatan • Konten contoh',
        'Jaga pola makan seimbang, bergerak secara rutin, dan luangkan waktu untuk beristirahat. Artikel ini adalah konten contoh untuk meninjau desain; ganti dengan artikel yang telah ditinjau tim rumah sakit.',
        '',
    ],
    [
        'event',
        'Bersama membangun kebiasaan hidup sehat',
        'Kegiatan RS • Konten contoh',
        'Ruang informasi kegiatan, edukasi pasien, dan acara rumah sakit. Konten contoh ini dapat diubah melalui menu master.',
        '',
    ],
    [
        'promo',
        'Kenali kebutuhan kesehatan Anda',
        'Informasi layanan • Konten contoh',
        'Hubungi petugas untuk mendapatkan informasi paket Medical Check Up yang tersedia. Ini contoh tampilan promo, bukan penawaran paket resmi.',
        '',
    ],
    [
        'popup',
        'Selamat datang di RS Bintang Laut',
        'Lebih dekat, lebih mudah',
        'Cari jadwal dokter dan ajukan pendaftaran melalui website. Jadwal pada versi review ini masih berupa contoh.',
        '',
    ],
    [
        'page',
        'Panduan Pasien',
        'Sebelum berkunjung',
        'Siapkan identitas dan dokumen penjamin bila diperlukan. Hubungi petugas untuk informasi persyaratan layanan.',
        '',
    ],
    [
        'page',
        'Visi & Misi',
        'Profil rumah sakit',
        'Silakan isi visi dan misi resmi rumah sakit melalui menu master.',
        '',
    ],
    [
        'partner',
        'Mitra & penjamin',
        'Informasi kepesertaan',
        'Hubungi petugas untuk memastikan kerja sama dan ketentuan penjamin Anda.',
        '',
    ],
];
foreach ($items as $i) {
    execute(
        'INSERT INTO contents (kind,title,subtitle,body,image,link,starts,ends,active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)',
        [$i[0], $i[1], $i[2], $i[3], '', '', '', '', 1, (int) $i[4]],
    );
}
foreach (
    [
        ['Dokter Contoh A', 'Poli Penyakit Dalam', '1,3,5', '09.00–12.00'],
        ['Dokter Contoh B', 'Poli Anak', '2,4,6', '10.00–13.00'],
        ['Dokter Contoh C', 'Poli Gigi & Mulut', '1,2,3,4,5', '08.00–11.00'],
    ]
    as $d
) {
    execute('INSERT INTO doctors (name,specialty,days,hours,image,active) VALUES (?,?,?,?,?,1)', [
        ...$d,
        '',
    ]);
}
