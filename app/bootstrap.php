<?php
// Menyiapkan folder dasar, sesi, header HTTP, koneksi database, dan helper.

// Semua include dan path penyimpanan memakai akar proyek yang sama.
define('APP_ROOT', dirname(__DIR__));
// Bootstrap bersama: konfigurasi, sesi aman, database, dan fungsi pembantu.
$config = require APP_ROOT . '/config.php';
date_default_timezone_set($config['timezone']);
// Konfigurasi cookie diterapkan sebelum sesi dibuka.
session_set_cookie_params([
    'httponly' => true,
    'secure' => $config['secure_cookie'],
    'samesite' => 'Lax',
]);
session_start();
// Header dasar mengatur keamanan respons browser.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' https: data: blob:; media-src 'self' https:; frame-src https://www.youtube-nocookie.com; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
);
require APP_ROOT . '/app/database/connection.php';
require APP_ROOT . '/app/support/helpers.php';
