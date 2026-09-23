(() => {
    'use strict';
    window.SiteTypography = {
        create(catalog) {
            const textTypes = new Set(catalog.textTypes);
            const css = (id) => typeof id === 'string' && Object.hasOwn(catalog.families, id)
                ? catalog.families[id] : catalog.families.Arial;
            return {
                css,
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
