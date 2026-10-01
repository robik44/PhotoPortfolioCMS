(() => {
    window.BuilderButton = {
        defaults: {
            button_background: '#222222',
            button_background_opacity: 100,
            button_border_color: '#222222',
            button_border_width: 0,
            button_radius: 4,
            button_padding_y: 13,
            button_padding_x: 24,
        },
        initialize(item) {
            return item;
        },
        value(item, key) {
            const value = item[key];
            return value === undefined || value === null || value === '' ? this.defaults[key] : value;
        },
        apply(node, item) {
            this.initialize(item);
            for (const [key, property] of [['background', 'backgroundColor'], ['border_color', 'borderColor']]) {
                const value = this.value(item, `button_${key}`);
                if (value !== undefined && value !== null && value !== '') node.style[property] = value;
            }
            const background = this.value(item, 'button_background');
            const hasExplicitOpacity = item.button_background_opacity !== undefined && item.button_background_opacity !== null && item.button_background_opacity !== '';
            const opacity = this.value(item, 'button_background_opacity');
            if (background && hasExplicitOpacity) {
                const hex = background.replace('#', '');
                const rgb = [0, 2, 4].map(offset => parseInt(hex.slice(offset, offset + 2), 16));
                const alpha = Math.max(0, Math.min(100, Number(opacity))) / 100;
                node.style.backgroundColor = `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${alpha})`;
            }
            for (const [key, properties] of Object.entries({border_width: ['borderWidth'], radius: ['borderRadius'], padding_y: ['paddingTop', 'paddingBottom'], padding_x: ['paddingLeft', 'paddingRight']})) {
                const value = this.value(item, `button_${key}`);
                if (value === undefined || value === null || value === '') continue;
                if (key === 'border_width') node.style.borderStyle = 'solid';
                for (const property of properties) node.style[property] = `${value}px`;
            }
        },
        fields(container, item, render, className) {
            if (item.type !== 'button') return;
            this.initialize(item);
            const add = (label, key, type, fallback = '') => {
                const wrapper = document.createElement('label');
                wrapper.className = className;
                wrapper.textContent = label;
                const input = document.createElement('input');
                input.type = type;
                input.setAttribute('aria-label', label);
                input.value = item[key] ?? this.value(item, key) ?? fallback;
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
            const opacity = add('Przezroczystość tła (%) — 100 = pełne', 'button_background_opacity', 'number', '100');
            opacity.max = '100';
            add('Kolor obramowania', 'button_border_color', 'color', '#222222');
            add('Grubość obramowania (px)', 'button_border_width', 'number');
            add('Zaokrąglenie narożników (px)', 'button_radius', 'number');
            add('Odstęp wewnętrzny pionowy (px)', 'button_padding_y', 'number');
            add('Odstęp wewnętrzny poziomy (px)', 'button_padding_x', 'number');
        },
    };
})();
