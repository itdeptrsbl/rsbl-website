<?php
// Katalog penjamin publik: kategori resmi API, tanpa kontrak atau rincian internal perusahaan.
namespace Rsbl\Integrations\Medinfras;
final class Payers
{
    public static function normalize(mixed $rows): array
    {
        if (
            !is_array($rows) ||
            ($rows !== [] && array_keys($rows) !== range(0, count($rows) - 1))
        ) {
            throw new \RuntimeException('Format penjamin belum sesuai.');
        }
        $result = [];
        foreach ($rows as $row) {
            if (
                !is_array($row) ||
                !in_array($row['IsActive'] ?? false, [true, 1, '1'], true) ||
                !empty($row['IsBlacklist'])
            ) {
                continue;
            }
            $category = $row['CustomerTypeDesc'] ?? null;
            $typeCode = $row['CustomerTypeCode'] ?? '';
            // Nama kategori berasal dari API; BPJS dikeluarkan berdasarkan kode maupun label.
            if (
                !is_string($category) ||
                trim($category) === '' ||
                $typeCode === 'X004^500' ||
                preg_match('/^BPJS\b/i', trim($category))
            ) {
                continue;
            }
            $category = trim($category);
            $code = $row['BusinessPartnerCode'] ?? null;
            $name = $row['BusinessPartnerName'] ?? null;
            if (
                !is_string($code) ||
                !is_string($name) ||
                trim($code) === '' ||
                trim($name) === ''
            ) {
                continue;
            }
            $result[$code] = [
                'code' => trim($code),
                'name' => trim($name),
                'category' => $category,
            ];
        }
        $result = array_values($result);
        usort($result, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        return $result;
    }
}
