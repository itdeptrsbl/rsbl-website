<?php
// Pemetaan form website ke field appointment resmi koleksi Postman RSBL.
namespace Rsbl\Integrations\Medinfras;

final class AppointmentPayload
{
    // $visit harus berasal dari sesi API yang sudah diverifikasi server, bukan POST mentah.
    public static function build(array $patient, array $visit, array $config): array
    {
        $existing = ($patient['patient_type'] ?? '') === 'existing';
        if (!in_array($patient['patient_type'] ?? '', ['new', 'existing'], true)) {
            throw new \InvalidArgumentException('Pilih jenis pasien.');
        }
        foreach (
            [
                'ServiceUnitCode',
                'ParamedicCode',
                'VisitTypeCode',
                'OperationalTimeCode',
                'StartTime',
            ]
            as $key
        ) {
            if (!is_string($visit[$key] ?? null) || trim($visit[$key]) === '') {
                throw new \InvalidArgumentException('Pilihan jadwal Medinfras belum lengkap.');
            }
        }
        $date = self::date((string) ($visit['visit_date'] ?? ''));
        if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $visit['StartTime'])) {
            throw new \InvalidArgumentException('Jam praktik tidak valid.');
        }
        $userId = trim((string) ($config['user_id'] ?? ''));
        if ($userId === '') {
            throw new \InvalidArgumentException('Akun integrasi Medinfras belum diatur.');
        }
        $payload = [
            'HealthcareID' => (string) ($config['healthcare_id'] ?? '001'),
            'IsNewPatient' => $existing ? '0' : '1',
            'MedicalNo' => '',
            'DepartmentID' => 'OUTPATIENT',
            'ServiceUnitCode' => $visit['ServiceUnitCode'],
            'ParamedicCode' => $visit['ParamedicCode'],
            'VisitTypeCode' => $visit['VisitTypeCode'],
            'OperationalTimeCode' => $visit['OperationalTimeCode'],
            'QueueNo' => '',
            'Session' => (int) ($visit['Session'] ?? 0),
            'StartDate' => $date,
            'StartTime' => $visit['StartTime'],
            'Notes' => trim((string) ($patient['notes'] ?? '')),
            'IsPersonalPayer' => '1',
            'ReferenceNo' => '',
            'BookingCode' => '',
            'ExternalPatientID' => '',
            'AppointmentMethod' => '002',
            'UserID' => $userId,
        ];
        if ($existing) {
            $medicalNumber = trim((string) ($patient['medical_record_number'] ?? ''));
            if (!preg_match('/^[A-Za-z0-9-]{1,40}$/D', $medicalNumber)) {
                throw new \InvalidArgumentException('Nomor rekam medis tidak valid.');
            }
            $payload['MedicalNo'] = $medicalNumber;
            return $payload;
        }
        // Jangan mengarang nama belakang untuk pasien bernama tunggal.
        foreach (['first_name', 'last_name', 'address', 'phone', 'birth_date'] as $key) {
            if (!is_string($patient[$key] ?? null) || trim($patient[$key]) === '') {
                throw new \InvalidArgumentException(
                    'Identitas pasien baru belum lengkap sesuai persyaratan Medinfras.',
                );
            }
        }
        $gender = ['Laki-laki' => 'M', 'Perempuan' => 'F'][$patient['gender'] ?? ''] ?? null;
        if ($gender === null) {
            throw new \InvalidArgumentException('Jenis kelamin tidak valid.');
        }
        $mapping = [
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
            // Kode agama/kewarganegaraan harus dari master Medinfras, bukan label UI.
            'Religion' => 'religion_code',
            'Nationality' => 'nationality_code',
            'IdentityNoType' => 'identity_type_code',
            'IdentityCardNo' => 'identity_number',
        ];
        foreach ($mapping as $target => $source) {
            $payload[$target] = trim((string) ($patient[$source] ?? ''));
        }
        $payload['PatientName'] = implode(
            ' ',
            array_filter(
                [$payload['FirstName'], $payload['MiddleName'], $payload['LastName']],
                static fn(string $part): bool => $part !== '',
            ),
        );
        $payload['DateOfBirth'] = self::date($patient['birth_date']);
        $payload['Gender'] = $gender;
        return $payload;
    }

    // Validasi kalender sebelum mengganti yyyy-MM-dd menjadi yyyyMMdd.
    private static function date(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException('Tanggal tidak valid.');
        }
        return $date->format('Ymd');
    }
}
