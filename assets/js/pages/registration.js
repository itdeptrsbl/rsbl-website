// Form bertahap, katalog API, dan verifikasi pasien lama; belum mengirim pendaftaran.
(() => {
    const form = document.querySelector('[data-registration]');
    if (!form) return;
    // Kategori pembayaran memuat master penjamin saja; tidak membaca data pasien atau mengirim appointment.
    const paymentCategory = form.elements.namedItem('payment_category');
    const payerSelect = form.elements.namedItem('payer_code');
    const payerFields = form.querySelector('[data-payer-fields]');
    const payerStatus = form.querySelector('[data-payer-status]');
    const payerRetry = form.querySelector('[data-payer-retry]');
    let paymentCategoriesLoaded = false;
    let paymentCategoriesLoading = false;
    const paymentStatus = form.querySelector('[data-payment-status]');
    const paymentRetry = form.querySelector('[data-payment-retry]');
    async function loadPaymentCategories() {
        if (paymentCategoriesLoaded || paymentCategoriesLoading) return;
        paymentCategoriesLoading = true;
        paymentStatus.textContent = 'Memuat jenis pembayaran...';
        paymentRetry.hidden = true;
        const abort = new AbortController();
        const timer = setTimeout(() => abort.abort(), 25000);
        try {
            const url = new URL(form.dataset.clinicsUrl, window.location.href);
            url.searchParams.set('registration_action', 'payers');
            const response = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                signal: abort.signal,
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok || !Array.isArray(payload.categories))
                throw new Error('Kategori belum tersedia.');
            payload.categories.forEach((name) => {
                if (name !== 'Pribadi') paymentCategory.add(new Option(name, name));
            });
            paymentCategoriesLoaded = true;
            paymentStatus.textContent = '';
        } catch (error) {
            paymentStatus.textContent =
                'Jenis pembayaran lainnya belum dapat dimuat. Silakan coba kembali.';
            paymentRetry.hidden = false;
        } finally {
            clearTimeout(timer);
            paymentCategoriesLoading = false;
        }
    }
    paymentRetry.addEventListener('click', loadPaymentCategories);
    let payerRequest = null;
    async function loadPayers() {
        payerRequest?.abort();
        const category = paymentCategory.value;
        payerSelect.replaceChildren(new Option('Pilih penjamin', ''));
        payerSelect.disabled = true;
        payerSelect.required = false;
        payerStatus.textContent = '';
        payerRetry.hidden = true;
        payerFields.hidden = !category || category === 'Pribadi';
        if (payerFields.hidden) return;
        form.querySelector('[data-payer-label]').textContent =
            category === 'Karyawan' ? 'Perusahaan / penjamin karyawan' : 'Perusahaan / penjamin';
        const abort = new AbortController();
        payerRequest = abort;
        const timer = setTimeout(() => abort.abort(), 25000);
        payerStatus.textContent = 'Memuat pilihan penjamin...';
        try {
            const url = new URL(form.dataset.clinicsUrl, window.location.href);
            url.searchParams.set('registration_action', 'payers');
            url.searchParams.set('category', category);
            const response = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                signal: abort.signal,
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            if (payerRequest !== abort || paymentCategory.value !== category) return;
            if (!response.ok || !Array.isArray(payload.payers))
                throw new Error('Pilihan penjamin belum dapat dimuat.');
            payload.payers.forEach((payer) => payerSelect.add(new Option(payer.name, payer.code)));
            payerSelect.disabled = !payload.payers.length;
            payerSelect.required = !!payload.payers.length;
            payerStatus.textContent = payload.payers.length
                ? ''
                : 'Belum tersedia pilihan penjamin untuk jenis pembayaran ini. Silakan hubungi petugas.';
        } catch (error) {
            if (payerRequest !== abort || paymentCategory.value !== category) return;
            payerStatus.textContent = 'Pilihan penjamin belum dapat dimuat. Silakan coba kembali.';
            payerRetry.hidden = false;
        } finally {
            clearTimeout(timer);
        }
    }
    paymentCategory.addEventListener('change', loadPayers);
    payerRetry.addEventListener('click', loadPayers);
    // Mode pratinjau tetap interaktif, tanpa fetch atau penyimpanan pendaftaran.
    if (form.dataset.apiEnabled !== '1') {
        const steps = [...form.querySelectorAll('[data-step]')];
        const field = (name) => form.elements.namedItem(name);
        const next = form.querySelector('[data-next]');
        const back = form.querySelector('[data-back]');
        const feedback = form.querySelector('[data-feedback]');
        let current = 0;
        let clinicsLoaded = false;
        let clinicsLoading = false;
        const clinicStatus = form.querySelector('[data-clinics-status]');
        const clinicRetry = form.querySelector('[data-clinics-retry]');
        // Satu-satunya koneksi pada tahap ini adalah GET daftar poli, tanpa pengelompokan nama.
        async function loadOnlyClinics() {
            if (clinicsLoading || form.dataset.clinicsEnabled !== '1') return;
            clinicsLoading = true;
            field('clinic').disabled = true;
            clinicRetry.hidden = true;
            clinicStatus.textContent = 'Memuat daftar poli...';
            const abort = new AbortController();
            const timer = setTimeout(() => abort.abort(), 25000);
            try {
                const url = new URL(form.dataset.clinicsUrl, window.location.href);
                url.searchParams.set('registration_action', 'clinics');
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: abort.signal,
                    headers: { Accept: 'application/json' },
                });
                const payload = await response.json();
                if (!response.ok || !Array.isArray(payload.clinics))
                    throw new Error('Daftar poli belum dapat dimuat.');
                field('clinic').replaceChildren(new Option('Pilih poli', ''));
                payload.clinics.forEach((unit) =>
                    field('clinic').add(new Option(unit.name, unit.code)),
                );
                field('clinic').disabled = !payload.clinics.length;
                clinicsLoaded = true;
                clinicStatus.textContent = payload.clinics.length ? '' : 'Belum ada poli tersedia.';
            } catch (error) {
                clinicStatus.textContent = 'Daftar poli belum dapat dimuat. Silakan coba kembali.';
                clinicRetry.hidden = false;
            } finally {
                clearTimeout(timer);
                clinicsLoading = false;
            }
        }
        clinicRetry.addEventListener('click', loadOnlyClinics);
        function showPreviewStep(index) {
            current = index;
            steps.forEach((step, i) => {
                step.hidden = i !== index;
            });
            form.closest('.registration-shell')
                .querySelectorAll('[data-progress]')
                .forEach((item, i) => {
                    item.classList.toggle('is-current', i === index);
                    item.classList.toggle('is-complete', i < index);
                    if (i === index) item.setAttribute('aria-current', 'step');
                    else item.removeAttribute('aria-current');
                });
            back.hidden = index === 0;
            next.hidden = index === 3 || field('payment').value === 'BPJS';
            form.querySelector('[data-submit]').hidden = index !== 3;
            feedback.textContent = '';
            if (index === 1 && !clinicsLoaded) loadOnlyClinics();
            if (index === 3) loadPaymentCategories();
        }
        // Poli dimuat dari API saja; dokter/jam tidak diberi data buatan atau master website.
        ['clinic', 'doctor_id', 'session'].forEach((name) => {
            const input = field(name);
            input.disabled = false;
            input.required = false;
            input.replaceChildren(
                new Option(
                    { clinic: 'Pilih poli', doctor_id: 'Pilih dokter', session: 'Pilih jadwal' }[
                        name
                    ],
                    '',
                ),
            );
        });
        field('visit_date').disabled = false;
        field('visit_date').required = false;
        form.querySelector('[data-schedule-status]').textContent = '';
        next.addEventListener('click', () => {
            if (field('payment').value === 'BPJS') return;
            const invalid = [...steps[current].querySelectorAll('input,select,textarea')].find(
                (input) => !input.disabled && !input.checkValidity(),
            );
            if (invalid) {
                invalid.reportValidity();
                return;
            }
            showPreviewStep(Math.min(current + 1, 3));
        });
        back.addEventListener('click', () => showPreviewStep(Math.max(0, current - 1)));
        form.querySelectorAll('[name="payment"]').forEach((input) =>
            input.addEventListener('change', () => {
                form.querySelector('[data-jkn]').hidden = field('payment').value !== 'BPJS';
                showPreviewStep(current);
            }),
        );
        form.querySelectorAll('[name="patient_type"]').forEach((input) =>
            input.addEventListener('change', () => {
                form.querySelector('[data-existing]').hidden =
                    field('patient_type').value !== 'existing';
                field('medical_record_number').disabled =
                    field('patient_type').value !== 'existing';
                field('medical_record_number').required =
                    field('patient_type').value === 'existing';
            }),
        );
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
            feedback.textContent = 'Formulir ini belum menerima pendaftaran.';
        });
        showPreviewStep(0);
        return;
    }
    const steps = [...form.querySelectorAll('[data-step]')];
    const field = (name) => form.elements.namedItem(name);
    const next = form.querySelector('[data-next]');
    const back = form.querySelector('[data-back]');
    const feedback = form.querySelector('[data-feedback]');
    const clinicStatus = form.querySelector('[data-clinics-status]');
    const scheduleStatus = form.querySelector('[data-schedule-status]');
    const retry = form.querySelector('[data-clinics-retry]');
    const requests = new Map();
    const calendar = form.querySelector('[data-visit-calendar]');
    const dateInput = field('visit_date');
    dateInput.hidden = true;
    // Pertahankan kolom tanggal ringkas; kalender dibuka hanya saat diperlukan.
    const dateTrigger = document.createElement('button');
    dateTrigger.type = 'button';
    dateTrigger.className = 'visit-date-trigger';
    dateTrigger.textContent = 'mm/dd/yyyy';
    dateTrigger.disabled = true;
    dateTrigger.setAttribute('aria-expanded', 'false');
    dateTrigger.setAttribute('aria-controls', 'visit-calendar');
    calendar.id = 'visit-calendar';
    calendar.hidden = true;
    calendar.before(dateTrigger);
    function closeCalendar() {
        calendar.hidden = true;
        dateTrigger.setAttribute('aria-expanded', 'false');
    }
    dateTrigger.addEventListener('click', () => {
        calendar.hidden = !calendar.hidden;
        dateTrigger.setAttribute('aria-expanded', String(!calendar.hidden));
    });
    document.addEventListener('click', (event) => {
        if (!dateTrigger.parentElement.contains(event.target)) closeCalendar();
    });
    calendar.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeCalendar();
            dateTrigger.focus();
        }
    });
    let availableDates = new Set();
    let calendarMonth = dateInput.min.slice(0, 7);
    let calendarLoading = false;

    // Kalender khusus diperlukan karena input date bawaan tidak mendukung tanggal nonaktif tertentu.
    function renderCalendar() {
        dateTrigger.disabled = dateInput.disabled;
        dateTrigger.textContent = dateInput.value
            ? dateInput.value.split('-').slice(1).concat(dateInput.value.slice(0, 4)).join('/')
            : 'mm/dd/yyyy';
        calendar.replaceChildren();
        const title = document.createElement('div');
        title.className = 'visit-calendar-heading';
        const month = new Date(`${calendarMonth}-01T12:00:00`);
        const monthLabel = month.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        for (const direction of [-1, 0, 1]) {
            const item = document.createElement(direction ? 'button' : 'strong');
            item.textContent = direction ? (direction < 0 ? '\u2039' : '\u203a') : monthLabel;
            if (direction) {
                item.type = 'button';
                item.setAttribute(
                    'aria-label',
                    direction < 0 ? 'Bulan sebelumnya' : 'Bulan berikutnya',
                );
                const target = new Date(month);
                target.setMonth(target.getMonth() + direction);
                const targetMonth = `${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}`;
                item.disabled =
                    targetMonth < dateInput.min.slice(0, 7) ||
                    targetMonth > dateInput.max.slice(0, 7);
                item.addEventListener('click', () => {
                    calendarMonth = targetMonth;
                    renderCalendar();
                });
            }
            title.append(item);
        }
        calendar.append(title);
        const grid = document.createElement('div');
        grid.className = 'visit-calendar-grid';
        ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].forEach((name) => {
            const label = document.createElement('small');
            label.textContent = name;
            grid.append(label);
        });
        for (let i = 0; i < month.getDay(); i++) grid.append(document.createElement('span'));
        const total = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
        for (let day = 1; day <= total; day++) {
            const value = `${calendarMonth}-${String(day).padStart(2, '0')}`;
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = day;
            button.disabled = dateInput.disabled || !availableDates.has(value);
            button.setAttribute(
                'aria-label',
                `${day} ${monthLabel}${button.disabled ? ', tidak tersedia' : ''}`,
            );
            button.setAttribute('aria-pressed', String(value === dateInput.value));
            button.addEventListener('click', () => {
                dateInput.value = value;
                renderCalendar();
                closeCalendar();
                dateTrigger.focus();
                loadSessions();
            });
            grid.append(button);
        }
        calendar.append(grid);
        const hint = document.createElement('small');
        hint.textContent = calendarLoading
            ? 'Memuat tanggal praktik...'
            : dateInput.disabled
              ? 'Pilih dokter terlebih dahulu.'
              : availableDates.size
                ? 'Tanggal abu-abu tidak tersedia.'
                : 'Belum ada tanggal praktik tersedia.';
        calendar.append(hint);
    }
    async function loadDates() {
        resetVisit();
        retry.hidden = true;
        if (!field('doctor_id').value) return;
        calendarLoading = true;
        renderCalendar();
        try {
            const payload = await getCatalog('dates', {
                clinic: field('clinic').value,
                doctor: field('doctor_id').value,
            });
            if (!Array.isArray(payload.dates)) throw new Error('Format tanggal tidak sesuai.');
            availableDates = new Set(payload.dates);
            dateInput.min = payload.min;
            dateInput.max = payload.max;
            dateInput.disabled = false;
        } catch (error) {
            if (error.name === 'AbortError') return;
            scheduleStatus.textContent =
                'Tanggal praktik belum dapat dimuat. Silakan coba kembali.';
            retryWith(loadDates);
        } finally {
            calendarLoading = false;
            renderCalendar();
        }
    }
    let current = 0;
    let clinicsLoaded = false;
    let retryAction = null;

    // Teks API dimasukkan sebagai teks opsi, bukan HTML.
    function options(select, placeholder, values = []) {
        select.replaceChildren(new Option(placeholder, ''));
        values.forEach((item) => select.add(new Option(item.name, item.code)));
    }

    // Batalkan hasil lama agar pergantian poli/dokter cepat tidak menampilkan pilihan sebelumnya.
    function cancel(action) {
        requests.get(action)?.abort();
        requests.delete(action);
    }
    async function getCatalog(action, params = {}) {
        cancel(action);
        const abort = new AbortController();
        requests.set(action, abort);
        const url = new URL(form.dataset.clinicsUrl, window.location.href);
        url.searchParams.set('registration_action', action);
        Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
        const timer = setTimeout(() => abort.abort(), 25000);
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                signal: abort.signal,
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            if (requests.get(action) !== abort) throw new DOMException('Stale', 'AbortError');
            if (!response.ok) throw new Error(payload.error || 'Pilihan belum dapat dimuat.');
            return payload;
        } catch (error) {
            if (error.name === 'AbortError' && requests.get(action) === abort) {
                throw new Error('Permintaan melewati batas waktu.');
            }
            throw error;
        } finally {
            clearTimeout(timer);
            if (requests.get(action) === abort) requests.delete(action);
        }
    }

    // Selanjutnya hanya tersedia setelah seluruh pilihan kunjungan sah terisi.
    function updateNext() {
        next.disabled =
            current === 1 &&
            !['clinic', 'doctor_id', 'visit_date', 'session'].every(
                (name) => !field(name).disabled && field(name).value,
            );
    }
    function retryWith(action) {
        retryAction = action;
        retry.hidden = false;
    }
    function resetSessions() {
        cancel('sessions');
        options(field('session'), 'Pilih tanggal terlebih dahulu');
        field('session').disabled = true;
        scheduleStatus.textContent = '';
        updateNext();
    }
    function resetVisit() {
        closeCalendar();
        cancel('dates');
        field('visit_date').value = '';
        field('visit_date').disabled = true;
        availableDates.clear();
        calendarLoading = false;
        calendarMonth = dateInput.min.slice(0, 7);
        renderCalendar();
        resetSessions();
    }
    async function loadClinics() {
        clinicStatus.textContent = 'Memuat daftar poli...';
        retry.hidden = true;
        field('clinic').disabled = true;
        try {
            const payload = await getCatalog('clinics');
            if (!Array.isArray(payload.clinics)) throw new Error('Format poli tidak sesuai.');
            options(
                field('clinic'),
                payload.clinics.length ? 'Pilih poli' : 'Belum ada poli tersedia',
                payload.clinics,
            );
            field('clinic').disabled = !payload.clinics.length;
            clinicsLoaded = true;
            clinicStatus.textContent = payload.clinics.length
                ? ''
                : 'Belum ada poli yang tersedia.';
        } catch (error) {
            if (error.name === 'AbortError') return;
            clinicStatus.textContent = 'Daftar poli belum dapat dimuat. Silakan coba kembali.';
            retryWith(loadClinics);
        }
    }
    async function loadDoctors() {
        cancel('doctors');
        options(field('doctor_id'), 'Pilih poli terlebih dahulu');
        field('doctor_id').disabled = true;
        resetVisit();
        retry.hidden = true;
        if (!field('clinic').value) return;
        options(field('doctor_id'), 'Memuat dokter...');
        clinicStatus.textContent = 'Memuat pilihan dokter...';
        try {
            const payload = await getCatalog('doctors', { clinic: field('clinic').value });
            if (!Array.isArray(payload.doctors)) throw new Error('Format dokter tidak sesuai.');
            options(
                field('doctor_id'),
                payload.doctors.length ? 'Pilih dokter' : 'Belum ada dokter tersedia',
                payload.doctors,
            );
            field('doctor_id').disabled = !payload.doctors.length;
            clinicStatus.textContent = payload.doctors.length
                ? 'Dokter ditampilkan sesuai unit poli dari jadwal Medinfras. Silakan pilih tanggal kunjungan.'
                : 'Belum ada dokter dengan jadwal pada unit poli ini di Medinfras. Silakan hubungi petugas.';
        } catch (error) {
            if (error.name === 'AbortError') return;
            clinicStatus.textContent = 'Pilihan dokter belum dapat dimuat. Silakan coba kembali.';
            retryWith(loadDoctors);
        }
    }
    async function loadSessions() {
        resetSessions();
        retry.hidden = true;
        const date = field('visit_date');
        if (!date.value || !date.checkValidity() || !field('doctor_id').value) return;
        options(field('session'), 'Memuat jadwal...');
        scheduleStatus.textContent = 'Memeriksa jadwal praktik...';
        try {
            const payload = await getCatalog('sessions', {
                clinic: field('clinic').value,
                doctor: field('doctor_id').value,
                date: date.value,
            });
            if (!Array.isArray(payload.sessions)) throw new Error('Format jadwal tidak sesuai.');
            options(
                field('session'),
                payload.sessions.length ? 'Pilih jadwal' : 'Jadwal belum tersedia',
                payload.sessions,
            );
            field('session').disabled = !payload.sessions.length;
            scheduleStatus.textContent = payload.message || '';
            updateNext();
        } catch (error) {
            if (error.name === 'AbortError') return;
            options(field('session'), 'Jadwal belum dapat dimuat');
            scheduleStatus.textContent = 'Jadwal belum dapat dimuat. Silakan coba kembali.';
            retryWith(loadSessions);
        }
    }

    // Berpindah langkah tanpa kehilangan isian yang telah diketik.
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
        updateNext();
        if (index === 1 && !clinicsLoaded) loadClinics();
        if (index === 3) loadPaymentCategories();
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
        if (current === 1 && next.disabled) return;
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
            field('medical_record_number').disabled = !existing;
            feedback.textContent = '';
        }),
    );
    retry.textContent = 'Coba muat kembali';
    retry.addEventListener('click', () => retryAction?.());
    field('clinic').addEventListener('change', loadDoctors);
    field('doctor_id').addEventListener('change', loadDates);
    field('visit_date').addEventListener('change', loadSessions);
    field('session').addEventListener('change', updateNext);
    ['first_name', 'middle_name', 'last_name'].forEach((name) =>
        field(name).addEventListener('input', () => {
            field('patient_name').value = ['first_name', 'middle_name', 'last_name']
                .map((part) => field(part).value.trim())
                .filter(Boolean)
                .join(' ');
        }),
    );
    // Transaksi pendaftaran tetap terkunci; tahap ini hanya membaca katalog kunjungan.
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        feedback.textContent = 'Pengiriman pendaftaran belum tersedia.';
    });
    options(field('clinic'), 'Daftar poli dimuat saat memilih kunjungan');
    renderCalendar();
    showStep(0);
})();
