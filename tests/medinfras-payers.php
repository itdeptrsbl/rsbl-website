<?php
// Data samaran untuk memastikan kategori penjamin tidak bercampur dan metadata tidak bocor.
require dirname(__DIR__) . '/app/integrations/medinfras/Payers.php';
require dirname(__DIR__) . '/app/integrations/medinfras/Client.php';
use Rsbl\Integrations\Medinfras\Payers;
use Rsbl\Integrations\Medinfras\Client;
function payerCheck(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException($label);
    }
    echo 'PASS ' . $label . PHP_EOL;
}
$base = ['IsActive' => true, 'IsBlacklist' => false, 'ContractInfo' => 'PRIVATE'];
$rows = [
    $base + [
        'BusinessPartnerCode' => 'R1',
        'BusinessPartnerName' => 'Rekanan Uji',
        'CustomerTypeCode' => 'X004^100',
        'CustomerTypeDesc' => 'Rekanan',
    ],
    $base + [
        'BusinessPartnerCode' => 'K1',
        'BusinessPartnerName' => 'Karyawan Uji',
        'CustomerTypeCode' => 'X004^250',
        'CustomerTypeDesc' => 'Karyawan',
    ],
    $base + [
        'BusinessPartnerCode' => 'B1',
        'BusinessPartnerName' => 'BPJS Uji',
        'CustomerTypeCode' => 'X004^500',
        'CustomerTypeDesc' => 'BPJS',
    ],
    $base + [
        'BusinessPartnerCode' => 'U1',
        'BusinessPartnerName' => 'Kategori Belum Dikenal',
        'CustomerTypeCode' => 'UNKNOWN',
    ],
];
$result = Payers::normalize($rows);
payerCheck(count($result) === 2, 'BPJS dan kategori belum dikenal tidak ditawarkan');
payerCheck(
    array_column($result, 'category', 'code') === ['K1' => 'Karyawan', 'R1' => 'Rekanan'],
    'Kategori mengikuti kode CustomerTypeCode API',
);
payerCheck(
    !str_contains(json_encode($result), 'PRIVATE'),
    'Kontrak dan metadata internal tidak diteruskan',
);
payerCheck(
    Payers::normalize([array_replace($rows[0], ['IsActive' => false])]) === [],
    'Penjamin nonaktif tidak ditawarkan',
);
payerCheck(
    Payers::normalize([array_replace($rows[0], ['IsBlacklist' => true])]) === [],
    'Penjamin blacklist tidak ditawarkan',
);
$client = new Client(
    [
        'enabled' => true,
        'base_url' => 'https://example.test/api',
        'consumer_id' => 'TEST',
        'password' => 'TEST',
    ],
    static function ($url, $headers, $method) {
        payerCheck(
            $method === 'GET' &&
                $url === 'https://example.test/api/businesspartners/base/payer/list',
            'Hanya GET endpoint master payer resmi',
        );
        return [200, json_encode(['Status' => 'SUCCESS', 'Data' => []])];
    },
);
payerCheck($client->payers() === [], 'Daftar penjamin kosong didukung');

$newCategory = array_replace($rows[0], [
    'BusinessPartnerCode' => 'P1',
    'CustomerTypeCode' => 'API-NEW',
    'CustomerTypeDesc' => 'Pemerintah',
]);
payerCheck(
    Payers::normalize([$newCategory])[0]['category'] === 'Pemerintah',
    'Kategori baru mengikuti label API tanpa ditulis tetap di website',
);
