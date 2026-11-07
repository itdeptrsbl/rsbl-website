// Animasi masuk bagian visi sesuai preferensi pengurangan gerak.
// Konten tetap terlihat tanpa JavaScript; animasi berjalan sekali saat masuk layar.
const visionRoot = document.querySelector('.vision-page');
if (
    visionRoot &&
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
                    const isItem = entry.target.matches('li, .vision-commitments section');
                    const index = isItem
                        ? [...entry.target.parentElement.children].indexOf(entry.target) % 4
                        : 0;
                    const animation = entry.target.animate(
                        [
                            { opacity: 0, transform: 'translateY(20px)' },
                            { opacity: 1, transform: 'translateY(0)' },
                        ],
                        {
                            duration: 650,
                            delay: index * 85,
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
            { threshold: 0.12 },
        );
        visionRoot
            .querySelectorAll(
                '.page-intro, .vision-statement, .vision-section > h2, .vision-missions li, .vision-commitments section, .vision-values li',
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
