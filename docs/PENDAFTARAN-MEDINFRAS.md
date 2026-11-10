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

## Tahap GET poli pada URL bpjs/rsbl

Base URL terbaru: `https://36.94.176.235/medinfrasapi2.1/bpjs/rsbl/`.
Konfigurasi klien menambahkan `/api` sesuai raw URL koleksi. GET
`healthcareserviceunit/base/list/department/outpatient` berhasil HTTP 200,
status SUCCESS, 62 poli rawat jalan. Pengujian melalui klien PHP juga berhasil.
Tidak ada GET pasien/dokter/jadwal atau POST pendaftaran yang dijalankan.

Pilihan Poli tujuan memuat data ketika pengguna membuka langkah Kunjungan.
Browser mengakses `index.php?page=register&registration_action=clinics` di
server website sendiri. Endpoint hanya meneruskan `code` dan `name`, menyimpan
cache sesi lima menit dan memberikan error umum bila koneksi gagal. Tidak
menggunakan fallback poli lokal agar hasil API tidak tercampur dengan master.
Dokter, tanggal, jadwal dan tombol lanjut masih tidak aktif pada tahap GET poli.

Rahasia dari koleksi Postman tersedia secara lokal dalam
`storage/private/medinfras.php`, bukan source code atau JavaScript. Folder ini
dikecualikan Git dan sudah terproteksi aturan storage pada Apache/IIS.
Environment MEDINFRAS_CONSUMER_ID dan MEDINFRAS_PASSWORD tetap diprioritaskan.
Salinan tes tidak membawa folder private. Jangan commit file tersebut.

Untuk menjalankan di server IIS, salin file kode yang berubah dan atur
kredensial melalui environment server atau salin konfigurasi privat tersebut
melalui jalur privat ke lokasi yang sama. Jangan menimpa database server.
Pastikan ekstensi cURL/openssl aktif dan sertifikat tetap diverifikasi.
Tes offline khusus poli: `php tests/medinfras-poli.php`.

## Tahap GET poli, dokter, dan jadwal praktik

Dropdown kini memakai 24 kelompok publik dari 62 unit rawat jalan. Pemetaan
ada di `app/config/registration-clinics.php`, menggunakan kode unit eksplisit.
Poli Gigi, Gigi Anak, Konservasi Gigi dan Periodonsia tetap berbeda. Unit baru
akan tampil terpisah dengan nama API sampai ditinjau dan dimasukkan pemetaan.
Tidak menebak kelompok berdasarkan penghapusan angka atau kata pagi/sore.

Endpoint website (semua GET, tidak ada API pasien atau transaksi):
- `index.php?page=register&registration_action=clinics`
- `index.php?page=register&registration_action=doctors&clinic=anak`
- `index.php?page=register&registration_action=sessions&clinic=anak&doctor=KODE&date=YYYY-MM-DD`

Calon dokter berasal dari GET `paramedic/base/list/search2`, dibatasi tipe
Dokter, IsAvailable aktif dan DepartmentID OUTPATIENT, lalu disaring berdasarkan
spesialisasi eksplisit per kelompok. Ini bukan bukti relasi tiap unit praktik.
Relasi akhir diperiksa saat menawarkan sesi menggunakan ParamedicCode dan
ServiceUnitCode pada jadwal. Endpoint relasi unit-per-dokter pada koleksi
mengembalikan HTTP gagal saat dicoba, sehingga tidak dipakai atau ditebak.

Hasil GET `paramedicschedule/base/all_schedule` kosong, tetapi GET per dokter
`ParamedicSchedule/base/list/param/;KODE;;;;` berisi jadwal rutin. Struktur nyata
memakai ScheduleRoutine, DayNumber, StartTime1..5, EndTime1..5,
OperationalTimeCode, ServiceUnitCode dan ListVisitType. DayNumber dipetakan
1=Senin sampai 6=Sabtu, 0/7=Minggu. Kode VT01 BEROBAT mengikuti koleksi dan
harus tercantum pada ListVisitType; tidak mengambil jenis kunjungan lain.
Rentang jam dipisah agar jeda antarpraktik tidak dianggap jam pelayanan.
Kuota 0 tidak ditafsirkan sebagai kuota tersedia: layar menampilkan jadwal
praktik saja dan belum menjanjikan antrean atau kuota.

GET tanggal khusus memakai `ParamedicSchedule/list/yyyyMMdd/yyyyMMdd`.
Respons FAILED/Data=null dengan pesan spesifik "There is no paramedic
information with ScheduleDate ..." dibaca kosong hanya pada endpoint ini.
Kegagalan lain tetap menjadi error. Jadwal tanggal khusus yang sesuai dokter
serta unit mendahului rutin. Format cuti yang belum terverifikasi menyebabkan
sesi ditahan, bukan diabaikan.

Sesi mempunyai token pilihan; kode unit/dokter/waktu/jenis kunjungan, tanggal,
jam dan indeks rentang tetap berada dalam sesi PHP server. Indeks rentang
belum dipetakan ke field Session appointment: verifikasi saat tahap transaksi.
Browser hanya menerima nama dan token sesi. Pengiriman appointment tetap
dinonaktifkan. Cache poli/dokter 5 menit per sesi; jadwal 1 menit.

Pengujian live melalui endpoint website berhasil untuk Poli Anak pada
2026-11-16: satu calon dokter rawat jalan, dengan sesi 09:00-13:00 dan
15:00-17:00 WITA. Unit/dokter tidak sah ditolak HTTP 400. Tidak ada pencarian
pasien atau POST appointment pada uji live.

Tes offline: `php tests/medinfras-visit.php`, `php tests/medinfras-poli.php` dan
`php tests/medinfras.php` (POST pada tes terakhir memakai transport tiruan).
Salin file integrasi, controller, konfigurasi pemetaan dan JavaScript terbaru
ke IIS untuk menggunakan perubahan ini online. Jangan menimpa database atau
mengunggah file rahasia ke GitHub. File konfigurasi privat tetap diperlukan
pada server bila kredensial tidak diatur lewat environment.


## Filter unit pendaftaran umum

Pilihan poli pendaftaran hanya menggunakan hasil API, tanpa master poli website.
Unit disaring dari nama API: SORE (termasuk SORE1 dan seterusnya), serta MALAM
khusus nama Obgyn/Obstetri/Kandungan. Unit pagi, tanpa penanda sesi, dan malam
non-Obgyn tidak dimasukkan. Filter ini mengikuti aturan operasional RS yang
diminta; bukan penentuan status penjamin dari API.
Nama publik tidak menampilkan sore/malam, tetapi kode unit asli tetap digunakan
untuk mencocokkan jadwal. Pemetaan hanya merapikan label dan spesialisasi;
tidak membuat poli yang tidak ada pada hasil API. Jumlah 24 kelompok dan contoh
jadwal pagi di bagian sebelumnya merupakan hasil uji sebelum filter ini.


## Kalender tanggal praktik

GET registration_action=dates&clinic=...&doctor=... membaca jadwal rutin dokter
serta jadwal tanggal khusus pada rentang hari ini hingga 30 hari ke depan.
Tanggal diaktifkan hanya jika aturan sesi yang sama menemukan jadwal cocok.
Kalender khusus menggantikan tampilan input date bawaan karena input bawaan
tidak mendukung menonaktifkan tanggal tertentu. Tanggal tanpa jadwal berwarna
abu-abu dan disabled. Setelah tanggal dipilih, endpoint sessions memeriksa
jadwal kembali. Ini menunjukkan jadwal praktik, bukan jaminan kuota antrean.


## Koreksi relasi dokter per unit

Penyaringan berdasarkan spesialisasi sebelumnya diganti dengan kecocokan
ParamedicCode dan ServiceUnitCode di ScheduleRoutine/ScheduleByDate dari API.
Seluruh dokter aktif diperiksa, sehingga unit baru tidak memerlukan pemetaan
spesialisasi untuk menemukan dokter. Respons jadwal per dokter disimpan dalam
cache sesi 60 detik. Panggilan pertama memerlukan beberapa GET jadwal.
Pemeriksaan live terhadap 48 dokter aktif menghasilkan 94 baris jadwal rutin;
tidak ditemukan kode unit SORE atau OBGYN MALAM. Jangan mengganti kode unit
pagi ke sore, maupun menafsirkan jam sore sebagai relasi unit sore. Vendor
perlu memastikan relasi/jadwal unit yang diperlukan tersedia pada API.


## Verifikasi pasien lama

Form pasien lama menerima MRN dan tanggal lahir, lalu POST ke website melalui
registration_action=patient_lookup. Data identitas tidak dimasukkan ke query URL.
Handler app/actions/registration/patient-lookup.php memeriksa CSRF, format isian,
dan batas 5 pencarian per IP dalam 15 menit. Header CF-Connecting-IP hanya
dipercaya untuk koneksi loopback dari tunnel lokal. Koneksi Medinfras tetap GET
patient/mrn/{MRN}; tidak ada perubahan data atau pengiriman appointment.

PatientLookup memeriksa MedicalNo dan DateOfBirth secara tepat sebelum meneruskan
field nama, tanggal lahir, jenis kelamin, dan kontak/alamat yang diperlukan.
Format respons objek atau daftar satu pasien didukung. Pemetaan field mengikuti
kontrak payload Medinfras yang tersedia; struktur respons live belum terverifikasi.
Diagnosis, riwayat medis, dan respons lengkap tidak diteruskan. Hasil mismatch
tidak membuka identitas. Respons no-store; status verifikasi tersimpan di sesi
15 menit. Mengubah MRN/tanggal lahir atau jenis pasien membatalkan verifikasi UI.

Tes offline: php tests/medinfras-patient.php. Tes HTTP dengan API tiruan berhasil
untuk penolakan GET/CSRF kosong, tanggal lahir salah, dan verifikasi cocok.
Uji langsung MRN dummy yang diberikan pengguna mengembalikan HTTP 500 dari API
Medinfras sebelum respons pasien tersedia. Nomor uji dan tanggal lahir tidak
disimpan dalam source. Vendor perlu memperbaiki endpoint pasien atau memastikan
MRN dummy tersedia sebelum uji kecocokan live bisa diselesaikan.


## Integrasi dihentikan sementara

Sesuai permintaan, enabled=false pada app/config/medinfras.php menonaktifkan
seluruh koneksi Medinfras. Client menolak koneksi sebelum transport dipanggil.
Controller menolak seluruh registration_action dan membersihkan cache katalog,
verifikasi pasien dan pilihan sesi lama. Form ditandai nonaktif, tidak melakukan
fetch dan tidak menerima isian. Source integrasi tetap disimpan untuk pekerjaan
lanjutan; fitur belum diaktifkan kembali hanya dengan mengisi kredensial.


## Tahap terbaru: hanya GET seluruh poli

clinics_enabled=true mengizinkan jalur clinics khusus, sementara enabled=false
memblokir dokter, tanggal, jadwal, pasien dan transaksi. Dropdown mengambil
semua unit rawat jalan dari API tanpa filter pagi/sore/malam, penggabungan
nama, atau master website. Nama dan kode asli dipertahankan. Uji live endpoint
website berhasil mengembalikan 62 unit; endpoint lainnya tetap HTTP 503.


## GET penjamin per jenis pembayaran

payers_enabled=true mengizinkan GET registration_action=payers&category=...
melalui endpoint businesspartners/base/payer/list. Kategori resmi X004^100
(Rekanan) dan X004^250 (Karyawan) dipakai untuk menyaring pilihan. BPJS
X004^500 tidak diteruskan. IsActive wajib aktif, blacklist dikeluarkan, dan
kontrak/metadata internal tidak dikirim ke browser. Cache sesi lima menit.
Uji live: 122 Rekanan dan 2 Karyawan. Pemerintah, INHEALT, Lain-lain belum
memiliki kode kategori terverifikasi sehingga tidak diberi data tebakan.
Pribadi tidak memanggil master payer. Pemilihan kategori menampilkan dropdown
perusahaan/penjamin yang sesuai. Tidak ada field klaim tambahan yang ditebak.
Pengiriman appointment serta API dokter/jadwal/pasien tetap nonaktif.
Tes offline: php tests/medinfras-payers.php.


## Kondisi akhir formulir saat commit

Urutan: Kategori, Kunjungan, Data diri (termasuk jenis pasien dan MRN opsional
khusus pasien lama), lalu Pembayaran. Tidak ada verifikasi tanggal lahir atau
lookup pasien pada formulir saat ini. Dokter, tanggal ketersediaan, sesi praktik
dan transaksi tetap nonaktif. Hanya GET seluruh poli serta master payer aktif.
Jenis pembayaran mengikuti CustomerTypeDesc dari master payer aktif dan tidak
blacklist, ditambah Pribadi; BPJS dikecualikan. Penjamin perusahaan dipilih dari
kategori tersebut. Kategori tidak lagi dibatasi daftar manual. Source integrasi
versi sebelumnya tetap tersimpan untuk pekerjaan lanjutan, bukan fitur aktif.
