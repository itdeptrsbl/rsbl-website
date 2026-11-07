<?php
// Memilih template publik berdasarkan route yang sudah disetujui controller.

if ($page === 'home'): ?>
<?php require APP_ROOT . '/views/public/pages/home.php'; ?>
<?php elseif (in_array($page, ['doctors', 'doctor_schedule', 'doctor_profile'], true)): ?>
<?php require APP_ROOT . '/views/public/doctors/index.php'; ?>
<?php elseif (isset(facilityPhotoPages()[$page])): ?>
<?php require APP_ROOT . '/views/public/services/detail.php'; ?>
<?php elseif ($page === 'emergency'): ?>
<?php require APP_ROOT . '/views/public/services/emergency.php'; ?>
<?php elseif ($page === 'inpatient'): ?>
<?php require APP_ROOT . '/views/public/services/inpatient.php'; ?>
<?php elseif ($page === 'outpatient'): ?>
<?php require APP_ROOT . '/views/public/services/outpatient.php'; ?>
<?php elseif ($page === 'services'): ?>
<?php require APP_ROOT . '/views/public/services/index.php'; ?>
<?php elseif ($page === 'news'): ?>
<?php require APP_ROOT . '/views/public/pages/information.php'; ?>
<?php elseif ($page === 'detail'): ?>
<?php require APP_ROOT . '/views/public/pages/detail.php'; ?>
<?php elseif ($page === 'history'): ?>
<?php require APP_ROOT . '/views/public/about/history.php'; ?>
<?php elseif ($page === 'vision'): ?>
<?php require APP_ROOT . '/views/public/about/vision.php'; ?>
<?php elseif ($page === 'quality'): ?>
<?php require APP_ROOT . '/views/public/about/quality.php'; ?>
<?php elseif ($page === 'about'): ?>
<?php require APP_ROOT . '/views/public/pages/about.php'; ?>
<?php elseif ($page === 'contact'): ?>
<?php require APP_ROOT . '/views/public/pages/contact.php'; ?>
<?php elseif ($page === 'register'): ?>
<?php require APP_ROOT . '/views/public/pages/registration.php'; ?>
<?php endif; ?>
