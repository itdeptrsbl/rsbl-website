// Pratinjau ukuran dan posisi foto pemimpin sebelum disimpan.
document.querySelectorAll('[data-leader-size]').forEach((control) => {
    const sizes = Array.from({ length: 11 }, (_, i) => 100 + i * 10);
    const input = control.querySelector('[name="leader_photo_zoom"]');
    const x = control.querySelector('[name="leader_photo_x"]');
    const y = control.querySelector('[name="leader_photo_y"]');
    const minus = control.querySelector('[data-size-minus]');
    const plus = control.querySelector('[data-size-plus]');
    const preview = control.querySelector('.leader-photo-frame');
    const image = control.querySelector('[data-leader-preview]');
    const file = control.closest('form').querySelector('[name="image_file"]');
    let objectUrl;
    // Menerapkan ukuran dan posisi foto pemimpin ke pratinjau.
    function update() {
        const size = Number(input.value);
        preview.className = `leader-photo-frame leader-zoom-${size} leader-x-${x.value} leader-y-${y.value}`;
        control.querySelector('output').textContent = `${size}%`;
        minus.disabled = size === sizes[0];
        plus.disabled = size === sizes[sizes.length - 1];
    }
    // Memilih ukuran foto pemimpin berikutnya tanpa melewati batas yang diizinkan.
    function change(direction) {
        const index = sizes.indexOf(Number(input.value));
        input.value = sizes[Math.max(0, Math.min(sizes.length - 1, index + direction))];
        update();
    }
    minus.addEventListener('click', () => change(-1));
    plus.addEventListener('click', () => change(1));
    // Membatasi posisi foto dan membulatkannya ke langkah lima persen.
    const clamp = (value) => Math.max(0, Math.min(100, Math.round(value / 5) * 5));
    let drag = null;
    image.draggable = false;
    preview.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || !image.naturalWidth) return;
        event.preventDefault();
        preview.focus();
        preview.setPointerCapture(event.pointerId);
        drag = {
            id: event.pointerId,
            px: event.clientX,
            py: event.clientY,
            x: Number(x.value),
            y: Number(y.value),
        };
        preview.classList.add('is-dragging');
    });
    preview.addEventListener('pointermove', (event) => {
        if (!drag || drag.id !== event.pointerId) return;
        const frame = preview.getBoundingClientRect();
        const cover = Math.max(
            frame.width / image.naturalWidth,
            frame.height / image.naturalHeight,
        );
        const zoom = Number(input.value) / 100;
        const overflowX = image.naturalWidth * cover * zoom - frame.width;
        const overflowY = image.naturalHeight * cover * zoom - frame.height;
        x.value =
            overflowX > 1 ? clamp(drag.x - ((event.clientX - drag.px) * 100) / overflowX) : drag.x;
        y.value =
            overflowY > 1 ? clamp(drag.y - ((event.clientY - drag.py) * 100) / overflowY) : drag.y;
        update();
        preview.classList.add('is-dragging');
    });
    // Mengakhiri pergeseran dan melepaskan tangkapan pointer.
    function stopDrag() {
        drag = null;
        preview.classList.remove('is-dragging');
    }
    preview.addEventListener('pointerup', stopDrag);
    preview.addEventListener('pointercancel', stopDrag);
    preview.addEventListener('lostpointercapture', stopDrag);
    preview.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'ArrowLeft') x.value = clamp(Number(x.value) + 5);
        if (event.key === 'ArrowRight') x.value = clamp(Number(x.value) - 5);
        if (event.key === 'ArrowUp') y.value = clamp(Number(y.value) + 5);
        if (event.key === 'ArrowDown') y.value = clamp(Number(y.value) - 5);
        update();
    });
    const original = image.src;
    file.addEventListener('change', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = file.files[0] ? URL.createObjectURL(file.files[0]) : null;
        image.src = objectUrl || original;
    });
    update();
});
