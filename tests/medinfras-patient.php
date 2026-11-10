<?php
// Data samaran saja: tes verifikasi pasien tidak memanggil SIMRS atau memakai identitas pasien nyata.
require dirname(__DIR__) . '/app/integrations/medinfras/PatientLookup.php';
require dirname(__DIR__) . '/app/integrations/medinfras/Client.php';
use Rsbl\Integrations\Medinfras\PatientLookup;
use Rsbl\Integrations\Medinfras\Client;
function patientCheck(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo 'PASS ' . $message . PHP_EOL;
}
$fixture = [
    'MedicalNo' => '00-99-99-99',
    'DateOfBirth' => '1999-03-11T00:00:00',
    'PatientName' => 'Pasien Samaran',
    'FirstName' => 'Pasien',
    'LastName' => 'Samaran',
    'Gender' => 'F',
    'MobileNo' => '0800000000',
    'Diagnosis' => 'PRIVATE',
    'MedicalHistory' => 'PRIVATE',
    'IdentityCardNo' => 'PRIVATE',
];
$verified = PatientLookup::verify($fixture, '00-99-99-99', '1999-03-11');
patientCheck(
    $verified['patient_name'] === 'Pasien Samaran' &&
        $verified['birth_date'] === '1999-03-11' &&
        $verified['gender'] === 'Perempuan',
    'Pasangan MRN dan tanggal lahir cocok mengisi field formulir',
);
patientCheck(
    !str_contains(json_encode($verified), 'PRIVATE'),
    'Diagnosis, riwayat, dan nomor identitas tidak diteruskan',
);
patientCheck(
    PatientLookup::verify($fixture, '00-99-99-99', '1999-03-12') === null,
    'Tanggal lahir salah tidak membuka data',
);
patientCheck(
    PatientLookup::verify($fixture, '00-99-99-98', '1999-03-11') === null,
    'MRN salah tidak membuka data',
);
patientCheck(
    PatientLookup::verify([$fixture, $fixture], '00-99-99-99', '1999-03-11') === null,
    'Respons ambigu ditolak',
);
$compact = array_replace($fixture, ['DateOfBirth' => '19990311']);
patientCheck(
    PatientLookup::verify([$compact], '00-99-99-99', '1999-03-11') !== null,
    'Tanggal yyyyMMdd dan daftar satu pasien didukung',
);
patientCheck(
    PatientLookup::verify([], '00-99-99-99', '1999-03-11') === null,
    'Respons tanpa pasien tidak dianggap cocok',
);
$client = new Client(
    ['base_url' => 'https://example.test/api', 'consumer_id' => 'TEST', 'password' => 'TEST'],
    static function ($url, $headers, $method) use ($fixture) {
        patientCheck(
            $method === 'GET' && $url === 'https://example.test/api/patient/mrn/00-99-99-99',
            'Lookup hanya memanggil GET MRN pada SIMRS',
        );
        return [200, json_encode(['Status' => 'SUCCESS', 'Data' => $fixture])];
    },
);
patientCheck(
    PatientLookup::verify(
        $client->patientByMedicalNumber('00-99-99-99'),
        '00-99-99-99',
        '1999-03-11',
    ) !== null,
    'Hasil Client diverifikasi sebelum dikirim ke browser',
);
$directory = sys_get_temp_dir() . '/rsbl-patient-test-' . bin2hex(random_bytes(8));
try {
    for ($i = 0; $i < 5; $i++) {
        patientCheck(
            PatientLookup::allowAttempt($directory, '192.0.2.1', 1000),
            'Percobaan dalam batas diizinkan',
        );
    }
    patientCheck(
        !PatientLookup::allowAttempt($directory, '192.0.2.1', 1001),
        'Percobaan keenam ditolak walaupun cookie sesi diganti',
    );
    patientCheck(
        PatientLookup::allowAttempt($directory, '192.0.2.2', 1001),
        'IP berbeda memiliki batas terpisah',
    );
    patientCheck(
        PatientLookup::allowAttempt($directory, '192.0.2.1', 1900),
        'Pencarian dapat dicoba kembali setelah 15 menit',
    );
} finally {
    foreach (glob($directory . '/*.json') ?: [] as $file) {
        unlink($file);
    }
    if (is_dir($directory)) {
        rmdir($directory);
    }
}
