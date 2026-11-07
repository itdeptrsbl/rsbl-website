<?php
// Menyiapkan pilihan formulir; transaksi pasien menunggu kontrak API Medinfras.
$doctors = query('SELECT * FROM doctors WHERE active=1 ORDER BY name');
$registrationDoctors = array_map(function (array $doctor): array {
    // Hanya data publik dokter yang boleh diteruskan ke browser.
    return [
        'id' => (int) $doctor['id'],
        'name' => $doctor['name'],
        'clinic' => $doctor['specialty'],
        'sessions' => doctorSessions($doctor),
    ];
}, $doctors);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    // Jangan membuat bukti pendaftaran lokal seolah transaksi sudah masuk SIMRS.
    http_response_code(503);
    $error =
        'Pendaftaran belum dapat dikirim. Integrasi Medinfras sedang disiapkan. Silakan hubungi petugas rumah sakit.';
}
