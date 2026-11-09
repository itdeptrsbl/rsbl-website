<?php
// Memvalidasi dan menyimpan konten umum, unggahan, tanggal, serta posisi foto.

// Memeriksa isian khusus tiap jenis konten sebelum proses simpan.
$historyType = $_POST['history_type'] ?? 'text';
if ($kind === 'history' && (!is_string($historyType) || !isset(historyTypes()[$historyType]))) {
    throw new RuntimeException('Pilih jenis bagian sejarah yang valid.');
}
if ($kind === 'history' && $historyType === 'organization') {
    $organizationWidth = filter_var($_POST['organization_width'] ?? 100, FILTER_VALIDATE_INT);
    if ($organizationWidth === false || !in_array($organizationWidth, range(20, 100, 10), true)) {
        throw new RuntimeException('Pilih ukuran gambar struktur organisasi antara 20% dan 100%.');
    }
}
if ($kind === 'history' && $historyType === 'leader') {
    $leaderPhotoSize = filter_var($_POST['leader_photo_zoom'] ?? 100, FILTER_VALIDATE_INT);
    if ($leaderPhotoSize === false || !in_array($leaderPhotoSize, range(100, 200, 10), true)) {
        throw new RuntimeException('Zoom foto pemimpin harus antara 100% dan 200%.');
    }
    $leaderPosition = [];
    foreach (['x' => 50, 'y' => 30] as $axis => $defaultPosition) {
        $position = filter_var(
            $_POST['leader_photo_' . $axis] ?? $defaultPosition,
            FILTER_VALIDATE_INT,
        );
        if ($position === false || !in_array($position, range(0, 100, 5), true)) {
            throw new RuntimeException('Posisi foto pemimpin tidak valid.');
        }
        $leaderPosition[$axis] = $position;
    }
}
// Membaca dan memvalidasi ukuran serta posisi foto untuk kategori yang mendukungnya.
$slideDisplay = null;
if (
    in_array(
        $kind,
        array_merge(
            ['slide', 'service_slide', 'outpatient_photo', 'clinic', 'emergency_photo'],
            facilityPhotoKinds(),
        ),
        true,
    )
) {
    $fit = $_POST['slide_fit'] ?? ($kind === 'clinic' ? 'contain' : 'cover');
    $zoom = filter_var($_POST['slide_zoom'] ?? 100, FILTER_VALIDATE_INT);
    $x = filter_var($_POST['slide_x'] ?? 50, FILTER_VALIDATE_INT);
    $y = filter_var($_POST['slide_y'] ?? 50, FILTER_VALIDATE_INT);
    if (
        !in_array($fit, ['cover', 'contain'], true) ||
        $zoom === false ||
        $zoom < ($kind === 'clinic' ? 50 : 100) ||
        $zoom > ($kind === 'clinic' ? 400 : 160) ||
        $x === false ||
        $x < 0 ||
        $x > 100 ||
        $y === false ||
        $y < 0 ||
        $y > 100
    ) {
        throw new RuntimeException('Pengaturan ukuran atau posisi foto tidak valid.');
    }
    $slideDisplay = compact('fit', 'zoom', 'x', 'y');
}

// Membersihkan isi formulir sebelum diperiksa dan disimpan.
$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');
$image = trim($_POST['image'] ?? '');
$link = trim($_POST['link'] ?? '');
if ($kind === 'quality') {
    $existingQuality =
        query('SELECT * FROM contents WHERE id=? AND kind=?', [
            (int) ($_POST['id'] ?? 0),
            'quality',
        ])[0] ?? [];
    $image = $existingQuality['image'] ?? '';
    $body = '';
    $link = '';
}
if ($kind === 'history') {
    $existingHistory =
        query('SELECT * FROM contents WHERE id=? AND kind=?', [
            (int) ($_POST['id'] ?? 0),
            'history',
        ])[0] ?? [];
    $image = $existingHistory['image'] ?? '';
    if ($historyType === 'leader') {
        $uploadToken = $_POST['leader_upload_token'] ?? '';
        if (!is_string($uploadToken) || !preg_match('/^[a-f0-9]{32}$/', $uploadToken)) {
            $uploadToken = bin2hex(random_bytes(16));
            $_POST['leader_upload_token'] = $uploadToken;
        }
        $pendingPhoto = $_SESSION['leader_uploads'][$uploadToken] ?? null;
        if ($pendingPhoto && (int) $pendingPhoto['id'] === (int) ($_POST['id'] ?? 0)) {
            $image = $pendingPhoto['path'];
        }
    }
    $link = $historyType === 'video' ? $existingHistory['link'] ?? '' : '';
    if ($historyType === 'video') {
        $uploadedVideo = uploadHistoryVideo('video_file');
        if ($uploadedVideo !== '') {
            $link = $uploadedVideo;
        }
        if (!historyVideo($link)) {
            throw new RuntimeException('Unggah video sejarah MP4 atau WebM (maksimal 20 MB).');
        }
    }
}

if (
    (!in_array(
        $kind,
        array_merge(
            ['slide', 'service_slide', 'outpatient_photo', 'emergency_photo'],
            facilityPhotoKinds(),
        ),
        true,
    ) &&
        $title === '') ||
    strlen($title) > 255 ||
    strlen($body) > 50000 ||
    strlen($_POST['subtitle'] ?? '') > 255
) {
    throw new RuntimeException(
        in_array(
            $kind,
            array_merge(
                ['slide', 'service_slide', 'outpatient_photo', 'emergency_photo'],
                facilityPhotoKinds(),
            ),
            true,
        )
            ? 'Judul dan subjudul maksimal 255 karakter; isi maksimal 50.000 karakter.'
            : 'Judul wajib diisi (maksimal 255 karakter); isi maksimal 50.000 karakter.',
    );
}
if (
    (!safeLink($link) &&
        !(
            $kind === 'history' && preg_match('~^assets/uploads/[a-f0-9]{32}\.(mp4|webm)$~', $link)
        )) ||
    ($image !== '' &&
        !preg_match('~^https://[^\s]+$~i', $image) &&
        !preg_match('~^assets/uploads/[a-f0-9]{32}\.(jpg|png|webp)$~', $image))
) {
    throw new RuntimeException(
        'Gambar harus URL HTTPS. Tautan harus HTTPS atau index.php?page=...',
    );
}

// Unggahan baru menggantikan gambar lama hanya jika berkas berhasil diterima.
$uploaded = uploadImage('image_file');
if ($uploaded !== '') {
    $image = $uploaded;
    if ($kind === 'history' && $historyType === 'leader') {
        $_SESSION['leader_uploads'][$uploadToken] = [
            'id' => (int) ($_POST['id'] ?? 0),
            'path' => $uploaded,
        ];
    }
}

if (
    in_array(
        $kind,
        array_merge(['service_slide', 'outpatient_photo', 'emergency_photo'], facilityPhotoKinds()),
        true,
    ) &&
    $image === ''
) {
    throw new RuntimeException('Unggah foto fasilitas atau masukkan URL gambar.');
}

// Popup promosi selalu berupa poster; teks lama tidak diteruskan ke tampilan publik.
if ($kind === 'popup') {
    if ($image === '') {
        throw new RuntimeException('Unggah gambar promosi atau masukkan URL gambar HTTPS.');
    }
    $body = '';
    $_POST['subtitle'] = '';
}

if ($kind === 'clinic') {
    $specialty = trim($_POST['subtitle'] ?? '');
    if ($specialty === '') {
        throw new RuntimeException('Isi poli penghubung sesuai field poliklinik dokter.');
    }
}

// Memeriksa tanggal tayang atau periode jabatan sesuai jenis konten.
$start = $_POST['starts'] ?? '';
if ($kind === 'quality' && $image === '') {
    throw new RuntimeException('Unggah gambar laporan mutu (JPG, PNG, atau WebP; maksimal 25 MB).');
}
$end = $_POST['ends'] ?? '';
if (
    $kind === 'history' &&
    $historyType === 'leader' &&
    (trim($_POST['subtitle'] ?? '') === '' || $start === '')
) {
    throw new RuntimeException('Isi jabatan dan tahun atau tanggal mulai menjabat.');
}
if ($kind === 'history' && $historyType === 'leader' && $image === '') {
    throw new RuntimeException('Unggah foto pemimpin (maksimal 25 MB).');
}
if ($kind === 'history' && $historyType === 'leader') {
    $start = historyTenureValue($start);
    $end = historyTenureValue($end);
    $startBound = strlen($start) === 4 ? $start . '-01-01' : $start;
    $endBound = strlen($end) === 4 ? $end . '-12-31' : $end;
    if ($end !== '' && $endBound < $startBound) {
        throw new RuntimeException('Akhir masa jabatan tidak boleh sebelum awal masa jabatan.');
    }
} elseif (
    ($start !== '' && !validDate($start)) ||
    ($end !== '' && !validDate($end)) ||
    ($start !== '' && $end !== '' && $end < $start)
) {
    throw new RuntimeException('Periksa urutan tanggal tayang.');
}

// Urutan nilai harus sama dengan urutan kolom pada query INSERT dan UPDATE.
$values = [
    $title,
    trim($_POST['subtitle'] ?? ''),
    $body,
    $image,
    $link,
    $start,
    $end,
    isset($_POST['active']) ? 1 : 0,
    (int) ($_POST['sort_order'] ?? 0),
];
$id = (int) ($_POST['id'] ?? 0);

if ($id) {
    execute(
        'UPDATE contents SET title=?,subtitle=?,body=?,image=?,link=?,starts=?,ends=?,active=?,sort_order=? WHERE id=? AND kind=?',
        [...$values, $id, $kind],
    );
} else {
    execute(
        'INSERT INTO contents (title,subtitle,body,image,link,starts,ends,active,sort_order,kind) VALUES (?,?,?,?,?,?,?,?,?,?)',
        [...$values, $kind],
    );
}

// Menyimpan pengaturan tampilan terpisah agar ukuran foto mengikuti data master.
if ($slideDisplay !== null) {
    $savedId = $id ?: (int) $db->lastInsertId();
    $displayKey = 'slide_display_' . $savedId;
    $displayValue = json_encode($slideDisplay);
    if (query('SELECT name FROM settings WHERE name=?', [$displayKey])) {
        execute('UPDATE settings SET value=? WHERE name=?', [$displayValue, $displayKey]);
    } else {
        execute('INSERT INTO settings (name,value) VALUES (?,?)', [$displayKey, $displayValue]);
    }
}

if ($kind === 'history') {
    $savedId = $id ?: (int) $db->lastInsertId();
    $typeKey = 'history_type_' . $savedId;
    if ($historyType === 'leader') {
        $photoSizeKey = 'history_leader_zoom_' . $savedId;
        foreach ($leaderPosition as $axis => $position) {
            $positionKey = 'history_leader_' . $axis . '_' . $savedId;
            if (query('SELECT name FROM settings WHERE name=?', [$positionKey])) {
                execute('UPDATE settings SET value=? WHERE name=?', [
                    (string) $position,
                    $positionKey,
                ]);
            } else {
                execute('INSERT INTO settings (name,value) VALUES (?,?)', [
                    $positionKey,
                    (string) $position,
                ]);
            }
        }
        if (query('SELECT name FROM settings WHERE name=?', [$photoSizeKey])) {
            execute('UPDATE settings SET value=? WHERE name=?', [
                (string) $leaderPhotoSize,
                $photoSizeKey,
            ]);
        } else {
            execute('INSERT INTO settings (name,value) VALUES (?,?)', [
                $photoSizeKey,
                (string) $leaderPhotoSize,
            ]);
        }
    }
    if (query('SELECT name FROM settings WHERE name=?', [$typeKey])) {
        execute('UPDATE settings SET value=? WHERE name=?', [$historyType, $typeKey]);
    } else {
        execute('INSERT INTO settings (name,value) VALUES (?,?)', [$typeKey, $historyType]);
    }
    if ($historyType === 'organization') {
        $widthKey = 'history_width_' . $savedId;
        if (query('SELECT name FROM settings WHERE name=?', [$widthKey])) {
            execute('UPDATE settings SET value=? WHERE name=?', [
                (string) $organizationWidth,
                $widthKey,
            ]);
        } else {
            execute('INSERT INTO settings (name,value) VALUES (?,?)', [
                $widthKey,
                (string) $organizationWidth,
            ]);
        }
    }
}

if ($kind === 'history' && $historyType === 'leader') {
    unset($_SESSION['leader_uploads'][$uploadToken]);
}
flash('Konten disimpan.');
