(() => {
    function initialize() {
        const root = document.querySelector('[data-menu-sort]');
        if (!root || root.dataset.menuOrderReady) return;
        root.dataset.menuOrderReady = 'true';
        const status = document.getElementById('menu-order-status');
        let dragging = null, group = null, saving = false, marker = null;
        const rows = list => Array.from(list.children).filter(row => row.hasAttribute('data-menu-id'));
        const rowBox = row => (row.querySelector(':scope > .cms-menu-item') || row).getBoundingClientRect();

        function clearMarker() {
            if (marker) marker.removeAttribute('data-menu-drop');
            marker = null;
        }

        function finishDrag() {
            clearMarker();
            if (dragging) dragging.removeAttribute('data-menu-dragging');
            dragging = null;
            group = null;
        }

        // Resolve a sibling boundary, including the padding/gaps of this list.
        function destination(event) {
            if (!dragging || event.target.closest('[data-menu-sort]') !== group) return null;
            const siblings = rows(group);
            const target = event.target.closest('[data-menu-id]');
            if (target && target.parentElement === group) {
                const box = rowBox(target);
                return { row: target, after: event.clientY >= box.top + box.height / 2 };
            }
            const next = siblings.find(row => {
                const box = rowBox(row);
                return event.clientY < box.top + box.height / 2;
            });
            return { row: next || siblings[siblings.length - 1], after: !next };
        }

        async function save(list, before) {
            const ordered = rows(list);
            if (ordered.every((row, index) => row === before[index])) return;
            saving = true;
            root.setAttribute('aria-busy', 'true');
            status.textContent = 'Zapisywanie…';
            try {
                const response = await fetch(root.dataset.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ parent_id: list.dataset.parent ? Number(list.dataset.parent) : null, items: ordered.map(row => ({ id: Number(row.dataset.menuId) })) }),
                });
                if (!response.ok || !(await response.json()).success) throw new Error('save');
                status.textContent = 'Kolejność została zapisana.';
            } catch {
                before.forEach(row => list.appendChild(row));
                status.textContent = 'Nie udało się zapisać kolejności. Przywrócono poprzedni układ. Spróbuj ponownie.';
            } finally {
                saving = false;
                root.removeAttribute('aria-busy');
            }
        }

        root.addEventListener('dragstart', event => {
            const handle = event.target.closest('.cms-menu-drag');
            if (!handle || saving) { event.preventDefault(); return; }
            dragging = handle.closest('[data-menu-id]');
            group = dragging.parentElement;
            dragging.setAttribute('data-menu-dragging', 'true');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragging.dataset.menuId);
        });
        root.addEventListener('dragover', event => {
            const position = destination(event);
            clearMarker();
            if (!position) {
                if (dragging) event.dataTransfer.dropEffect = 'none';
                return;
            }
            // Native drop is delivered only when dragover is cancelled, even
            // over the dragged row itself or over whitespace between rows.
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            marker = position.row;
            marker.setAttribute('data-menu-drop', position.after ? 'after' : 'before');
        });
        root.addEventListener('dragleave', event => {
            if (!root.contains(event.relatedTarget)) clearMarker();
        });
        root.addEventListener('drop', event => {
            const position = destination(event);
            if (!position) return;
            event.preventDefault();
            const list = group, before = rows(list), row = dragging;
            if (position.row !== row) {
                list.insertBefore(row, position.after ? position.row.nextSibling : position.row);
            }
            // The committed DOM order must survive the browser's dragend.
            finishDrag();
            save(list, before);
        });
        // No DOM moves happen until drop, so cancellation only clears feedback.
        root.addEventListener('dragend', finishDrag);
        root.addEventListener('keydown', event => {
            if (saving || dragging || !event.target.matches('.cms-menu-drag') || !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
            event.preventDefault();
            const row = event.target.closest('[data-menu-id]'), list = row.parentElement, before = rows(list);
            const next = before[before.indexOf(row) + (event.key === 'ArrowUp' ? -1 : 1)];
            if (!next) return;
            list.insertBefore(row, event.key === 'ArrowUp' ? next : next.nextSibling);
            event.target.focus();
            save(list, before);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
