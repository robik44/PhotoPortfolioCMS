(() => {
    const dialog = document.getElementById('photo-library-dialog');
    if (!dialog) return;
    const cards = Array.from(dialog.querySelectorAll('[data-library-photo]'));
    const search = dialog.querySelector('[data-library-search]');
    const none = dialog.querySelector('[data-library-none]');
    const confirm = dialog.querySelector('[data-library-confirm]');
    const status = dialog.querySelector('[data-library-status]');
    let activePicker, trigger, draft;

    function refresh() {
        const query = search.value.trim().toLocaleLowerCase('pl');
        cards.forEach(card => {
            card.hidden = !card.dataset.search.toLocaleLowerCase('pl').includes(query);
            card.setAttribute('aria-pressed', String(card.dataset.libraryPhoto === draft));
        });
        dialog.querySelector('[data-library-empty]').hidden = cards.some(card => !card.hidden);
        none.setAttribute('aria-pressed', String(draft === ''));
        confirm.disabled = draft === null;
        status.textContent = draft === null ? 'Wybierz zdjęcie.' : (draft === '' ? none.textContent : 'Wybrano zdjęcie.');
    }

    function commit(picker, id) {
        const card = cards.find(item => item.dataset.libraryPhoto === id);
        const input = picker.querySelector('[data-photo-value]');
        input.value = card ? id : '';
        const clear = picker.querySelector('[data-photo-clear]');
        if (clear) clear.value = card ? '0' : '1';
        const preview = picker.querySelector('[data-photo-preview]');
        const img = picker.querySelector('[data-photo-image]');
        if (card) {
            const source = card.querySelector('img');
            img.src = source.src;
            img.alt = source.alt;
        } else img.removeAttribute('src');
        picker.querySelector('[data-photo-caption]').textContent = card?.querySelector('[data-library-caption]').textContent || '';
        preview.hidden = !card;
        picker.querySelector('[data-photo-remove]').hidden = !card;
        picker.querySelector('[data-photo-empty]').hidden = !!card;
        picker.querySelector('[data-photo-open]').textContent = card ? 'Zmień' : 'Wybierz z Biblioteki';
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.querySelectorAll('[data-photo-picker]').forEach(picker => {
        picker.querySelector('[data-photo-open]').addEventListener('click', event => {
            activePicker = picker;
            trigger = event.currentTarget;
            const current = picker.querySelector('[data-photo-value]').value;
            // A legacy file has no Photo ID: merely opening/closing must retain it.
            draft = cards.some(card => card.dataset.libraryPhoto === current) ? current : null;
            search.value = '';
            none.textContent = picker.dataset.fallback === '1' ? 'Brak / użyj domyślnego' : 'Brak zdjęcia';
            refresh();
            dialog.showModal();
            search.focus();
        });
        picker.querySelector('[data-photo-remove]').addEventListener('click', () => commit(picker, ''));
    });
    cards.forEach(card => card.addEventListener('click', () => { draft = card.dataset.libraryPhoto; refresh(); }));
    none.addEventListener('click', () => { draft = ''; refresh(); });
    search.addEventListener('input', refresh);
    // The dialog can be inside the editing form; Enter in search must not save it.
    search.addEventListener('keydown', event => { if (event.key === 'Enter') event.preventDefault(); });
    dialog.querySelectorAll('[data-library-cancel]').forEach(button => button.addEventListener('click', () => dialog.close()));
    confirm.addEventListener('click', () => {
        if (draft === null) return;
        commit(activePicker, draft);
        dialog.close();
    });
    dialog.addEventListener('close', () => { draft = null; trigger?.focus(); });
})();
