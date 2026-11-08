<?php
// Pengelolaan kamar Rawat Inap, fasilitas, kapasitas, foto, dan posisi gambar.

// Mengubah data JSON kamar menjadi array yang dapat dipakai formulir dan halaman.
function inpatientRoomData(array $room): array
{
    $data = json_decode($room['body'] ?? '{}', true);
    return is_array($data) ? $data : [];
}

// Memecah teks per baris menjadi daftar tanpa baris kosong.
function inpatientLines(string $text): array
{
    return array_values(
        array_filter(array_map('trim', preg_split('/\R/u', $text)), fn($line) => $line !== ''),
    );
}

// Menambah atau memperbarui satu nilai pengaturan di database.
function inpatientSetting(string $key, string $value): void
{
    if (query('SELECT name FROM settings WHERE name=?', [$key])) {
        execute('UPDATE settings SET value=? WHERE name=?', [$value, $key]);
    } else {
        execute('INSERT INTO settings (name,value) VALUES (?,?)', [$key, $value]);
    }
}

// Seluruh aksi dibatasi pada data Rawat Inap; autentikasi dan CSRF di controller admin.
// Menangani simpan halaman, simpan kamar, dan hapus jenis kamar Rawat Inap.
function saveInpatient(string $action): void
{
    global $db;
    if ($action === 'inpatient_page') {
        $intro = trim($_POST['inpatient_intro'] ?? '');
        $wards = trim($_POST['inpatient_wards'] ?? '');
        if (strlen($intro) > 10000 || strlen($wards) > 10000) {
            throw new RuntimeException(
                'Penjelasan dan daftar ruang masing-masing maksimal 10.000 karakter.',
            );
        }
        $db->beginTransaction();
        try {
            inpatientSetting('inpatient_intro', $intro);
            inpatientSetting('inpatient_wards', $wards);
            $db->commit();
        } catch (Throwable $ex) {
            $db->rollBack();
            throw $ex;
        }
        flash('Pengaturan halaman Rawat Inap disimpan.');
        return;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $existing = $id
        ? query('SELECT * FROM contents WHERE id=? AND kind=?', [$id, 'inpatient_room'])[0] ?? null
        : null;
    if ($id && !$existing) {
        throw new RuntimeException('Jenis kamar tidak ditemukan.');
    }
    if ($action === 'inpatient_delete') {
        execute('DELETE FROM contents WHERE id=? AND kind=?', [$id, 'inpatient_room']);
        flash('Jenis kamar dihapus dari halaman Rawat Inap.');
        return;
    }

    $name = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $facilities = trim($_POST['facilities'] ?? '');
    $bedValue = trim((string) ($_POST['beds'] ?? ''));
    $beds = $bedValue === '' ? null : filter_var($bedValue, FILTER_VALIDATE_INT);
    $order = filter_var($_POST['sort_order'] ?? 1, FILTER_VALIDATE_INT);
    if (
        $name === '' ||
        strlen($name) > 255 ||
        strlen($description) > 5000 ||
        strlen($facilities) > 10000 ||
        ($beds !== null && ($beds === false || $beds < 1 || $beds > 50)) ||
        $order === false ||
        $order < 1
    ) {
        throw new RuntimeException(
            'Isi nama kamar dan urutan mulai dari 1. Jika diisi, kapasitas 1-50 tempat tidur. Periksa panjang deskripsi dan fasilitas.',
        );
    }
    $fit = $_POST['slide_fit'] ?? 'contain';
    $zoom = filter_var($_POST['slide_zoom'] ?? 100, FILTER_VALIDATE_INT);
    $x = filter_var($_POST['slide_x'] ?? 50, FILTER_VALIDATE_INT);
    $y = filter_var($_POST['slide_y'] ?? 50, FILTER_VALIDATE_INT);
    if (
        !in_array($fit, ['cover', 'contain'], true) ||
        $zoom === false ||
        $zoom < 100 ||
        $zoom > 160 ||
        $x === false ||
        $x < 0 ||
        $x > 100 ||
        $y === false ||
        $y < 0 ||
        $y > 100
    ) {
        throw new RuntimeException('Ukuran atau posisi foto kamar tidak valid.');
    }

    // Membaca media lama supaya edit kamar tidak menghapus foto secara tidak sengaja.
    $oldData = $existing ? inpatientRoomData($existing) : [];
    $coverCaption = trim($_POST['cover_caption'] ?? ($oldData['cover_caption'] ?? ''));
    $oldCaptions = (array) ($oldData['photo_captions'] ?? []);
    $postedCaptions = (array) ($_POST['photo_captions'] ?? []);
    $newCaptions = (array) ($_POST['new_photo_captions'] ?? []);
    foreach (array_merge([$coverCaption], $postedCaptions, $newCaptions) as $caption) {
        if (!is_string($caption) || strlen($caption) > 255) {
            throw new RuntimeException('Keterangan setiap foto maksimal 255 karakter.');
        }
    }
    // Pengaturan tiap foto mengikuti indeks caption dan berkasnya.
    $oldDisplays = (array) ($oldData['photo_displays'] ?? []);
    $postedDisplays = (array) ($_POST['photo_display'] ?? []);
    $newDisplays = (array) ($_POST['new_photo_display'] ?? []);
    foreach ($postedDisplays as &$display) {
        $display = inpatientPhotoDisplay($display);
    }
    unset($display);
    foreach ($newDisplays as &$display) {
        $display = inpatientPhotoDisplay($display);
    }
    unset($display);
    $photos = [];
    $photoCaptions = [];
    $photoDisplays = [];
    $remove = array_map('intval', (array) ($_POST['remove_photos'] ?? []));
    foreach ((array) ($oldData['photos'] ?? []) as $index => $path) {
        if (!in_array($index, $remove, true)) {
            $photos[] = $path;
            $photoCaptions[] = trim($postedCaptions[$index] ?? ($oldCaptions[$index] ?? ''));
            $photoDisplays[] =
                $postedDisplays[$index] ?? ($oldDisplays[$index] ?? ['fit' => 'contain']);
        }
    }
    // Menambahkan foto baru setelah data dan pengaturan formulir dinyatakan valid.
    $files = $_FILES['room_photos'] ?? null;
    $cover = $existing['image'] ?? '';
    $uploaded = uploadImage('image_file');
    if ($uploaded !== '') {
        $cover = $uploaded;
    }
    if ($files && is_array($files['name'])) {
        try {
            foreach ($files['name'] as $index => $filename) {
                $_FILES['inpatient_photo_upload'] = [
                    'name' => $filename,
                    'type' => $files['type'][$index],
                    'tmp_name' => $files['tmp_name'][$index],
                    'error' => $files['error'][$index],
                    'size' => $files['size'][$index],
                ];
                $path = uploadImage('inpatient_photo_upload');
                if ($path !== '') {
                    $photos[] = $path;
                    $photoCaptions[] = trim($newCaptions[$index] ?? '');
                    $photoDisplays[] = $newDisplays[$index] ?? ['fit' => 'contain'];
                }
            }
        } finally {
            unset($_FILES['inpatient_photo_upload']);
        }
    }
    $data = [
        'description' => $description,
        'beds' => $beds,
        'facilities' => inpatientLines($facilities),
        'photos' => $photos,
        'cover_caption' => $coverCaption,
        'photo_captions' => $photoCaptions,
        'photo_displays' => $photoDisplays,
    ];
    // Status aktif menentukan apakah jenis kamar muncul pada halaman publik.
    $active = isset($_POST['active']) ? 1 : 0;
    $db->beginTransaction();
    try {
        if ($id) {
            execute(
                'UPDATE contents SET title=?,subtitle=?,body=?,image=?,active=?,sort_order=? WHERE id=? AND kind=?',
                [
                    $name,
                    (string) $beds,
                    json_encode($data),
                    $cover,
                    $active,
                    $order,
                    $id,
                    'inpatient_room',
                ],
            );
        } else {
            execute(
                'INSERT INTO contents (kind,title,subtitle,body,image,link,starts,ends,active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)',
                [
                    'inpatient_room',
                    $name,
                    (string) $beds,
                    json_encode($data),
                    $cover,
                    '',
                    '',
                    '',
                    $active,
                    $order,
                ],
            );
            $id = (int) $db->lastInsertId();
        }
        inpatientSetting('slide_display_' . $id, json_encode(compact('fit', 'zoom', 'x', 'y')));
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        throw $ex;
    }
    flash('Jenis kamar, fasilitas, dan foto disimpan.');
}

// Menggabungkan foto utama dan foto tambahan dengan caption serta pengaturannya.
function inpatientRoomPhotos(array $room): array
{
    $data = inpatientRoomData($room);
    $photos = [];
    if ($room['image'] !== '') {
        $photos[] = [
            'src' => $room['image'],
            'caption' => $data['cover_caption'] ?? '',
            'cover' => true,
        ];
    }
    foreach ($data['photos'] ?? [] as $index => $path) {
        $photos[] = [
            'src' => $path,
            'caption' => $data['photo_captions'][$index] ?? '',
            'cover' => false,
            'display' => $data['photo_displays'][$index] ?? ['fit' => 'contain'],
        ];
    }
    return $photos;
}

// Memvalidasi mode, perbesaran, dan posisi satu foto tambahan.
function inpatientPhotoDisplay($value): array
{
    if (!is_array($value)) {
        throw new RuntimeException('Pengaturan foto tambahan tidak valid.');
    }
    $fit = $value['fit'] ?? 'contain';
    $zoom = filter_var($value['zoom'] ?? 100, FILTER_VALIDATE_INT);
    $x = filter_var($value['x'] ?? 50, FILTER_VALIDATE_INT);
    $y = filter_var($value['y'] ?? 50, FILTER_VALIDATE_INT);
    if (
        !in_array($fit, ['contain', 'cover'], true) ||
        $zoom === false ||
        $zoom < 50 ||
        $zoom > 400 ||
        $x === false ||
        $x < 0 ||
        $x > 100 ||
        $y === false ||
        $y < 0 ||
        $y > 100
    ) {
        throw new RuntimeException('Ukuran atau posisi foto tambahan tidak valid.');
    }
    return compact('fit', 'zoom', 'x', 'y');
}
