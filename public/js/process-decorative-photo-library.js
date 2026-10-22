/* Reuse the CMS photo-library dialog for the decorative image in "Jak pracuję".
 * This small compatibility layer activates only if the newer process controls
 * are present; it never changes image positioning, opacity or persisted data.
 */
(() => {
    'use strict';

    const headingText = 'Własny obrazek dekoracyjny pod tekstami';
    const inputText = 'Obraz z biblioteki zdjęć';
    const thumbnailText = 'Zdjęcia z biblioteki — kliknij miniaturę';
    const normalize = value => (value || '').replace(/\s+/g, ' ').trim().toLocaleLowerCase('pl');

    function exactTextNode(root, text) {
        const needle = normalize(text);
        return Array.from(root.querySelectorAll('h2,h3,h4,h5,h6,legend,strong,span,p,div'))
            .find(node => normalize(node.textContent) === needle
                && !Array.from(node.children).some(child => normalize(child.textContent) === needle));
    }

    function findPhotoSelect(panel) {
        return Array.from(panel.querySelectorAll('select')).find(select => {
            const field = select.closest('label, .fve-field') || select.parentElement;
            if (!field) return false;
            const label = field.querySelector('span, label') || field;
            return normalize(label.textContent).startsWith(normalize(inputText));
        });
    }

    function hideOldThumbnails(panel) {
        const heading = exactTextNode(panel, thumbnailText);
        if (!heading) return;
        let wrapper = heading.parentElement;
        while (wrapper && wrapper !== panel) {
            const hasSearch = !!wrapper.querySelector('input[placeholder*="Szukaj"],input[type="search"]');
            const hasThumbnails = wrapper.querySelectorAll('img').length > 1;
            const hasRemove = Array.from(wrapper.querySelectorAll('button'))
                .some(button => normalize(button.textContent).includes('usuń obrazek z tej sekcji'));
            if (hasRemove && (hasSearch || hasThumbnails)) {
                wrapper.hidden = true;
                wrapper.style.display = 'none';
                return;
            }
            wrapper = wrapper.parentElement;
        }
    }

    function attach(panel) {
        if (!window.PhotoLibrary || typeof window.PhotoLibrary.open !== 'function') return;
        if (!exactTextNode(panel, headingText)) return;

        const select = findPhotoSelect(panel);
        if (!select || select.dataset.sharedProcessPhotoPicker === '1') return;
        const field = select.closest('label, .fve-field') || select.parentElement;
        if (!field || !field.parentElement) return;
        select.dataset.sharedProcessPhotoPicker = '1';

        const picker = document.createElement('div');
        picker.className = 'fve-process-photo-library-picker';
        const caption = document.createElement('div');
        caption.className = 'fve-process-photo-library-label';
        caption.textContent = inputText;

        const preview = document.createElement('div');
        preview.className = 'fve-process-photo-library-preview';
        const photo = document.createElement('img');
        photo.alt = '';
        const description = document.createElement('span');
        preview.append(photo, description);

        const actions = document.createElement('div');
        actions.className = 'fve-process-photo-library-actions';
        const choose = document.createElement('button');
        choose.type = 'button';
        choose.className = 'fve-button';
        choose.textContent = 'Wybierz zdjęcie z biblioteki';
        choose.setAttribute('aria-haspopup', 'dialog');
        choose.setAttribute('aria-controls', 'photo-library-dialog');
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'fve-button';
        remove.textContent = 'Usuń zdjęcie';
        actions.append(choose, remove);
        picker.append(caption, preview, actions);

        const libraryCards = Array.from(document.querySelectorAll('#photo-library-dialog [data-library-photo]'));
        const findCard = id => libraryCards.find(card => card.dataset.libraryPhoto === id);
        function refresh() {
            const id = String(select.value || '');
            const card = findCard(id);
            const image = card?.querySelector('img');
            const title = card?.querySelector('[data-library-caption]')?.textContent.trim();
            if (id && image) {
                photo.src = image.src;
                photo.alt = image.alt || title || 'Wybrane zdjęcie';
                photo.hidden = false;
                description.textContent = title || 'Wybrane zdjęcie';
            } else {
                photo.removeAttribute('src');
                photo.hidden = true;
                description.textContent = id ? 'Wybrane zdjęcie z biblioteki' : 'Nie wybrano zdjęcia dekoracyjnego.';
            }
            choose.textContent = id ? 'Zmień zdjęcie' : 'Wybierz zdjęcie z biblioteki';
            remove.hidden = !id;
        }

        function changePhoto(id) {
            // The original select owns saving/rendering. Preserve that behavior.
            select.value = String(id || '');
            refresh();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        choose.addEventListener('click', () => window.PhotoLibrary.open({
            trigger: choose,
            selected: String(select.value || ''),
            emptyLabel: 'Bez dodatkowego obrazka',
            onConfirm: changePhoto,
        }));
        remove.addEventListener('click', () => changePhoto(''));
        select.addEventListener('change', refresh);

        field.before(picker);
        field.hidden = true;
        field.style.display = 'none';
        hideOldThumbnails(panel);
        refresh();
    }

    function initialize() {
        const panel = document.getElementById('fve-properties');
        if (!panel) return;
        let scheduled = false;
        const schedule = () => {
            if (scheduled) return;
            scheduled = true;
            window.requestAnimationFrame(() => {
                scheduled = false;
                attach(panel);
            });
        };
        const observer = new MutationObserver(schedule);
        observer.observe(panel, { childList: true, subtree: true });
        schedule();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
