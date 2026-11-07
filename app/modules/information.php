<?php
// Pengelolaan informasi/berita beserta banyak foto dan video opsional.

// Daftar kategori yang memakai master Informasi dan galeri media.
function informationKinds(): array
{
    return ['news', 'event', 'promo', 'career'];
}

// Membaca galeri informasi; memakai sampul lama jika belum ada data galeri.
function informationPhotos(array $item): array
{
    $photos = json_decode(setting('information_photos_' . $item['id'], 'null'), true);
    if (is_array($photos)) {
        return $photos;
    }
    return $item['image'] !== ''
        ? [['src' => $item['image'], 'caption' => '', 'display' => ['fit' => 'contain']]]
        : [];
}

// Mengambil video tersimpan hanya jika path unggahannya valid.
function informationVideo(int $id): string
{
    $path = setting('information_video_' . $id);
    return preg_match('~^assets/uploads/[a-f0-9]{32}\.(mp4|webm)$~', $path) ? $path : '';
}

// Memvalidasi video MP4/WebM maksimal 50 MB lalu menyimpannya.
function uploadInformationVideo(): string
{
    $file = $_FILES['information_video'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 50 * 1024 * 1024) {
        throw new RuntimeException(
            'Video maksimal 50 MB. Pilih video yang lebih kecil atau periksa batas unggah PHP.',
        );
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    if (!isset($types[$mime])) {
        throw new RuntimeException('Unggah video MP4 atau WebM yang valid.');
    }
    $path = 'assets/uploads/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], APP_ROOT . '/' . $path)) {
        throw new RuntimeException('Video tidak dapat disimpan. Periksa folder assets/uploads.');
    }
    return $path;
}

// Menyimpan judul, isi, status publikasi, foto, caption, dan video informasi.
function saveInformation(string $kind): void
{
    global $db;
    if (!in_array($kind, informationKinds(), true)) {
        throw new RuntimeException('Kategori informasi tidak valid.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $old = $id
        ? query('SELECT * FROM contents WHERE id=? AND kind=?', [$id, $kind])[0] ?? null
        : null;
    if ($id && !$old) {
        throw new RuntimeException('Informasi tidak ditemukan.');
    }
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    if ($title === '' || strlen($title) > 255 || $body === '' || strlen($body) > 50000) {
        throw new RuntimeException(
            'Isi judul maksimal 255 karakter dan isi informasi maksimal 50.000 karakter.',
        );
    }
    // Mempertahankan foto lama kecuali yang dicentang untuk dihapus.
    $photos = [];
    $remove = array_map('intval', (array) ($_POST['remove_photos'] ?? []));
    foreach ($old ? informationPhotos($old) : [] as $index => $photo) {
        if (in_array($index, $remove, true)) {
            continue;
        }
        $caption = $_POST['photo_captions'][$index] ?? ($photo['caption'] ?? '');
        if (!is_string($caption) || strlen($caption) > 255) {
            throw new RuntimeException('Keterangan foto maksimal 255 karakter.');
        }
        $photo['caption'] = trim($caption);
        $photo['display'] = inpatientPhotoDisplay(
            $_POST['photo_display'][$index] ?? ($photo['display'] ?? []),
        );
        $photos[] = $photo;
    }
    $newCaptions = (array) ($_POST['new_photo_captions'] ?? []);
    $newDisplays = (array) ($_POST['new_photo_display'] ?? []);
    foreach ($newCaptions as $caption) {
        if (!is_string($caption) || strlen($caption) > 255) {
            throw new RuntimeException('Keterangan foto maksimal 255 karakter.');
        }
    }
    foreach ($newDisplays as &$display) {
        $display = inpatientPhotoDisplay($display);
    }
    unset($display);
    // Memproses setiap field foto baru tanpa mengubah foto yang sudah tersimpan.
    $files = $_FILES['room_photos'] ?? null;
    if ($files && is_array($files['name'])) {
        try {
            foreach ($files['name'] as $index => $name) {
                $_FILES['information_upload'] = [];
                foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $key) {
                    $_FILES['information_upload'][$key] = $files[$key][$index];
                }
                $path = uploadImage('information_upload', 10);
                if ($path !== '') {
                    $photos[] = [
                        'src' => $path,
                        'caption' => trim($newCaptions[$index] ?? ''),
                        'display' => $newDisplays[$index] ?? ['fit' => 'contain'],
                    ];
                }
            }
        } finally {
            unset($_FILES['information_upload']);
        }
    }
    // Mempertahankan video lama, kecuali dihapus atau diganti melalui formulir.
    $video = $old ? informationVideo((int) $old['id']) : '';
    if (isset($_POST['remove_information_video'])) {
        $video = '';
    }
    $uploadedVideo = uploadInformationVideo();
    if ($uploadedVideo !== '') {
        $video = $uploadedVideo;
    }
    $cover = $photos[0]['src'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;
    // Menyimpan artikel dan daftar media sebagai satu perubahan database.
    $db->beginTransaction();
    try {
        if ($id) {
            execute('UPDATE contents SET title=?,body=?,image=?,active=? WHERE id=? AND kind=?', [
                $title,
                $body,
                $cover,
                $active,
                $id,
                $kind,
            ]);
        } else {
            execute(
                'INSERT INTO contents (kind,title,subtitle,body,image,link,starts,ends,active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$kind, $title, '', $body, $cover, '', '', '', $active, 0],
            );
            $id = (int) $db->lastInsertId();
        }
        inpatientSetting('information_photos_' . $id, json_encode($photos));
        inpatientSetting('information_video_' . $id, $video);
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        throw $ex;
    }
    flash('Informasi dan foto-foto disimpan.');
}
