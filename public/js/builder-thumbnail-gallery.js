(() => {
    const defaults = {
        photo_ids: [],
        columns_desktop: 5,
        columns_tablet: 3,
        columns_mobile: 2,
        gap: 20,
        thumbnail_height: 0,
        thumbnail_ratio: 'auto',
        thumbnail_fit: 'cover',
        thumbnail_radius: 0,
        group_align: 'left',
        photo_settings: {}
    };

    const selections = new WeakMap();

    function getSelection(item) {
        if (!selections.has(item)) selections.set(item, new Set());
        return selections.get(item);
    }

    function getPhotoSettings(item, id) {
        if (!item.photo_settings || typeof item.photo_settings !== 'object' || Array.isArray(item.photo_settings)) {
            item.photo_settings = {};
        }
        const key = String(id);
        if (!item.photo_settings[key] || typeof item.photo_settings[key] !== 'object') item.photo_settings[key] = {};
        return item.photo_settings[key];
    }
    function initialize(item) {
        Object.assign(item, {
            ...defaults,
            photo_ids: [],
            photo_settings: {}
        });
    }

    function preview(item, photos, onSelectionChange = null) {
        const settings = { ...defaults, ...item };
        const grid = document.createElement('div');
        grid.className = 'thumbnail-gallery-grid';
        const alignMap = { left: 'flex-start', center: 'center', right: 'flex-end' };
        grid.style.setProperty('--tg-justify', alignMap[settings.group_align] || 'flex-start');
        const selectedIds = getSelection(item);
        for (const [key, variable] of Object.entries({ columns_desktop: 'desktop', columns_tablet: 'tablet', columns_mobile: 'mobile', gap: 'gap' })) {
            grid.style.setProperty(`--tg-${variable}`, settings[key] + (variable === 'gap' ? 'px' : ''));
        }
        const library = new Map(photos.map(photo => [Number(photo.id), photo]));
        for (const id of item.photo_ids || []) {
            const photo = library.get(Number(id));
            if (!photo) continue;
            const card = document.createElement('div');
            card.className = 'thumbnail-gallery-item';
            card.dataset.photoId = String(id);

            const individual = getPhotoSettings(item, id);
            const individualWidth = Number(individual.width) || 0;
            if (individualWidth > 0) card.style.flexBasis = Math.max(5, Math.min(100, individualWidth)) + '%';
            if (selectedIds.has(Number(id))) card.classList.add('is-selected');

            const image = document.createElement('img');
            image.src = photo.thumbnail_url || photo.url;
            image.alt = photo.alt || photo.title || '';
            image.draggable = false;
            image.style.width = '100%';
            image.style.display = 'block';
            image.style.borderRadius = (Number(settings.thumbnail_radius) || 0) + 'px';

            const individualFit = individual.fit === 'contain' ? 'contain' : (settings.thumbnail_fit === 'contain' ? 'contain' : 'cover');
            const individualHeight = Number(individual.height) || 0;
            image.style.objectFit = individualFit;

            if (individualHeight > 0) {
                image.style.height = individualHeight + 'px';
            } else if (Number(settings.thumbnail_height) > 0) {
                image.style.height = Number(settings.thumbnail_height) + 'px';
            } else if (settings.thumbnail_ratio && settings.thumbnail_ratio !== 'auto') {
                image.style.aspectRatio = settings.thumbnail_ratio;
                image.style.height = 'auto';
            } else {
                image.style.height = 'auto';
                image.style.objectFit = 'contain';
            }

            card.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                const numericId = Number(id);
                if (event.shiftKey || event.metaKey || event.ctrlKey) {
                    selectedIds.has(numericId) ? selectedIds.delete(numericId) : selectedIds.add(numericId);
                } else {
                    selectedIds.clear();
                    selectedIds.add(numericId);
                }

                grid.querySelectorAll('.thumbnail-gallery-item').forEach(node => {
                    node.classList.toggle('is-selected', selectedIds.has(Number(node.dataset.photoId)));
                });

                if (typeof onSelectionChange === 'function') onSelectionChange();
            });

            card.appendChild(image);
            grid.appendChild(card);
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

        const selectionBox = document.createElement('div');
        selectionBox.className = fieldClass;
        selectionBox.style.padding = '12px';
        selectionBox.style.border = '1px solid #ddd';
        selectionBox.style.borderRadius = '6px';
        selectionBox.style.background = '#fafafa';

        const selectionTitle = document.createElement('strong');
        selectionTitle.textContent = 'Edycja zaznaczonych miniaturek';
        selectionTitle.style.display = 'block';
        selectionTitle.style.marginBottom = '8px';
        selectionBox.appendChild(selectionTitle);

        const selectionInfo = document.createElement('div');
        selectionInfo.style.fontSize = '12px';
        selectionInfo.style.marginBottom = '10px';
        selectionBox.appendChild(selectionInfo);

        function selectedIdsArray() {
            return [...getSelection(item)].filter(id => (item.photo_ids || []).map(Number).includes(Number(id)));
        }

        function refreshSelectionInfo() {
            const ids = selectedIdsArray();
            selectionInfo.textContent = ids.length
                ? 'Zaznaczono: ' + ids.length
                : 'Kliknij miniaturę w podglądzie. Shift/Cmd/Ctrl + klik zaznacza kilka.';
        }

        function action(text, callback) {
            const control = document.createElement('button');
            control.type = 'button';
            control.className = 'cms-button';
            control.style.marginRight = '6px';
            control.style.marginBottom = '6px';
            control.textContent = text;
            control.addEventListener('click', () => {
                callback();
                render();
                refreshSelectionInfo();
            });
            selectionBox.appendChild(control);
        }

        action('Zaznacz wszystkie', () => {
            const selection = getSelection(item);
            selection.clear();
            (item.photo_ids || []).forEach(id => selection.add(Number(id)));
        });

        action('Wyczyść zaznaczenie', () => {
            getSelection(item).clear();
        });

        function numericSetting(label, key, min, max, fallback = 0) {
            const wrapper = document.createElement('label');
            wrapper.style.display = 'block';
            wrapper.style.marginTop = '10px';
            wrapper.style.fontSize = '12px';
            wrapper.textContent = label;

            const input = document.createElement('input');
            input.type = 'number';
            input.min = min;
            input.max = max;
            input.step = key === 'width' ? '0.1' : '1';
            input.style.width = '100%';

            const ids = selectedIdsArray();
            if (ids.length) {
                const values = ids.map(id => Number(getPhotoSettings(item, id)[key]) || fallback);
                input.value = values.every(value => value === values[0]) ? values[0] : '';
            } else {
                input.value = '';
            }

            input.placeholder = ids.length ? 'różne wartości' : 'najpierw zaznacz zdjęcia';
            input.addEventListener('change', () => {
                const value = Math.max(min, Math.min(max, Number(input.value) || fallback));
                selectedIdsArray().forEach(id => {
                    getPhotoSettings(item, id)[key] = value;
                });
                render();
            });

            wrapper.appendChild(input);
            selectionBox.appendChild(wrapper);
        }

        numericSetting('Szerokość zaznaczonych (% boxu)', 'width', 5, 100, 0);
        numericSetting('Wysokość zaznaczonych (px, 0 = auto)', 'height', 0, 1600, 0);

        const fitLabel = document.createElement('label');
        fitLabel.style.display = 'block';
        fitLabel.style.marginTop = '10px';
        fitLabel.style.fontSize = '12px';
        fitLabel.textContent = 'Kadrowanie zaznaczonych';

        const fitSelect = document.createElement('select');
        fitSelect.style.width = '100%';
        [['cover', 'Wypełnij / przytnij'], ['contain', 'Pokaż całe zdjęcie']].forEach(([value, title]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = title;
            fitSelect.appendChild(option);
        });
        fitSelect.addEventListener('change', () => {
            selectedIdsArray().forEach(id => {
                getPhotoSettings(item, id).fit = fitSelect.value;
            });
            render();
        });
        fitLabel.appendChild(fitSelect);
        selectionBox.appendChild(fitLabel);

        const sameSize = document.createElement('button');
        sameSize.type = 'button';
        sameSize.className = 'cms-button';
        sameSize.style.width = '100%';
        sameSize.style.marginTop = '10px';
        sameSize.textContent = 'Nadaj zaznaczonym ten sam rozmiar';
        sameSize.addEventListener('click', () => {
            const ids = selectedIdsArray();
            if (ids.length < 2) return;
            const source = { ...getPhotoSettings(item, ids[0]) };
            ids.slice(1).forEach(id => {
                item.photo_settings[String(id)] = { ...source };
            });
            render();
        });
        selectionBox.appendChild(sameSize);

        const alignTitle = document.createElement('div');
        alignTitle.textContent = 'Położenie całej grupy w boxie';
        alignTitle.style.marginTop = '12px';
        alignTitle.style.marginBottom = '6px';
        alignTitle.style.fontSize = '12px';
        selectionBox.appendChild(alignTitle);

        [['left', 'Do lewej'], ['center', 'Wyśrodkuj'], ['right', 'Do prawej']].forEach(([value, title]) => {
            const alignButton = document.createElement('button');
            alignButton.type = 'button';
            alignButton.className = 'cms-button';
            alignButton.style.marginRight = '6px';
            alignButton.style.marginBottom = '6px';
            alignButton.textContent = title;
            alignButton.addEventListener('click', () => {
                item.group_align = value;
                render();
            });
            selectionBox.appendChild(alignButton);
        });

        refreshSelectionInfo();
        container.appendChild(selectionBox);

        for (const [key, label, min, max] of [
            ['columns_desktop', 'Kolumny — desktop', 1, 12], ['columns_tablet', 'Kolumny — tablet', 1, 12],
            ['columns_mobile', 'Kolumny — telefon', 1, 12], ['gap', 'Odstęp (px)', 0, 100],
            ['thumbnail_height', 'Wysokość miniatury (px, 0 = auto)', 0, 1200],
            ['thumbnail_radius', 'Zaokrąglenie miniatury (px)', 0, 200],
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

        function selectSetting(key, label, values) {
            const wrapper = document.createElement('label');
            wrapper.className = fieldClass;
            wrapper.textContent = label;
            const select = document.createElement('select');
            values.forEach(([value, title]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = title;
                select.appendChild(option);
            });
            select.value = item[key] ?? defaults[key];
            select.addEventListener('change', () => {
                item[key] = select.value;
                render();
            });
            wrapper.appendChild(select);
            container.appendChild(wrapper);
        }

        selectSetting('thumbnail_ratio', 'Proporcja miniatury', [
            ['auto', 'Naturalna'],
            ['1 / 1', '1:1'],
            ['4 / 3', '4:3'],
            ['3 / 2', '3:2'],
            ['16 / 9', '16:9'],
        ]);
        selectSetting('thumbnail_fit', 'Kadrowanie miniatury', [
            ['cover', 'Wypełnij / przytnij'],
            ['contain', 'Pokaż całe zdjęcie'],
        ]);
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
