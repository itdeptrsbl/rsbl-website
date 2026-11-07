// Menambah dan menghapus sesi praktik pada formulir dokter.
document.querySelectorAll('[data-schedule-editor]').forEach((editor) => {
    const list = editor.querySelector('[data-session-list]');
    const template = editor.querySelector('[data-session-template]');
    const add = editor.querySelector('[data-add-session]');
    const message = editor.querySelector('[data-session-message]');
    let next = list.children.length;

    // Mengatur tombol tambah dan hapus agar jumlah sesi tetap valid.
    function update() {
        const count = list.children.length;
        add.disabled = count >= 14;
        list.querySelectorAll('[data-remove-session]').forEach((button) => {
            button.disabled = false;
        });
    }

    add.addEventListener('click', () => {
        if (list.children.length >= 14) return;
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replace('__INDEX__', next);
        });
        next++;
        list.append(fragment);
        list.lastElementChild.querySelector('input').focus();
        message.textContent = 'Sesi praktik ditambahkan.';
        update();
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-session]');
        if (!button) return;
        button.closest('[data-session]').remove();
        message.textContent = 'Sesi praktik dihapus. Simpan dokter untuk menerapkan perubahan.';
        update();
    });
    update();
});
