// Pergantian slide, panah, titik navigasi, dan pengaturan animasi yang ramah pengguna.
// Slideshow berhenti saat diarahkan, mendapat fokus, atau tab disembunyikan.
document.querySelectorAll('[data-slideshow]').forEach((root) => {
    const slides = [...root.querySelectorAll('[data-slide]')];
    if (slides.length < 2) return;

    const dots = [...root.querySelectorAll('[data-slide-to]')];
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    let index = 0;
    let hovered = false;
    let focused = false;
    let timer = null;

    // Menampilkan foto terpilih dan menyelaraskan caption serta indikator navigasi.
    function show(next, announce = false) {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.hidden = i !== index;
            slide.classList.remove('is-current');
        });
        // Terapkan kondisi awal dahulu agar animasi selalu dimulai ulang.
        const currentSlide = slides[index];
        if (!reduced.matches) void currentSlide.offsetWidth;
        currentSlide.classList.add('is-current');

        dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === index)));
        if (announce) {
            root.querySelector('[data-slide-status]').textContent =
                `Foto ${index + 1} dari ${slides.length}`;
        }
    }

    // Menjalankan atau menghentikan pergantian otomatis sesuai interaksi pengguna.
    function schedule() {
        clearInterval(timer);
        if (!reduced.matches && !hovered && !focused && !document.hidden) {
            timer = setInterval(() => show(index + 1), 6500);
        }
    }

    root.querySelector('[data-prev]').addEventListener('click', () => {
        show(index - 1, true);
        schedule();
    });
    root.querySelector('[data-next]').addEventListener('click', () => {
        show(index + 1, true);
        schedule();
    });
    dots.forEach((dot, i) =>
        dot.addEventListener('click', () => {
            show(i, true);
            schedule();
        }),
    );
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
    document.addEventListener('visibilitychange', schedule);
    reduced.addEventListener('change', schedule);

    let startX = null;
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
            if (startX === null) return;
            const delta = event.changedTouches[0].clientX - startX;
            if (Math.abs(delta) > 55) {
                show(index + (delta < 0 ? 1 : -1), true);
                schedule();
            }
            startX = null;
        },
        { passive: true },
    );
    schedule();
});
