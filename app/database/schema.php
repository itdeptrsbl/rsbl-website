<?php
// Membuat tabel awal saat instalasi tanpa menghapus tabel yang sudah ada.

// SQL portabel untuk SQLite dan MySQL; instalasi tidak menghapus data yang sudah ada.
// Sintaks primary key disesuaikan untuk driver SQLite atau MySQL.
$identity =
    $config['driver'] === 'mysql'
        ? 'INT AUTO_INCREMENT PRIMARY KEY'
        : 'INTEGER PRIMARY KEY AUTOINCREMENT';
$db->exec(
    "CREATE TABLE IF NOT EXISTS users (id $identity, email VARCHAR(190) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL)",
);
$db->exec(
    'CREATE TABLE IF NOT EXISTS settings (name VARCHAR(100) PRIMARY KEY, value TEXT NOT NULL)',
);
$db->exec(
    "CREATE TABLE IF NOT EXISTS contents (id $identity, kind VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL, subtitle VARCHAR(255) NOT NULL, body TEXT NOT NULL, image TEXT NOT NULL, link TEXT NOT NULL, starts VARCHAR(10) NOT NULL, ends VARCHAR(10) NOT NULL, active INT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0)",
);
$db->exec(
    "CREATE TABLE IF NOT EXISTS doctors (id $identity, name VARCHAR(255) NOT NULL, specialty VARCHAR(255) NOT NULL, days VARCHAR(30) NOT NULL, hours VARCHAR(100) NOT NULL, image TEXT NOT NULL, active INT NOT NULL DEFAULT 1)",
);
$db->exec(
    "CREATE TABLE IF NOT EXISTS appointments (id $identity, reference VARCHAR(50) NOT NULL UNIQUE, patient_name VARCHAR(150) NOT NULL, phone VARCHAR(25) NOT NULL, doctor_id INT NOT NULL, visit_date VARCHAR(10) NOT NULL, payment VARCHAR(30) NOT NULL, status VARCHAR(30) NOT NULL, notes TEXT NOT NULL, created_at VARCHAR(30) NOT NULL)",
);
// Percobaan login disimpan di database agar pembatasan tidak hanya bergantung pada cookie.
$db->exec(
    'CREATE TABLE IF NOT EXISTS login_attempts (attempt_key VARCHAR(64) PRIMARY KEY, attempts INT NOT NULL, blocked_until INT NOT NULL)',
);
