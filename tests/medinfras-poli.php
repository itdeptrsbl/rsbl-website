<?php
// Uji GET poli memakai transport tiruan; tidak memanggil API atau transaksi lain.
require dirname(__DIR__) . '/app/integrations/medinfras/Client.php';
require dirname(__DIR__) . '/app/integrations/medinfras/OutpatientUnits.php';

use Rsbl\Integrations\Medinfras\Client;
use Rsbl\Integrations\Medinfras\OutpatientUnits;

// Pemeriksaan eksplisit tetap aktif walaupun assert PHP dinonaktifkan.
function checkPoli(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException($label);
    }
    echo 'PASS ' . $label . PHP_EOL;
}
$rows = [
    [
        'DepartmentID' => 'OUTPATIENT',
        'ServiceUnitCode' => 'A-003',
        'ServiceUnitName' => 'KLINIK ANAK SORE',
        'IPAddress' => 'private',
    ],
    [
        'DepartmentID' => 'OUTPATIENT',
        'ServiceUnitCode' => 'A-002',
        'ServiceUnitName' => 'KLINIK ANAK PAGI',
        'HealthcareName' => 'Internal',
    ],
    ['DepartmentID' => 'INPATIENT', 'ServiceUnitCode' => 'I-001', 'ServiceUnitName' => 'Kamar'],
    ['DepartmentID' => 'OUTPATIENT', 'ServiceUnitCode' => '', 'ServiceUnitName' => 'Tanpa kode'],
];
$calls = 0;
$client = new Client(
    [
        'base_url' => 'https://example.test/api',
        'consumer_id' => 'test-only',
        'password' => 'test-only-secret',
    ],
    function ($url, $headers, $method, $body) use (&$calls, $rows): array {
        $calls++;
        checkPoli(
            $method === 'GET' &&
                $body === null &&
                $url ===
                    'https://example.test/api/healthcareserviceunit/base/list/department/outpatient',
            'Hanya GET endpoint poli rawat jalan',
        );
        return [200, json_encode(['Status' => 'SUCCESS', 'Data' => json_encode($rows)])];
    },
);
$units = OutpatientUnits::normalize($client->outpatientUnits());
checkPoli($calls === 1, 'Hanya satu permintaan API');
checkPoli(
    $units === [
        ['code' => 'A-002', 'name' => 'KLINIK ANAK PAGI'],
        ['code' => 'A-003', 'name' => 'KLINIK ANAK SORE'],
    ],
    'Kode API dipertahankan, hasil diurutkan, metadata privat tidak diteruskan',
);
checkPoli(OutpatientUnits::normalize([]) === [], 'Daftar kosong ditangani');
checkPoli(
    count(OutpatientUnits::normalize([$rows[0], $rows[0]])) === 1,
    'Kode ganda tidak menjadi opsi ganda',
);
try {
    OutpatientUnits::normalize('invalid');
    throw new LogicException('Format salah seharusnya ditolak.');
} catch (RuntimeException $exception) {
    echo 'PASS Format respons salah ditolak' . PHP_EOL;
}
