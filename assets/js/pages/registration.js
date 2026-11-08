// Mengatur langkah formulir dan pilihan jadwal tanpa menyimpan data pasien di browser.
(() => {
    const form = document.querySelector('[data-registration]');
    if (!form) return;
    const doctors = JSON.parse(form.dataset.doctors || '[]');
    const steps = [...form.querySelectorAll('[data-step]')];
    const field = (name) => form.elements.namedItem(name);
    const feedback = form.querySelector('[data-feedback]');
    const next = form.querySelector('[data-next]');
    const back = form.querySelector('[data-back]');
    let current = 0;

    // Hanya field pada langkah aktif yang divalidasi oleh tombol selanjutnya.
    function showStep(index) {
        current = index;
        steps.forEach((step, i) => {
            step.hidden = i !== index;
        });
        document.querySelectorAll('[data-progress]').forEach((item, i) => {
            item.classList.toggle('is-current', i === index);
            item.classList.toggle('is-complete', i < index);
            if (i === index) item.setAttribute('aria-current', 'step');
            else item.removeAttribute('aria-current');
        });
        back.hidden = index === 0;
        next.hidden = index === 3 || field('payment').value === 'BPJS';
        form.querySelector('[data-submit]').hidden = index !== 3;
        feedback.textContent = '';
        if (index === 3) updateSummary();
    }

    // Membuat opsi memakai textContent agar nama dari database tetap aman.
    function options(select, placeholder, values) {
        select.replaceChildren(new Option(placeholder, ''));
        values.forEach(([value, label]) => select.add(new Option(label, value)));
    }

    // Mengosongkan pilihan turunan ketika poli atau dokter berubah.
    function resetSchedule() {
        field('visit_date').value = '';
        field('visit_date').disabled = !field('doctor_id').value;
        options(field('session'), 'Pilih tanggal terlebih dahulu', []);
        field('session').disabled = true;
    }
    function updateDoctors() {
        options(
            field('doctor_id'),
            'Pilih dokter',
            doctors
                .filter((doctor) => doctor.clinic === field('clinic').value)
                .map((doctor) => [doctor.id, doctor.name]),
        );
        field('doctor_id').disabled = !field('clinic').value;
        resetSchedule();
    }

    // Sesi lokal hanya referensi; kuota aktual nantinya dibaca dari Medinfras.
    function updateSessions() {
        const date = field('visit_date').value;
        const doctor = doctors.find((item) => item.id === Number(field('doctor_id').value));
        const weekday = date ? new Date(date + 'T12:00:00').getDay() || 7 : 0;
        const sessions = (doctor?.sessions || []).filter((session) =>
            session.days.map(Number).includes(weekday),
        );
        options(
            field('session'),
            sessions.length ? 'Pilih jadwal' : 'Tidak ada jadwal pada tanggal ini',
            sessions.map((session) => [
                session.start + '-' + session.end,
                session.start + ' - ' + session.end + ' WITA',
            ]),
        );
        field('session').disabled = !sessions.length;
        form.querySelector('[data-schedule-status]').textContent = sessions.length
            ? ''
            : 'Belum ada jadwal praktik pada tanggal ini. Silakan pilih tanggal lain.';
    }

    // Ringkasan selalu dibentuk dari pilihan terbaru, tanpa HTML dari input pengguna.
    function updateSummary() {
        const doctor = doctors.find((item) => item.id === Number(field('doctor_id').value));
        form.querySelector('[data-summary]').textContent = [
            field('clinic').value,
            doctor?.name,
            field('visit_date').value,
            field('session').value + ' WITA',
        ].join(' / ');
    }
    next.addEventListener('click', () => {
        if (field('payment').value === 'BPJS') return;
        const invalid = [...steps[current].querySelectorAll('input,select,textarea')].find(
            (input) => !input.disabled && !input.checkValidity(),
        );
        if (invalid) {
            invalid.reportValidity();
            return;
        }
        if (current === 1 && field('patient_type').value === 'existing') {
            feedback.textContent =
                'Pencarian pasien lama belum tersedia. Silakan hubungi petugas untuk melanjutkan pendaftaran.';
            return;
        }
        if (current === 2 && !field('session').value) {
            feedback.textContent = 'Pilih tanggal yang memiliki jadwal praktik.';
            return;
        }
        showStep(Math.min(current + 1, 3));
        const heading = steps[current].querySelector('h2');
        heading.tabIndex = -1;
        heading.focus();
    });
    back.addEventListener('click', () => showStep(Math.max(0, current - 1)));
    form.querySelectorAll('[name="payment"]').forEach((input) =>
        input.addEventListener('change', () => {
            form.querySelector('[data-jkn]').hidden = field('payment').value !== 'BPJS';
            showStep(current);
        }),
    );
    form.querySelectorAll('[name="patient_type"]').forEach((input) =>
        input.addEventListener('change', () => {
            const existing = field('patient_type').value === 'existing';
            form.querySelector('[data-existing]').hidden = !existing;
            field('medical_record_number').required = existing;
            field('verification_birth_date').required = existing;
            feedback.textContent = '';
        }),
    );
    form.querySelector('[data-lookup]').addEventListener('click', () => {
        // Jangan mengirim nomor rekam medis sebelum endpoint dan verifikasi disepakati.
        form.querySelector('[data-lookup-status]').textContent =
            'Pencarian Medinfras belum aktif. Hubungi petugas rumah sakit untuk bantuan.';
    });
    field('clinic').addEventListener('change', updateDoctors);
    field('doctor_id').addEventListener('change', resetSchedule);
    field('visit_date').addEventListener('change', updateSessions);
    ['first_name', 'middle_name', 'last_name'].forEach((name) =>
        field(name).addEventListener('input', () => {
            field('patient_name').value = ['first_name', 'middle_name', 'last_name']
                .map((part) => field(part).value.trim())
                .filter(Boolean)
                .join(' ');
        }),
    );
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        feedback.textContent = 'Pengiriman pendaftaran menunggu integrasi Medinfras.';
    });
    options(
        field('clinic'),
        'Pilih poli',
        [...new Set(doctors.map((doctor) => doctor.clinic))]
            .filter(Boolean)
            .sort()
            .map((clinic) => [clinic, clinic]),
    );
    const selected = doctors.find((doctor) => doctor.id === Number(form.dataset.selectedDoctor));
    if (selected) {
        field('clinic').value = selected.clinic;
        updateDoctors();
        field('doctor_id').value = selected.id;
        resetSchedule();
    }
    showStep(0);
})();
