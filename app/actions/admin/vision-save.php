<?php
// Memvalidasi dan menyimpan kelima bagian visi dan nilai rumah sakit.

$content = validateVisionContent($_POST);
$value = json_encode($content, JSON_UNESCAPED_UNICODE);
if (query('SELECT name FROM settings WHERE name=?', ['vision_content'])) {
    execute('UPDATE settings SET value=? WHERE name=?', [$value, 'vision_content']);
} else {
    execute('INSERT INTO settings (name,value) VALUES (?,?)', ['vision_content', $value]);
}
flash('Visi, Misi, Janji, Motto, dan Nilai-Nilai disimpan.');
