// Menambah dan menghapus poin misi serta nilai-nilai rumah sakit.
document.querySelectorAll('[data-vision-list]').forEach((editor) => {
    const list = editor.querySelector('[data-vision-items]');
    const add = editor.querySelector('[data-add-point]');
    const status = editor.querySelector('[data-list-status]');
    // Menyelaraskan daftar isian visi dengan jumlah poin yang masih ada.
    const update = () => {
        add.disabled = list.children.length >= 50;
    };
    add.addEventListener('click', () => {
        if (list.children.length >= 50) return;
        list.append(editor.querySelector('template').content.cloneNode(true));
        list.lastElementChild.querySelector('textarea').focus();
        status.textContent = 'Poin ditambahkan. Klik Simpan semua untuk menerapkan.';
        update();
    });
    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-point]');
        if (!button) return;
        button.closest('.vision-editor-row').remove();
        status.textContent = 'Poin dihapus. Klik Simpan semua untuk menerapkan.';
        update();
    });
    update();
});
