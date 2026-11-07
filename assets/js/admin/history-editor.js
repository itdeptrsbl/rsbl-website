// Memberi bullet, nomor, atau teks biasa pada baris editor yang dipilih.
// Tombol format bekerja pada baris terpilih, tanpa menyimpan HTML mentah.
document.querySelectorAll('[data-history-editor]').forEach((editor) => {
    const textarea = editor.querySelector('textarea');
    editor.querySelectorAll('[data-list]').forEach((button) => {
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            const value = textarea.value;
            const start = value.lastIndexOf('\n', textarea.selectionStart - 1) + 1;
            const selectedEnd = textarea.selectionEnd;
            const last =
                selectedEnd > textarea.selectionStart && value[selectedEnd - 1] === '\n'
                    ? selectedEnd - 1
                    : selectedEnd;
            const nextBreak = value.indexOf('\n', last);
            const end = nextBreak === -1 ? value.length : nextBreak;
            const lines = value.slice(start, end).split('\n');
            const formatted = lines
                .map((line, index) => {
                    const clean = line.replace(/^\s*(?:[-*\u2022]|\d+[.)])\s*/, '');
                    const prefix =
                        button.dataset.list === 'plain'
                            ? ''
                            : button.dataset.list === 'number'
                              ? `${index + 1}. `
                              : '\u2022 ';
                    return prefix + clean;
                })
                .join('\n');
            if (value.length - (end - start) + formatted.length > textarea.maxLength) return;
            textarea.setRangeText(formatted, start, end, 'select');
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
});
