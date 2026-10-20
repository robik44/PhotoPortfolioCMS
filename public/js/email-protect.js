/* FoodFoto contact links. Prevents literal addresses from appearing in HTML
 * source while retaining visible, clickable email links after JS initializes.
 * This is a deterrent to basic scrapers, not protection from JS-aware bots.
 */
(function () {
    'use strict';
    function decoded(value) {
        if (!value) return '';
        try {
            const bytes = Uint8Array.from(atob(value), c => c.charCodeAt(0));
            return new TextDecoder('utf-8').decode(bytes);
        } catch (error) {
            return '';
        }
    }
    function activate() {
        document.querySelectorAll('a[data-ff-email]').forEach(link => {
            const email = decoded(link.dataset.ffEmail);
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
            const caption = decoded(link.dataset.ffEmailLabel);
            link.href = 'mailto:' + email;
            link.textContent = caption || email;
            link.removeAttribute('data-ff-email');
            link.removeAttribute('data-ff-email-label');
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', activate, { once: true });
    } else activate();
})();
