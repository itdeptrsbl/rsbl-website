<?php
// Tes katalog kunjungan offline; transport tiruan memastikan tidak ada transaksi/pasien yang dipanggil.
require dirname(__DIR__) . '/app/integrations/medinfras/Client.php';
require dirname(__DIR__) . '/app/integrations/medinfras/VisitCatalog.php';
use Rsbl\Integrations\Medinfras\VisitCatalog;
use Rsbl\Integrations\Medinfras\Client;

// Assertions eksplisit tetap berjalan pada semua konfigurasi PHP.
function visitCheck(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException($label);
    }
    echo 'PASS ' . $label . PHP_EOL;
}
$mapping = require dirname(__DIR__) . '/app/config/registration-clinics.php';
$groups = VisitCatalog::groups(
    [
        ['code' => 'A-002', 'name' => 'KLINIK ANAK1 PAGI'],
        ['code' => 'A-003', 'name' => 'KLINIK ANAK1 SORE'],
        ['code' => 'G-001', 'name' => 'KLINIK GIGI SORE'],
        ['code' => 'GA-001', 'name' => 'KLINIK GIGI ANAK SORE'],
        ['code' => 'NEW-001', 'name' => 'KLINIK BARU SORE'],
    ],
    $mapping,
);
visitCheck(
    count($groups) === 4 &&
        $groups['anak']['name'] === 'Poli Anak' &&
        $groups['anak']['units'] === ['A-003'],
    'Hanya unit sore masuk kelompok poli; unit pagi dikeluarkan',
);
visitCheck(isset($groups['gigi'], $groups['gigi-anak']), 'Gigi Anak tidak digabung ke Gigi');
visitCheck(isset($groups['unit-NEW-001']), 'Unit baru tidak otomatis dimasukkan ke kelompok salah');
$nightGroups = VisitCatalog::groups(
    [
        ['code' => 'O-001', 'name' => 'KLINIK OBGYN PAGI'],
        ['code' => 'O-002', 'name' => 'KLINIK OBGYN MALAM'],
        ['code' => 'X-001', 'name' => 'KLINIK UMUM MALAM'],
        ['code' => 'X-002', 'name' => 'KLINIK INTERNAL'],
    ],
    $mapping,
);
visitCheck(
    count($nightGroups) === 1 && $nightGroups['obgyn']['units'] === ['O-002'],
    'Obgyn malam disertakan; pagi, internal dan malam non-Obgyn tidak ditawarkan',
);
visitCheck(
    $groups['unit-NEW-001']['name'] === 'Poli BARU',
    'Label unit baru tidak menampilkan sore, sementara kode asli dipertahankan',
);
$rows = [
    [
        'ParamedicCode' => 'D-001',
        'FullName' => 'Dokter Anak Uji',
        'ParamedicType' => 'Dokter',
        'DepartmentID' => 'OUTPATIENT',
        'IsAvailable' => true,
        'SpecialtyName' => 'Anak',
        'SSN' => 'PRIVATE',
        'HomeAddress' => 'PRIVATE',
        'UserData' => ['Password' => 'PRIVATE'],
    ],
    [
        'ParamedicCode' => 'N-001',
        'FullName' => 'Perawat',
        'ParamedicType' => 'Perawat',
        'DepartmentID' => 'OUTPATIENT',
        'IsAvailable' => true,
        'SpecialtyName' => 'Anak',
    ],
    [
        'ParamedicCode' => 'D-002',
        'FullName' => 'Dokter Tidak Aktif',
        'ParamedicType' => 'Dokter',
        'DepartmentID' => 'OUTPATIENT',
        'IsAvailable' => false,
        'SpecialtyName' => 'Anak',
    ],
];
$doctors = VisitCatalog::doctors($rows);
visitCheck(
    count($doctors) === 1 && array_keys($doctors[0]) === ['code', 'name', 'specialty'],
    'Hanya dokter aktif dan field publik; identitas pribadi tidak bocor',
);
visitCheck(
    count(VisitCatalog::candidates($doctors, $groups['anak'])) === 1 &&
        VisitCatalog::candidates($doctors, $groups['gigi']) === [],
    'Calon dokter disaring sesuai spesialisasi',
);
visitCheck(
    VisitCatalog::scheduleState([
        'LeaveSchedule' => null,
        'ScheduleByDate' => null,
        'ScheduleRoutine' => [],
    ])['state'] === 'empty',
    'Jadwal API kosong tidak dibuat menjadi jadwal perkiraan',
);
visitCheck(
    VisitCatalog::scheduleState(['ScheduleRoutine' => [['UnknownField' => 'value']]])['state'] ===
        'unmapped',
    'Format jadwal berisi data yang belum dikenali tidak ditebak',
);
$empty = json_encode([
    'Status' => 'FAILED',
    'Remarks' => 'There is no paramedic information with ScheduleDate 20261109 AND 20261109',
    'Data' => null,
]);
visitCheck(
    Client::decodeResponse($empty, true) === [],
    'Respons tanggal tanpa jadwal dibaca kosong',
);
try {
    Client::decodeResponse($empty);
    throw new LogicException('Respons gagal tidak boleh diterima endpoint lain.');
} catch (RuntimeException $error) {
    echo 'PASS Perlakuan hasil kosong tidak berlaku untuk API lain' . PHP_EOL;
}
$calls = [];
$client = new Client(
    ['base_url' => 'https://example.test/api', 'consumer_id' => 'test', 'password' => 'test'],
    function ($url, $headers, $method, $body) use (&$calls): array {
        visitCheck($method === 'GET' && $body === null, 'Pemanggilan jadwal hanya GET');
        $calls[] = $url;
        return [200, '{"Status":"SUCCESS","Data":[]}'];
    },
);
$client->schedulesByParamedic('D-001');
$client->schedulesByDate('20261109', '20261109');
visitCheck(
    $calls === [
        'https://example.test/api/ParamedicSchedule/base/list/param/;D-001;;;;',
        'https://example.test/api/ParamedicSchedule/list/20261109/20261109',
    ],
    'Path jadwal mengikuti koleksi Postman',
);
try {
    $client->schedulesByDate('20260230', '20260301');
    throw new LogicException('Tanggal tidak valid seharusnya ditolak.');
} catch (InvalidArgumentException $error) {
    visitCheck(count($calls) === 2, 'Tanggal salah ditolak sebelum mengakses API');
}

// Jadwal rutin nyata memakai StartTime1..5; dua rentang dipertahankan terpisah.
$row = [
    'ParamedicCode' => 'D-001',
    'ServiceUnitCode' => 'A-003',
    'DayNumber' => 1,
    'OperationalTimeCode' => 'T064',
    'ScheduleDate' => null,
    'StartTime1' => '09:00',
    'EndTime1' => '13:00',
    'StartTime2' => '15:00',
    'EndTime2' => '17:00',
    'ListVisitType' => [['VisitTypeCode' => 'VT01']],
];
$source = ['LeaveSchedule' => null, 'ScheduleByDate' => null, 'ScheduleRoutine' => [$row]];
$availability = VisitCatalog::sessions($source, [], $groups['anak'], 'D-001', '2026-11-16');
visitCheck(
    $availability['state'] === 'ready' &&
        count($availability['sessions']) === 2 &&
        array_column($availability['sessions'], 'name') === [
            '09:00 - 13:00 WITA',
            '15:00 - 17:00 WITA',
        ],
    'Jadwal rutin per hari menjaga dua sesi dan jeda',
);
visitCheck(
    $availability['sessions'][0]['selection']['ServiceUnitCode'] === 'A-003' &&
        $availability['sessions'][0]['selection']['OperationalTimeCode'] === 'T064',
    'Pilihan sesi mempertahankan kode unit dan kode waktu asli',
);
visitCheck(
    VisitCatalog::sessions($source, [], $groups['anak'], 'D-001', '2026-11-17')['state'] ===
        'empty',
    'Jadwal hari lain tidak ditawarkan',
);
visitCheck(
    VisitCatalog::sessions($source, [], $groups['gigi'], 'D-001', '2026-11-16')['state'] ===
        'empty',
    'Jadwal unit di luar kelompok poli tidak ditawarkan',
);
visitCheck(
    VisitCatalog::sessions($source, [], $groups['anak'], 'D-OTHER', '2026-11-16')['state'] ===
        'empty',
    'Jadwal dokter lain tidak ditawarkan',
);
$special = array_replace($row, [
    'ScheduleDate' => '20261116',
    'StartTime1' => '10:00',
    'EndTime1' => '12:00',
    'StartTime2' => '',
    'EndTime2' => '',
]);
$specialResult = VisitCatalog::sessions(
    $source,
    [$special],
    $groups['anak'],
    'D-001',
    '2026-11-16',
);
visitCheck(
    count($specialResult['sessions']) === 1 &&
        $specialResult['sessions'][0]['name'] === '10:00 - 12:00 WITA',
    'Jadwal tanggal khusus mendahului jadwal rutin',
);
visitCheck(
    VisitCatalog::sessions(
        array_replace($source, ['LeaveSchedule' => [['Unknown' => 1]]]),
        [],
        $groups['anak'],
        'D-001',
        '2026-11-16',
    )['state'] === 'unmapped',
    'Data cuti yang belum dikenali tidak diabaikan',
);

// Kalender hanya mengaktifkan hari yang menghasilkan sesi untuk unit dan dokter terpilih.
$calendarDates = [];
for (
    $day = new DateTimeImmutable('2026-11-16');
    $day <= new DateTimeImmutable('2026-11-22');
    $day = $day->modify('+1 day')
) {
    $value = $day->format('Y-m-d');
    if (
        VisitCatalog::sessions($source, [], $groups['anak'], 'D-001', $value)['state'] === 'ready'
    ) {
        $calendarDates[] = $value;
    }
}
visitCheck(
    $calendarDates === ['2026-11-16'],
    'Kalender hanya mengaktifkan tanggal dengan sesi praktik',
);

visitCheck(
    VisitCatalog::doctorsForUnits($doctors, ['D-001' => $source], $groups['anak']) === $doctors,
    'Dokter pada kode unit sore yang sesuai ditawarkan',
);
$morningOnly = ['ScheduleRoutine' => [array_replace($row, ['ServiceUnitCode' => 'A-002'])]];
visitCheck(
    VisitCatalog::doctorsForUnits($doctors, ['D-001' => $morningOnly], $groups['anak']) === [],
    'Dokter unit pagi tidak dimasukkan ke unit sore meskipun spesialisasinya sama',
);
