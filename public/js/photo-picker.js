(() => {
    const dialog = document.getElementById('photo-library-dialog');
    if (!dialog) return;
    const cards = Array.from(dialog.querySelectorAll('[data-library-photo]'));
    const search = dialog.querySelector('[data-library-search]');
    const none = dialog.querySelector('[data-library-none]');
    const confirm = dialog.querySelector('[data-library-confirm]');
    const status = dialog.querySelector('[data-library-status]');
    let activePicker, trigger, draft, multiple = false, onConfirm;

    function refresh() {
        const query = search.value.trim().toLocaleLowerCase('pl');
        cards.forEach(card => {
            card.hidden = !card.dataset.search.toLocaleLowerCase('pl').includes(query);
            card.setAttribute('aria-pressed', String(multiple ? draft.includes(card.dataset.libraryPhoto) : card.dataset.libraryPhoto === draft));
        });
        dialog.querySelector('[data-library-empty]').hidden = cards.some(card => !card.hidden);
        none.setAttribute('aria-pressed', String(multiple ? draft.length === 0 : draft === ''));
        confirm.disabled = draft === null;
        status.textContent = multiple ? `Wybrano zdjęć: ${draft.length}` : (draft === null ? 'Wybierz zdjęcie.' : (draft === '' ? none.textContent : 'Wybrano zdjęcie.'));
    }

    // Shared dialog API for dynamic builder fields. The draft never mutates callers.
    function open(options) {
        multiple = options.multiple === true;
        trigger = options.trigger;
        onConfirm = options.onConfirm;
        draft = multiple ? [...new Set((options.selected || []).map(String))] : options.selected;
        search.value = '';
        none.textContent = multiple ? 'Wyczyść zaznaczenie' : options.emptyLabel;
        refresh();
        dialog.showModal();
        search.focus();
    }
    if (typeof window !== 'undefined') window.PhotoLibrary = { open };

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
            const current = picker.querySelector('[data-photo-value]').value;
            // A legacy file has no Photo ID: merely opening/closing must retain it.
            open({
                trigger: event.currentTarget,
                selected: cards.some(card => card.dataset.libraryPhoto === current) ? current : null,
                emptyLabel: picker.dataset.fallback === '1' ? 'Brak / użyj domyślnego' : 'Brak zdjęcia',
                onConfirm: id => commit(activePicker, id),
            });
        });
        picker.querySelector('[data-photo-remove]').addEventListener('click', () => commit(picker, ''));
    });
    cards.forEach(card => card.addEventListener('click', () => {
        const id = card.dataset.libraryPhoto;
        draft = multiple ? (draft.includes(id) ? draft.filter(value => value !== id) : [...draft, id]) : id;
        refresh();
    }));
    none.addEventListener('click', () => { draft = multiple ? [] : ''; refresh(); });
    search.addEventListener('input', refresh);
    // The dialog can be inside the editing form; Enter in search must not save it.
    search.addEventListener('keydown', event => { if (event.key === 'Enter') event.preventDefault(); });
    dialog.querySelectorAll('[data-library-cancel]').forEach(button => button.addEventListener('click', () => dialog.close()));
    confirm.addEventListener('click', () => {
        if (draft === null) return;
        onConfirm(multiple ? draft.map(Number) : draft);
        dialog.close();
    });
    dialog.addEventListener('close', () => { draft = null; trigger?.focus(); });
})();
