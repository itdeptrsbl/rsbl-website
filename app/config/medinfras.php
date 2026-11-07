<?php
// Rahasia Medinfras dibaca dari lingkungan server, bukan dari master atau browser.
return [
    // URL dasar adalah nilai URL Postman ditambah /api (jangan lupa prefix ini).
    'base_url' => rtrim(
        (string) (getenv('MEDINFRAS_BASE_URL') ?:
        'https://36.94.176.235/medinfrasapi2.1/workshop/api'),
        '/',
    ),
    'consumer_id' => (string) getenv('MEDINFRAS_CONSUMER_ID'),
    'password' => (string) getenv('MEDINFRAS_PASSWORD'),
    // HTTP hanya untuk jaringan internal yang memang disiapkan tim rumah sakit.
    'allow_http' =>
        getenv('MEDINFRAS_ALLOW_HTTP') === '1' ||
        (getenv('MEDINFRAS_ALLOW_HTTP') === false &&
            parse_url(
                (string) (getenv('MEDINFRAS_BASE_URL') ?: 'http://192.168.10.5'),
                PHP_URL_HOST,
            ) === '192.168.10.5'),
    'timeout' => 15,
    'healthcare_id' => (string) (getenv('MEDINFRAS_HEALTHCARE_ID') ?: '001'),
    'user_id' => (string) getenv('MEDINFRAS_USER_ID'),
];
