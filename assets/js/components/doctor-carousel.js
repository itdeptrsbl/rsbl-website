// Menggeser deretan kartu dokter di beranda.
document.querySelectorAll('[data-doctor-carousel]').forEach((section) => {
    const track = section.querySelector('.doctor-carousel-track');
    const previous = section.querySelector('[data-doctor-prev]');
    const next = section.querySelector('[data-doctor-next]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Menyesuaikan kondisi panah dengan posisi deretan kartu dokter.
    function update() {
        const overflow = track.scrollWidth > track.clientWidth + 2;
        next.hidden = !overflow;
        previous.hidden = !overflow || track.scrollLeft <= 2;
        previous.disabled = track.scrollLeft <= 2;
        next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
    }

    // Menggeser tampilan sesuai arah yang dipilih pengguna.
    function move(direction) {
        const card = track.querySelector('.doctor-card');
        if (!card) return;
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        track.scrollBy({
            left: direction * (card.getBoundingClientRect().width + gap),
            behavior: reduced.matches ? 'instant' : 'smooth',
        });
    }

    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    track.addEventListener('scroll', update, { passive: true });
    track.addEventListener('keydown', (event) => {
        if (event.target !== track || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        move(event.key === 'ArrowRight' ? 1 : -1);
    });
    if ('ResizeObserver' in window) new ResizeObserver(update).observe(track);
    else window.addEventListener('resize', update);
    update();

    // Animasi sekali saat bagian masuk layar; konten tetap terlihat tanpa JavaScript.
    if ('IntersectionObserver' in window && !reduced.matches) {
        const observer = new IntersectionObserver(
            (entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) return;
                section.classList.add('is-revealed');
                observer.disconnect();
            },
            { threshold: 0.12 },
        );
        section.classList.add('reveal-ready');
        observer.observe(section);
        section.addEventListener('focusin', () => section.classList.add('is-revealed'), {
            once: true,
        });
    }
});
