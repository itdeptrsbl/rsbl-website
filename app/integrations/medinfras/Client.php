<?php
// Koneksi server Medinfras berdasarkan koleksi Postman; belum dipanggil halaman publik.
namespace Rsbl\Integrations\Medinfras;

final class Client
{
    private array $config;
    private ?\Closure $transport;

    // Transport pengganti hanya dipakai tes agar tidak menghubungi rumah sakit.
    public function __construct(array $config, ?\Closure $transport = null)
    {
        $this->config = $config;
        $this->transport = $transport;
    }

    // Memeriksa kelengkapan konfigurasi tanpa menguji koneksi atau membuka rahasia.
    public function configured(): bool
    {
        $url = $this->config['base_url'] ?? '';
        $parts = parse_url($url);
        return is_array($parts) &&
            !empty($parts['host']) &&
            !isset($parts['user']) &&
            !isset($parts['pass']) &&
            !isset($parts['query']) &&
            !isset($parts['fragment']) &&
            (strtolower($parts['scheme'] ?? '') === 'https' ||
                (strtolower($parts['scheme'] ?? '') === 'http' &&
                    !empty($this->config['allow_http']))) &&
            ($this->config['consumer_id'] ?? '') !== '' &&
            ($this->config['password'] ?? '') !== '';
    }

    // Rumus Postman: base64(HMAC-SHA256(timestamp + consumer ID, password)).
    public static function signature(
        string $timestamp,
        string $consumerId,
        string $password,
    ): string {
        return base64_encode(hash_hmac('sha256', $timestamp . $consumerId, $password, true));
    }

    // Data pada beberapa respons berupa string JSON di dalam envelope JSON.
    public static function decodeResponse(string $body): mixed
    {
        try {
            $envelope = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
            if (
                !is_array($envelope) ||
                !in_array($envelope['Status'] ?? '', ['SUCCESS (000)', 'SUCCESS'], true)
            ) {
                throw new \RuntimeException('Medinfras tidak dapat memproses permintaan.');
            }
            if (!array_key_exists('Data', $envelope)) {
                throw new \RuntimeException('Respons Medinfras tidak lengkap.');
            }
            $data = $envelope['Data'];
            if (is_string($data)) {
                $data = json_decode($data, true, 64, JSON_THROW_ON_ERROR);
            }
            return $data;
        } catch (\JsonException $exception) {
            // Jangan masukkan respons atau identitas pasien ke pesan kesalahan/log.
            throw new \RuntimeException('Format respons Medinfras tidak valid.');
        }
    }

    // Mengambil daftar poli rawat jalan menggunakan kode asli Medinfras.
    public function outpatientUnits(): mixed
    {
        return $this->get('healthcareserviceunit/base/list/department/outpatient');
    }

    // Filter memakai parameter yang dicontohkan koleksi; bukan ID dokter lokal.
    public function paramedics(string $specialtyName = ''): mixed
    {
        return $this->get(
            'paramedic/base/list/search2',
            $specialtyName === '' ? [] : ['specialtyName' => $specialtyName],
        );
    }

    // Seluruh jadwal masih perlu dipetakan setelah contoh respons tersedia.
    public function schedules(): mixed
    {
        return $this->get('paramedicschedule/base/all_schedule');
    }

    // Hanya boleh dipakai setelah verifikasi pasien di controller, bukan endpoint publik langsung.
    public function patientByMedicalNumber(string $medicalNumber): mixed
    {
        if (!preg_match('/^[A-Za-z0-9-]{1,40}$/D', $medicalNumber)) {
            throw new \InvalidArgumentException('Format nomor rekam medis tidak valid.');
        }
        return $this->get('patient/mrn/' . rawurlencode($medicalNumber));
    }

    // Payload disiapkan di server; nomor antrean dibuat otomatis oleh Medinfras.
    public function createAppointment(array $payload): array
    {
        foreach (
            [
                'HealthcareID',
                'IsNewPatient',
                'DepartmentID',
                'ServiceUnitCode',
                'ParamedicCode',
                'VisitTypeCode',
                'OperationalTimeCode',
                'StartDate',
                'StartTime',
                'AppointmentMethod',
                'UserID',
            ]
            as $key
        ) {
            if (
                !isset($payload[$key]) ||
                !is_scalar($payload[$key]) ||
                (string) $payload[$key] === ''
            ) {
                throw new \InvalidArgumentException('Data appointment belum lengkap.');
            }
        }
        // Konfirmasi RSBL: QueueNo kosong, bukan nomor yang dihitung website.
        $payload['QueueNo'] = '';
        $data = $this->request('POST', 'v2/centerback/ADT_A05', [], $payload);
        if (!is_array($data) || empty($data['AppointmentNo'])) {
            // Jangan mengulang POST: transaksi mungkin sudah diterima Medinfras.
            throw new \RuntimeException(
                'Hasil pendaftaran belum dapat dipastikan. Hubungi petugas sebelum mengirim ulang.',
            );
        }
        return $data;
    }

    // Permintaan GET tidak mengubah data; semua path ditentukan oleh kode server.
    private function get(string $path, array $query = []): mixed
    {
        return $this->request('GET', $path, $query);
    }

    // Koneksi hanya ke URL konfigurasi server; tidak mengikuti redirect ke host lain.
    private function request(
        string $method,
        string $path,
        array $query = [],
        ?array $payload = null,
    ): mixed {
        if (!$this->configured()) {
            throw new \RuntimeException('Konfigurasi Medinfras belum lengkap.');
        }
        $consumerId = $this->config['consumer_id'];
        if (preg_match('/[\r\n]/', $consumerId)) {
            throw new \RuntimeException('Konfigurasi Medinfras tidak valid.');
        }
        $timestamp = (string) time();
        $headers = [
            'Accept: application/json',
            'X-cons-id: ' . $consumerId,
            'X-timestamp: ' . $timestamp,
            'X-signature: ' . self::signature($timestamp, $consumerId, $this->config['password']),
        ];
        $body = $payload === null ? null : json_encode($payload, JSON_THROW_ON_ERROR);
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        $url = rtrim($this->config['base_url'], '/') . '/' . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        if ($this->transport !== null) {
            [$status, $body] = ($this->transport)($url, $headers, $method, $body);
        } else {
            [$status, $body] = $this->send($url, $headers, $method, $body);
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(
                'Koneksi Medinfras belum berhasil. Silakan coba kembali nanti.',
            );
        }
        return self::decodeResponse($body);
    }

    // Batasi waktu dan ukuran respons; TLS tetap diverifikasi dan tanpa retry otomatis.
    private function send(string $url, array $headers, string $method, ?string $requestBody): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Ekstensi cURL PHP diperlukan untuk koneksi Medinfras.');
        }
        $handle = curl_init($url);
        $body = '';
        curl_setopt_array($handle, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => max(1, min(30, (int) ($this->config['timeout'] ?? 15))),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > 4 * 1024 * 1024) {
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        if ($method === 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $requestBody);
        }
        $ok = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($ok === false) {
            throw new \RuntimeException('Koneksi Medinfras terputus atau melewati batas waktu.');
        }
        return [$status, $body];
    }
}
