(() => {
    'use strict';

    const form = document.getElementById('header-settings-form');
    if (!form) return;

    const families = JSON.parse(form.dataset.fontFamilies);
    const preview = document.getElementById('header-live-preview');
    const status = document.getElementById('header-font-status');
    const fileInput = form.elements.namedItem('font_file');
    const targetInput = form.elements.namedItem('font_target');
    const clearButton = document.getElementById('header-clear-font');
    let localFace = null;
    let loadVersion = 0;

    const value = (name) => form.elements.namedItem(name).value;
    const number = (name, fallback, min, max) => {
        const parsed = Number(value(name));
        return Number.isFinite(parsed) ? Math.min(max, Math.max(min, parsed)) : fallback;
    };

    function render() {
        for (const part of ['logo', 'subtitle']) {
            const prefix = `header_${part}_`;
            const node = document.getElementById(`header-preview-${part}`);
            const selected = value(`${prefix}font_family`);
            const useLocal = localFace && [part, 'both'].includes(targetInput.value);
            node.textContent = value(part === 'logo' ? 'logo' : 'logo_subtitle');
            node.style.fontFamily = useLocal
                ? `${localFace.family}, Arial, sans-serif`
                : Object.hasOwn(families, selected) ? families[selected] : families.Arial;
            node.style.fontSize = `${number(`${prefix}font_size`, part === 'logo' ? 28 : 10, 8, part === 'logo' ? 96 : 48)}px`;
            node.style.fontWeight = String(number(`${prefix}font_weight`, part === 'logo' ? 700 : 400, 100, 900));
            const color = value(`${prefix}color`);
            node.style.color = /^#[0-9a-f]{6}$/i.test(color) ? color : (part === 'logo' ? '#222222' : '#777777');
            node.style.letterSpacing = `${number(`${prefix}letter_spacing`, 0, 0, 1)}em`;
        }
        const layout = value('header_layout');
        preview.dataset.layout = ['left', 'center', 'right'].includes(layout) ? layout : 'left';
        preview.style.paddingTop = `${number('header_padding_top', 48, 0, 160)}px`;
        preview.style.paddingBottom = `${number('header_padding_bottom', 48, 0, 160)}px`;
        preview.style.setProperty('--header-logo-subtitle-gap', `${number('header_logo_subtitle_gap', 4, 0, 80)}px`);
    }

    async function loadLocalFont() {
        const version = ++loadVersion;
        if (localFace) document.fonts.delete(localFace);
        localFace = null;
        fileInput.setCustomValidity('');
        const file = fileInput.files[0];
        clearButton.hidden = !file;
        status.textContent = '';
        render();
        if (!file) return;

        if (!/\.(woff2?|ttf|otf)$/i.test(file.name) || file.size > 5 * 1024 * 1024) {
            status.textContent = 'Wybierz plik WOFF2, WOFF, TTF lub OTF o rozmiarze do 5 MB.';
            fileInput.setCustomValidity(status.textContent);
            return;
        }
        if (!window.FontFace || !document.fonts) {
            status.textContent = 'Ta przeglądarka nie obsługuje lokalnego podglądu czcionki. Możesz zapisać formularz.';
            return;
        }
        status.textContent = 'Ładowanie czcionki do podglądu…';
        try {
            const buffer = await file.arrayBuffer();
            if (version !== loadVersion) return;
            const face = await new FontFace(`header_preview_${version}`, buffer).load();
            if (version !== loadVersion) return;
            document.fonts.add(face);
            localFace = face;
            status.textContent = 'Nowa czcionka jest widoczna w podglądzie dla wybranego zastosowania. Plik nie został jeszcze zapisany.';
            render();
        } catch {
            if (version !== loadVersion) return;
            status.textContent = 'Przeglądarka nie może odczytać tej czcionki. Wybierz inny plik.';
            fileInput.setCustomValidity(status.textContent);
        }
    }

    form.addEventListener('input', render);
    form.addEventListener('change', render);
    fileInput.addEventListener('change', loadLocalFont);
    for (const part of ['logo', 'subtitle']) {
        form.elements.namedItem(`header_${part}_font_family`).addEventListener('change', () => {
            if (fileInput.files[0] && [part, 'both'].includes(targetInput.value)) {
                fileInput.value = '';
                loadLocalFont();
            }
        });
    }
    clearButton.addEventListener('click', () => {
        fileInput.value = '';
        loadLocalFont();
    });
    render();
})();
