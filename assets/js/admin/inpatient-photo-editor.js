// Editor foto tambahan: membuat field unggah, pratinjau, zoom, posisi, dan caption.
// Membuat kontrol ukuran, mode, dan geser untuk satu foto.
function setupRoomPhotoEditor(root, prefix, display = {}) {
    const image = root.querySelector('img');
    const frame = document.createElement('div');
    frame.className = 'room-photo-adjust-frame';
    frame.tabIndex = 0;
    frame.setAttribute('role', 'group');
    frame.setAttribute(
        'aria-label',
        'Seret untuk mengatur posisi foto; gunakan tombol panah untuk menggeser',
    );
    image.replaceWith(frame);
    frame.append(image);
    image.draggable = false;
    const fields = {};
    const toolbar = document.createElement('div');
    toolbar.className = 'room-photo-adjust-toolbar';
    ['fit', 'zoom', 'x', 'y'].forEach((key) => {
        const field = document.createElement(key === 'fit' ? 'select' : 'input');
        field.name = `${prefix}[${key}]`;
        field.dataset.adjustKey = key;
        if (key === 'fit') {
            [
                ['contain', 'Foto utuh'],
                ['cover', 'Penuhi area'],
            ].forEach(([value, label]) => {
                field.add(new Option(label, value));
            });
            field.setAttribute('aria-label', 'Mode tampilan foto');
            toolbar.append(field);
        } else {
            field.type = 'hidden';
            root.append(field);
        }
        field.value = display[key] ?? (key === 'fit' ? 'contain' : key === 'zoom' ? 100 : 50);
        fields[key] = field;
    });
    const output = document.createElement('output');
    // Membuat tombol editor beserta label aksesibilitas dan aksi kliknya.
    const makeButton = (label, title, action) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.setAttribute('aria-label', title);
        button.addEventListener('click', action);
        return button;
    };
    // Menerapkan pengaturan foto dan memperbarui angka zoom serta tombolnya.
    function update() {
        const state = Object.fromEntries(
            Object.entries(fields).map(([key, field]) => [key, field.value]),
        );
        applySlideDisplay(image, state);
        output.value = `${state.zoom}%`;
        minus.disabled = Number(state.zoom) <= 50;
        plus.disabled = Number(state.zoom) >= 400;
    }
    const minus = makeButton('-', 'Perkecil foto', () => {
        fields.zoom.value = Math.max(50, Number(fields.zoom.value) - 10);
        update();
    });
    const plus = makeButton('+', 'Perbesar foto', () => {
        fields.zoom.value = Math.min(400, Number(fields.zoom.value) + 10);
        update();
    });
    const reset = makeButton('Pas-kan', 'Tampilkan foto utuh dan reset posisi', () => {
        Object.entries({ fit: 'contain', zoom: 100, x: 50, y: 50 }).forEach(([key, value]) => {
            fields[key].value = value;
        });
        update();
    });
    toolbar.append(minus, output, plus, reset);
    root.prepend(toolbar);
    fields.fit.addEventListener('change', update);
    let drag = null;
    // Menyimpan posisi foto dalam batas 0-100 lalu memperbarui pratinjau.
    function move(x, y) {
        fields.x.value = Math.round(Math.min(100, Math.max(0, x)));
        fields.y.value = Math.round(Math.min(100, Math.max(0, y)));
        update();
    }
    frame.addEventListener('pointerdown', (event) => {
        if (!event.isPrimary || event.button !== 0 || !image.naturalWidth) return;
        const ratios = [
            frame.clientWidth / image.naturalWidth,
            frame.clientHeight / image.naturalHeight,
        ];
        const fit = fields.fit.value === 'contain' ? Math.min(...ratios) : Math.max(...ratios);
        const zoom = Number(fields.zoom.value) / 100;
        drag = {
            id: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            x: Number(fields.x.value),
            y: Number(fields.y.value),
            dx: image.naturalWidth * fit * zoom - frame.clientWidth,
            dy: image.naturalHeight * fit * zoom - frame.clientHeight,
        };
        frame.setPointerCapture(event.pointerId);
        frame.classList.add('is-dragging');
        frame.focus({ preventScroll: true });
        event.preventDefault();
    });
    frame.addEventListener('pointermove', (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        move(
            Math.abs(drag.dx) > 1
                ? drag.x - ((event.clientX - drag.startX) / drag.dx) * 100
                : drag.x,
            Math.abs(drag.dy) > 1
                ? drag.y - ((event.clientY - drag.startY) / drag.dy) * 100
                : drag.y,
        );
    });
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((name) =>
        frame.addEventListener(name, (event) => {
            if (!drag || event.pointerId !== drag.id) return;
            drag = null;
            frame.classList.remove('is-dragging');
            if (frame.hasPointerCapture(event.pointerId))
                frame.releasePointerCapture(event.pointerId);
        }),
    );
    frame.addEventListener('keydown', (event) => {
        const directions = {
            ArrowLeft: [2, 0],
            ArrowRight: [-2, 0],
            ArrowUp: [0, 2],
            ArrowDown: [0, -2],
        };
        if (!directions[event.key]) return;
        event.preventDefault();
        const [dx, dy] = directions[event.key];
        move(Number(fields.x.value) + dx, Number(fields.y.value) + dy);
    });
    update();
}

document.querySelectorAll('[data-room-adjust]').forEach((root) => {
    setupRoomPhotoEditor(root, root.dataset.fieldPrefix, JSON.parse(root.dataset.display));
});

// Tombol + membuat field baru; file pada baris lain tidak disentuh.
document.querySelectorAll('[data-room-upload-list]').forEach((list) => {
    const template = list.closest('form').querySelector('[data-room-upload-template]');
    let nextIndex = 1;

    // Mengaktifkan unggahan, pratinjau, dan tombol tambah/hapus pada satu baris foto.
    function setupRow(row) {
        const input = row.querySelector('[data-room-file]');
        const preview = row.querySelector('[data-room-file-preview]');
        const status = row.querySelector('[data-room-file-status]');
        let generation = 0;

        // Membaca file yang dipilih dan membangun pratinjau beserta editor fotonya.
        function updatePreview() {
            const current = ++generation;
            const file = input.files[0];
            preview.replaceChildren();
            status.textContent = '';
            input.setCustomValidity('');
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) {
                status.textContent = 'Foto melebihi 10 MB. Pilih foto yang lebih kecil.';
                input.setCustomValidity(status.textContent);
                return;
            }
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                status.textContent = 'Pilih foto JPG, PNG, atau WebP.';
                input.setCustomValidity(status.textContent);
                return;
            }
            status.textContent = 'Memuat pratinjau...';
            const image = document.createElement('img');
            image.alt = `Pratinjau: ${file.name}`;
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                if (current === generation && row.isConnected) image.src = reader.result;
            });
            image.addEventListener('load', () => {
                if (current !== generation || !row.isConnected) return;
                preview.append(image);
                setupRoomPhotoEditor(preview, `new_photo_display[${row.dataset.uploadIndex}]`);
                status.textContent = `${image.naturalWidth} x ${image.naturalHeight} piksel`;
            });
            // Menampilkan pesan saat berkas foto tidak bisa dibaca atau dimuat.
            const failed = () => {
                if (current === generation) {
                    status.textContent = 'Foto tidak dapat dibaca. Pilih ulang file gambar.';
                    input.setCustomValidity(status.textContent);
                }
            };
            reader.addEventListener('error', failed);
            image.addEventListener('error', failed);
            reader.readAsDataURL(file);
        }

        input.addEventListener('change', updatePreview);
        row.querySelector('[data-add-room-field]').addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            const added = fragment.querySelector('[data-room-upload-row]');
            added.dataset.uploadIndex = nextIndex;
            added.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace('__INDEX__', nextIndex);
            });
            nextIndex++;
            row.after(fragment);
            setupRow(added);
            added.querySelector('[data-room-file]').focus();
        });
        row.querySelector('[data-remove-room-field]').addEventListener('click', () => {
            generation++;
            if (list.children.length === 1) {
                input.value = '';
                row.querySelector('[name^="new_photo_captions"]').value = '';
                updatePreview();
            } else {
                const next = row.nextElementSibling || row.previousElementSibling;
                row.remove();
                next.querySelector('[data-room-file]').focus();
            }
        });
        if (input.files.length) updatePreview();
    }

    list.querySelectorAll('[data-room-upload-row]').forEach(setupRow);
});
