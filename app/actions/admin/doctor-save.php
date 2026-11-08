<?php
// Menyimpan identitas dokter, riwayat profesional, foto, dan sesi praktik.

$profile = [];
foreach (
    ['bio', 'education', 'experience', 'training', 'certifications', 'organizations']
    as $key
) {
    $profile[$key] = trim($_POST[$key] ?? '');
    if (strlen($profile[$key]) > 10000) {
        throw new RuntimeException('Setiap bagian riwayat dokter maksimal 10.000 karakter.');
    }
}

$name = trim($_POST['name'] ?? '');
$specialty = trim($_POST['specialty'] ?? '');
$hours = trim($_POST['hours'] ?? '');
$image = trim($_POST['image'] ?? '');

$days = array_values(
    array_unique(
        array_filter(array_map('intval', $_POST['days'] ?? []), fn($n) => $n >= 1 && $n <= 7),
    ),
);
sort($days);

if (isset($_POST['sessions']) || isset($_POST['schedule_editor'])) {
    $submittedSessions = $_POST['sessions'] ?? [];
    if (!is_array($submittedSessions) || count($submittedSessions) > 14) {
        throw new RuntimeException('Maksimal 14 sesi praktik.');
    }
    $sessions = [];
    $days = [];
    foreach ($submittedSessions as $session) {
        $sessionDays = array_values(
            array_unique(
                array_filter(
                    array_map('intval', (array) ($session['days'] ?? [])),
                    fn($day) => $day >= 1 && $day <= 7,
                ),
            ),
        );
        sort($sessionDays);
        $start = $session['start'] ?? '';
        $end = $session['end'] ?? '';
        if (!$sessionDays && $start === '' && $end === '') {
            continue;
        }
        if (
            !$sessionDays ||
            !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $start) ||
            !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $end) ||
            $end <= $start
        ) {
            throw new RuntimeException(
                'Setiap sesi wajib memiliki hari dan jam selesai setelah jam mulai.',
            );
        }
        $sessions[] = ['days' => $sessionDays, 'start' => $start, 'end' => $end];
        $days = array_merge($days, $sessionDays);
    }
    $days = array_values(array_unique($days));
    sort($days);
    $hours = $sessions ? $sessions[0]['start'] . '-' . $sessions[0]['end'] : '';
    $profile['sessions'] = $sessions;
}

if (
    $name === '' ||
    $specialty === '' ||
    strlen($name) > 255 ||
    strlen($specialty) > 255 ||
    strlen($hours) > 100
) {
    throw new RuntimeException('Isi nama dan poliklinik. Periksa panjang data dokter.');
}
if (
    $image !== '' &&
    !preg_match('~^https://[^\s]+$~i', $image) &&
    !preg_match('~^assets/uploads/[a-f0-9]{32}\.(jpg|png|webp)$~', $image)
) {
    throw new RuntimeException('Foto harus URL HTTPS atau unggahan lokal.');
}

$uploaded = uploadImage('image_file');
if ($uploaded !== '') {
    $image = $uploaded;
}

$values = [$name, $specialty, implode(',', $days), $hours, $image, isset($_POST['active']) ? 1 : 0];
$id = (int) ($_POST['id'] ?? 0);

if ($id) {
    execute('UPDATE doctors SET name=?,specialty=?,days=?,hours=?,image=?,active=? WHERE id=?', [
        ...$values,
        $id,
    ]);
} else {
    execute(
        'INSERT INTO doctors (name,specialty,days,hours,image,active) VALUES (?,?,?,?,?,?)',
        $values,
    );
}

$savedId = $id ?: (int) $db->lastInsertId();
$profileKey = 'doctor_profile_' . $savedId;
if (query('SELECT name FROM settings WHERE name=?', [$profileKey])) {
    execute('UPDATE settings SET value=? WHERE name=?', [json_encode($profile), $profileKey]);
} else {
    execute('INSERT INTO settings (name,value) VALUES (?,?)', [$profileKey, json_encode($profile)]);
}

flash('Profil dokter dan jadwal disimpan.');
