# Pendaftaran online dan integrasi Medinfras

Form publik ada di `views/public/pages/registration.php`, interaksi di
`assets/js/pages/registration.js`, tampilan di `assets/css/pages/registration.css`,
dan pengamanan pengiriman di `app/controllers/registration.php`.

## Status saat ini

Form empat langkah sudah dapat dicoba: kategori, jenis pasien, kunjungan, dan
identitas. BPJS diarahkan ke Mobile JKN melalui informasi pada halaman. Pasien
lama belum dapat melanjutkan sebelum pencarian dan verifikasi API tersedia.
Jadwal memakai data dokter website sebagai referensi, bukan kuota Medinfras.
Pengiriman dinonaktifkan; POST langsung juga ditolak dengan status 503 setelah
pemeriksaan CSRF. Tidak ada bukti antrean atau transaksi baru yang dibuat.
Pendaftaran lama tetap tersimpan dan dapat dibaca pengelola.
Data yang diketik tidak disimpan ke localStorage, sessionStorage, atau URL.
Belum ada perubahan skema database maupun field baru pada master.

## Yang diperlukan dari dokumentasi API

- Lingkungan uji, URL dasar, autentikasi, dan pemetaan kode poli/dokter.
- Endpoint pasien lama beserta verifikasi identitas sebelum data boleh ditampilkan.
- Daftar jadwal, ID sesi, zona waktu, kuota, dan batas tanggal kunjungan.
- Endpoint pendaftaran, field wajib, enum identitas, dan format respons/gagal.
- Cara menangani timeout, pengiriman ulang, pembatalan, dan duplikasi transaksi.

## Langkah penyambungan berikutnya

Buat klien Medinfras di sisi server sesuai kontrak resmi; jangan menaruh token
atau memanggil endpoint berisi kredensial dari JavaScript. Rahasia berasal dari
konfigurasi lingkungan server. Terapkan pembatasan permintaan dan verifikasi
pasien lama; nomor rekam medis saja tidak cukup untuk membuka data pasien.
Jangan mencatat isi respons pasien atau identitas ke log aplikasi.
Ganti pilihan lokal dengan ID poli/dokter/sesi API dan periksa ulang kuota di
server saat submit. Gunakan kunci idempotensi agar pengiriman ulang tidak
membuat dua kunjungan. Hanya tampilkan konfirmasi berhasil setelah ada respons
sukses Medinfras. Uji di sandbox sebelum tombol pengiriman diaktifkan.

## Hasil pemeriksaan koleksi Postman RSBL

Koleksi internal yang dikirim pengguna sudah diperiksa sebagai dokumentasi API.
Pemeriksaan awal dilakukan offline. Pada pemeriksaan lanjutan, hanya GET uji
koneksi, poli, dan jadwal ke lingkungan dev yang dicoba; semuanya menerima
HTTP 404, termasuk ketika prefix `/api` sesuai raw URL koleksi dipakai.
Tidak ada pencarian pasien atau transaksi pendaftaran yang dikirim.
File asli beserta contoh identitas dan variabel rahasianya tidak disalin ke proyek.

Koneksi awal ada di `app/integrations/medinfras/Client.php` dan konfigurasi di
`app/config/medinfras.php`. Klien ini belum dipasang ke formulir publik: format
respons pasien dan pemetaan jadwal masih perlu dipastikan.
Tidak ada endpoint proxy pasien publik yang dibuka.

Autentikasi mengikuti skrip koleksi:
`X-signature = base64(HMAC-SHA256(timestamp + consumer_id, password))`.
Kirim bersama `X-cons-id` dan `X-timestamp` dalam detik Unix. Respons sukses
menggunakan `Status: SUCCESS (000)` atau `Status: SUCCESS`; `Data` dapat berupa string JSON yang perlu
dibaca lagi. HTTP 200 saja tidak cukup untuk menyatakan berhasil.

Variabel lingkungan server (bukan di JavaScript atau master):

- `MEDINFRAS_BASE_URL`: nilai variabel URL Postman ditambah `/api` sesuai raw URL request.
- `MEDINFRAS_CONSUMER_ID`: consumer ID yang diberikan tim Medinfras.
- `MEDINFRAS_PASSWORD`: kunci untuk membuat signature, jangan commit ke repository.
- `MEDINFRAS_USER_ID`: akun integrasi yang ditetapkan RS, bukan UserID contoh.
- `MEDINFRAS_HEALTHCARE_ID`: default 001 sesuai koleksi.
- `MEDINFRAS_ALLOW_HTTP=1`: hanya jika endpoint jaringan internal memang memakai HTTP.

Ekstensi PHP cURL diperlukan. TLS diverifikasi, redirect tidak diikuti, waktu
koneksi dibatasi dan isi respons pasien tidak dimasukkan ke pesan error.
Tes offline: `php tests/medinfras.php`.

| Kebutuhan | Endpoint pada koleksi |
| --- | --- |
| Poli rawat jalan | GET `healthcareserviceunit/base/list/department/outpatient` |
| Dokter | GET `paramedic/base/list/search2?specialtyName=...` |
| Semua jadwal | GET `paramedicschedule/base/all_schedule` |
| Pasien lama | GET `patient/mrn/{MedicalNo}` |
| Buat appointment | POST `v2/centerback/ADT_A05` |

Payload appointment memakai `HealthcareID=001`, `DepartmentID=OUTPATIENT`,
`IsNewPatient=1/0`, `ServiceUnitCode`, `ParamedicCode`, `VisitTypeCode`,
`OperationalTimeCode`, `QueueNo`, `Session`, `StartDate` format yyyyMMdd,
`StartTime`, `IsPersonalPayer=1`, `AppointmentMethod=002`, dan `UserID` yang
harus ditetapkan untuk akun integrasi. Nilai contoh bukan konfigurasi produksi.
Pasien lama mengirim `MedicalNo`. Pasien baru juga mengirim identitas pasien.
`Gender` memakai M/F; kode agama/kewarganegaraan berasal dari master API.
Nomor identitas: 001 KTP, 002 SIM, 003 KITAS, 004 PASSPORT.

### Hal yang masih perlu konfirmasi sebelum aktivasi

1. Konfirmasi pengguna: `QueueNo` dikirim sebagai string kosong (`""`).
   Medinfras membuat nomor antrean otomatis saat menerima pendaftaran. Jangan
   menghitung atau mengalokasikan nomor antrean di website. Saat uji sandbox,
   pastikan nomor hasil akhir dibaca dari respons Medinfras.
2. Contoh respons pasien lama (belum ada) serta cara memverifikasi identitas
   sebelum mengungkap data. Tanggal lahir saja perlu ditinjau bersama tim RS.
3. Contoh respons jadwal aktual dan arti setiap posisi parameter bertanda titik
   koma pada `ParamedicSchedule/base/list/param/...`, termasuk kuota dan sesi.
4. Pemetaan kode poli/dokter website ke kode Medinfras. ID lokal tidak dapat
   dipakai langsung sebagai kode Medinfras.
5. Catatan payload menandai `LastName` dan `Address` wajib, sedangkan rancangan
   formulir sebelumnya opsional. Pastikan aturan untuk pasien bernama tunggal
   tanpa mengarang nama belakang atau identitas pasien.
6. Lingkungan uji dan akun integrasi; ketentuan duplikasi, timeout, pencarian
   hasil transaksi, dan idempotensi sebelum mencoba POST appointment.

Setelah lengkap, sambungkan GET melalui controller server yang membatasi akses,
validasi ulang kunjungan dan kuota, lalu tambahkan transaksi appointment. Tombol
kirim tetap nonaktif sampai pengujian sandbox dari awal sampai akhir berhasil.

## Implementasi lanjutan dari koleksi

`AppointmentPayload.php` memetakan identitas baru/lama dan pilihan sesi yang
sudah diverifikasi server ke payload `ADT_A05`. `Client::createAppointment()`
mengirim JSON satu kali, memaksa `QueueNo` kosong, dan memeriksa nomor appointment
pada respons. Keduanya telah diuji memakai transport tiruan, tidak dipanggil
oleh halaman publik selama koneksi dev belum terverifikasi.

Koleksi memang memuat endpoint pasien, poli, dan jadwal. Pada file yang diterima,
contoh body respons GET pasien dan semua jadwal belum terisi; beberapa contoh
respons lainnya memiliki body string kosong. Ini tidak menghalangi pembuatan
klien request, tetapi pemetaan hasil GET harus diverifikasi pada server yang
bisa diakses. Permintaan menggunakan raw URL, bukan hanya properti `path`,
karena raw URL menyertakan prefix `api` setelah variabel URL.

URL dan kredensial yang tersedia telah digunakan langsung dari file Postman
untuk GET diagnostik tanpa ditulis ke source atau log. Tidak ada impor rahasia
ke konfigurasi aplikasi. Status HTTP 404 perlu diselesaikan pada alamat deploy
API sebelum integrasi publik diaktifkan. Beberapa raw URL uji juga merujuk
variabel lain yang tidak didefinisikan pada tingkat koleksi.

## Base URL workshop dari pengguna

Alamat yang diberikan: `http://192.168.10.5//medinfrasapi2.1/workshop/`.
Default konfigurasi kini memakai alamat ini ditambah `api`, mengikuti raw URL
request pada koleksi. `MEDINFRAS_BASE_URL` tetap dapat menggantinya. HTTP secara
default hanya diizinkan untuk host internal `192.168.10.5`; kredensial tetap
harus berasal dari variabel lingkungan server.

GET uji autentikasi, poli rawat jalan, dan semua jadwal ke alamat tersebut
mengembalikan HTTP 404. GET uji autentikasi dengan satu slash, dua slash,
dengan/tanpa prefix api juga 404. Pemeriksaan langsung tanpa proxy menerima
404 dari Microsoft-IIS/10.0. Server dapat dijangkau, tetapi route API yang
diuji belum ditemukan. Tidak ada POST appointment yang dikirim.

## Pembaruan alamat HTTPS workshop

Pengguna kemudian memberikan `https://36.94.176.235//medinfrasapi2.1/workshop/`.
Default terbaru memakai HTTPS ini dengan satu slash dan prefix `/api` sesuai
koleksi. Variabel lingkungan `MEDINFRAS_BASE_URL` tetap memiliki prioritas.
Verifikasi TLS tidak dinonaktifkan.

GET autentikasi, poli rawat jalan dan semua jadwal pada prefix `/api` menerima
HTTP 500 dari IIS. Uji autentikasi dengan satu maupun dua slash juga menerima
500 JSON: `{"Message":"An error has occurred."}`. Tanpa prefix `/api`, respons
404. Ini menunjukkan koneksi HTTPS mencapai server, tetapi permintaan API masih
gagal di sisi aplikasi. Respons generik tidak menjelaskan apakah penyebabnya
konfigurasi, autentikasi, koneksi database, atau hal lain; perlu log server IIS
atau aplikasi Medinfras untuk memastikan. Tidak ada POST appointment dikirim.

## Uji alamat internal bpjs/rsbl

Alamat `http://192.168.10.5//medinfrasapi2.1/bpjs/rsbl` diuji dengan
GET autentikasi, poli, dan jadwal menggunakan prefix `/api`. Ketiganya
mengembalikan HTTP 404 dari Microsoft-IIS/10.0. Variasi satu slash dan
GET autentikasi tanpa prefix `/api` juga 404. Pengujian memakai koneksi
langsung tanpa proxy dan tidak mengirim POST pendaftaran. URL ini belum
menggantikan default konfigurasi karena route koleksi belum ditemukan.
