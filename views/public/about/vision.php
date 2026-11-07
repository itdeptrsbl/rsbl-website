<?php
// Menampilkan visi, misi, janji, motto, dan nilai rumah sakit.

$visionData = visionContent(); ?>
<div class="vision-page">
    <section class="container page-intro">
        <p class="eyebrow">TENTANG KAMI</p>
        <h1>Visi &amp; Misi</h1>
        <div class="tab-row" aria-label="Tentang rumah sakit">
            <a href="index.php?page=history">Sejarah</a>
            <a class="selected" href="index.php?page=vision" aria-current="page">Visi &amp; Misi</a>
            <a href="index.php?page=quality">Laporan Mutu</a>
        </div>
    </section>
    <div class="container vision-sections">
        <?php if ($visionData['vision'] !== ''): ?>
        <section class="vision-statement" aria-labelledby="vision-title">
            <div class="vision-trust-heading">
                <p class="eyebrow" id="vision-title">VISI</p>
                <span class="vision-care-icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 27S4 20 4 11.5a6.5 6.5 0 0 1 12-3.4 6.5 6.5 0 0 1 12 3.4C28 20 16 27 16 27Z"/>
                        <path d="M7 16h5l2-5 4 10 2-5h5"/>
                    </svg>
                </span>
            </div>
            <p class="vision-main-text"><?= nl2br(e($visionData['vision'])) ?></p>
        </section>
        <?php endif; ?>
<?php if ($visionData['missions']): ?>
        <section class="vision-section" aria-labelledby="mission-title">
            <h2 id="mission-title">MISI</h2>
            <ol class="vision-missions">
                <?php foreach ($visionData['missions'] as $index => $mission): ?>
                <li>
                    <span class="mission-number" aria-hidden="true"><?= sprintf(
                        '%02d',
                        $index + 1,
                    ) ?></span>
                    <p><?= nl2br(e($mission)) ?></p>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>
        <?php endif; ?>
<?php if ($visionData['promise'] !== '' || $visionData['motto'] !== ''): ?>
        <div class="vision-commitments">
            <?php if ($visionData['promise'] !== ''): ?>
            <section class="vision-promise">
                <div class="promise-trust-heading">
                    <span class="promise-care-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 3 5 7v8c0 7 11 14 11 14s11-7 11-14V7L16 3Z"/>
                            <path d="M16 21s-6-3.5-6-7a3.3 3.3 0 0 1 6-1.8 3.3 3.3 0 0 1 6 1.8c0 3.5-6 7-6 7Z"/>
                        </svg>
                    </span>
                    <h2>JANJI</h2>
                </div>
                <p><?= nl2br(e($visionData['promise'])) ?></p>
            </section>
            <?php endif; ?>
<?php if ($visionData['motto'] !== ''): ?>
            <section class="vision-motto">
                <div class="promise-trust-heading">
                    <span class="motto-care-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 10h8v9H7c0 4 2 6 5 7M19 10h8v9h-7c0 4 2 6 5 7"/>
                        </svg>
                    </span>
                    <h2>MOTTO</h2>
                </div>
                <p><?= nl2br(e($visionData['motto'])) ?></p>
            </section>
            <?php endif; ?>
        </div>
        <?php endif; ?>
<?php if ($visionData['values']): ?>
        <section class="vision-section vision-values-section" aria-labelledby="values-title">
            <h2 id="values-title">NILAI-NILAI</h2>
            <ul class="vision-values">
                <?php foreach ($visionData['values'] as $value): ?>
                <li>
                    <span class="value-mark" aria-hidden="true">✦</span>
                    <p><?= nl2br(e($value)) ?></p>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
<?php if (!array_filter($visionData)): ?>
        <div class="panel pale">
            <p>Informasi visi, misi, dan nilai-nilai belum tersedia.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
