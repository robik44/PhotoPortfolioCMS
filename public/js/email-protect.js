/* FoodFoto public email links: keep literal addresses out of static HTML,
 * but always restore the saved button caption and working mailto action.
 * Runs on all pages, including reusable builder contact sections. */
(() => {
  'use strict';
  function decode(value) {
    if (!value) return '';
    try {
      const bytes = Uint8Array.from(atob(value), c => c.charCodeAt(0));
      return new TextDecoder('utf-8').decode(bytes);
    } catch (_) {
      return '';
    }
  }
  function restore(link) {
    if (!link || !link.hasAttribute('data-ff-email')) return;
    const email = decode(link.getAttribute('data-ff-email'));
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
    const savedLabel = decode(link.getAttribute('data-ff-email-label'));
    link.href = 'mailto:' + email;
    link.textContent = savedLabel || email;
    link.removeAttribute('data-ff-email');
    link.removeAttribute('data-ff-email-label');
  }
  function restoreAll() {
    document.querySelectorAll('a[data-ff-email]').forEach(restore);
  }
  // Capture clicks as well: if a third-party script injects or changes a
  // contact button later, set its mailto href before browser navigation.
  document.addEventListener('click', event => {
    const link = event.target.closest?.('a[data-ff-email]');
    if (link) restore(link);
  }, true);
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', restoreAll, {once: true});
  } else restoreAll();
  window.addEventListener('pageshow', restoreAll);
})();
