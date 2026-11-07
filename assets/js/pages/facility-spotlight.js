// Galeri fasilitas bertumpuk dengan foto aktif di tengah.
// Galeri bertumpuk khusus fasilitas; slideshow beranda tetap terpisah.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-facility-spotlight]').forEach((root) => {
        const photos = [...root.querySelectorAll('[data-spotlight-photo]')];
        const dots = [...root.querySelectorAll('[data-slide-to]')];
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        let current = 0;
        let timer;
        let hovered = false;
        let focused = false;

        // Dua foto tetap memiliki pratinjau tetangga di kedua sisi.
        const mirror = photos.length === 2 ? photos[1].cloneNode(true) : null;
        if (mirror) {
            mirror.removeAttribute('data-spotlight-photo');
            mirror.setAttribute('aria-hidden', 'true');
            root.querySelector('.slide-stage').append(mirror);
        }

        // Menampilkan foto terpilih dan menyelaraskan caption serta indikator navigasi.
        function show(next, announce = false) {
            current = (next + photos.length) % photos.length;
            photos.forEach((photo, i) => {
                const previous =
                    i === (current - 1 + photos.length) % photos.length && i !== current;
                const following = i === (current + 1) % photos.length && i !== current;
                photo.classList.toggle('is-current', i === current);
                photo.classList.toggle('is-previous', previous && !following);
                photo.classList.toggle('is-next', following);
                photo.setAttribute('aria-hidden', String(i !== current));
            });
            if (mirror) {
                const other = photos[1 - current].querySelector('img');
                const image = mirror.querySelector('img');
                image.src = other.src;
                applySlideDisplay(image, JSON.parse(other.dataset.display || '{}'));
                mirror.className = 'hospital-slide is-previous';
            }
            dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === current)));
            if (announce)
                root.querySelector('[data-slide-status]').textContent =
                    `Foto ${current + 1} dari ${photos.length}`;
        }

        // Menjalankan atau menghentikan pergantian otomatis sesuai interaksi pengguna.
        function schedule() {
            clearInterval(timer);
            if (photos.length > 1 && !reduced.matches && !hovered && !focused && !document.hidden) {
                timer = setInterval(() => show(current + 1), 6500);
            }
        }

        // Memindahkan foto aktif lalu mengatur ulang waktu pergantian otomatis.
        function navigate(index) {
            show(index, true);
            schedule();
        }

        root.querySelector('[data-prev]')?.addEventListener('click', () => navigate(current - 1));
        root.querySelector('[data-next]')?.addEventListener('click', () => navigate(current + 1));
        dots.forEach((dot, i) => dot.addEventListener('click', () => navigate(i)));
        photos.forEach((photo, i) => photo.addEventListener('click', () => navigate(i)));
        mirror?.addEventListener('click', () => navigate(current - 1));
        root.addEventListener('mouseenter', () => {
            hovered = true;
            schedule();
        });
        root.addEventListener('mouseleave', () => {
            hovered = false;
            schedule();
        });
        root.addEventListener('focusin', () => {
            focused = true;
            schedule();
        });
        root.addEventListener('focusout', () => {
            setTimeout(() => {
                focused = root.contains(document.activeElement);
                schedule();
            }, 0);
        });
        root.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            navigate(current + (event.key === 'ArrowRight' ? 1 : -1));
        });
        let startX;
        root.addEventListener(
            'touchstart',
            (event) => {
                startX = event.touches[0].clientX;
            },
            { passive: true },
        );
        root.addEventListener(
            'touchend',
            (event) => {
                if (startX === undefined) return;
                const delta = event.changedTouches[0].clientX - startX;
                if (Math.abs(delta) > 55) navigate(current + (delta < 0 ? 1 : -1));
                startX = undefined;
            },
            { passive: true },
        );
        document.addEventListener('visibilitychange', schedule);
        reduced.addEventListener('change', schedule);
        show(0);
        schedule();
    });
});
