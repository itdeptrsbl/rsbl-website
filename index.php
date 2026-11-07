<?php
// Pintu masuk website: memilih halaman, memuat modul, dan menjalankan controller.
require __DIR__ . '/app/bootstrap.php';

$page = $_GET['page'] ?? 'home';
// Daftar tertutup ini mencegah input URL dipakai untuk memuat file sembarang.
$allowed = [
    'home',
    'doctors',
    'doctor_schedule',
    'doctor_profile',
    'services',
    'outpatient',
    'inpatient',
    'emergency',
    'farmasi',
    'radiologi',
    'fisioterapi',
    'laboratorium',
    'news',
    'about',
    'history',
    'vision',
    'quality',
    'register',
    'contact',
    'detail',
    'login',
    'admin',
    'logout',
    'install',
];

if (!is_string($page) || !in_array($page, $allowed, true)) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}
if (!installed() && $page !== 'install') {
    go('index.php?page=install');
}
if ($page === 'install') {
    require APP_ROOT . '/views/auth/install.php';
    exit();
}
// Keluar akun harus melalui formulir POST, bukan sekadar membuka tautan.
if ($page === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit();
    }
    verifyCsrf();
    $_SESSION = [];
    session_regenerate_id(true);
    go('index.php');
}
if ($page === 'login') {
    require APP_ROOT . '/views/auth/login.php';
    exit();
}

// Muat fungsi fitur sebelum controller atau template menggunakannya.
require APP_ROOT . '/app/modules/doctors.php';
require APP_ROOT . '/app/modules/services.php';
require APP_ROOT . '/app/modules/inpatient.php';
require APP_ROOT . '/app/modules/information.php';
require APP_ROOT . '/app/modules/emergency.php';
require APP_ROOT . '/app/modules/history.php';
require APP_ROOT . '/app/modules/vision.php';

// Halaman pengelola harus melewati pemeriksaan login sebelum diproses.
if ($page === 'admin') {
    adminOnly();
    require APP_ROOT . '/app/controllers/admin.php';
    require APP_ROOT . '/views/admin/layout.php';
    exit();
}

$error = '';
$success = '';
if ($page === 'register') {
    require APP_ROOT . '/app/controllers/registration.php';
}
if ($page === 'doctor_profile') {
    $doctor =
        query('SELECT * FROM doctors WHERE id=? AND active=1', [(int) ($_GET['id'] ?? 0)])[0] ??
        null;
    if (!$doctor) {
        http_response_code(404);
    }
}

require APP_ROOT . '/views/layouts/header.php';
require APP_ROOT . '/views/public/dispatch.php';
require APP_ROOT . '/views/layouts/footer.php';
