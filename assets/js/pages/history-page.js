// Interaksi tampilan sejarah dan struktur organisasi.
// Animasi sekali saat terlihat; isi tetap tampil tanpa JavaScript.
const historyRoot = document.querySelector('.history-page');
if (
    historyRoot &&
    'IntersectionObserver' in window &&
    typeof Element.prototype.animate === 'function'
) {
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    if (!reduced.matches) {
        const animations = new Set();
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    observer.unobserve(entry.target);
                    if (reduced.matches) return;
                    const isLeader = entry.target.classList.contains('history-leader');
                    const index = isLeader
                        ? [...entry.target.parentElement.children].indexOf(entry.target) % 4
                        : 0;
                    const animation = entry.target.animate(
                        [
                            { opacity: 0, transform: 'translateY(16px)' },
                            { opacity: 1, transform: 'translateY(0)' },
                        ],
                        {
                            duration: 550,
                            delay: index * 65,
                            easing: 'cubic-bezier(.2,.65,.3,1)',
                            fill: 'backwards',
                        },
                    );
                    animations.add(animation);
                    animation.finished.then(
                        () => animations.delete(animation),
                        () => animations.delete(animation),
                    );
                });
            },
            { threshold: 0.08 },
        );
        historyRoot
            .querySelectorAll(
                '.history-intro, .history-video-section, #history-leaders-heading, .history-leader, .history-organization',
            )
            .forEach((element) => observer.observe(element));
        reduced.addEventListener('change', () => {
            if (!reduced.matches) return;
            observer.disconnect();
            animations.forEach((animation) => animation.cancel());
            animations.clear();
        });
    }
}
