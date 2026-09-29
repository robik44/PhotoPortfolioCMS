(() => {
    window.BuilderButton = {
        apply(node, item) {
            for (const [key, property] of [['background', 'backgroundColor'], ['border_color', 'borderColor']]) {
                if (item[`button_${key}`]) node.style[property] = item[`button_${key}`];
            }
            for (const [key, properties] of Object.entries({border_width: ['borderWidth'], radius: ['borderRadius'], padding_y: ['paddingTop', 'paddingBottom'], padding_x: ['paddingLeft', 'paddingRight']})) {
                const value = item[`button_${key}`];
                if (value === undefined || value === null || value === '') continue;
                if (key === 'border_width') node.style.borderStyle = 'solid';
                for (const property of properties) node.style[property] = `${value}px`;
            }
        },
        fields(container, item, render, className) {
            if (item.type !== 'button') return;
            const add = (label, key, type, fallback = '') => {
                const wrapper = document.createElement('label');
                wrapper.className = className;
                wrapper.textContent = label;
                const input = document.createElement('input');
                input.type = type;
                input.setAttribute('aria-label', label);
                input.value = item[key] ?? fallback;
                if (type === 'number') { input.min = '0'; input.max = '200'; }
                input.addEventListener('input', () => {
                    if (type === 'number' && input.value === '') delete item[key];
                    else item[key] = type === 'number' ? Math.max(0, Math.min(200, Number(input.value))) : input.value;
                    render();
                });
                wrapper.appendChild(input);
                container.appendChild(wrapper);
                return input;
            };
            const link = add('Akcja / link', 'button_link', 'text');
            const targets = window.builderButtonTargets || [];
            if (targets.length) {
                const wrapper = document.createElement('label');
                wrapper.className = className;
                wrapper.textContent = 'Wybierz stronę lub galerię';
                const select = document.createElement('select');
                for (const target of [{url: '', label: 'Wybierz…'}, ...targets]) {
                    const option = document.createElement('option');
                    option.value = target.url; option.textContent = target.label; select.appendChild(option);
                }
                select.addEventListener('change', () => {
                    if (!select.value) return;
                    item.button_link = select.value; link.value = select.value; render();
                });
                wrapper.appendChild(select); container.appendChild(wrapper);
            }
            const wrapper = document.createElement('label');
            wrapper.className = className;
            wrapper.textContent = 'Otwórz w nowej karcie';
            const select = document.createElement('select');
            for (const [value, label] of [['0', 'NIE'], ['1', 'TAK']]) {
                const option = document.createElement('option'); option.value = value; option.textContent = label; select.appendChild(option);
            }
            select.value = item.button_new_tab ? '1' : '0';
            select.addEventListener('change', () => { item.button_new_tab = select.value === '1'; render(); });
            wrapper.appendChild(select); container.appendChild(wrapper);
            add('Kolor tła przycisku', 'button_background', 'color', '#222222');
            add('Kolor obramowania', 'button_border_color', 'color', '#222222');
            add('Grubość obramowania (px)', 'button_border_width', 'number');
            add('Zaokrąglenie narożników (px)', 'button_radius', 'number');
            add('Odstęp wewnętrzny pionowy (px)', 'button_padding_y', 'number');
            add('Odstęp wewnętrzny poziomy (px)', 'button_padding_x', 'number');
        },
    };
})();
