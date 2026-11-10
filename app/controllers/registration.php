<?php
// Katalog kunjungan dan verifikasi pasien lama; transaksi pendaftaran tetap dinonaktifkan.
// Matikan endpoint lama juga agar tab/cache JavaScript sebelumnya tidak memanggil SIMRS.
$registrationApiConfig = require APP_ROOT . '/app/config/medinfras.php';
$registrationApiEnabled =
    is_array($registrationApiConfig) && ($registrationApiConfig['enabled'] ?? false);
$registrationClinicsEnabled =
    is_array($registrationApiConfig) && ($registrationApiConfig['clinics_enabled'] ?? false);
// Jalur khusus poli tidak mengaktifkan endpoint dokter, jadwal, pasien atau transaksi.
if (($_GET['registration_action'] ?? '') === 'clinics' && $registrationClinicsEnabled) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        echo json_encode(['error' => 'Metode tidak diizinkan.']);
        exit();
    }
    require_once APP_ROOT . '/app/integrations/medinfras/Client.php';
    require_once APP_ROOT . '/app/integrations/medinfras/OutpatientUnits.php';
    try {
        $poliConfig = $registrationApiConfig;
        $poliConfig['enabled'] = true;
        $poliClient = new \Rsbl\Integrations\Medinfras\Client($poliConfig);
        echo json_encode(
            [
                'clinics' => \Rsbl\Integrations\Medinfras\OutpatientUnits::normalize(
                    $poliClient->outpatientUnits(),
                ),
            ],
            JSON_THROW_ON_ERROR,
        );
    } catch (Throwable $exception) {
        http_response_code(503);
        echo json_encode(['error' => 'Daftar poli belum dapat dimuat. Silakan coba kembali.']);
    }
    exit();
}
// Pengecualian GET penjamin: hanya kategori yang dipilih, tanpa mengaktifkan bridging lain.
if (
    ($_GET['registration_action'] ?? '') === 'payers' &&
    ($registrationApiConfig['payers_enabled'] ?? false)
) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        echo json_encode(['error' => 'Metode tidak diizinkan.']);
        exit();
    }
    $category = $_GET['category'] ?? '';
    if (!is_string($category)) {
        http_response_code(400);
        echo json_encode(['error' => 'Jenis pembayaran tidak valid.']);
        exit();
    }
    require_once APP_ROOT . '/app/integrations/medinfras/Client.php';
    require_once APP_ROOT . '/app/integrations/medinfras/Payers.php';
    try {
        $payerConfig = $registrationApiConfig;
        $payerConfig['enabled'] = true;
        $key = hash(
            'sha256',
            'payer-categories-v2' . $payerConfig['base_url'] . $payerConfig['consumer_id'],
        );
        $cache = $_SESSION['medinfras_payers'] ?? [];
        if (($cache['key'] ?? '') !== $key || ($cache['expires'] ?? 0) <= time()) {
            $client = new \Rsbl\Integrations\Medinfras\Client($payerConfig);
            $cache = [
                'key' => $key,
                'expires' => time() + 300,
                'rows' => \Rsbl\Integrations\Medinfras\Payers::normalize($client->payers()),
            ];
            $_SESSION['medinfras_payers'] = $cache;
        }
        $categories = array_values(array_unique(array_column($cache['rows'], 'category')));
        natcasesort($categories);
        $categories = array_values($categories);
        if ($category === '') {
            echo json_encode(['categories' => $categories], JSON_THROW_ON_ERROR);
            exit();
        }
        if (!in_array($category, $categories, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Jenis pembayaran tidak tersedia.']);
            exit();
        }
        $payers = array_values(
            array_filter($cache['rows'], static fn($row) => $row['category'] === $category),
        );
        echo json_encode(
            [
                'payers' => array_map(
                    static fn($row) => ['code' => $row['code'], 'name' => $row['name']],
                    $payers,
                ),
            ],
            JSON_THROW_ON_ERROR,
        );
    } catch (Throwable $exception) {
        http_response_code(503);
        echo json_encode(['error' => 'Pilihan penjamin belum dapat dimuat. Silakan coba kembali.']);
    }
    exit();
}
if (!$registrationApiEnabled) {
    unset(
        $_SESSION['medinfras_catalog'],
        $_SESSION['medinfras_verified_patient'],
        $_SESSION['medinfras_visit_selections'],
    );
    if (isset($_GET['registration_action'])) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        http_response_code(503);
        echo json_encode([
            'error' => 'Pendaftaran online belum tersedia. Silakan hubungi petugas rumah sakit.',
        ]);
        exit();
    }
}
if (($_GET['registration_action'] ?? '') === 'patient_lookup') {
    require APP_ROOT . '/app/actions/registration/patient-lookup.php';
    exit();
}
if (isset($_GET['registration_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        echo json_encode(['error' => 'Metode tidak diizinkan.']);
        exit();
    }
    require_once APP_ROOT . '/app/integrations/medinfras/Client.php';
    require_once APP_ROOT . '/app/integrations/medinfras/OutpatientUnits.php';
    require_once APP_ROOT . '/app/integrations/medinfras/VisitCatalog.php';
    try {
        $action = $_GET['registration_action'];
        if (
            !is_string($action) ||
            !in_array($action, ['clinics', 'doctors', 'dates', 'sessions'], true)
        ) {
            throw new InvalidArgumentException('Permintaan katalog tidak valid.');
        }
        $apiConfig = require APP_ROOT . '/app/config/medinfras.php';
        $client = new \Rsbl\Integrations\Medinfras\Client($apiConfig);
        if (!$client->configured()) {
            throw new RuntimeException('Konfigurasi belum siap.');
        }
        $mapping = require APP_ROOT . '/app/config/registration-clinics.php';
        $cacheKey = hash(
            'sha256',
            $apiConfig['base_url'] . $apiConfig['consumer_id'] . serialize($mapping),
        );
        // Data cache selalu dibatasi pada field yang dibutuhkan; tidak menyimpan profil pribadi dokter.
        $cached = static function (string $key, callable $load, int $ttl = 300) use (
            $cacheKey,
        ): mixed {
            $entry = $_SESSION['medinfras_catalog'][$key] ?? [];
            if (($entry['key'] ?? '') === $cacheKey && ($entry['expires'] ?? 0) > time()) {
                return $entry['data'];
            }
            $value = $load();
            $_SESSION['medinfras_catalog'][$key] = [
                'key' => $cacheKey,
                'expires' => time() + $ttl,
                'data' => $value,
            ];
            return $value;
        };
        $units = $cached(
            'units',
            static fn() => \Rsbl\Integrations\Medinfras\OutpatientUnits::normalize(
                $client->outpatientUnits(),
            ),
        );
        $groups = \Rsbl\Integrations\Medinfras\VisitCatalog::groups($units, $mapping);
        if ($action === 'clinics') {
            $result = [
                'clinics' => array_values(
                    array_map(
                        static fn($g) => ['code' => $g['code'], 'name' => $g['name']],
                        $groups,
                    ),
                ),
            ];
        } else {
            $groupId = $_GET['clinic'] ?? '';
            if (!is_string($groupId) || !isset($groups[$groupId])) {
                throw new InvalidArgumentException('Pilih poli yang tersedia.');
            }
            $allDoctors = $cached(
                'doctors',
                static fn() => \Rsbl\Integrations\Medinfras\VisitCatalog::doctors(
                    $client->paramedics(),
                ),
            );
            // Relasi dokter wajib berasal dari jadwal API unit sore/malam, bukan master atau spesialisasi lokal.
            $candidates = $cached(
                'unit-doctors-' . $groupId,
                static function () use ($allDoctors, $cached, $client, $groups, $groupId): array {
                    $schedules = [];
                    foreach ($allDoctors as $doctor) {
                        $code = $doctor['code'];
                        $schedules[$code] = $cached(
                            'routine-' . $code,
                            static fn() => $client->schedulesByParamedic($code),
                            60,
                        );
                    }
                    return \Rsbl\Integrations\Medinfras\VisitCatalog::doctorsForUnits(
                        $allDoctors,
                        $schedules,
                        $groups[$groupId],
                    );
                },
                60,
            );
            if ($action === 'doctors') {
                $result = ['doctors' => $candidates, 'basis' => 'service-unit-schedule'];
            } else {
                $doctorCode = $_GET['doctor'] ?? '';
                if (
                    !is_string($doctorCode) ||
                    !in_array($doctorCode, array_column($candidates, 'code'), true)
                ) {
                    throw new InvalidArgumentException('Pilih dokter yang tersedia pada poli ini.');
                }
                if ($action === 'dates') {
                    // Baca satu rentang API, lalu gunakan aturan sesi yang sama untuk setiap tanggal.
                    $start = new DateTimeImmutable('today');
                    $end = $start->modify('+30 days');
                    $routine = $cached(
                        'routine-' . $doctorCode,
                        static fn() => $client->schedulesByParamedic($doctorCode),
                        60,
                    );
                    $dated = $cached(
                        'range-' . $start->format('Ymd'),
                        static fn() => $client->schedulesByDate(
                            $start->format('Ymd'),
                            $end->format('Ymd'),
                        ),
                        60,
                    );
                    $dates = [];
                    for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
                        $value = $day->format('Y-m-d');
                        $available = \Rsbl\Integrations\Medinfras\VisitCatalog::sessions(
                            $routine,
                            $dated,
                            $groups[$groupId],
                            $doctorCode,
                            $value,
                        );
                        if ($available['state'] === 'ready') {
                            $dates[] = $value;
                        }
                    }
                    echo json_encode(
                        [
                            'dates' => $dates,
                            'min' => $start->format('Y-m-d'),
                            'max' => $end->format('Y-m-d'),
                        ],
                        JSON_THROW_ON_ERROR,
                    );
                    exit();
                }
                $date = $_GET['date'] ?? '';
                if (
                    !is_string($date) ||
                    !validDate($date) ||
                    $date < date('Y-m-d') ||
                    $date > date('Y-m-d', strtotime('+30 days'))
                ) {
                    throw new InvalidArgumentException(
                        'Tanggal kunjungan harus hari ini sampai 30 hari ke depan.',
                    );
                }
                $routine = $cached(
                    'routine-' . $doctorCode,
                    static fn() => $client->schedulesByParamedic($doctorCode),
                    60,
                );
                $day = str_replace('-', '', $date);
                $byDate = $cached(
                    'dates-' . $day,
                    static fn() => $client->schedulesByDate($day, $day),
                    60,
                );
                $availability = \Rsbl\Integrations\Medinfras\VisitCatalog::sessions(
                    $routine,
                    $byDate,
                    $groups[$groupId],
                    $doctorCode,
                    $date,
                );
                // Simpan identitas sesi di server untuk pengembangan transaksi berikutnya; belum dipakai membuat appointment.
                foreach ($availability['sessions'] as $session) {
                    $_SESSION['medinfras_visit_selections'][$session['code']] = [
                        'expires' => time() + 300,
                        'selection' => $session['selection'],
                    ];
                }
                foreach ($_SESSION['medinfras_visit_selections'] ?? [] as $key => $value) {
                    if (($value['expires'] ?? 0) <= time()) {
                        unset($_SESSION['medinfras_visit_selections'][$key]);
                    }
                }
                $state = $availability['state'];
                $result = [
                    'sessions' => array_map(
                        static fn($row) => ['code' => $row['code'], 'name' => $row['name']],
                        $availability['sessions'],
                    ),
                    'state' => $state,
                    'message' =>
                        $state === 'ready'
                            ? 'Jadwal praktik sesuai data Medinfras. Kuota dan pendaftaran belum dikonfirmasi.'
                            : ($state === 'empty'
                                ? 'Belum ada jadwal praktik untuk pilihan ini. Silakan pilih tanggal lain atau hubungi petugas.'
                                : 'Jadwal praktik belum dapat ditampilkan. Silakan hubungi petugas.'),
                ];
            }
        }
        echo json_encode($result, JSON_THROW_ON_ERROR);
    } catch (InvalidArgumentException $exception) {
        http_response_code(400);
        echo json_encode(['error' => $exception->getMessage()]);
    } catch (Throwable $exception) {
        http_response_code(503);
        echo json_encode([
            'error' => 'Pilihan kunjungan belum dapat dimuat. Silakan coba kembali.',
        ]);
    }
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    // Jangan membuat bukti pendaftaran lokal seolah transaksi sudah masuk SIMRS.
    http_response_code(503);
    $error =
        'Pendaftaran belum dapat dikirim. Integrasi Medinfras sedang disiapkan. Silakan hubungi petugas rumah sakit.';
}
