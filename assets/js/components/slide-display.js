// Menerapkan ukuran foto tersimpan dan menjalankan editor foto di master.
// Pengaturan yang sama dipakai untuk pratinjau master dan foto di beranda.
// Menerapkan mode tampilan, skala, dan titik fokus foto ke elemen gambar.
function applySlideDisplay(image, display) {
    // Mengubah nilai ke angka dan membatasinya ke rentang yang aman.
    const bounded = (value, fallback, min, max) => {
        const number = Number(value ?? fallback);
        return Number.isFinite(number) ? Math.min(max, Math.max(min, number)) : fallback;
    };
    image.style.objectFit = display.fit === 'contain' ? 'contain' : 'cover';
    image.parentElement.classList.toggle('is-photo-contained', display.fit === 'contain');
    image.style.objectPosition = `${bounded(display.x, 50, 0, 100)}% ${bounded(display.y, 50, 0, 100)}%`;
    image.style.transform = `scale(${bounded(display.zoom, 100, 50, 400) / 100})`;
    image.style.transformOrigin = image.style.objectPosition;
}

document.querySelectorAll('[data-slide-image]').forEach((image) => {
    try {
        applySlideDisplay(image, JSON.parse(image.dataset.display));
    } catch {
        applySlideDisplay(image, {});
    }
});

document.querySelectorAll('[data-slide-editor]').forEach((editor) => {
    const form = editor.closest('form');
    const image = editor.querySelector('[data-preview-image]');
    const frame = editor.querySelector('[data-preview-frame]');
    const message = editor.querySelector('[data-preview-message]');
    const file = form.elements.image_file;
    const url = form.elements.image;
    let sourceVersion = 0;
    const minZoom = editor.dataset.photoKind === 'clinic' ? 50 : 100;
    const maxZoom = editor.dataset.photoKind === 'clinic' ? 400 : 160;

    // Membaca nilai formulir lalu memperbarui skala, posisi, dan tombol editor foto.
    function update() {
        const display = {};
        ['fit', 'zoom', 'x', 'y'].forEach((key) => {
            display[key] = form.elements[`slide_${key}`].value;
            const output = editor.querySelector(`[data-value="${key}"]`);
            if (output) output.value = `${display[key]}%`;
        });
        applySlideDisplay(image, display);
        editor.querySelectorAll('[data-zoom-step]').forEach((button) => {
            button.disabled =
                Number(display.zoom) + Number(button.dataset.zoomStep) < minZoom ||
                Number(display.zoom) + Number(button.dataset.zoomStep) > maxZoom;
        });
    }

    // Memperbarui pratinjau dari file baru atau URL foto yang valid.
    function updateSource() {
        const version = ++sourceVersion;
        if (file.files[0]) {
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                if (version === sourceVersion) image.src = reader.result;
            });
            reader.readAsDataURL(file.files[0]);
            return;
        }
        const value = url.value.trim();
        const valid =
            /^https:\/\/[^\s]+$/i.test(value) ||
            /^assets\/uploads\/[a-f0-9]{32}\.(jpg|png|webp)$/.test(value);
        image.src = valid
            ? value
            : editor.dataset.photoKind === 'inpatient_room'
              ? 'assets/room-placeholder.svg'
              : 'assets/branding/doctors.jpg';
    }

    let drag = null;

    // Membatasi posisi foto lalu menampilkan hasil pergeserannya.
    function movePosition(x, y) {
        form.elements.slide_x.value = Math.round(Math.max(0, Math.min(100, x)));
        form.elements.slide_y.value = Math.round(Math.max(0, Math.min(100, y)));
        update();
    }

    editor.querySelectorAll('[data-zoom-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const zoom = Number(form.elements.slide_zoom.value);
            form.elements.slide_zoom.value = Math.max(
                minZoom,
                Math.min(maxZoom, zoom + Number(button.dataset.zoomStep)),
            );
            update();
        });
    });

    frame.addEventListener('pointerdown', (event) => {
        if (!event.isPrimary || event.button !== 0 || !image.naturalWidth) return;
        const width = frame.clientWidth;
        const height = frame.clientHeight;
        const ratios = [width / image.naturalWidth, height / image.naturalHeight];
        const fit =
            form.elements.slide_fit.value === 'contain' ? Math.min(...ratios) : Math.max(...ratios);
        const zoom = Number(form.elements.slide_zoom.value) / 100;
        drag = {
            id: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            x: Number(form.elements.slide_x.value),
            y: Number(form.elements.slide_y.value),
            overflowX: image.naturalWidth * fit * zoom - width,
            overflowY: image.naturalHeight * fit * zoom - height,
        };
        frame.setPointerCapture(event.pointerId);
        frame.classList.add('is-dragging');
        frame.focus({ preventScroll: true });
        event.preventDefault();
    });

    frame.addEventListener('pointermove', (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        const x =
            Math.abs(drag.overflowX) > 1
                ? drag.x - ((event.clientX - drag.startX) / drag.overflowX) * 100
                : drag.x;
        const y =
            Math.abs(drag.overflowY) > 1
                ? drag.y - ((event.clientY - drag.startY) / drag.overflowY) * 100
                : drag.y;
        movePosition(x, y);
    });

    // Mengakhiri pergeseran dan melepaskan tangkapan pointer.
    function stopDrag(event) {
        if (!drag || event.pointerId !== drag.id) return;
        drag = null;
        frame.classList.remove('is-dragging');
        if (frame.hasPointerCapture(event.pointerId)) frame.releasePointerCapture(event.pointerId);
    }

    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((name) => {
        frame.addEventListener(name, stopDrag);
    });
    frame.addEventListener('keydown', (event) => {
        const offsets = {
            ArrowLeft: [2, 0],
            ArrowRight: [-2, 0],
            ArrowUp: [0, 2],
            ArrowDown: [0, -2],
        };
        if (!offsets[event.key]) return;
        event.preventDefault();
        const [dx, dy] = offsets[event.key];
        movePosition(
            Number(form.elements.slide_x.value) + dx,
            Number(form.elements.slide_y.value) + dy,
        );
    });

    editor.addEventListener('input', update);
    editor.querySelector('[data-preview-size]').addEventListener('change', (event) => {
        frame.dataset.size = event.target.value;
    });
    // Mengembalikan foto utuh ke ukuran awal dan posisi tengah.
    function fitPhoto() {
        Object.entries({ fit: 'contain', zoom: 100, x: 50, y: 50 }).forEach(([key, value]) => {
            form.elements[`slide_${key}`].value = value;
        });
        update();
    }

    editor.querySelector('[data-fit-photo]').addEventListener('click', fitPhoto);
    form.elements.slide_fit.addEventListener('change', () => {
        if (form.elements.slide_fit.value === 'contain') fitPhoto();
    });

    editor.querySelector('[data-reset-display]').addEventListener('click', () => {
        Object.entries({
            fit: editor.dataset.photoKind === 'clinic' ? 'contain' : 'cover',
            zoom: 100,
            x: 50,
            y: 50,
        }).forEach(([key, value]) => {
            form.elements[`slide_${key}`].value = value;
        });
        update();
    });
    file.addEventListener('change', updateSource);
    url.addEventListener('input', updateSource);
    image.addEventListener('load', () => {
        message.textContent = `Ukuran asli: ${image.naturalWidth} x ${image.naturalHeight} piksel.`;
    });
    image.addEventListener('error', () => {
        message.textContent = 'Foto tidak dapat dimuat. Periksa URL atau unggah gambar lain.';
    });
    update();
});
