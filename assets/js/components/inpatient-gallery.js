// Galeri foto manual dengan panah, thumbnail, dan caption yang mengikuti foto.
// Galeri manual per kamar: tidak bergerak sendiri agar detail mudah dibandingkan.
document.querySelectorAll('[data-room-gallery]').forEach((gallery) => {
    const buttons = [...gallery.querySelectorAll('[data-room-photo]')];
    if (buttons.length < 2) return;
    const main = gallery.querySelector('[data-room-main]');
    const link = gallery.querySelector('[data-room-main-link]');
    const status = gallery.querySelector('[data-room-status]');
    const caption = gallery.querySelector('[data-room-caption]');
    const originalAlt = main.alt;
    let index = 0;

    // Menampilkan foto terpilih dan menyelaraskan caption serta indikator navigasi.
    function show(next) {
        index = (next + buttons.length) % buttons.length;
        const selected = buttons[index];
        main.src = selected.dataset.src;
        main.alt = selected.dataset.caption || originalAlt;
        caption.textContent = selected.dataset.caption || '';
        caption.hidden = !caption.textContent;
        link.href = selected.dataset.src;
        applySlideDisplay(main, JSON.parse(selected.dataset.display));
        buttons.forEach((button, i) => button.setAttribute('aria-pressed', String(i === index)));
        status.textContent = `Foto ${index + 1} dari ${buttons.length}`;
        const track = selected.parentElement;
        const left = selected.offsetLeft - track.offsetLeft;
        if (left < track.scrollLeft) track.scrollLeft = left;
        if (left + selected.offsetWidth > track.scrollLeft + track.clientWidth) {
            track.scrollLeft = left + selected.offsetWidth - track.clientWidth;
        }
    }

    buttons.forEach((button, i) => button.addEventListener('click', () => show(i)));
    gallery.querySelector('[data-room-prev]').addEventListener('click', () => show(index - 1));
    gallery.querySelector('[data-room-next]').addEventListener('click', () => show(index + 1));
    gallery.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        show(index + (event.key === 'ArrowRight' ? 1 : -1));
    });
});
