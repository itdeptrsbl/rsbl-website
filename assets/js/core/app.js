// Interaksi dasar: menu ponsel, pencarian dokter, popup, dan konfirmasi hapus.
// JavaScript menambah interaksi; navigasi dan formulir tetap berjalan tanpa JS.
const toggle = document.querySelector('.menu-toggle');
if (toggle)
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        document.querySelector('#navigation').classList.toggle('open', open);
    });
const search = document.querySelector('#doctor-search');
if (search)
    search.addEventListener('input', () => {
        let count = 0;
        document.querySelectorAll('[data-search]').forEach((card) => {
            card.hidden = !card.dataset.search.includes(search.value.toLowerCase().trim());
            if (!card.hidden) count++;
        });
        document.querySelector('#no-doctors').hidden = count > 0;
    });
// Popup hanya muncul sekali per versi konten dan per tab, agar tidak mengganggu pasien.
const dialog = document.querySelector('#promo-dialog');
if (dialog) {
    const key = 'rsbl-popup-' + dialog.dataset.key;
    let seen = false;
    try {
        seen = sessionStorage.getItem(key) === '1';
    } catch (e) {}
    if (!seen && typeof dialog.showModal === 'function') {
        dialog.showModal();
        try {
            sessionStorage.setItem(key, '1');
        } catch (e) {}
    }
    dialog.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            const r = dialog.getBoundingClientRect();
            if (
                event.clientX < r.left ||
                event.clientX > r.right ||
                event.clientY < r.top ||
                event.clientY > r.bottom
            )
                dialog.close();
        }
    });
}
// Konfirmasi penghapusan mencegah klik yang tidak disengaja.
document.querySelectorAll('form[data-confirm]').forEach((form) =>
    form.addEventListener('submit', (event) => {
        if (!confirm(form.dataset.confirm)) event.preventDefault();
    }),
);
