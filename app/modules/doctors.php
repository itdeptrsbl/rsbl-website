<?php
// Membaca profil dan jadwal dokter serta menyiapkan riwayat untuk ditampilkan.

// Membaca data riwayat tambahan milik satu dokter.
function doctorProfile(int $id): array
{
    $profile = json_decode(setting('doctor_profile_' . $id, '{}'), true);
    return is_array($profile) ? $profile : [];
}

// Mengubah kode angka hari menjadi nama hari yang mudah dibaca.
function doctorDays(string $days): string
{
    $labels = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];
    return implode(', ', array_map(fn($day) => $labels[(int) $day] ?? '', explode(',', $days)));
}

// Bentuk daftar dari teks biasa; isi setiap poin tetap di-escape.
// Mengubah teks riwayat menjadi paragraf atau daftar aman untuk halaman publik.
function doctorHistory(string $text): string
{
    $html = '';
    $list = null;
    foreach (preg_split('/\R/u', $text) as $line) {
        $line = trim($line);
        $type = null;
        if (preg_match('/^(?:[-*]|\x{2022})\s+(.+)$/u', $line, $match)) {
            $type = 'ul';
        } elseif (preg_match('/^\d+[.)]\s+(.+)$/u', $line, $match)) {
            $type = 'ol';
        }
        if ($list !== $type) {
            if ($list !== null) {
                $html .= '</' . $list . '>';
            }
            if ($type !== null) {
                $html .= '<' . $type . '>';
            }
            $list = $type;
        }
        if ($type !== null) {
            $html .= '<li>' . e($match[1]) . '</li>';
        } elseif ($line !== '') {
            $html .= '<p>' . e($line) . '</p>';
        }
    }
    if ($list !== null) {
        $html .= '</' . $list . '>';
    }
    return $html;
}

// Data lama tetap dapat dibaca sebagai satu sesi tanpa migrasi database.
// Membaca seluruh sesi praktik; tetap mendukung data jadwal versi lama.
function doctorSessions(array $doctor): array
{
    $profile = doctorProfile((int) ($doctor['id'] ?? 0));
    if (isset($profile['sessions']) && is_array($profile['sessions'])) {
        return $profile['sessions'];
    }
    $times = [];
    preg_match_all('/([0-2]?[0-9])[.:]([0-5][0-9])/', $doctor['hours'] ?? '', $times);
    if (trim($doctor['days'] ?? '') === '' || count($times[0]) < 2) {
        return [];
    }
    return [
        [
            'days' => array_map('intval', explode(',', $doctor['days'] ?? '')),
            'start' => isset($times[1][0]) ? sprintf('%02d:%s', $times[1][0], $times[2][0]) : '',
            'end' => isset($times[1][1]) ? sprintf('%02d:%s', $times[1][1], $times[2][1]) : '',
        ],
    ];
}

// Merangkum hari dan jam semua sesi dokter menjadi satu teks.
function doctorScheduleText(array $doctor): string
{
    return implode(
        ' | ',
        array_map(
            fn($session) => doctorDays(implode(',', $session['days'])) .
                ' (' .
                $session['start'] .
                ' - ' .
                $session['end'] .
                ' WITA)',
            doctorSessions($doctor),
        ),
    );
}
