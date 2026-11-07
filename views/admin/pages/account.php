<?php
// Form penggantian kata sandi akun admin.
?><form class="panel narrow" method="post" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="action" value="password">
    <h2>Ganti kata sandi</h2>
    <?php
    field('current_password', 'Kata sandi saat ini', '', 'password');
    field('new_password', 'Kata sandi baru (minimal 12 karakter)', '', 'password');
    ?>
    <button class="button">Perbarui kata sandi</button>
</form>
