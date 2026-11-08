// Animasi sekali saat bagian beranda masuk layar; konten tetap tampil tanpa JS.
(() => {
    const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (preference.matches || !('IntersectionObserver' in window) || !Element.prototype.animate)
        return;
    const animations = new Set();
    const groups = [
        ['.hero-motto', '.hero-intro-description'],
        ['.quick-links > a'],
        ['.home-services .section-heading'],
        ['.home-services .service-card'],
        ['.registration-banner > div > *'],
        ['.home-information .section-heading'],
        ['.home-information .article-card'],
        ['.doctors-intro > *'],
        ['#doctors-list .doctor-card'],
    ];
    const order = new Map();
    groups.forEach((selectors) => {
        document
            .querySelectorAll(selectors.join(','))
            .forEach((element, index) => order.set(element, index));
    });
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(({ target, isIntersecting }) => {
                if (!isIntersecting) return;
                observer.unobserve(target);
                if (preference.matches) return;
                const animation = target.animate(
                    [
                        { opacity: 0, transform: 'translateY(22px)' },
                        { opacity: 1, transform: 'translateY(0)' },
                    ],
                    {
                        duration: 650,
                        delay: Math.min(order.get(target), 3) * 110,
                        easing: 'cubic-bezier(.2,.7,.2,1)',
                        fill: 'backwards',
                    },
                );
                animations.add(animation);
                animation.onfinish = () => animations.delete(animation);
            });
        },
        { threshold: 0.12 },
    );
    order.forEach((index, element) => observer.observe(element));
    preference.addEventListener('change', () => {
        if (!preference.matches) return;
        observer.disconnect();
        animations.forEach((animation) => animation.cancel());
        animations.clear();
    });
})();
