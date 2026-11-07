<?php
// Memeriksa permintaan admin lalu memilih aksi simpan, hapus, atau ubah status.

require_once APP_ROOT . '/app/support/branding.php';

// Semua perubahan master wajib melalui autentikasi dan token CSRF.
require APP_ROOT . '/app/config/admin.php';

$tab = $_GET['tab'] ?? 'dashboard';
if (!isset($modules[$tab])) {
    $tab = 'dashboard';
}

$kind = $_GET['kind'] ?? 'service';
if (!isset($kinds[$kind])) {
    $kind = 'service';
}

if ($tab === 'information' && !in_array($kind, informationKinds(), true)) {
    $kind = 'news';
}

$error = '';

// Membatasi tautan konten ke HTTPS atau halaman internal index.php.
function safeLink(string $link): bool
{
    return $link === '' ||
        preg_match('~^https://[^\s]+$~i', $link) ||
        preg_match('~^index\.php(?:\?[^\s]*)?$~', $link);
}

// Semua aksi perubahan wajib melalui POST dan pemeriksaan token CSRF.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Pilih handler sesuai nama action; handler memakai data formulir yang sama.
    try {
        if ($action === 'information_save') {
            require APP_ROOT . '/app/actions/admin/information-save.php';
        } elseif ($action === 'facility_intro') {
            require APP_ROOT . '/app/actions/admin/facility-intro.php';
        } elseif ($action === 'emergency_save') {
            require APP_ROOT . '/app/actions/admin/emergency-save.php';
        } elseif (
            in_array($action, ['inpatient_page', 'inpatient_save', 'inpatient_delete'], true)
        ) {
            require APP_ROOT . '/app/actions/admin/inpatient.php';
        } elseif ($action === 'outpatient_intro') {
            require APP_ROOT . '/app/actions/admin/outpatient-intro.php';
        } elseif ($action === 'vision_save') {
            require APP_ROOT . '/app/actions/admin/vision-save.php';
        } elseif ($action === 'partner_heading') {
            require APP_ROOT . '/app/actions/admin/partner-heading.php';
        } elseif ($action === 'settings') {
            require APP_ROOT . '/app/actions/admin/settings.php';
        } elseif ($action === 'content_save') {
            require APP_ROOT . '/app/actions/admin/content-save.php';
        } elseif ($action === 'content_delete') {
            require APP_ROOT . '/app/actions/admin/content-delete.php';
        } elseif ($action === 'doctor_save') {
            require APP_ROOT . '/app/actions/admin/doctor-save.php';
        } elseif ($action === 'doctor_archive') {
            require APP_ROOT . '/app/actions/admin/doctor-archive.php';
        } elseif ($action === 'appointment_update') {
            require APP_ROOT . '/app/actions/admin/appointment-update.php';
        } elseif ($action === 'appointment_delete') {
            require APP_ROOT . '/app/actions/admin/appointment-delete.php';
        } elseif ($action === 'password') {
            require APP_ROOT . '/app/actions/admin/password.php';
        } else {
            throw new RuntimeException('Tindakan tidak dikenal.');
        }

        // Setelah berhasil, redirect mencegah formulir terkirim ulang saat halaman di-refresh.
        go('index.php?page=admin&tab=' . urlencode($tab) . '&kind=' . urlencode($kind));
        // Ubah kegagalan validasi menjadi pesan yang bisa ditampilkan pada form.
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? 'Perubahan tidak dapat disimpan. Periksa database.'
                : $ex->getMessage();
    }
}
