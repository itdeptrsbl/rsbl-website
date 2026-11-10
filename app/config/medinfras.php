<?php
// Rahasia hanya di server: variabel lingkungan diprioritaskan atas konfigurasi privat lokal.
$privatePath = dirname(__DIR__, 2) . '/storage/private/medinfras.php';
$private = is_file($privatePath) ? require $privatePath : [];
if (!is_array($private)) {
    $private = [];
}
return [
    // Integrasi dihentikan sementara sesuai permintaan; tidak ada koneksi SIMRS saat false.
    'enabled' => false,
    // Pengecualian terbatas: hanya GET daftar poli rawat jalan.
    'clinics_enabled' => true,
    // GET penjamin per kategori saja; appointment dan pasien tetap nonaktif.
    'payers_enabled' => true,
    // URL dasar adalah nilai URL Postman ditambah /api (jangan lupa prefix ini).
    'base_url' => rtrim(
        (string) (getenv('MEDINFRAS_BASE_URL') ?:
        'https://36.94.176.235/medinfrasapi2.1/bpjs/rsbl/api'),
        '/',
    ),
    'consumer_id' => (string) (getenv('MEDINFRAS_CONSUMER_ID') ?: $private['consumer_id'] ?? ''),
    'password' => (string) (getenv('MEDINFRAS_PASSWORD') ?: $private['password'] ?? ''),
    // HTTP hanya untuk jaringan internal yang memang disiapkan tim rumah sakit.
    'allow_http' =>
        getenv('MEDINFRAS_ALLOW_HTTP') === '1' ||
        (getenv('MEDINFRAS_ALLOW_HTTP') === false &&
            parse_url(
                (string) (getenv('MEDINFRAS_BASE_URL') ?: 'http://192.168.10.5'),
                PHP_URL_HOST,
            ) === '192.168.10.5'),
    'timeout' => 15,
    'healthcare_id' => (string) (getenv('MEDINFRAS_HEALTHCARE_ID') ?: '001'),
    'user_id' => (string) getenv('MEDINFRAS_USER_ID'),
];
