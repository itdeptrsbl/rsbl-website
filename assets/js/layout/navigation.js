// Menutup dropdown menu saat klik di luar atau menekan Escape.
// Tutup dropdown saat memilih area lain atau menekan Escape.
document.querySelectorAll('.doctor-menu').forEach((menu) => {
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) menu.open = false;
    });

    menu.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !menu.open) return;
        menu.open = false;
        menu.querySelector('summary').focus();
    });
});
