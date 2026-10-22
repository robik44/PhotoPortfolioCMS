/* FOODFOTO_CUSTOM_PROCESS_BACKGROUND_V1
 * Hide obsolete branch/wash controls; the custom image library and
 * image positioning/scale/opacity controls remain fully operational.
 */
(() => {
  'use strict';
  const normalize = value => (value || '').replace(/\s+/g, ' ').trim().toLocaleLowerCase('pl');
  const obsolete = [
    'intensywność poświaty', 'kolor poświaty', 'kolor rozświetlenia',
    'pokaż rysunek gałązki', 'krycie gałązki', 'szerokość gałązki',
    'pozycja gałązki w poziomie', 'pozycja gałązki w pionie',
    'poświata i gałązka za napisami'
  ];
  const oldField = value => {
    const label = normalize(value);
    return obsolete.some(term => label === term || label.startsWith(term + ' ('));
  };
  function update() {
    const panel = document.getElementById('fve-properties');
    if (!panel) return;
    const heading = [...panel.querySelectorAll('h4')].find(el =>
      normalize(el.textContent).includes('poświata i gałązka'));
    if (heading) {
      let node = heading;
      while (node && (node === heading || node.tagName !== 'H4')) {
        const next = node.nextElementSibling;
        node.hidden = true;
        node.style.display = 'none';
        node = next;
      }
    }
    for (const field of panel.querySelectorAll('label.fve-field')) {
      const caption = field.querySelector(':scope > span, :scope > label');
      if (caption && oldField(caption.textContent)) {
        field.hidden = true;
        field.style.display = 'none';
      }
    }
  }
  function init() {
    const panel = document.getElementById('fve-properties');
    if (!panel) return;
    let queued = false;
    const schedule = () => {
      if (queued) return;
      queued = true;
      requestAnimationFrame(() => { queued = false; update(); });
    };
    new MutationObserver(changes => {
      if (changes.some(change => change.type === 'childList')) schedule();
    }).observe(panel, {childList: true, subtree: true});
    schedule();
  }
  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', init, {once: true});
  else init();
})();