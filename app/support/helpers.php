<?php
// Fungsi bersama untuk database, keamanan, formulir, tanggal, dan unggahan foto.

// Semua isi dari database ditampilkan sebagai teks aman, bukan HTML mentah.
// Mengubah teks menjadi aman untuk ditampilkan sebagai HTML.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
// Mengalihkan browser ke URL tujuan dan menghentikan proses halaman.
function go(string $url): void
{
    header('Location: ' . $url);
    exit();
}
// Membuat token sesi dan field tersembunyi untuk melindungi formulir.
function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
// Menolak formulir yang tidak memiliki token sesuai sesi pengguna.
function verifyCsrf(): void
{
    if (
        empty($_SESSION['csrf']) ||
        !is_string($_POST['csrf'] ?? null) ||
        !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])
    ) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman.');
    }
}
// Memeriksa apakah akun pengelola pertama sudah tersedia.
function installed(): bool
{
    global $db;
    try {
        return (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}
// Menjalankan SELECT dengan parameter terikat dan mengembalikan baris data.
function query(string $sql, array $args = []): array
{
    global $db;
    $q = $db->prepare($sql);
    $q->execute($args);
    return $q->fetchAll();
}
// Menjalankan INSERT, UPDATE, atau DELETE dengan parameter terikat.
function execute(string $sql, array $args = []): void
{
    global $db;
    $q = $db->prepare($sql);
    $q->execute($args);
}
// Membaca satu pengaturan; memakai nilai cadangan jika belum tersimpan.
function setting(string $key, string $fallback = ''): string
{
    $rows = query('SELECT value FROM settings WHERE name=?', [$key]);
    return $rows[0]['value'] ?? $fallback;
}
// Mengambil konten aktif pada satu kategori sesuai urutan tampil.
function records(string $kind): array
{
    return query('SELECT * FROM contents WHERE kind=? AND active=1 ORDER BY sort_order, id DESC', [
        $kind,
    ]);
}
// Mewajibkan login admin dan menonaktifkan cache halaman pengelola.
function adminOnly(): void
{
    if (empty($_SESSION['admin_id'])) {
        go('index.php?page=login');
    }
    header('Cache-Control: no-store');
}
// Menyimpan pesan singkat agar tampil setelah redirect.
function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}
// Mencetak label dan input formulir dengan isi yang sudah di-escape.
function field(
    string $name,
    string $label,
    $value = '',
    string $type = 'text',
    bool $required = true,
): void {
    echo '<label>' .
        e($label) .
        '<input type="' .
        e($type) .
        '" name="' .
        e($name) .
        '" value="' .
        e($value) .
        '" ' .
        ($required ? 'required' : '') .
        ' maxlength="' .
        ($type === 'password' ? '128' : '255') .
        '"></label>';
}
// Memastikan URL foto memakai HTTPS atau pola unggahan yang diizinkan.
function photo(string $url): string
{
    return preg_match('~^https://[^\s]+$~i', $url) ||
        preg_match('~^assets/uploads/[a-f0-9]{32}\.(jpg|png|webp|avif)$~', $url)
        ? $url
        : 'assets/care.svg';
}
// Memeriksa tanggal kalender dalam format tahun-bulan-hari.
function validDate(string $date): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
// Memeriksa apakah tanggal kunjungan termasuk hari praktik dokter.
function scheduleMatches(array $doctor, string $date): bool
{
    return in_array(
        (int) date('N', strtotime($date)),
        array_map('intval', explode(',', $doctor['days'])),
        true,
    );
}

// Unggah hanya gambar raster yang lolos pemeriksaan tipe dan dimensi, tanpa SVG atau kode.
// Memvalidasi ukuran dan tipe gambar, lalu menyimpan unggahan dengan nama acak.
function uploadImage(string $field, int $maxMb = 25, bool $allowAvif = false): string
{
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxMb * 1024 * 1024) {
        throw new RuntimeException('Gambar maksimal ' . $maxMb . ' MB. Periksa batas upload PHP.');
    }

    $size = @getimagesize($file['tmp_name']);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if ($allowAvif) {
        $types['image/avif'] = 'avif';
    }
    if (!$size || !isset($types[$size['mime']]) || $size[0] > 6000 || $size[1] > 6000) {
        throw new RuntimeException(
            ($allowAvif ? 'Unggah JPG, PNG, WebP, atau AVIF' : 'Unggah JPG, PNG, atau WebP') .
                ' dengan dimensi maksimal 6000 piksel.',
        );
    }

    $path = 'assets/uploads/' . bin2hex(random_bytes(16)) . '.' . $types[$size['mime']];
    if (!move_uploaded_file($file['tmp_name'], APP_ROOT . '/' . $path)) {
        throw new RuntimeException('Folder assets/uploads tidak dapat ditulis.');
    }
    return $path;
}
