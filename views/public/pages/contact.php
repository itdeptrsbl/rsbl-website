<?php
// Alamat, telepon, WhatsApp, dan tautan petunjuk arah rumah sakit.
?><section class="container page-intro">
    <p class="eyebrow">HUBUNGI KAMI</p>
    <h1>Kami siap membantu.</h1>
    <p class="lead">Informasi kunjungan, layanan, dan kebutuhan Anda.</p>
</section>
<section class="container section compact-top">
    <div class="grid three">
        <article class="panel">
            <h3>Alamat</h3>
            <p><?= e(setting('address')) ?></p>
            <a class="text-link" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode(
                setting('address'),
            ) ?>" target="_blank" rel="noopener">Buka petunjuk arah ↗</a>
        </article>
        <article class="panel">
            <h3>Telepon</h3>
            <p><?= e(setting('phone')) ?></p>
            <a class="text-link" href="tel:<?= e(
                preg_replace('/[^+0-9]/', '', setting('phone')),
            ) ?>">Telepon rumah sakit ↗</a>
        </article>
        <article class="panel">
            <h3>WhatsApp</h3>
            <p>Tanyakan informasi layanan kepada petugas.</p>
            <a class="text-link" href="https://wa.me/<?= e(
                preg_replace('/\D/', '', setting('whatsapp')),
            ) ?>" target="_blank" rel="noopener">Hubungi petugas ↗</a>
        </article>
    </div>
    <div class="notice">Kondisi darurat: datang langsung ke IGD atau hubungi rumah sakit. Formulir online tidak dipantau sebagai saluran darurat.</div>
</section>
