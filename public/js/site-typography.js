(() => {
    'use strict';
    window.SiteTypography = {
        create(catalog) {
            const textTypes = new Set(catalog.textTypes);
            const css = (id) => typeof id === 'string' && Object.hasOwn(catalog.families, id)
                ? catalog.families[id] : catalog.families.Arial;
            return {
                css,
                captionFields(container, item, render, className) {
                    if (!['image', 'gallery'].includes(item.type)) return;
                    const add = (labelText, input) => {
                        const wrapper = document.createElement('label');
                        wrapper.className = className;
                        wrapper.textContent = labelText;
                        input.setAttribute('aria-label', labelText);
                        wrapper.appendChild(input);
                        container.appendChild(wrapper);
                    };
                    if (item.type === 'image') {
                        const text = document.createElement('textarea');
                        text.value = item.caption ?? '';
                        text.addEventListener('input', () => { item.caption = text.value; render(); });
                        add('Podpis / opis zdjęcia', text);
                    }
                    const prefix = item.type === 'gallery' ? 'Opis zdjęcia w podglądzie' : 'Podpis zdjęcia';
                    const select = document.createElement('select');
                    for (const choice of [{ value: '', label: 'Domyślna (bez zmiany)' }, ...catalog.choices]) {
                        const option = document.createElement('option');
                        option.value = choice.value;
                        option.textContent = choice.label;
                        select.appendChild(option);
                    }
                    select.value = item.caption_font_family ?? '';
                    select.addEventListener('change', () => {
                        if (select.value) item.caption_font_family = select.value;
                        else delete item.caption_font_family;
                        render();
                    });
                    add(`${prefix} — rodzaj czcionki`, select);
                    const size = document.createElement('input');
                    size.type = 'number';
                    size.min = '1';
                    size.max = '200';
                    size.placeholder = 'Domyślny (bez zmiany)';
                    size.value = item.caption_font_size ?? '';
                    size.addEventListener('input', () => {
                        if (size.value === '') delete item.caption_font_size;
                        else item.caption_font_size = Math.max(1, Math.min(200, Number(size.value) || 1));
                        render();
                    });
                    add(`${prefix} — rozmiar czcionki (px)`, size);
                },
                imageCaption(container, item) {
                    if (item.type !== 'image' || !item.photo_url || !item.caption?.trim()) return;
                    const caption = document.createElement('div');
                    caption.textContent = item.caption;
                    caption.style.cssText = 'font:16px Arial,sans-serif;color:#222;letter-spacing:normal;text-align:left;white-space:pre-line;';
                    if (item.caption_font_family) caption.style.fontFamily = css(item.caption_font_family);
                    if (item.caption_font_size) caption.style.fontSize = `${item.caption_font_size}px`;
                    container.appendChild(caption);
                },
                isText: (item) => textTypes.has(item.type),
                initialize(item) {
                    if (!textTypes.has(item.type)) return;
                    item.style ??= {};
                    item.style.font_family ??= catalog.defaults[item.type === 'heading' ? 'site_heading_font_family' : 'site_body_font_family'];
                },
                apply(node, item) {
                    if (textTypes.has(item.type)) node.style.fontFamily = css(item.style?.font_family);
                },
                field(container, item, render, className) {
                    if (!textTypes.has(item.type)) return;
                    const wrapper = document.createElement('div');
                    wrapper.className = className;
                    const label = document.createElement('label');
                    label.textContent = 'Rodzaj czcionki';
                    const select = document.createElement('select');
                    select.setAttribute('aria-label', 'Rodzaj czcionki');
                    for (const choice of catalog.choices) {
                        const option = document.createElement('option');
                        option.value = choice.value;
                        option.textContent = choice.label;
                        option.style.fontFamily = css(choice.value);
                        select.appendChild(option);
                    }
                    const id = item.style?.font_family;
                    select.value = typeof id === 'string' && Object.hasOwn(catalog.families, id) ? id : 'Arial';
                    const update = () => {
                        if (!Object.hasOwn(catalog.families, select.value)) return;
                        item.style ??= {};
                        item.style.font_family = select.value;
                        render();
                    };
                    select.addEventListener('input', update);
                    select.addEventListener('change', update);
                    label.appendChild(select);
                    wrapper.appendChild(label);
                    container.appendChild(wrapper);
                },
            };
        },
    };
})();
