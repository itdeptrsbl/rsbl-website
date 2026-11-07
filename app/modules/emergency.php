<?php
// Pengaturan isi halaman, jam layanan, dan kontak Instalasi Gawat Darurat.

// Membaca pengaturan IGD dan melengkapi bagian yang belum diisi dengan nilai awal.
function emergencyContent(): array
{
    $stored = json_decode(setting('emergency_content', '{}'), true);
    return (is_array($stored) ? $stored : []) + [
        'intro' =>
            'Instalasi Gawat Darurat (IGD) memberikan pelayanan penanganan awal bagi pasien dengan kondisi gawat darurat.',
        'hours' => '24 jam',
        'phone' => '',
        'ambulance_phone' => '',
        'ambulance_note' => '',
        'location' => '',
        'facilities' => '',
        'arrival_note' =>
            'Untuk kondisi darurat, datang langsung ke IGD atau hubungi rumah sakit. Formulir pendaftaran online bukan saluran darurat.',
    ];
}

// Memvalidasi nomor kontak dan menyimpan seluruh pengaturan IGD.
function saveEmergencyContent(): void
{
    $limits = [
        'intro' => 10000,
        'hours' => 100,
        'phone' => 40,
        'ambulance_phone' => 40,
        'ambulance_note' => 2000,
        'location' => 2000,
        'facilities' => 10000,
        'arrival_note' => 2000,
    ];
    $content = [];
    foreach ($limits as $key => $limit) {
        $value = $_POST[$key] ?? '';
        if (!is_string($value) || strlen(trim($value)) > $limit) {
            throw new RuntimeException('Isi pengaturan IGD tidak valid atau terlalu panjang.');
        }
        $content[$key] = trim($value);
    }
    foreach (['phone', 'ambulance_phone'] as $key) {
        if ($content[$key] !== '' && !preg_match('/^\+?[0-9][0-9 ()-]{5,38}$/', $content[$key])) {
            throw new RuntimeException(
                'Nomor IGD atau ambulans harus berupa nomor telepon yang valid.',
            );
        }
    }
    $value = json_encode($content, JSON_UNESCAPED_UNICODE);
    if (query('SELECT name FROM settings WHERE name=?', ['emergency_content'])) {
        execute('UPDATE settings SET value=? WHERE name=?', [$value, 'emergency_content']);
    } else {
        execute('INSERT INTO settings (name,value) VALUES (?,?)', ['emergency_content', $value]);
    }
    flash('Informasi dan kontak IGD disimpan.');
}
