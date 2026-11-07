<?php
// Ringkasan profil rumah sakit dan tautan halaman informasi tambahan.
?><section class="container page-intro">
    <p class="eyebrow">TENTANG KAMI</p>
    <h1>
        Dekat dengan Anda.
        <br>
        Melayani dalam kasih.
    </h1>
    <p class="lead"><?= nl2br(e(setting('about'))) ?></p>
</section>
<section class="container section compact-top">
    <div class="grid three"><?php foreach (records('page') as $i => $item):
        require APP_ROOT . '/views/components/cards/article.php';
    endforeach; ?></div>
</section>
