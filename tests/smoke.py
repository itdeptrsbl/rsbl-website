"""Tes integrasi pada salinan sementara. Tidak mengubah data proyek asli.
Jalankan: python tests/smoke.py --php C:\\xampp\\php\\php.exe
"""

import argparse
import base64
import datetime
import http.cookiejar
import os
import re
import shutil
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.request
import urllib.parse
import urllib.error
from contextlib import closing
from pathlib import Path

parser = argparse.ArgumentParser()
parser.add_argument("--php", default="php")
parser.add_argument("--extension", action="append", default=[])
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory() as tmp:
    target = Path(tmp) / "site"
    shutil.copytree(
        root,
        target,
        ignore=shutil.ignore_patterns("node_modules", ".git", "backups", ".tools", "__pycache__"),
    )
    for f in (target / "storage").glob("*.sqlite*"):
        f.unlink()
    (target / "config.php").write_text(
        "<?php return ['driver'=>'sqlite','timezone'=>'Asia/Makassar','secure_cookie'=>false];"
    )
    with socket.socket() as sock:
        sock.bind(("127.0.0.1", 0))
        port = sock.getsockname()[1]
    cmd = [args.php]
    if args.extension:
        cmd += ["-n"]
        for extension in args.extension:
            cmd += ["-d", "extension=" + extension]
    cmd += ["-d", "session.save_path=" + tmp]
    log = open(Path(tmp) / "server.log", "w+")
    proc = subprocess.Popen(
        cmd + ["-S", f"127.0.0.1:{port}", "router.php"], cwd=target, stdout=log, stderr=log
    )
    base = f"http://127.0.0.1:{port}/"

    # Membuat browser uji dengan cookie sesi terpisah.
    def client():
        return urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
        )

    browser = client()

    # Mengabaikan spasi antar-tag agar tes tidak bergantung pada indentasi template.
    def compact_html(body):
        return re.sub(r">\s+<", "><", body)

    # Mengirim GET/POST ke server uji dan mengembalikan status, isi, serta URL.
    def req(path="", data=None, who=None):
        encoded = urllib.parse.urlencode(data, doseq=True).encode() if data is not None else None
        try:
            with (who or browser).open(base + path, encoded, timeout=10) as response:
                return response.status, compact_html(response.read().decode()), response.url
        except urllib.error.HTTPError as response:
            return response.code, compact_html(response.read().decode()), response.url

    # Mengambil token formulir dari HTML untuk permintaan uji berikutnya.
    def csrf(html):
        return re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)

    # Mencatat hasil pengujian dan menghentikan tes jika syarat tidak terpenuhi.
    def check(condition, label):
        assert condition, label
        print("PASS", label)

    try:
        for _ in range(50):
            try:
                code, html, url = req()
                break
            except urllib.error.URLError:
                time.sleep(0.1)
        else:
            raise RuntimeError("Server PHP tidak berjalan")
        check("Pasang website" in html, "Installer tersedia pada database kosong")
        check(
            req(
                "index.php?page=install",
                {"email": "admin@example.test", "password": "Testing-Password-2026", "csrf": ""},
                client(),
            )[0]
            == 419,
            "CSRF kosong ditolak",
        )
        code, html, url = req(
            "index.php?page=install",
            {
                "email": "admin@example.test",
                "password": "Testing-Password-2026",
                "csrf": csrf(html),
            },
        )
        check("Instalasi selesai" in html, "Instalasi membuat akun dan tabel")
        check(
            "Pasang website" not in req("index.php?page=install")[1],
            "Installer terkunci setelah instalasi",
        )
        check(
            "Khusus" in req("index.php?page=admin")[1]
            or "KHUSUS PENGELOLA" in req("index.php?page=admin")[1],
            "Admin dilindungi login",
        )
        code, html, url = req(
            "index.php?page=login",
            {
                "email": "admin@example.test",
                "password": "Testing-Password-2026",
                "csrf": csrf(html),
            },
        )
        check("Ringkasan" in html and "Mulai mengelola" in html, "Login pengelola berhasil")
        asset_urls = set()
        for page in [
            "home",
            "about",
            "doctors",
            "doctor_schedule",
            "services",
            "outpatient",
            "inpatient",
            "emergency",
            "farmasi",
            "radiologi",
            "fisioterapi",
            "laboratorium",
            "news",
            "contact",
            "register",
        ]:
            code, body, _ = req("index.php?page=" + page)
            check(
                code == 200 and "Fatal error" not in body and "Warning:" not in body,
                "Halaman publik "
                + page
                + " "
                + (body if code != 200 or "Warning:" in body or "Fatal error" in body else ""),
            )
            asset_urls.update(re.findall(r'(?:src|href)="(assets/(?:css|js)/[^"]+)"', body))
        for asset_url in sorted(asset_urls):
            asset_code, asset_body, _ = req(asset_url)
            check(
                asset_code == 200 and "Halaman tidak ditemukan" not in asset_body,
                "Aset publik " + asset_url.split("?")[0],
            )
        for tab in ["settings", "contents", "doctors", "appointments", "account"]:
            code, body, _ = req("index.php?page=admin&tab=" + tab)
            check(code == 200 and "Fatal error" not in body, "Panel admin " + tab)
        services = req("index.php?page=services")[1]
        check(
            "Fasilitas dan Layanan" in services and services.count('class="facility-card"') == 7,
            "Halaman fasilitas berisi tujuh kategori",
        )
        check(
            "page=farmasi" in services and "facilities-slideshow" in services,
            "Dropdown dan galeri fasilitas tersedia",
        )
        _, facility_form, _ = req("index.php?page=admin&tab=contents&kind=service_slide")
        check(
            'name="image_file"' in facility_form and 'name="body"' not in facility_form,
            "Master foto fasilitas tanpa isian konten",
        )
        for order in [2, 1]:
            _, facility_form, _ = req(
                "index.php?page=admin&tab=contents&kind=service_slide",
                {
                    "csrf": csrf(facility_form),
                    "action": "content_save",
                    "title": "",
                    "image": "https://example.test/facility" + str(order) + ".jpg",
                    "sort_order": order,
                    "active": "1",
                    "slide_fit": "contain",
                    "slide_zoom": "110",
                    "slide_x": "30",
                    "slide_y": "40",
                },
            )
            check(
                "Konten disimpan" in facility_form,
                "Foto fasilitas tersimpan tanpa judul " + str(order),
            )
        services = req("index.php?page=services")[1]
        check(
            services.index("facility1.jpg") < services.index("facility2.jpg"),
            "Urutan foto fasilitas mengikuti master",
        )
        check(
            "data-next" in services and "&quot;zoom&quot;:110" in services,
            "Navigasi dan posisi foto fasilitas diterapkan",
        )
        check(
            "facility1.jpg" not in req("index.php?page=home")[1],
            "Foto fasilitas terpisah dari beranda",
        )
        _, empty_photo, _ = req(
            "index.php?page=admin&tab=contents&kind=service_slide",
            {"csrf": csrf(facility_form), "action": "content_save", "title": "", "image": ""},
        )
        check("Unggah foto fasilitas" in empty_photo, "Foto fasilitas kosong ditolak")
        _, outpatient_master, _ = req("index.php?page=admin&tab=contents&kind=outpatient_photo")
        _, outpatient_master, _ = req(
            "index.php?page=admin&tab=contents&kind=outpatient_photo",
            {
                "csrf": csrf(outpatient_master),
                "action": "outpatient_intro",
                "outpatient_intro": "Penjelasan rawat jalan <aman>",
            },
        )
        check(
            "Penjelasan Rawat Jalan disimpan" in outpatient_master,
            "Penjelasan rawat jalan tersimpan",
        )
        _, outpatient_master, _ = req(
            "index.php?page=admin&tab=contents&kind=outpatient_photo",
            {
                "csrf": csrf(outpatient_master),
                "action": "content_save",
                "title": "Foto Ruang Rawat Jalan",
                "image": "https://example.test/outpatient.jpg",
                "active": "1",
                "sort_order": "0",
                "slide_fit": "contain",
                "slide_zoom": "115",
                "slide_x": "35",
                "slide_y": "60",
            },
        )
        check("Konten disimpan" in outpatient_master, "Foto rawat jalan tersimpan terpisah")
        _, clinic_master, _ = req("index.php?page=admin&tab=contents&kind=clinic")
        _, clinic_master, _ = req(
            "index.php?page=admin&tab=contents&kind=clinic",
            {
                "csrf": csrf(clinic_master),
                "action": "content_save",
                "title": "Poli Penyakit Dalam Uji",
                "subtitle": "Poli Penyakit Dalam",
                "image": "https://example.test/clinic.jpg",
                "active": "1",
                "sort_order": "1",
            },
        )
        check("Konten disimpan" in clinic_master, "Master kartu poli tersimpan")
        outpatient = req("index.php?page=outpatient")[1]
        check(
            "outpatient.jpg" in outpatient
            and "clinic.jpg" in outpatient
            and "Penjelasan rawat jalan &lt;aman&gt;" in outpatient,
            "Galeri, penjelasan aman dan kartu poli tampil",
        )
        check(
            "data-slide-image" in outpatient
            and "&quot;zoom&quot;:115" in outpatient
            and "&quot;x&quot;:35" in outpatient
            and "&quot;y&quot;:60" in outpatient,
            "Galeri rawat jalan menerapkan posisi dan ukuran dari master",
        )
        match = re.search(r"page=doctors&amp;clinic=(\d+)", outpatient)
        check(match is not None, "Kartu poli memiliki tautan filter dokter")
        filtered = req("index.php?page=doctors&clinic=" + match.group(1))[1]
        check(
            "Dokter Contoh A" in filtered and "Dokter Contoh B" not in filtered,
            "Poli memfilter dokter sesuai field poliklinik",
        )
        check(
            "Dokter Contoh A" not in req("index.php?page=doctors&clinic=999999")[1],
            "Poli tidak tersedia tidak membuka semua dokter",
        )
        _, clinic_bad, _ = req(
            "index.php?page=admin&tab=contents&kind=clinic",
            {
                "csrf": csrf(clinic_master),
                "action": "content_save",
                "title": "Poli Tanpa Pemetaan",
                "subtitle": "",
            },
        )
        check("poli penghubung" in clinic_bad, "Poli wajib memiliki penghubung ke data dokter")
        emergency = req("index.php?page=emergency")[1]
        check(
            "Kontak IGD" in emergency and "Informasi ambulans" not in emergency,
            "Halaman IGD memakai kontak RS dan menyembunyikan ambulans kosong",
        )
        _, emergency_master, _ = req("index.php?page=admin&tab=contents&kind=emergency_photo")
        check(
            'name="ambulance_phone"' in emergency_master
            and 'name="image_file"' in emergency_master,
            "Master IGD memisahkan informasi kontak dan foto",
        )
        emergency_data = {
            "csrf": csrf(emergency_master),
            "action": "emergency_save",
            "intro": "Pelayanan IGD <aman>",
            "hours": "24 jam",
            "phone": "+6247112345",
            "ambulance_phone": "081234567899",
            "ambulance_note": "Informasi ambulans uji",
            "location": "Pintu masuk IGD uji",
            "facilities": "Ruang tindakan uji\nRuang observasi uji",
            "arrival_note": "Pesan kunjungan uji",
        }
        _, emergency_saved, _ = req(
            "index.php?page=admin&tab=contents&kind=emergency_photo", emergency_data
        )
        check(
            "Informasi dan kontak IGD disimpan" in emergency_saved,
            "Master IGD menyimpan kontak, jam, fasilitas, dan lokasi",
        )
        emergency = req("index.php?page=emergency")[1]
        check(
            "Pelayanan IGD &lt;aman&gt;" in emergency
            and 'href="tel:+6247112345"' in emergency
            and 'href="tel:081234567899"' in emergency,
            "Kontak IGD dan ambulans dapat ditelepon serta teks di-escape",
        )
        check(
            "<li>Ruang tindakan uji</li>" in emergency and "Pintu masuk IGD uji" in emergency,
            "Fasilitas dan petunjuk kedatangan tampil",
        )
        invalid_emergency = dict(
            emergency_data, csrf=csrf(emergency_saved), phone="javascript:alert(1)"
        )
        _, emergency_bad, _ = req(
            "index.php?page=admin&tab=contents&kind=emergency_photo", invalid_emergency
        )
        check("nomor telepon yang valid" in emergency_bad, "Nomor kontak IGD tidak valid ditolak")
        for order in [2, 1]:
            _, emergency_bad, _ = req(
                "index.php?page=admin&tab=contents&kind=emergency_photo",
                {
                    "csrf": csrf(emergency_bad),
                    "action": "content_save",
                    "title": "Foto IGD " + str(order),
                    "image": "https://example.test/igd" + str(order) + ".jpg",
                    "active": "1",
                    "sort_order": order,
                    "slide_fit": "contain",
                    "slide_zoom": "120",
                    "slide_x": "30",
                    "slide_y": "40",
                },
            )
            check("Konten disimpan" in emergency_bad, "Foto IGD tersimpan " + str(order))
        emergency = req("index.php?page=emergency")[1]
        check(
            emergency.index("igd1.jpg") < emergency.index("igd2.jpg")
            and "data-next" in emergency
            and "&quot;zoom&quot;:120" in emergency,
            "Galeri IGD mengikuti urutan dan pengaturan master",
        )
        check(
            "igd1.jpg" not in req("index.php?page=outpatient")[1],
            "Foto IGD terpisah dari Rawat Jalan",
        )
        check(
            "page=register" not in emergency.split('<main id="main">')[1].split("</main>")[0],
            "Halaman IGD mengutamakan kontak langsung tanpa pendaftaran online",
        )
        simple_services = {
            "farmasi": "pharmacy_photo",
            "radiologi": "radiology_photo",
            "fisioterapi": "physiotherapy_photo",
            "laboratorium": "laboratory_photo",
        }
        for service_slug, photo_kind in simple_services.items():
            _, service_master, _ = req("index.php?page=admin&tab=contents&kind=" + photo_kind)
            check(
                'name="facility_intro"' in service_master and 'name="image_file"' in service_master,
                "Master penjelasan dan foto " + service_slug,
            )
            _, service_saved, _ = req(
                "index.php?page=admin&tab=contents&kind=" + photo_kind,
                {
                    "csrf": csrf(service_master),
                    "action": "facility_intro",
                    "service_page": service_slug,
                    "facility_intro": "Penjelasan " + service_slug + " <aman>",
                },
            )
            check(
                "Penjelasan layanan disimpan" in service_saved,
                "Penjelasan tersimpan " + service_slug,
            )
            for photo_order in [2, 1]:
                _, service_saved, _ = req(
                    "index.php?page=admin&tab=contents&kind=" + photo_kind,
                    {
                        "csrf": csrf(service_saved),
                        "action": "content_save",
                        "title": "Foto " + service_slug + " " + str(photo_order),
                        "image": "https://example.test/" + service_slug + str(photo_order) + ".jpg",
                        "active": "1",
                        "sort_order": photo_order,
                        "slide_fit": "contain",
                        "slide_zoom": "110",
                        "slide_x": "35",
                        "slide_y": "45",
                    },
                )
                check(
                    "Konten disimpan" in service_saved,
                    "Foto layanan tersimpan " + service_slug + str(photo_order),
                )
            detail = req("index.php?page=" + service_slug)[1]
            check(
                "Penjelasan " + service_slug + " &lt;aman&gt;" in detail
                and "data-room-next" in detail,
                "Penjelasan aman dan galeri " + service_slug,
            )
            check(
                detail.index(service_slug + "1.jpg") < detail.index(service_slug + "2.jpg")
                and "&quot;zoom&quot;:110" in detail,
                "Urutan dan posisi foto " + service_slug,
            )
            check(
                'href="index.php?page=' + service_slug + '" aria-current="page"' in detail,
                "Menu layanan aktif " + service_slug,
            )
        for service_slug in simple_services:
            detail = req("index.php?page=" + service_slug)[1]
            for other_slug in simple_services:
                if service_slug != other_slug:
                    check(
                        other_slug + "1.jpg" not in detail,
                        "Galeri " + service_slug + " terpisah dari " + other_slug,
                    )
        _, invalid_service, _ = req(
            "index.php?page=admin&tab=contents&kind=pharmacy_photo",
            {
                "csrf": csrf(service_saved),
                "action": "facility_intro",
                "service_page": "unknown",
                "facility_intro": "tidak valid",
            },
        )
        check(
            "Layanan tidak valid" in invalid_service,
            "Penyimpanan pengantar dibatasi pada layanan yang dikenal",
        )
        home = req("index.php?page=home")[1]
        check(
            "assets/css/layout/header.css" in home and "assets/js/components/slideshow.js" in home,
            "Aset header dan slideshow dimuat",
        )
        check(
            "company-brand" in home and "accreditation-mark" in home and "social-links" in home,
            "Identitas perusahaan dan media sosial tampil",
        )
        topbar = home.split('<div class="topbar topbar-v2">')[1].split("<header")[0]
        check(
            "Servire" not in topbar and "wa.me/" in topbar and "tel:" in topbar,
            "Topbar memuat telepon dan WhatsApp tanpa slogan",
        )
        _, slide_form, _ = req("index.php?page=admin&tab=contents&kind=slide")
        check('maxlength="50000" >' in slide_form, "Deskripsi slide opsional")
        for order in [1, 2]:
            _, slide_form, _ = req(
                "index.php?page=admin&tab=contents&kind=slide",
                {
                    "csrf": csrf(slide_form),
                    "action": "content_save",
                    "title": "Slide Test " + str(order),
                    "body": "",
                    "image": "https://example.test/slide.jpg",
                    "link": "",
                    "active": "1",
                    "sort_order": order,
                    "slide_fit": "contain",
                    "slide_zoom": "120",
                    "slide_x": "40",
                    "slide_y": "30",
                },
            )
            check("Konten disimpan" in slide_form, "Master slide tersimpan " + str(order))
        home = req("index.php?page=home")[1]
        check(
            home.index('alt="Slide Test 1"') < home.index('alt="Slide Test 2"')
            and "data-next" in home
            and "data-pause" not in home,
            "Slide mengikuti urutan master dan memiliki kontrol",
        )
        check(
            "&quot;zoom&quot;:120" in home and "&quot;fit&quot;:&quot;contain&quot;" in home,
            "Ukuran dan posisi slide tersimpan di beranda",
        )
        code, html, _ = req("index.php?page=admin&tab=contents&kind=news")
        code, html, _ = req(
            "index.php?page=admin&tab=contents&kind=news",
            {
                "csrf": csrf(html),
                "action": "content_save",
                "title": "Berita Uji <script>alert(1)</script>",
                "subtitle": "Label uji",
                "body": "Isi berita integrasi",
                "image": "",
                "link": "",
                "starts": "",
                "ends": "",
                "sort_order": 0,
                "active": 1,
            },
        )
        check("Konten disimpan" in html, "Master berita tersimpan")
        public = req("index.php?page=news")[1]
        check(
            "Berita Uji &lt;script&gt;" in public and "<script>alert(1)</script>" not in public,
            "Konten publik di-escape",
        )
        code, body, _ = req(
            "index.php?page=admin&tab=contents&kind=news",
            {
                "csrf": csrf(html),
                "action": "content_save",
                "title": "Tautan tidak aman",
                "body": "contoh",
                "image": "",
                "link": "javascript:alert(1)",
            },
        )
        check("Tautan harus HTTPS" in body, "Tautan javascript ditolak")
        token = csrf(body)
        # Uji unggahan gambar PNG kecil melalui multipart/form-data.
        existing_pngs = set((target / "assets/uploads").glob("*.png"))
        boundary = "RSBLBoundaryTest"
        parts = []
        for key, value in {
            "csrf": token,
            "action": "content_save",
            "title": "Gambar Uji",
            "subtitle": "",
            "body": "Tes upload",
            "image": "",
            "link": "",
            "active": "1",
            "sort_order": "0",
        }.items():
            parts.append(
                f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
            )
        image = base64.b64decode(
            "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j6XkAAAAASUVORK5CYII="
        )
        parts.append(
            f'--{boundary}\r\nContent-Disposition: form-data; name="image_file"; filename="test.png"\r\nContent-Type: image/png\r\n\r\n'.encode()
            + image
            + b"\r\n"
        )
        parts.append(f"--{boundary}--\r\n".encode())
        upload = urllib.request.Request(
            base + "index.php?page=admin&tab=contents&kind=news",
            data=b"".join(parts),
            headers={"Content-Type": "multipart/form-data; boundary=" + boundary},
        )
        with browser.open(upload) as response:
            uploaded = response.read().decode()
        check(
            "Konten disimpan" in uploaded
            and len(set((target / "assets/uploads").glob("*.png")) - existing_pngs) == 1,
            "Unggahan gambar tersimpan",
        )
        vision_path = "index.php?page=admin&tab=contents&kind=vision"
        vision_form = req(vision_path)[1]
        check(
            "+ Tambah poin Misi" in vision_form and "+ Tambah poin Nilai-Nilai" in vision_form,
            "Master visi memiliki daftar Misi dan Nilai-Nilai",
        )
        vision_data = {
            "csrf": csrf(vision_form),
            "action": "vision_save",
            "vision": "Visi pengujian <script>uji</script>",
            "missions[]": ["Misi pertama", "Misi kedua", ""],
            "promise": "Janji pengujian",
            "motto": "Motto pengujian",
            "values[]": ["Integritas", "Kasih Sayang"],
        }
        vision_saved = req(vision_path, vision_data)[1]
        check("Nilai-Nilai disimpan" in vision_saved, "Master visi menyimpan kelima bagian")
        vision_html = req("index.php?page=vision")[1]
        check(
            all(
                text in vision_html
                for text in [
                    "Misi pertama",
                    "Misi kedua",
                    "Janji pengujian",
                    "Motto pengujian",
                    "Integritas",
                    "Kasih Sayang",
                    "&lt;script&gt;uji&lt;/script&gt;",
                ]
            )
            and "<script>uji</script>" not in vision_html,
            "Halaman visi menampilkan isi dengan aman",
        )
        vision_data.update(csrf=csrf(vision_saved), promise="", motto="")
        vision_data["missions[]"] = ["Misi kedua"]
        vision_data["values[]"] = []
        vision_saved = req(vision_path, vision_data)[1]
        vision_html = req("index.php?page=vision")[1]
        check(
            "Misi pertama" not in vision_html
            and "Misi kedua" in vision_html
            and 'id="values-title"' not in vision_html
            and "<h2>JANJI</h2>" not in vision_html,
            "Poin dapat dihapus dan bagian kosong disembunyikan",
        )
        history_path = "index.php?page=admin&tab=contents&kind=history"
        history_form = req(history_path)[1]
        check(
            "Simpan sejarah" in history_form
            and "Teks di bawah video (opsional)" in history_form
            and "+ Tambah pemimpin" in history_form
            and 'name="history_type" data-history-type' not in history_form,
            "Master sejarah memiliki video, teks opsional, dan tambah pemimpin",
        )

        # Membuat permintaan multipart untuk menguji unggahan video atau foto sejarah.
        def history_upload(fields, video_bytes=None, leader_image=None, destination=None):
            chunks = []
            for key, value in fields.items():
                chunks.append(
                    f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
                )
            if video_bytes is not None:
                chunks.append(
                    f'--{boundary}\r\nContent-Disposition: form-data; name="video_file"; filename="history.mp4"\r\nContent-Type: video/mp4\r\n\r\n'.encode()
                    + video_bytes
                    + b"\r\n"
                )
            if leader_image is not None:
                chunks.append(
                    f'--{boundary}\r\nContent-Disposition: form-data; name="image_file"; filename="leader.png"\r\nContent-Type: image/png\r\n\r\n'.encode()
                    + leader_image
                    + b"\r\n"
                )
            chunks.append(f"--{boundary}--\r\n".encode())
            request = urllib.request.Request(
                base + (destination or history_path),
                data=b"".join(chunks),
                headers={"Content-Type": "multipart/form-data; boundary=" + boundary},
            )
            with browser.open(request) as response:
                return response.read().decode()

        history_data = {
            "csrf": csrf(history_form),
            "action": "content_save",
            "history_type": "video",
            "title": "Video sejarah pengujian",
            "body": "Catatan setelah video <script>uji</script>",
            "active": "1",
            "sort_order": "1",
        }
        # Kontainer MP4 minimal untuk memeriksa validasi MIME unggahan.
        mp4 = b"\x00\x00\x00\x18ftypisom\x00\x00\x02\x00isomiso2"
        history_saved = history_upload(history_data, mp4)
        check("Konten disimpan" in history_saved, "Video sejarah tersimpan")
        history_html = req("index.php?page=history")[1]
        check(
            re.search(r"assets/uploads/[a-f0-9]{32}\.mp4", history_html)
            and history_html.index("</video>") < history_html.index("Catatan setelah video")
            and "&lt;script&gt;uji&lt;/script&gt;" in history_html,
            "Video unggahan tampil dengan teks aman di bawahnya",
        )
        video_path = re.search(r"assets/uploads/[a-f0-9]{32}\.mp4", history_html).group(0)
        with browser.open(base + video_path) as response:
            check(
                response.status == 200 and response.read() == mp4,
                "File video unggahan dapat diakses publik",
            )
        invalid_video = dict(history_data, csrf=csrf(history_saved))
        check(
            "video MP4 atau WebM yang valid" in history_upload(invalid_video, b"not a video"),
            "File video palsu ditolak",
        )
        history_data.update(csrf=csrf(history_saved), title="Video tanpa teks", body="")
        check(
            "Konten disimpan" in history_upload(history_data, mp4),
            "Video unggahan dapat disimpan tanpa teks tambahan",
        )
        quality_path = "index.php?page=admin&tab=contents&kind=quality"
        quality_form = req(quality_path)[1]
        check(
            "Judul laporan" in quality_form
            and 'name="image_file"' in quality_form
            and 'name="body"' not in quality_form
            and 'name="sort_order"' not in quality_form
            and 'name="image"' not in quality_form,
            "Master laporan mutu hanya berisi judul dan unggahan gambar",
        )
        quality_data = {
            "csrf": csrf(quality_form),
            "action": "content_save",
            "title": "Laporan Mutu <script>uji</script>",
            "active": "1",
        }
        quality_saved = history_upload(quality_data, leader_image=image, destination=quality_path)
        check("Konten disimpan" in quality_saved, "Gambar laporan mutu tersimpan")
        quality_html = req("index.php?page=quality")[1]
        check(
            "data-quality-report" in quality_html
            and "Laporan Mutu &lt;script&gt;uji&lt;/script&gt;" in quality_html
            and "data-quality-zoom" in quality_html
            and "data-quality-download" in quality_html,
            "Galeri laporan mutu memiliki judul aman, perbesar, dan unduh",
        )
        quality_id = re.search(r"kind=quality&amp;edit=(\d+)", quality_saved).group(1)
        quality_data.update(
            id=quality_id, title="Laporan Mutu Diperbarui", csrf=csrf(quality_saved)
        )
        quality_edited = req(quality_path, quality_data)[1]
        check(
            "Konten disimpan" in quality_edited
            and "Laporan Mutu Diperbarui" in req("index.php?page=quality")[1],
            "Edit laporan mutu mempertahankan gambar sebelumnya",
        )
        req(
            quality_path,
            {"csrf": csrf(quality_edited), "action": "content_delete", "id": quality_id},
        )
        check(
            "Laporan Mutu Diperbarui" not in req("index.php?page=quality")[1],
            "Hapus laporan mutu menghapus kartu publik",
        )
        for leader_index in range(3):
            leader_data = {
                "csrf": csrf(history_saved),
                "action": "content_save",
                "history_type": "leader",
                "title": "Pemimpin Uji " + str(leader_index),
                "subtitle": "Direktur",
                "starts": str(2000 + leader_index) + "-01-01",
                "ends": str(2001 + leader_index) + "-12-31",
                "active": "1",
            }
            check(
                "Konten disimpan" in history_upload(leader_data, leader_image=image),
                "Foto dan tanggal pemimpin tersimpan " + str(leader_index),
            )
        retry_data = {
            "csrf": csrf(history_saved),
            "action": "content_save",
            "history_type": "leader",
            "leader_upload_token": "a" * 32,
            "title": "Pemimpin Retry",
            "subtitle": "Direktur",
            "starts": "2005",
            "ends": "2000",
            "active": "1",
        }
        failed_leader = history_upload(retry_data, leader_image=image)
        check(
            "Akhir masa jabatan tidak boleh" in failed_leader
            and "Foto tetap tersimpan" in failed_leader
            and re.search(
                r'data-leader-preview src="assets/uploads/[a-f0-9]{32}\.png"', failed_leader
            ),
            "Foto baru tetap tampil setelah validasi masa jabatan gagal",
        )
        retry_data.update(ends="2010", csrf=csrf(failed_leader))
        retry_data["starts"] = "16 April 1995"
        check(
            "Konten disimpan" in history_upload(retry_data)
            and "Pemimpin Retry" in req("index.php?page=history")[1],
            "Pemimpin dapat disimpan ulang tanpa unggah ulang foto",
        )
        check(
            "16 April 1995 &ndash; 2010" in req("index.php?page=history")[1],
            "Tanggal dengan nama bulan dan tahun saja dapat dipakai bersamaan",
        )
        retry_edit_page = req(history_path)[1]
        retry_id = re.search(
            r'Pemimpin Retry</span></summary>.*?name="id" value="(\d+)"', retry_edit_page, re.S
        ).group(1)
        retry_data.update(
            id=retry_id, ends="1990", csrf=csrf(retry_edit_page), leader_upload_token="b" * 32
        )
        failed_edit = history_upload(retry_data)
        check(
            "Foto tetap tersimpan" in failed_edit
            and "Pemimpin Retry" in req("index.php?page=history")[1],
            "Foto lama tetap tersedia ketika edit pemimpin gagal",
        )
        leaders_html = req("index.php?page=history")[1]
        check(
            leaders_html.count('id="history-leaders-heading"') == 1
            and all("Pemimpin Uji " + str(n) in leaders_html for n in range(3))
            and leaders_html.index("</video>") < leaders_html.index('id="history-leaders-heading"')
            and "1 Januari 2000" in leaders_html,
            "Semua pemimpin dikelompokkan setelah video dengan periode jabatan",
        )
        _, doctor_form, _ = req("index.php?page=admin&tab=doctors&edit=1")
        _, doctor_saved, _ = req(
            "index.php?page=admin&tab=doctors&edit=1",
            {
                "csrf": csrf(doctor_form),
                "action": "doctor_save",
                "id": "1",
                "name": "Dokter Contoh A",
                "specialty": "Poli Penyakit Dalam",
                "hours": "09.00-12.00",
                "days[]": ["1", "3", "5"],
                "active": "1",
                "bio": "Profil dokter pengujian",
                "education": "Pendidikan <script>test</script>",
                "experience": "Pengalaman praktik pengujian",
                "certifications": "Sertifikat uji",
                "organizations": "Organisasi uji",
                "image": "",
            },
        )
        check(
            "Profil dokter dan jadwal disimpan" in doctor_saved,
            "Master profil dan jadwal tersimpan",
        )
        profile = req("index.php?page=doctor_profile&id=1")[1]
        check(
            "Pengalaman praktik pengujian" in profile
            and "&lt;script&gt;test&lt;/script&gt;" in profile,
            "Riwayat dokter tampil dan di-escape",
        )
        check(
            "doctor_profile" in req("index.php?page=doctors")[1],
            "Daftar dokter terhubung ke profil",
        )
        schedule = req("index.php?page=doctor_schedule")[1]
        check(
            "page=doctor_profile&amp;id=1" in schedule
            and "page=register&amp;doctor_id=1" in schedule,
            "Jadwal terhubung ke profil dan pendaftaran",
        )
        registration = req("index.php?page=register&doctor_id=1")[1]
        check(
            'data-selected-doctor="1"' in registration,
            "Book Now memilih dokter pada pendaftaran",
        )
        check(
            req("index.php?page=doctor_profile&id=999999")[0] == 404,
            "Profil dokter tidak tersedia menghasilkan 404",
        )
        _, schedule_form, _ = req("index.php?page=admin&tab=doctors&edit=1")
        session_data = {
            "csrf": csrf(schedule_form),
            "action": "doctor_save",
            "id": "1",
            "name": "Dokter Contoh A",
            "specialty": "Poli Anak",
            "active": "1",
            "image": "",
            "sessions[0][days][]": ["1", "2", "3", "4", "5", "6"],
            "sessions[0][start]": "07:00",
            "sessions[0][end]": "14:00",
            "sessions[1][days][]": ["1", "2", "3", "4", "5"],
            "sessions[1][start]": "16:00",
            "sessions[1][end]": "18:00",
        }
        _, saved, _ = req("index.php?page=admin&tab=doctors&edit=1", session_data)
        check("Profil dokter dan jadwal disimpan" in saved, "Beberapa sesi dokter tersimpan")
        schedule = req("index.php?page=doctor_schedule")[1]
        profile = req("index.php?page=doctor_profile&id=1")[1]
        check(
            all(
                time in schedule and time in profile for time in ["07:00 - 14:00", "16:00 - 18:00"]
            ),
            "Dua sesi tampil pada jadwal dan profil",
        )
        invalid = dict(session_data)
        invalid["sessions[1][end]"] = "15:00"
        invalid["csrf"] = csrf(saved)
        _, bad_schedule, _ = req("index.php?page=admin&tab=doctors&edit=1", invalid)
        check(
            "jam selesai setelah jam mulai" in bad_schedule, "Sesi dengan urutan jam salah ditolak"
        )
        _, room_master, _ = req("index.php?page=admin&tab=contents&kind=inpatient_room")
        check(
            "Jumlah tempat tidur per kamar" in room_master
            and "room_photos[0]" in room_master
            and "data-add-room-field" in room_master,
            "Master rawat inap memiliki kapasitas, fasilitas, dan galeri",
        )
        _, room_master, _ = req(
            "index.php?page=admin&tab=contents&kind=inpatient_room",
            {
                "csrf": csrf(room_master),
                "action": "inpatient_page",
                "inpatient_intro": "Perawatan <aman>",
                "inpatient_wards": "Ruang Umum\nRuang Anak",
            },
        )
        check(
            "Pengaturan halaman Rawat Inap disimpan" in room_master,
            "Pengantar dan daftar ruang tersimpan",
        )
        room_data = {
            "csrf": csrf(room_master),
            "action": "inpatient_save",
            "title": "VIP Uji <aman>",
            "beds": "1",
            "cover_caption": "Anak Kelas 1 <aman>",
            "new_photo_captions[0]": "Anak Kelas 2",
            "new_photo_display[0][fit]": "cover",
            "new_photo_display[0][zoom]": "180",
            "new_photo_display[0][x]": "30",
            "new_photo_display[0][y]": "60",
            "description": "Kenyamanan untuk pasien uji",
            "facilities": "Smart TV\nKamar mandi",
            "sort_order": "1",
            "active": "1",
            "slide_fit": "contain",
            "slide_zoom": "110",
            "slide_x": "40",
            "slide_y": "50",
        }
        room_boundary = "InpatientPhotoBoundary"
        room_parts = []
        for key, value in room_data.items():
            room_parts.append(
                f'--{room_boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
            )
        for field in ["image_file", "room_photos[]"]:
            room_parts.append(
                f'--{room_boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="room.png"\r\nContent-Type: image/png\r\n\r\n'.encode()
                + image
                + b"\r\n"
            )
        room_parts.append(f"--{room_boundary}--\r\n".encode())
        room_request = urllib.request.Request(
            base + "index.php?page=admin&tab=contents&kind=inpatient_room",
            data=b"".join(room_parts),
            headers={"Content-Type": "multipart/form-data; boundary=" + room_boundary},
        )
        with browser.open(room_request) as response:
            room_saved = response.read().decode()
        check(
            "Jenis kamar, fasilitas, dan foto disimpan" in room_saved,
            "Jenis kamar dengan foto utama dan tambahan tersimpan",
        )
        room_id = re.search(r"kind=inpatient_room&amp;edit=(\d+)", room_saved).group(1)
        inpatient = req("index.php?page=inpatient")[1]
        check(
            "VIP Uji &lt;aman&gt;" in inpatient
            and "<li>Smart TV</li>" in inpatient
            and "Perawatan &lt;aman&gt;" in inpatient,
            "Rawat inap menampilkan nama, fasilitas, dan pengantar aman",
        )
        check(
            "data-room-next" in inpatient
            and "data-room-photo" in inpatient
            and "&quot;zoom&quot;:110" in inpatient,
            "Galeri kamar dan pengaturan foto diterapkan",
        )
        check(
            "Ruang Umum" in inpatient and "Ruang Anak" in inpatient, "Daftar ruang perawatan tampil"
        )
        check(
            "Anak Kelas 1 &lt;aman&gt;" in inpatient and 'data-caption="Anak Kelas 2"' in inpatient,
            "Keterangan foto utama dan slide tampil aman",
        )
        check(
            "&quot;zoom&quot;:180" in inpatient and "&quot;x&quot;:30" in inpatient,
            "Pengaturan foto tambahan baru diterapkan pada slider publik",
        )
        _, room_master, _ = req(
            "index.php?page=admin&tab=contents&kind=inpatient_room&edit=" + room_id
        )
        changed = dict(
            room_data, id=room_id, csrf=csrf(room_master), description="Deskripsi diperbarui"
        )
        _, room_saved, _ = req("index.php?page=admin&tab=contents&kind=inpatient_room", changed)
        after = req("index.php?page=inpatient")[1]
        check(
            "Deskripsi diperbarui" in after and "data-room-next" in after,
            "Edit kamar mempertahankan foto lama",
        )
        check(
            "&quot;zoom&quot;:180" in after,
            "Edit kamar mempertahankan pengaturan foto tambahan lama",
        )
        changed.update(
            {
                "photo_display[0][fit]": "contain",
                "photo_display[0][zoom]": "220",
                "photo_display[0][x]": "45",
                "photo_display[0][y]": "55",
            }
        )
        blank_beds = dict(changed, csrf=csrf(room_saved), beds="")
        _, room_saved, _ = req("index.php?page=admin&tab=contents&kind=inpatient_room", blank_beds)
        no_beds = req("index.php?page=inpatient")[1]
        check(
            'class="inpatient-capacity"' not in no_beds and "Anak Kelas 2" in no_beds,
            "Kapasitas opsional dan keterangan foto tetap tersimpan",
        )
        check(
            "&quot;zoom&quot;:220" in no_beds,
            "Ukuran foto tambahan yang sudah tersimpan dapat diperbarui",
        )
        invalid = dict(changed, csrf=csrf(room_saved), beds="0")
        _, invalid_room, _ = req("index.php?page=admin&tab=contents&kind=inpatient_room", invalid)
        check("kapasitas 1-50" in invalid_room, "Kapasitas kamar tidak valid ditolak")
        changed["csrf"] = csrf(invalid_room)
        changed.pop("active")
        _, draft_room, _ = req("index.php?page=admin&tab=contents&kind=inpatient_room", changed)
        check(
            "VIP Uji &lt;aman&gt;" not in req("index.php?page=inpatient")[1],
            "Kamar draft tidak tampil di publik",
        )
        _, deleted_room, _ = req(
            "index.php?page=admin&tab=contents&kind=inpatient_room",
            {"csrf": csrf(draft_room), "action": "inpatient_delete", "id": room_id},
        )
        check("Jenis kamar dihapus" in deleted_room, "Jenis kamar dapat dihapus")
        _, info_form, _ = req("index.php?page=admin&tab=contents&kind=news")
        check(
            'name="action" value="information_save"' in info_form
            and "data-add-room-field" in info_form
            and 'name="body"' in info_form,
            "Master informasi sederhana dengan field tambah foto",
        )
        info_fields = {
            "csrf": csrf(info_form),
            "action": "information_save",
            "title": "Informasi Galeri <uji>",
            "body": "Pengumuman uji\n- Poin pertama\n- Poin kedua",
            "active": "1",
            "new_photo_captions[0]": "Foto awal <aman>",
            "new_photo_captions[5]": "Foto kegiatan kedua",
            "new_photo_display[5][fit]": "contain",
            "new_photo_display[5][zoom]": "150",
            "new_photo_display[5][x]": "40",
            "new_photo_display[5][y]": "60",
        }
        info_boundary = "InformationGalleryBoundary"
        info_parts = []
        for key, value in info_fields.items():
            info_parts.append(
                f'--{info_boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
            )
        for field in ["room_photos[0]", "room_photos[5]"]:
            info_parts.append(
                f'--{info_boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="information.png"\r\nContent-Type: image/png\r\n\r\n'.encode()
                + image
                + b"\r\n"
            )
        info_parts.append(
            f'--{info_boundary}\r\nContent-Disposition: form-data; name="information_video"; filename="information.mp4"\r\nContent-Type: video/mp4\r\n\r\n'.encode()
            + mp4
            + b"\r\n"
        )
        info_parts.append(f"--{info_boundary}--\r\n".encode())
        info_request = urllib.request.Request(
            base + "index.php?page=admin&tab=contents&kind=news",
            data=b"".join(info_parts),
            headers={"Content-Type": "multipart/form-data; boundary=" + info_boundary},
        )
        with browser.open(info_request) as response:
            info_saved = response.read().decode()
        check(
            "Informasi dan foto-foto disimpan" in info_saved, "Informasi dengan dua foto tersimpan"
        )
        info_id = re.search(r"page=detail&amp;id=(\d+)", info_saved).group(1)
        info_detail = req("index.php?page=detail&id=" + info_id)[1]
        check(
            '<video class="information-video" controls' in info_detail
            and re.search(r"assets/uploads/[a-f0-9]{32}\.mp4", info_detail),
            "Video informasi tersimpan dan tampil dengan kontrol pemutar",
        )
        check(
            "Informasi Galeri &lt;uji&gt;" in info_detail
            and "<li>Poin pertama</li>" in info_detail,
            "Judul aman dan format daftar informasi tampil",
        )
        check(
            "Foto awal &lt;aman&gt;" in info_detail
            and "Foto kegiatan kedua" in info_detail
            and "data-room-next" in info_detail
            and "&quot;zoom&quot;:150" in info_detail,
            "Galeri informasi menampilkan caption dan ukuran masing-masing foto",
        )
        _, info_edit, _ = req("index.php?page=admin&tab=contents&kind=news&edit=" + info_id)
        _, info_updated, _ = req(
            "index.php?page=admin&tab=contents&kind=news",
            {
                "csrf": csrf(info_edit),
                "action": "information_save",
                "id": info_id,
                "title": "Informasi Galeri <uji>",
                "body": "Isi diperbarui",
                "active": "1",
                "remove_photos[]": ["0"],
            },
        )
        updated_detail = req("index.php?page=detail&id=" + info_id)[1]
        check(
            "Foto kegiatan kedua" in updated_detail
            and "Foto awal &lt;aman&gt;" not in updated_detail
            and "data-room-next" not in updated_detail,
            "Foto dapat dihapus satu per satu tanpa kehilangan foto lain",
        )
        check(
            '<video class="information-video"' in updated_detail,
            "Edit informasi mempertahankan video lama",
        )
        _, removed_video, _ = req(
            "index.php?page=admin&tab=contents&kind=news",
            {
                "csrf": csrf(info_updated),
                "action": "information_save",
                "id": info_id,
                "title": "Informasi Galeri <uji>",
                "body": "Isi diperbarui",
                "active": "1",
                "remove_information_video": "1",
            },
        )
        check(
            '<video class="information-video"' not in req("index.php?page=detail&id=" + info_id)[1],
            "Video informasi dapat dihapus tanpa menghapus foto",
        )
        size_script = "require 'app/modules/information.php'; $_FILES['information_video']=['error'=>0,'size'=>52428801]; try { uploadInformationVideo(); } catch (RuntimeException $e) { echo $e->getMessage(); }"
        result = subprocess.run(
            [args.php, "-r", size_script], cwd=target, capture_output=True, text=True
        )
        check(
            result.returncode == 0 and "50 MB" in result.stdout,
            "Batas video 50 MB ditegakkan server",
        )

        # Tanggal dihitung menggunakan zona hari yang sama dengan server.
        today = datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=8))).date()
        visit = next(
            today + datetime.timedelta(days=n)
            for n in range(7)
            if (today + datetime.timedelta(days=n)).isoweekday() in [1, 3, 5]
        )
        code, form, _ = req("index.php?page=register")
        data = {
            "csrf": csrf(form),
            "patient_name": "Pasien Pengujian",
            "phone": "081234567890",
            "doctor_id": "1",
            "visit_date": visit.isoformat(),
            "payment": "Pribadi",
            "consent": "1",
            "website": "",
        }
        check(
            all(f'data-step="{step}"' in form for step in range(4))
            and "data-jkn" in form
            and "data-existing" in form,
            "Form memiliki empat langkah, cabang BPJS dan pasien lama",
        )
        check(
            'name="session"' in form
            and 'name="birth_date"' in form
            and "data-submit hidden disabled" in form,
            "Jadwal dan identitas tersedia; pengiriman menunggu API",
        )
        check(
            req("index.php?page=register", dict(data, csrf="invalid"))[0] == 419,
            "POST pendaftaran tetap dilindungi CSRF",
        )
        code, receipt, _ = req("index.php?page=register", data)
        check(
            code == 503
            and "Integrasi Medinfras sedang disiapkan" in receipt
            and "Permohonan tersimpan" not in receipt,
            "Pendaftaran tidak mengklaim sukses sebelum Medinfras tersedia",
        )
        # Fixture terpisah mempertahankan uji pengelolaan pendaftaran terdahulu.
        with closing(sqlite3.connect(target / "storage" / "rsbl.sqlite")) as connection:
            check(
                connection.execute("SELECT COUNT(*) FROM appointments").fetchone()[0] == 0,
                "POST belum membuat transaksi atau menyimpan identitas pasien",
            )
            connection.execute(
                "INSERT INTO appointments (reference,patient_name,phone,doctor_id,visit_date,payment,status,notes,created_at) VALUES (?,?,?,?,?,?,?,?,?)",
                (
                    "BL-TEST-OLD",
                    "Pasien Pengujian",
                    "081234567890",
                    1,
                    visit.isoformat(),
                    "Pribadi",
                    "Menunggu verifikasi",
                    "",
                    "2026-01-01 08:00:00",
                ),
            )
            connection.commit()
        code, appointments, _ = req("index.php?page=admin&tab=appointments")
        check("Pasien Pengujian" in appointments, "Pendaftaran muncul pada admin")
        code, appointments, _ = req(
            "index.php?page=admin&tab=appointments",
            {
                "csrf": csrf(appointments),
                "action": "appointment_update",
                "id": "1",
                "status": "Dikonfirmasi",
                "notes": "Sudah dihubungi untuk pengujian",
            },
        )
        check(
            "Dikonfirmasi" in appointments and "Sudah dihubungi untuk pengujian" in appointments,
            "Status dan catatan disimpan",
        )
        check(
            req("index.php?page=admin&tab=settings", {"action": "settings", "csrf": "invalid"})[0]
            == 419,
            "Perubahan admin tanpa CSRF ditolak",
        )
        check(
            req("storage/rsbl.sqlite")[0] == 404 and req("config.php")[0] == 404,
            "Database dan konfigurasi tidak dapat diunduh",
        )
        check(req("index.php?page=../../config.php")[0] == 404, "Routing traversal ditolak")
        req("index.php?page=logout", {"csrf": csrf(appointments)})
        check("KHUSUS PENGELOLA" in req("index.php?page=admin")[1], "Logout mengakhiri akses admin")
        login = req("index.php?page=login")[1]
        for _ in range(6):
            _, login, _ = req(
                "index.php?page=login",
                {"csrf": csrf(login), "email": "admin@example.test", "password": "wrong"},
            )
        check("Terlalu banyak percobaan" in login, "Pembatasan login aktif")
        print("SEMUA TES LULUS (SQLite / PHP). MySQL dan browser perlu diuji di lingkungan tujuan.")
    finally:
        proc.terminate()
        proc.wait(timeout=10)
        log.seek(0)
        text = log.read()
        log.close()
        if "Fatal error" in text:
            print(text)
