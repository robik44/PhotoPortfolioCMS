(() => {
    const defaults = { photo_ids: [], columns_desktop: 5, columns_tablet: 3, columns_mobile: 2, gap: 20 };
    function initialize(item) {
        Object.assign(item, { ...defaults, photo_ids: [] });
    }

    function preview(item, photos) {
        const settings = { ...defaults, ...item };
        const grid = document.createElement('div');
        grid.className = 'thumbnail-gallery-grid';
        for (const [key, variable] of Object.entries({ columns_desktop: 'desktop', columns_tablet: 'tablet', columns_mobile: 'mobile', gap: 'gap' })) {
            grid.style.setProperty(`--tg-${variable}`, settings[key] + (variable === 'gap' ? 'px' : ''));
        }
        const library = new Map(photos.map(photo => [Number(photo.id), photo]));
        for (const id of item.photo_ids || []) {
            const photo = library.get(Number(id));
            if (!photo) continue;
            const image = document.createElement('img');
            image.src = photo.thumbnail_url || photo.url;
            image.alt = photo.alt || photo.title || '';
            image.draggable = false;
            grid.appendChild(image);
        }
        if (!grid.children.length) {
            const empty = document.createElement('p');
            empty.textContent = 'Wybierz zdjęcia z Biblioteki we właściwościach elementu.';
            empty.style.gridColumn = '1 / -1';
            grid.appendChild(empty);
        }
        return grid;
    }

    function properties(container, item, photos, render, fieldClass) {
        const library = new Map(photos.map(photo => [Number(photo.id), photo]));
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'cms-button';
        button.textContent = 'Wybierz z Biblioteki';
        button.setAttribute('data-thumbnail-select', '');
        const list = document.createElement('ol');
        list.className = 'thumbnail-gallery-selection';
        list.setAttribute('aria-label', 'Wybrane zdjęcia — kolejność');
        let dragging = null;

        function update() { render(); drawList(); }
        function move(from, to) {
            if (from === to || from < 0 || to < 0 || to >= item.photo_ids.length) return;
            const ids = [...item.photo_ids];
            ids.splice(to, 0, ids.splice(from, 1)[0]);
            item.photo_ids = ids;
            update();
        }
        function drawList() {
            list.replaceChildren();
            (item.photo_ids || []).forEach((id, index) => {
                const photo = library.get(Number(id));
                const row = document.createElement('li');
                row.draggable = true;
                row.dataset.photoId = id;
                if (photo) {
                    const image = document.createElement('img');
                    image.src = photo.thumbnail_url || photo.url;
                    image.alt = photo.alt || photo.title || '';
                    image.draggable = false;
                    row.appendChild(image);
                }
                const caption = document.createElement('span');
                caption.textContent = photo ? (photo.title || photo.alt || `Zdjęcie ${index + 1}`) : 'Zdjęcie niedostępne';
                row.appendChild(caption);
                function action(text, label, callback, disabled = false) {
                    const control = document.createElement('button');
                    control.type = 'button';
                    control.textContent = text;
                    control.setAttribute('aria-label', label);
                    control.disabled = disabled;
                    control.addEventListener('click', () => {
                        callback();
                        // Preserve a useful focus after rebuilding the ordered list.
                        list.querySelector('button:not(:disabled)')?.focus();
                    });
                    row.appendChild(control);
                }
                action('↑', 'Przesuń wcześniej', () => move(index, index - 1), index === 0);
                action('↓', 'Przesuń później', () => move(index, index + 1), index === item.photo_ids.length - 1);
                action('Usuń', 'Usuń zdjęcie z bloku', () => { item.photo_ids = item.photo_ids.filter((_, i) => i !== index); update(); });
                row.addEventListener('dragstart', event => {
                    dragging = index;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', String(id));
                    event.stopPropagation();
                });
                row.addEventListener('dragover', event => {
                    if (dragging === null) return;
                    event.preventDefault(); event.stopPropagation();
                    event.dataTransfer.dropEffect = 'move';
                    row.setAttribute('data-drop-target', '');
                });
                row.addEventListener('dragleave', () => row.removeAttribute('data-drop-target'));
                row.addEventListener('drop', event => {
                    if (dragging === null) return;
                    event.preventDefault(); event.stopPropagation();
                    const from = dragging;
                    dragging = null;
                    row.removeAttribute('data-drop-target');
                    move(from, index);
                });
                row.addEventListener('dragend', () => {
                    dragging = null;
                    list.querySelectorAll('[data-drop-target]').forEach(target => target.removeAttribute('data-drop-target'));
                });
                list.appendChild(row);
            });
        }
        button.addEventListener('click', () => window.PhotoLibrary.open({
            multiple: true, selected: item.photo_ids || [], trigger: button,
            onConfirm: ids => { item.photo_ids = ids; update(); },
        }));
        container.appendChild(button);
        const hint = document.createElement('p');
        hint.textContent = 'Przeciągnij miniatury, aby zmienić kolejność, lub użyj przycisków ↑ ↓. Usuń odłącza zdjęcie tylko od tego bloku.';
        container.appendChild(hint);
        container.appendChild(list);
        drawList();

        for (const [key, label, min, max] of [
            ['columns_desktop', 'Kolumny — desktop', 1, 12], ['columns_tablet', 'Kolumny — tablet', 1, 12],
            ['columns_mobile', 'Kolumny — telefon', 1, 12], ['gap', 'Odstęp (px)', 0, 100],
        ]) {
            const wrapper = document.createElement('label');
            wrapper.className = fieldClass;
            wrapper.textContent = label;
            const input = document.createElement('input');
            input.setAttribute('data-thumbnail-setting', key);
            input.type = 'number'; input.min = min; input.max = max; input.step = 1;
            input.value = item[key] ?? defaults[key];
            input.addEventListener('change', () => {
                item[key] = Math.max(min, Math.min(max, Math.round(Number(input.value) || 0)));
                input.value = item[key];
                render();
            });
            wrapper.appendChild(input);
            container.appendChild(wrapper);
        }
    }

    // Expand only canvases containing the new block; existing layouts are untouched.
    function fitCanvas(canvas) {
        if (!canvas.offsetHeight) return;
        const blocks = [...canvas.querySelectorAll('[data-thumbnail-block]')];
        if (!blocks.length) {
            if (canvas.dataset.thumbnailMinimum) canvas.style.minHeight = `${canvas.dataset.thumbnailMinimum}px`;
            return;
        }
        const minimum = Number(canvas.dataset.thumbnailMinimum || canvas.offsetHeight);
        canvas.dataset.thumbnailMinimum = minimum;
        const height = Math.max(minimum, ...blocks.map(block => {
            const top = block.style.top.endsWith('%')
                ? (parseFloat(block.style.top) || 0)
                : Number(block.dataset.thumbnailTop || 0);
            block.dataset.thumbnailTop = top;
            if (top >= 100) block.style.top = `${minimum * top / 100}px`;
            return top < 100 ? (block.offsetHeight + 20) / (1 - top / 100) : minimum * top / 100 + block.offsetHeight + 20;
        }));
        canvas.style.minHeight = `${Math.ceil(height)}px`;
    }
    window.ThumbnailGallery = { initialize, preview, properties, fitCanvas };
})();
