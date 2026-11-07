// Panah dan pergeseran deretan foto tambahan Rawat Jalan.
// Foto utama disertai deretan foto yang dapat digeser lewat panah atau sentuhan.
document.querySelectorAll('[data-outpatient-gallery]').forEach((gallery) => {
    const track = gallery.querySelector('[data-gallery-track]');
    if (!track) return;
    const previous = gallery.querySelector('[data-gallery-prev]');
    const next = gallery.querySelector('[data-gallery-next]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Menonaktifkan panah ketika deretan foto sudah mencapai tepi.
    function update() {
        previous.disabled = track.scrollLeft <= 2;
        next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
    }

    // Menggeser tampilan sesuai arah yang dipilih pengguna.
    function move(direction) {
        const photo = track.querySelector('.outpatient-photo');
        const step = photo.getBoundingClientRect().width + 12;
        track.scrollBy({
            left: direction * step,
            behavior: reduced.matches ? 'instant' : 'smooth',
        });
    }

    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    track.addEventListener('scroll', update, { passive: true });
    track.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        move(event.key === 'ArrowLeft' ? -1 : 1);
    });
    new ResizeObserver(update).observe(track);
    update();
});
