<?php
// Kartu dokter yang mengarah ke profil serta riwayat profesionalnya.

// Profil dokter menjadi tujuan utama kartu, termasuk kartu di beranda.
$profileUrl = 'index.php?page=doctor_profile&id=' . (int) $d['id']; ?>
<article class="doctor-card" data-search="<?= e(strtolower($d['name'] . ' ' . $d['specialty'])) ?>">
    <a href="<?= e($profileUrl) ?>" class="doctor-photo-link">
        <div class="doctor-photo">
            <img src="<?= e(photo($d['image'])) ?>" alt="<?= e($d['name']) ?>" loading="lazy">
        </div>
    </a>
    <div class="doctor-info">
        <p class="eyebrow"><?= e($d['specialty']) ?></p>
        <h3>
            <a href="<?= e($profileUrl) ?>"><?= e($d['name']) ?></a>
        </h3>
        <a class="doctor-cta" href="<?= e($profileUrl) ?>">
            Lihat profil &amp; riwayat
            <span>&rarr;</span>
        </a>
    </div>
</article>
