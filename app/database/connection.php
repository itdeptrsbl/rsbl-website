<?php
// Membuka koneksi PDO ke SQLite atau MySQL sesuai konfigurasi.

try {
    if ($config['driver'] === 'mysql') {
        $m = $config['mysql'];
        $db = new PDO(
            "mysql:host={$m['host']};port={$m['port']};dbname={$m['database']};charset=utf8mb4",
            $m['username'],
            $m['password'],
        );
    } else {
        $db = new PDO('sqlite:' . APP_ROOT . '/storage/rsbl.sqlite');
        $db->exec('PRAGMA foreign_keys = ON');
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(503);
    exit('Database belum tersedia. Periksa config.php dan ekstensi PDO sesuai README.');
}
