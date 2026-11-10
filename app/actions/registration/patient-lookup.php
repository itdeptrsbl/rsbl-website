<?php
// POST website hanya untuk verifikasi; koneksi ke Medinfras tetap GET pasien berdasarkan MRN.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
$reply = static function (int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body);
    exit();
};
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    $reply(405, ['error' => 'Metode tidak diizinkan.']);
}
if (
    !is_string($_POST['csrf'] ?? null) ||
    empty($_SESSION['csrf']) ||
    !hash_equals($_SESSION['csrf'], $_POST['csrf'])
) {
    $reply(419, ['error' => 'Sesi formulir kedaluwarsa. Muat ulang halaman.']);
}
unset($_SESSION['medinfras_verified_patient']);
$medicalNumber = $_POST['medical_record_number'] ?? null;
$birthDate = $_POST['verification_birth_date'] ?? null;
if (!is_string($medicalNumber) || !is_string($birthDate)) {
    $reply(400, ['error' => 'Isi nomor rekam medis dan tanggal lahir.']);
}
$medicalNumber = trim($medicalNumber);
if (
    !preg_match('/^[A-Za-z0-9-]{1,40}$/D', $medicalNumber) ||
    !validDate($birthDate) ||
    $birthDate > date('Y-m-d')
) {
    $reply(400, ['error' => 'Periksa nomor rekam medis dan tanggal lahir Anda.']);
}
require_once APP_ROOT . '/app/integrations/medinfras/Client.php';
require_once APP_ROOT . '/app/integrations/medinfras/PatientLookup.php';
try {
    // Header Cloudflare hanya dipercaya bila request datang dari tunnel lokal, bukan koneksi publik langsung.
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (
        in_array($clientIp, ['127.0.0.1', '::1'], true) &&
        filter_var($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '', FILTER_VALIDATE_IP)
    ) {
        $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (
        !\Rsbl\Integrations\Medinfras\PatientLookup::allowAttempt(
            APP_ROOT . '/storage/private/patient-lookup-limits',
            $clientIp,
            time(),
        )
    ) {
        header('Retry-After: 900');
        $reply(429, [
            'error' => 'Batas pencarian tercapai. Coba lagi dalam 15 menit atau hubungi petugas.',
        ]);
    }
    $client = new \Rsbl\Integrations\Medinfras\Client(
        require APP_ROOT . '/app/config/medinfras.php',
    );
    $patient = \Rsbl\Integrations\Medinfras\PatientLookup::verify(
        $client->patientByMedicalNumber($medicalNumber),
        $medicalNumber,
        $birthDate,
    );
    if ($patient === null) {
        $reply(422, [
            'error' =>
                'Data belum dapat diverifikasi. Periksa nomor rekam medis dan tanggal lahir Anda.',
        ]);
    }
    $_SESSION['medinfras_verified_patient'] = [
        'medical_number' => $medicalNumber,
        'birth_date' => $birthDate,
        'expires' => time() + 900,
    ];
    $reply(200, ['patient' => $patient]);
} catch (Throwable $exception) {
    // Jangan membocorkan respons SIMRS, nomor rekam medis, atau identitas dalam pesan/log.
    $reply(503, [
        'error' => 'Data pasien belum dapat diverifikasi. Coba kembali atau hubungi petugas.',
    ]);
}
