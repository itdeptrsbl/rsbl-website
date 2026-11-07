<?php
// Jenis konten sejarah, masa jabatan pemimpin, dan validasi video sejarah.

// Daftar bagian sejarah yang dikenali master dan halaman publik.
function historyTypes(): array
{
    return [
        'banner' => 'Banner halaman',
        'timeline' => 'Perjalanan sejarah',
        'leader' => 'Kepemimpinan',
        'organization' => 'Struktur Organisasi PT',
        'video' => 'Video sejarah',
        'text' => 'Teks tambahan',
    ];
}

// Menormalkan tahun atau tanggal masa jabatan ke format yang dapat disimpan.
function historyTenureValue(string $value): string
{
    $value = trim($value);
    if ($value === '' || preg_match('/^[1-9][0-9]{3}$/', $value)) {
        return $value;
    }
    if (preg_match('/^(\d{1,2})\s+([\p{L}]+)\s+(\d{4})$/u', $value, $match)) {
        $months = [
            'januari' => 1,
            'februari' => 2,
            'maret' => 3,
            'april' => 4,
            'mei' => 5,
            'juni' => 6,
            'juli' => 7,
            'agustus' => 8,
            'september' => 9,
            'oktober' => 10,
            'november' => 11,
            'desember' => 12,
        ];
        $month = $months[strtolower($match[2])] ?? null;
        if ($month !== null) {
            $value = sprintf('%04d-%02d-%02d', $match[3], $month, $match[1]);
        }
    }
    if (preg_match('~^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$~', $value, $match)) {
        $value = sprintf('%04d-%02d-%02d', $match[3], $match[2], $match[1]);
    }
    if (validDate($value)) {
        return $value;
    }
    throw new RuntimeException(
        'Isi masa jabatan berupa tahun (2007) atau tanggal lengkap (16 April 1995 atau 16/04/1995).',
    );
}

// Membaca jenis bagian sejarah berdasarkan ID konten.
function historyType(int $id): string
{
    $type = setting('history_type_' . $id, 'text');
    return isset(historyTypes()[$type]) ? $type : 'text';
}

// Hanya URL video langsung atau ID YouTube yang dikenali, tanpa HTML embed dari admin.
// Mengenali video unggahan, tautan berkas, atau tautan YouTube yang didukung.
function historyVideo(string $url): array
{
    if (preg_match('~^assets/uploads/[a-f0-9]{32}\.(mp4|webm)$~', $url)) {
        return ['type' => 'file', 'url' => $url];
    }
    $parts = parse_url($url);
    if (
        !$parts ||
        ($parts['scheme'] ?? '') !== 'https' ||
        isset($parts['user']) ||
        isset($parts['pass'])
    ) {
        return [];
    }
    $host = strtolower($parts['host'] ?? '');
    $path = $parts['path'] ?? '';
    $id = '';
    if ($host === 'youtu.be') {
        $id = trim($path, '/');
    }
    if (
        in_array(
            $host,
            ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com'],
            true,
        )
    ) {
        parse_str($parts['query'] ?? '', $params);
        if ($path === '/watch') {
            $id = $params['v'] ?? '';
        } elseif (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})/?$~', $path, $match)) {
            $id = $match[1];
        }
    }
    if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
        return ['type' => 'youtube', 'url' => 'https://www.youtube-nocookie.com/embed/' . $id];
    }
    if (preg_match('/\.(mp4|webm)$/i', $path)) {
        return ['type' => 'file', 'url' => $url];
    }
    return [];
}

// Memvalidasi dan menyimpan video sejarah maksimal 20 MB.
function uploadHistoryVideo(string $field): string
{
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 20 * 1024 * 1024) {
        throw new RuntimeException('Video maksimal 20 MB. Periksa batas upload PHP.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    if (!isset($types[$mime])) {
        throw new RuntimeException('Unggah video MP4 atau WebM yang valid.');
    }
    $path = 'assets/uploads/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], APP_ROOT . '/' . $path)) {
        throw new RuntimeException('Folder assets/uploads tidak dapat ditulis.');
    }
    return $path;
}
