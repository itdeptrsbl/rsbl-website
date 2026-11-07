<?php
// Pengaturan koneksi database, zona waktu, dan keamanan cookie sesi.
// SQLite memudahkan review; ubah driver menjadi mysql untuk memakai database XAMPP.
return [
    'driver' => 'sqlite',
    'mysql' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'rsbl_website',
        'username' => 'root',
        'password' => '',
    ],
    'timezone' => 'Asia/Makassar', // Palopo menggunakan WITA.
    'secure_cookie' => false, // Aktifkan saat situs dipasang dengan HTTPS.
];
