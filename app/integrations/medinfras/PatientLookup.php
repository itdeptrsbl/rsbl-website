<?php
// Memverifikasi pasangan nomor rekam medis dan tanggal lahir sebelum data boleh diteruskan.
namespace Rsbl\Integrations\Medinfras;

final class PatientLookup
{
    // Terima objek atau daftar satu pasien; kecocokan wajib tepat dan tidak ambigu.
    public static function verify(mixed $data, string $medicalNumber, string $birthDate): ?array
    {
        if (!is_array($data)) {
            throw new \RuntimeException('Format data pasien belum sesuai.');
        }
        $rows = isset($data['MedicalNo']) ? [$data] : $data;
        $matches = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $number = $row['MedicalNo'] ?? null;
            $dob = $row['DateOfBirth'] ?? null;
            if (!is_string($number) || !is_string($dob)) {
                continue;
            }
            // Dukungan format tanggal API tetap ketat; tanggal tidak ditebak dari teks bebas.
            $date = substr($dob, 0, 10);
            if (preg_match('/^\d{8}$/D', $dob)) {
                $date = substr($dob, 0, 4) . '-' . substr($dob, 4, 2) . '-' . substr($dob, 6, 2);
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}(?:$|T| )/', $dob)) {
                continue;
            }
            if (
                !hash_equals(strtoupper($medicalNumber), strtoupper(trim($number))) ||
                !hash_equals($birthDate, $date)
            ) {
                continue;
            }
            $name = trim((string) ($row['PatientName'] ?? ''));
            if ($name === '') {
                throw new \RuntimeException('Nama pasien belum tersedia.');
            }
            // Hanya field formulir yang diperlukan; respons SIMRS lengkap tidak dikirim ke browser.
            $fields = ['patient_name' => $name, 'birth_date' => $date];
            foreach (
                [
                    'FirstName' => 'first_name',
                    'MiddleName' => 'middle_name',
                    'LastName' => 'last_name',
                    'MobileNo' => 'phone',
                    'EmailAddress' => 'email',
                    'CityOfBirth' => 'birth_place',
                    'Address' => 'address',
                    'County' => 'village',
                    'District' => 'district',
                    'City' => 'city',
                ]
                as $source => $target
            ) {
                $fields[$target] = is_string($row[$source] ?? null) ? trim($row[$source]) : '';
            }
            if ($fields['first_name'] === '') {
                $fields['first_name'] = $name;
            }
            $fields['gender'] =
                ['M' => 'Laki-laki', 'F' => 'Perempuan'][$row['Gender'] ?? ''] ?? '';
            $matches[] = $fields;
        }
        return count($matches) === 1 ? $matches[0] : null;
    }

    // Batas percobaan tersimpan di server agar tidak dapat dilewati dengan mengganti cookie sesi.
    public static function allowAttempt(string $directory, string $ip, int $now): bool
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Pembatas pencarian belum tersedia.');
        }
        $path = $directory . '/' . hash('sha256', 'patient-lookup:' . $ip) . '.json';
        $file = fopen($path, 'c+');
        if (!$file) {
            throw new \RuntimeException('Pembatas pencarian belum tersedia.');
        }
        try {
            if (!flock($file, LOCK_EX)) {
                throw new \RuntimeException('Pembatas pencarian sibuk.');
            }
            $entry = json_decode(stream_get_contents($file), true);
            if (!is_array($entry) || ($entry['expires'] ?? 0) <= $now) {
                $entry = ['count' => 0, 'expires' => $now + 900];
            }
            if ($entry['count'] >= 5) {
                return false;
            }
            $entry['count']++;
            rewind($file);
            if (!ftruncate($file, 0) || fwrite($file, json_encode($entry)) === false) {
                throw new \RuntimeException('Pembatas pencarian belum tersedia.');
            }
            fflush($file);
            return true;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
