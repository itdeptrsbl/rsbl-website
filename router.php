<?php
// Router server lokal: melayani halaman utama dan aset yang diizinkan saja.
// Server lokal: hanya entry point dan aset publik yang dapat dilayani.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$asset =
    preg_match('~^/assets/(?:css|js)/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_.-]+\.(css|js)$~', $path) ||
    preg_match(
        '~^/assets/(?:(?:uploads|branding)/)?[a-zA-Z0-9_.-]+\.(svg|png|jpg|webp|avif)$~',
        $path,
    ) ||
    preg_match('~^/assets/uploads/[a-f0-9]{32}\.(mp4|webm)$~', $path);
if ($asset && is_file(__DIR__ . $path)) {
    return false;
}
if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/index.php';
    return true;
}
http_response_code(404);
echo 'Halaman tidak ditemukan.';
