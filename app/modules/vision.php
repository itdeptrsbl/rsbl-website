<?php
// Isi dan validasi visi, misi, janji, motto, dan nilai rumah sakit.

// Membaca isi visi dan bagian terkait beserta nilai awalnya.
function visionContent(): array
{
    $saved = json_decode(setting('vision_content', '{}'), true);
    $saved = is_array($saved) ? $saved : [];
    $content = [];
    foreach (['vision', 'promise', 'motto'] as $key) {
        $content[$key] = is_string($saved[$key] ?? null) ? $saved[$key] : '';
    }
    foreach (['missions', 'values'] as $key) {
        $content[$key] = array_values(
            array_filter(
                is_array($saved[$key] ?? null) ? $saved[$key] : [],
                fn($value) => is_string($value) && trim($value) !== '',
            ),
        );
    }
    return $content;
}

// Memeriksa isian visi serta membersihkan daftar misi dan nilai sebelum disimpan.
function validateVisionContent(array $posted): array
{
    $content = [];
    foreach (['vision', 'promise', 'motto'] as $key) {
        if (!is_string($posted[$key] ?? '')) {
            throw new RuntimeException('Isi teks tidak valid.');
        }
        $content[$key] = trim($posted[$key] ?? '');
        if (mb_strlen($content[$key]) > 5000) {
            throw new RuntimeException(
                'Visi, Janji, dan Motto masing-masing maksimal 5.000 karakter.',
            );
        }
    }
    foreach (['missions', 'values'] as $key) {
        $items = $posted[$key] ?? [];
        if (!is_array($items) || count($items) > 50) {
            throw new RuntimeException('Misi dan Nilai-Nilai masing-masing maksimal 50 poin.');
        }
        $content[$key] = [];
        foreach ($items as $item) {
            if (!is_string($item) || mb_strlen($item) > 3000) {
                throw new RuntimeException('Setiap poin maksimal 3.000 karakter.');
            }
            if (trim($item) !== '') {
                $content[$key][] = trim($item);
            }
        }
    }
    if (strlen(json_encode($content)) > 60000) {
        throw new RuntimeException('Total teks terlalu panjang. Persingkat beberapa poin.');
    }
    return $content;
}
