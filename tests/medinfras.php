<?php
// Tes kontrak koneksi memakai respons samaran; tidak mengakses jaringan Medinfras.
require dirname(__DIR__) . '/app/integrations/medinfras/Client.php';

use Rsbl\Integrations\Medinfras\Client;

// Hentikan tes ketika hasil tidak sesuai tanpa bergantung pengaturan assert PHP.
function expect(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
    echo 'PASS ' . $label . PHP_EOL;
}

// Memastikan kegagalan ditangani tanpa membocorkan respons atau rahasia.
function expectFailure(Closure $call, string $label): void
{
    try {
        $call();
    } catch (RuntimeException | InvalidArgumentException $exception) {
        expect(!str_contains($exception->getMessage(), 'SENSITIVE'), $label);
        return;
    }
    throw new RuntimeException('Seharusnya gagal: ' . $label);
}

expect(
    Client::signature('1700000000', 'test-consumer', 'test-secret') ===
        'Yc2ZTxwJu9c4OXRiwSc0vPs0fcuB0pSnO3e3jbOnwxA=',
    'Signature sesuai vektor HMAC Postman',
);
$fixture = ['AppointmentNo' => 'TEST-ONLY', 'QueueNo' => '1'];
foreach ([$fixture, json_encode($fixture)] as $data) {
    expect(
        Client::decodeResponse(json_encode(['Status' => 'SUCCESS (000)', 'Data' => $data])) ===
            $fixture,
        'Envelope membaca Data objek maupun string JSON',
    );
}
expectFailure(fn() => Client::decodeResponse('not json SENSITIVE'), 'JSON rusak ditolak');
expectFailure(
    fn() => Client::decodeResponse('{"Status":"ERROR","Remarks":"SENSITIVE"}'),
    'Status gagal tidak dianggap sukses',
);
expectFailure(
    fn() => Client::decodeResponse('{"Status":"SUCCESS (000)"}'),
    'Envelope tidak lengkap ditolak',
);
$config = [
    'base_url' => 'https://medinfras.example.test/api',
    'consumer_id' => 'test-consumer',
    'password' => 'test-secret',
];
$calls = [];
$client = new Client($config, function (string $url, array $headers) use (&$calls): array {
    $calls[] = $url;
    $timestamp = substr($headers[2], strlen('X-timestamp: '));
    expect(
        $headers[3] ===
            'X-signature: ' . Client::signature($timestamp, 'test-consumer', 'test-secret'),
        'Header signature dibuat ulang untuk permintaan',
    );
    return [200, '{"Status":"SUCCESS (000)","Data":[]}'];
});
$client->outpatientUnits();
$client->paramedics('Anak & Umum');
$client->schedules();
$client->patientByMedicalNumber('00-00-00-01');
expect(
    $calls === [
        $config['base_url'] . '/healthcareserviceunit/base/list/department/outpatient',
        $config['base_url'] . '/paramedic/base/list/search2?specialtyName=Anak%20%26%20Umum',
        $config['base_url'] . '/paramedicschedule/base/all_schedule',
        $config['base_url'] . '/patient/mrn/00-00-00-01',
    ],
    'Path dan encoding parameter mengikuti koleksi',
);
expectFailure(
    fn() => $client->patientByMedicalNumber('../SENSITIVE'),
    'Nomor rekam medis tidak dapat mengubah path',
);
expect(!(new Client([]))->configured(), 'Konfigurasi kosong tidak aktif');
expect(
    !(new Client(array_replace($config, ['base_url' => 'http://internal.test/api'])))->configured(),
    'HTTP memerlukan pengaturan internal eksplisit',
);
expect(
    (new Client(
        array_replace($config, ['base_url' => 'http://internal.test/api', 'allow_http' => true]),
    ))->configured(),
    'HTTP internal dapat diaktifkan melalui konfigurasi server',
);
expect(
    !(new Client(
        array_replace($config, ['base_url' => 'https://user@internal.test/api']),
    ))->configured(),
    'Rahasia pada URL ditolak',
);
expectFailure(
    fn() => (new Client($config, fn() => [503, 'SENSITIVE']))->schedules(),
    'HTTP gagal tidak dibaca sebagai sukses',
);
expectFailure(
    fn() => (new Client($config, fn() => [302, 'SENSITIVE']))->schedules(),
    'Redirect tidak diikuti',
);
expectFailure(
    fn() => (new Client([]))->schedules(),
    'Permintaan tanpa konfigurasi ditolak sebelum jaringan',
);

// Uji pemetaan pasien dan POST hanya melalui transport tiruan.
require dirname(__DIR__) . '/app/integrations/medinfras/AppointmentPayload.php';
$visit = [
    'ServiceUnitCode' => 'TEST-POLI',
    'ParamedicCode' => 'TEST-DOKTER',
    'VisitTypeCode' => 'VT01',
    'OperationalTimeCode' => 'TEST-JAM',
    'visit_date' => '2026-11-09',
    'StartTime' => '08:00',
    'Session' => 0,
];
$integration = ['user_id' => 'test-integration'];
$oldPatient = ['patient_type' => 'existing', 'medical_record_number' => '00-00-00-01'];
$payload = \Rsbl\Integrations\Medinfras\AppointmentPayload::build(
    $oldPatient,
    $visit,
    $integration,
);
expect(
    $payload['IsNewPatient'] === '0' &&
        $payload['QueueNo'] === '' &&
        $payload['StartDate'] === '20261109' &&
        $payload['MedicalNo'] === '00-00-00-01',
    'Pasien lama memakai MRN, tanggal API, dan QueueNo kosong',
);
$newPatient = [
    'patient_type' => 'new',
    'first_name' => 'Pasien',
    'last_name' => 'Uji',
    'middle_name' => '',
    'address' => 'Alamat pengujian',
    'phone' => '081234567890',
    'birth_date' => '2000-02-29',
    'gender' => 'Perempuan',
    'religion_code' => 'TEST-AGAMA',
];
$newPayload = \Rsbl\Integrations\Medinfras\AppointmentPayload::build(
    $newPatient,
    $visit,
    $integration,
);
expect(
    $newPayload['PatientName'] === 'Pasien Uji' &&
        $newPayload['DateOfBirth'] === '20000229' &&
        $newPayload['Gender'] === 'F' &&
        $newPayload['Religion'] === 'TEST-AGAMA',
    'Pasien baru dipetakan tanpa menebak kode master',
);
expectFailure(
    fn() => \Rsbl\Integrations\Medinfras\AppointmentPayload::build(
        $newPatient,
        array_replace($visit, ['visit_date' => '2026-02-30']),
        $integration,
    ),
    'Tanggal kalender salah ditolak',
);
expectFailure(
    fn() => \Rsbl\Integrations\Medinfras\AppointmentPayload::build($oldPatient, $visit, []),
    'UserID contoh tidak dipakai sebagai akun integrasi',
);
$postCalls = 0;
$postClient = new Client($config, function ($url, $headers, $method, $body) use (
    &$postCalls,
): array {
    $postCalls++;
    $sent = json_decode($body, true);
    expect(
        $method === 'POST' &&
            str_ends_with($url, '/v2/centerback/ADT_A05') &&
            $sent['QueueNo'] === '' &&
            in_array('Content-Type: application/json', $headers, true),
        'POST appointment memakai endpoint, JSON dan antrean otomatis',
    );
    return [
        200,
        '{"Status":"SUCCESS (000)","Data":"{\"AppointmentNo\":\"TEST-ONLY\",\"QueueNo\":\"12\"}"}',
    ];
});
$result = $postClient->createAppointment(array_replace($payload, ['QueueNo' => 99]));
expect(
    $result['AppointmentNo'] === 'TEST-ONLY' && $postCalls === 1,
    'Nomor appointment dibaca dari respons tanpa POST ulang',
);
expect(
    Client::decodeResponse('{"Status":"SUCCESS","Data":[]}') === [],
    'Status SUCCESS tanpa kode juga didukung sesuai koleksi',
);
expectFailure(
    fn() => (new Client(
        $config,
        fn() => [200, '{"Status":"SUCCESS (000)","Data":{}}'],
    ))->createAppointment($payload),
    'Respons tanpa nomor appointment tidak dianggap konfirmasi',
);

// Penghentian integrasi harus mencegah koneksi walaupun kredensial terisi.
$disabledCalls = 0;
$disabledClient = new Client(
    [
        'enabled' => false,
        'base_url' => 'https://example.test/api',
        'consumer_id' => 'TEST',
        'password' => 'TEST',
    ],
    static function () use (&$disabledCalls) {
        $disabledCalls++;
        return [200, '{}'];
    },
);
expectFailure(
    static fn() => $disabledClient->outpatientUnits(),
    'Integrasi nonaktif menolak permintaan sebelum menghubungi API',
);
expect($disabledCalls === 0, 'Tidak ada koneksi API ketika integrasi dinonaktifkan');
