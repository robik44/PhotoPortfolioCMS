(function () {
    'use strict';

    // Admin-wide enhancement: retain the original native color input (and all
    // its existing event handlers / form names), adding a synchronized HEX field.
    function normalizeHex(value) {
        const match = String(value ?? '').trim().match(/^#?([a-f\d]{6}|[a-f\d]{3})$/i);
        if (!match) return null;
        const digits = match[1].length === 3
            ? [...match[1]].map(char => char + char).join('')
            : match[1];
        return '#' + digits.toUpperCase();
    }

    function attachColorInput(input) {
        if (!input.isConnected || input.dataset.cmsHexEnhanced === '1') return;
        input.dataset.cmsHexEnhanced = '1';

        const pair = document.createElement('span');
        pair.className = 'cms-hex-pair';
        input.parentNode.insertBefore(pair, input);
        pair.appendChild(input);

        const hex = document.createElement('input');
        hex.type = 'text';
        hex.className = 'cms-hex-value';
        hex.setAttribute('aria-label', 'Kod koloru HEX');
        hex.setAttribute('title', 'Wpisz kolor, np. #222222, i naciśnij Enter');
        hex.placeholder = '#RRGGBB';
        hex.maxLength = 7;
        hex.autocomplete = 'off';
        hex.spellcheck = false;
        hex.value = normalizeHex(input.value) || '#000000';
        pair.appendChild(hex);

        let saved = normalizeHex(input.value) || '#000000';

        function updateFromPicker() {
            const normalized = normalizeHex(input.value);
            if (!normalized) return;
            saved = normalized;
            // Preserve manually entered partial value until it is committed.
            if (document.activeElement !== hex) hex.value = normalized;
            hex.classList.remove('cms-hex-invalid');
            hex.removeAttribute('aria-invalid');
        }

        input.addEventListener('input', updateFromPicker);
        input.addEventListener('change', updateFromPicker);

        hex.addEventListener('input', function () {
            const normalized = normalizeHex(hex.value);
            if (normalized) input.value = normalized; // Preview swatch; defer saving.
            hex.classList.toggle('cms-hex-invalid', !normalized);
            if (normalized) hex.removeAttribute('aria-invalid');
            else hex.setAttribute('aria-invalid', 'true');
        });

        function commitHex() {
            const normalized = normalizeHex(hex.value);
            if (!normalized) {
                // Invalid values never overwrite the saved model or form value.
                hex.value = saved;
                input.value = saved;
                hex.classList.remove('cms-hex-invalid');
                hex.removeAttribute('aria-invalid');
                return;
            }
            input.value = normalized;
            hex.value = normalized;
            hex.classList.remove('cms-hex-invalid');
            hex.removeAttribute('aria-invalid');
            if (normalized !== saved) {
                saved = normalized;
                // Existing page and gallery builders observe input / change
                // on the native picker, not on our newly inserted text field.
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        hex.addEventListener('change', commitHex);
        hex.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                // Do not submit the whole CMS form while applying one HEX value.
                event.preventDefault();
                hex.blur();
            }
            if (event.key === 'Escape') {
                hex.value = saved;
                input.value = saved;
                hex.blur();
            }
        });
    }

    // Gallery and homepage inspectors replace controls dynamically. Observe
    // additions once and scan only added subtrees rather than the whole page.
    function addColorInputs(root) {
        if (root.nodeType !== 1) return;
        if (root.matches('input[type="color"]')) attachColorInput(root);
        root.querySelectorAll('input[type="color"]').forEach(attachColorInput);
    }

    function start() {
        addColorInputs(document.body);
        const observer = new MutationObserver(records => {
            for (const record of records) {
                for (const node of record.addedNodes) addColorInputs(node);
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
