// Menggeser deretan logo mitra dan memperbarui tombol navigasinya.
document.querySelectorAll('.home-partners').forEach((section) => {
    const track = section.querySelector('.home-partner-logos');
    const previous = section.querySelector('.partner-prev');
    const next = section.querySelector('.partner-next');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    // Menyesuaikan kondisi panah dengan posisi deretan logo mitra.
    function update() {
        previous.hidden = next.hidden = track.scrollWidth <= track.clientWidth + 2;
        previous.disabled = track.scrollLeft <= 2;
        next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
    }
    // Menggeser tampilan sesuai arah yang dipilih pengguna.
    function move(direction) {
        const logo = track.querySelector('figure');
        if (!logo) return;
        const step =
            logo.getBoundingClientRect().width +
            (parseFloat(getComputedStyle(track).columnGap) || 0);
        track.scrollBy({
            left: direction * step,
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
    if ('IntersectionObserver' in window && !reduced.matches) {
        const observer = new IntersectionObserver(
            (entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) return;
                section.classList.add('partner-revealed');
                observer.disconnect();
            },
            { threshold: 0.15 },
        );
        section.classList.add('partner-reveal-ready');
        observer.observe(section);
        section.addEventListener('focusin', () => section.classList.add('partner-revealed'), {
            once: true,
        });
    }
});
