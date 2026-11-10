<?php
// Menyiapkan katalog kunjungan publik dari GET API, tanpa membuka data pribadi tenaga medis.
namespace Rsbl\Integrations\Medinfras;

final class VisitCatalog
{
    // Hanya unit sore dan Obgyn malam dari API yang boleh masuk pilihan pendaftaran.
    public static function groups(array $units, array $mapping): array
    {
        $units = array_values(
            array_filter($units, static function (array $unit): bool {
                $name = strtoupper(trim($unit['name']));
                $isObgyn = preg_match('/OBGYN|OBSTETRI|KANDUNGAN/', $name) === 1;
                return preg_match('/\bSORE\d*\b/', $name) === 1 ||
                    ($isObgyn && preg_match('/\bMALAM\d*\b/', $name) === 1);
            }),
        );
        $remaining = array_column($units, null, 'code');
        $groups = [];
        foreach ($mapping as $id => $group) {
            $codes = array_values(
                array_filter($group['units'], static fn($code) => isset($remaining[$code])),
            );
            if ($codes === []) {
                continue;
            }
            $groups[$id] = [
                'code' => $id,
                'name' => $group['name'],
                'units' => $codes,
                'specialties' => $group['specialties'],
            ];
            foreach ($codes as $code) {
                unset($remaining[$code]);
            }
        }
        foreach ($remaining as $unit) {
            $id = 'unit-' . $unit['code'];
            $groups[$id] = [
                'code' => $id,
                // Bersihkan label saja; kode unit dan jadwal API tidak diubah.
                'name' => trim(
                    preg_replace(
                        ['/^(?:KLINIK|POLI)\s+/i', '/\d*\s*(?:SORE|MALAM)\d*\s*$/i'],
                        ['Poli ', ''],
                        $unit['name'],
                    ),
                ),
                'units' => [$unit['code']],
                'specialties' => [],
            ];
        }
        uasort($groups, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        return $groups;
    }

    // Daftar tenaga medis API juga berisi identitas pribadi: ambil hanya field publik dokter aktif.
    public static function doctors(mixed $rows): array
    {
        if (
            !is_array($rows) ||
            ($rows !== [] && array_keys($rows) !== range(0, count($rows) - 1))
        ) {
            throw new \RuntimeException('Format dokter belum sesuai.');
        }
        $doctors = [];
        foreach ($rows as $row) {
            if (
                !is_array($row) ||
                ($row['ParamedicType'] ?? '') !== 'Dokter' ||
                !in_array($row['IsAvailable'] ?? null, [true, 1, '1'], true) ||
                ($row['DepartmentID'] ?? '') !== 'OUTPATIENT'
            ) {
                continue;
            }
            $code = $row['ParamedicCode'] ?? null;
            $name = $row['FullName'] ?? null;
            if (
                !is_string($code) ||
                !is_string($name) ||
                trim($code) === '' ||
                trim($name) === ''
            ) {
                continue;
            }
            $doctors[$code] = [
                'code' => $code,
                'name' => trim($name),
                'specialty' => trim((string) ($row['SpecialtyName'] ?? '')),
            ];
        }
        return array_values($doctors);
    }

    // Pencocokan spesialisasi ini belum menjamin dokter praktik di tiap unit pagi/sore.
    public static function candidates(array $doctors, array $group): array
    {
        $specialties = array_map(static fn($v) => mb_strtoupper(trim($v)), $group['specialties']);
        $matches = array_values(
            array_filter(
                $doctors,
                static fn($doctor) => in_array(
                    mb_strtoupper(trim($doctor['specialty'])),
                    $specialties,
                    true,
                ),
            ),
        );
        usort($matches, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        return $matches;
    }

    // Dokter harus memiliki relasi jadwal dengan kode unit terpilih; kesamaan spesialisasi tidak cukup.
    public static function doctorsForUnits(array $doctors, array $schedules, array $group): array
    {
        $result = [];
        foreach ($doctors as $doctor) {
            $schedule = $schedules[$doctor['code']] ?? [];
            foreach (['ScheduleRoutine', 'ScheduleByDate'] as $key) {
                foreach ($schedule[$key] ?? [] as $row) {
                    if (
                        is_array($row) &&
                        ($row['ParamedicCode'] ?? '') === $doctor['code'] &&
                        in_array($row['ServiceUnitCode'] ?? '', $group['units'], true)
                    ) {
                        $result[$doctor['code']] = $doctor;
                    }
                }
            }
        }
        $result = array_values($result);
        usort($result, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        return $result;
    }

    // Jadwal rutin memakai DayNumber (1 Senin sampai 6 Sabtu, 0/7 Minggu).
    // Satu OperationalTime dapat berisi beberapa rentang; jeda tidak digabung menjadi satu sesi panjang.
    public static function sessions(
        mixed $routine,
        mixed $byDate,
        array $group,
        string $doctor,
        string $date,
    ): array {
        if (!is_array($routine) || !is_array($byDate)) {
            throw new \RuntimeException('Format jadwal belum sesuai.');
        }
        foreach ([$routine, $byDate] as $data) {
            if (!empty($data['LeaveSchedule'])) {
                // Format cuti belum terverifikasi: jangan menawarkan kunjungan saat ada data yang belum dikenali.
                return ['sessions' => [], 'state' => 'unmapped'];
            }
        }
        $dated = [];
        foreach (
            [
                $routine['ScheduleByDate'] ?? [],
                $byDate['ScheduleByDate'] ?? [],
                array_keys($byDate) === range(0, count($byDate) - 1) ? $byDate : [],
            ]
            as $rows
        ) {
            if (!is_array($rows)) {
                return ['sessions' => [], 'state' => 'unmapped'];
            }
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    return ['sessions' => [], 'state' => 'unmapped'];
                }
                $value = $row['ScheduleDate'] ?? '';
                $day = is_string($value) ? str_replace('-', '', substr($value, 0, 10)) : '';
                if (
                    ($row['ParamedicCode'] ?? '') === $doctor &&
                    $day === str_replace('-', '', $date) &&
                    in_array($row['ServiceUnitCode'] ?? '', $group['units'], true)
                ) {
                    $dated[] = $row;
                }
            }
        }
        $weekday = (int) (new \DateTimeImmutable($date))->format('N');
        $rows = $dated !== [] ? $dated : $routine['ScheduleRoutine'] ?? [];
        if (!is_array($rows)) {
            return ['sessions' => [], 'state' => 'unmapped'];
        }
        $sessions = [];
        $unmapped = false;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                $unmapped = true;
                continue;
            }
            if (
                ($row['ParamedicCode'] ?? '') !== $doctor ||
                !in_array($row['ServiceUnitCode'] ?? '', $group['units'], true)
            ) {
                continue;
            }
            if ($dated === []) {
                if (!isset($row['DayNumber']) || !is_numeric($row['DayNumber'])) {
                    $unmapped = true;
                    continue;
                }
                $number = (int) $row['DayNumber'];
                if (($number === 0 ? 7 : $number) !== $weekday) {
                    continue;
                }
            }
            $operational = $row['OperationalTimeCode'] ?? '';
            $visitTypes = $row['ListVisitType'] ?? [];
            if (!is_string($operational) || $operational === '' || !is_array($visitTypes)) {
                $unmapped = true;
                continue;
            }
            if (!in_array('VT01', array_column($visitTypes, 'VisitTypeCode'), true)) {
                continue;
            }
            for ($part = 1; $part <= 5; $part++) {
                $start = $row['StartTime' . $part] ?? '';
                $end = $row['EndTime' . $part] ?? '';
                if ($start === '' && $end === '') {
                    continue;
                }
                if (
                    !is_string($start) ||
                    !is_string($end) ||
                    !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $start) ||
                    !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $end) ||
                    $end <= $start
                ) {
                    $unmapped = true;
                    continue;
                }
                if ($date === date('Y-m-d') && $end <= date('H:i')) {
                    continue;
                }
                $selection = [
                    'ServiceUnitCode' => $row['ServiceUnitCode'],
                    'ParamedicCode' => $doctor,
                    'VisitTypeCode' => 'VT01',
                    'OperationalTimeCode' => $operational,
                    'visit_date' => $date,
                    'StartTime' => $start,
                    'EndTime' => $end,
                    'session_index' => $part - 1,
                ];
                $token = hash('sha256', json_encode($selection));
                $sessions[$token] = [
                    'code' => $token,
                    'name' => $start . ' - ' . $end . ' WITA',
                    'selection' => $selection,
                ];
            }
        }
        if ($sessions === [] && $unmapped) {
            return ['sessions' => [], 'state' => 'unmapped'];
        }
        $sessions = array_values($sessions);
        usort(
            $sessions,
            static fn($a, $b) => strcmp($a['selection']['StartTime'], $b['selection']['StartTime']),
        );
        return ['sessions' => $sessions, 'state' => $sessions === [] ? 'empty' : 'ready'];
    }

    // Fail closed: jangan menebak jam, kode unit, atau pola jadwal rutin dari field yang belum terverifikasi.
    public static function scheduleState(mixed $data): array
    {
        if (!is_array($data)) {
            throw new \RuntimeException('Format jadwal belum sesuai.');
        }
        if ($data === []) {
            return ['sessions' => [], 'state' => 'empty'];
        }
        $sections = ['LeaveSchedule', 'ScheduleByDate', 'ScheduleRoutine'];
        if (array_keys($data) !== range(0, count($data) - 1)) {
            foreach ($sections as $key) {
                if (isset($data[$key]) && $data[$key] !== []) {
                    return ['sessions' => [], 'state' => 'unmapped'];
                }
            }
            if (array_intersect(array_keys($data), $sections) !== []) {
                return ['sessions' => [], 'state' => 'empty'];
            }
        }
        return ['sessions' => [], 'state' => 'unmapped'];
    }
}
