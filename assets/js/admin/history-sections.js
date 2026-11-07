// Menyesuaikan isian yang muncul berdasarkan jenis konten sejarah.
document.querySelectorAll('[data-history-editor]').forEach((editor) => {
    const select = editor.querySelector('[data-history-type]');
    // Menampilkan field yang sesuai dengan bagian sejarah yang dipilih.
    function update() {
        editor.querySelectorAll('[data-history-for]').forEach((group) => {
            group.hidden = !group.dataset.historyFor.split(',').includes(select.value);
        });
    }
    select.addEventListener('change', update);
    update();
});
