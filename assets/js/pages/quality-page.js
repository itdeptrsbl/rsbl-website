// Membuka dan menutup gambar laporan mutu dalam tampilan perbesar.
const qualityRoot = document.querySelector('.quality-page');
if (qualityRoot) {
    const dialog = qualityRoot.querySelector('.quality-dialog');
    if (dialog && typeof dialog.showModal === 'function') {
        const image = dialog.querySelector('img');
        const pane = dialog.querySelector('.quality-dialog-image');
        const zoom = dialog.querySelector('[data-quality-zoom]');
        let opener;
        qualityRoot.querySelectorAll('[data-quality-report]').forEach((link) =>
            link.addEventListener('click', (event) => {
                if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                event.preventDefault();
                opener = link;
                dialog.querySelector('h2').textContent = link.dataset.reportTitle;
                image.src = link.href;
                image.alt = link.dataset.reportTitle;
                dialog.querySelector('[data-quality-download]').href = link.href;
                pane.classList.remove('is-zoomed');
                zoom.setAttribute('aria-pressed', 'false');
                zoom.textContent = 'Perbesar gambar';
                dialog.showModal();
                pane.scrollTop = pane.scrollLeft = 0;
            }),
        );
        dialog
            .querySelector('[data-quality-close]')
            .addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => opener?.focus());
        dialog.addEventListener('click', (event) => {
            const bounds = dialog.getBoundingClientRect();
            if (
                event.target === dialog &&
                (event.clientX < bounds.left ||
                    event.clientX > bounds.right ||
                    event.clientY < bounds.top ||
                    event.clientY > bounds.bottom)
            )
                dialog.close();
        });
        zoom.addEventListener('click', () => {
            const expanded = pane.classList.toggle('is-zoomed');
            zoom.setAttribute('aria-pressed', String(expanded));
            zoom.textContent = expanded ? 'Sesuaikan layar' : 'Perbesar gambar';
        });
    }
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    if (
        !reduced.matches &&
        'IntersectionObserver' in window &&
        typeof Element.prototype.animate === 'function'
    ) {
        const animations = new Set();
        const observer = new IntersectionObserver(
            (entries) =>
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    observer.unobserve(entry.target);
                    if (reduced.matches) return;
                    const animation = entry.target.animate(
                        [
                            { opacity: 0, transform: 'translateY(18px)' },
                            { opacity: 1, transform: 'translateY(0)' },
                        ],
                        { duration: 550, easing: 'cubic-bezier(.2,.65,.3,1)' },
                    );
                    animations.add(animation);
                    animation.finished.then(
                        () => animations.delete(animation),
                        () => animations.delete(animation),
                    );
                }),
            { threshold: 0.1 },
        );
        qualityRoot
            .querySelectorAll('.quality-gallery-heading, .quality-card')
            .forEach((card) => observer.observe(card));
        reduced.addEventListener('change', () => {
            if (!reduced.matches) return;
            observer.disconnect();
            animations.forEach((animation) => animation.cancel());
        });
    }
}
