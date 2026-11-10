<?php
// Membatasi respons poli publik menjadi kode dan nama; tidak meneruskan metadata internal RS.
namespace Rsbl\Integrations\Medinfras;

final class OutpatientUnits
{
    // Tidak menggabungkan nama mirip: sesi pagi/sore tetap mengikuti kode Medinfras.
    public static function normalize(mixed $rows): array
    {
        if (
            !is_array($rows) ||
            ($rows !== [] && array_keys($rows) !== range(0, count($rows) - 1))
        ) {
            throw new \RuntimeException('Format daftar poli belum sesuai.');
        }
        $units = [];
        foreach ($rows as $row) {
            if (!is_array($row) || ($row['DepartmentID'] ?? '') !== 'OUTPATIENT') {
                continue;
            }
            $code = $row['ServiceUnitCode'] ?? null;
            $name = $row['ServiceUnitName'] ?? null;
            if (
                !is_string($code) ||
                !is_string($name) ||
                trim($code) === '' ||
                trim($name) === ''
            ) {
                continue;
            }
            $units[trim($code)] = ['code' => trim($code), 'name' => trim($name)];
        }
        $units = array_values($units);
        usort($units, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
        return $units;
    }
}
