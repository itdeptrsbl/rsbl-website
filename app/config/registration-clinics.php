<?php
// Nama publik dan kode unit resmi Medinfras; tidak menghapus angka/pagi/sore dengan tebakan.
// Specialty hanya menyaring calon dokter; kecocokan unit wajib diverifikasi dari jadwal API.
return [
    'anak' => [
        'name' => 'Poli Anak',
        'units' => ['A-002', 'A-003', 'A-004'],
        'specialties' => ['Anak'],
    ],
    'gigi' => [
        'name' => 'Poli Gigi',
        'units' => [
            'G-001',
            'G-001P',
            'G-002P',
            'G-003P',
            'G-004P',
            'G-001S',
            'G-002S',
            'G-003S',
            'G-004S',
            'G-003.1S',
        ],
        'specialties' => ['Gigi dan Mulut'],
    ],
    'gigi-anak' => [
        'name' => 'Poli Gigi Anak',
        'units' => ['GA-001', 'GA-002'],
        'specialties' => ['Kedokteran Gigi Anak'],
    ],
    'bedah' => ['name' => 'Poli Bedah', 'units' => ['B-001', 'B-002'], 'specialties' => ['Bedah']],
    'bedah-mulut' => [
        'name' => 'Poli Bedah Mulut',
        'units' => ['BM-001', 'BM-002'],
        'specialties' => ['BEDAH MULUT DAN MAKSILOFASIAL'],
    ],
    'gizi' => [
        'name' => 'Poli Gizi',
        'units' => ['G-002'],
        'specialties' => ['GIZI KLINIK', 'GIZI'],
    ],
    'ibu-anak' => [
        'name' => 'Poli Ibu dan Anak',
        'units' => ['I-001'],
        'specialties' => ['IBU & ANAK'],
    ],
    'infeksius' => ['name' => 'Poli Infeksius', 'units' => ['I-002'], 'specialties' => ['Umum']],
    'cepat' => ['name' => 'Poli Cepat', 'units' => ['U-001'], 'specialties' => ['Umum']],
    'jantung' => [
        'name' => 'Poli Jantung',
        'units' => ['J-001', 'J-002', 'J-003', 'J-004'],
        'specialties' => ['Jantung dan Pembuluh Darah'],
    ],
    'kulit' => [
        'name' => 'Poli Kulit dan Kelamin',
        'units' => ['K-001', 'K-002'],
        'specialties' => ['Kulit dan Kelamin'],
    ],
    'mata' => ['name' => 'Poli Mata', 'units' => ['M-002', 'M-003'], 'specialties' => ['Mata']],
    'obgyn' => [
        'name' => 'Poli Obgyn',
        'units' => ['O-001', 'O-002'],
        'specialties' => ['KEBIDANAN & KANDUNGAN'],
    ],
    'tindakan' => [
        'name' => 'Poli Tindakan',
        'units' => ['Z-001', 'Z-002', 'Z-003'],
        'specialties' => ['Umum'],
    ],
    'tht' => ['name' => 'Poli THT', 'units' => ['T-001', 'T-002'], 'specialties' => ['T H T']],
    'ortopedi' => [
        'name' => 'Poli Ortopedi dan Traumatologi',
        'units' => ['OT-001', 'OT-002'],
        'specialties' => ['ORTHOPEDI DAN TRAUMATOLOGI'],
    ],
    'saraf' => [
        'name' => 'Poli Saraf',
        'units' => ['S-001', 'S-002', 'S-003', 'S-004'],
        'specialties' => ['SYARAF'],
    ],
    'rehabilitasi' => [
        'name' => 'Poli Rehabilitasi Medik',
        'units' => ['F-001', 'F-002'],
        'specialties' => ['REHABILITASI MEDIK'],
    ],
    'penyakit-dalam' => [
        'name' => 'Poli Penyakit Dalam',
        'units' => ['P-001', 'P-002', 'PD-001', 'PD-002', 'PD-003', 'PD-004'],
        'specialties' => ['PENYAKIT DALAM'],
    ],
    'penyakit-mulut' => [
        'name' => 'Poli Penyakit Mulut',
        'units' => ['PM-001', 'PM-002'],
        'specialties' => [],
    ],
    'paru' => ['name' => 'Poli Paru', 'units' => ['PA-001', 'PA-002'], 'specialties' => ['Paru']],
    'periodonsia' => [
        'name' => 'Poli Periodonsia',
        'units' => ['GP-001P', 'GP-002'],
        'specialties' => ['PERIODENSIA'],
    ],
    'konservasi-gigi' => [
        'name' => 'Poli Konservasi Gigi',
        'units' => ['KG-001', 'KG-002'],
        'specialties' => ['Konservasi Gigi'],
    ],
    'jiwa' => [
        'name' => 'Poli Kesehatan Jiwa',
        'units' => ['J-005', 'J-006'],
        'specialties' => ['Jiwa'],
    ],
];
