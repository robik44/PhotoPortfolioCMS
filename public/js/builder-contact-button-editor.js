/* FoodFoto: expose button label/action controls at the top of the properties
   sidebar for every builder page. Existing inputs and event listeners are
   MOVED, never duplicated, so persisted element data remains unchanged. */
(() => {
  'use strict';
  function normalize(value) {
    return String(value || '').replace(/\s+/g, ' ').trim().toLocaleLowerCase('pl');
  }
  function fieldCaption(node) {
    const ownLabel = node.querySelector(':scope > label');
    if (ownLabel) return normalize(ownLabel.textContent);
    return normalize(Array.from(node.childNodes)
      .filter(child => child.nodeType === 3)
      .map(child => child.textContent).join(' '));
  }
  function enhance(panel) {
    const title = panel.querySelector(':scope > h3');
    if (!title || normalize(title.textContent) !== 'przycisk') return;
    const actions = title.nextElementSibling;
    if (!actions) return;
    const fields = Array.from(panel.children)
      .filter(node => node.classList?.contains('fve-field'));
    const text = fields.find(node => fieldCaption(node) === 'tekst przycisku');
    const link = fields.find(node => fieldCaption(node) === 'akcja / link');
    if (!text || !link) return;
    let previous = actions;
    for (const node of [text, link]) {
      if (previous.nextElementSibling !== node) previous.insertAdjacentElement('afterend', node);
      previous = node;
    }
    if (!link.querySelector('[data-ff-mailto-help]')) {
      const hint = document.createElement('small');
      hint.dataset.ffMailtoHelp = '1';
      hint.textContent = 'Aby po kliknięciu otwierać pocztę, wpisz np. mailto:magda@foodfoto.pl. Napis na przycisku edytujesz powyżej.';
      hint.style.cssText = 'display:block;margin:5px 0 0;color:#647062;font-size:12px;line-height:1.45';
      link.appendChild(hint);
    }
  }
  function init() {
    const panel = document.getElementById('fve-properties');
    if (!panel) return;
    let queued = false;
    const schedule = () => {
      if (queued) return;
      queued = true;
      requestAnimationFrame(() => { queued = false; enhance(panel); });
    };
    new MutationObserver(changes => {
      if (changes.some(change => change.type === 'childList')) schedule();
    }).observe(panel, {childList: true, subtree: true});
    schedule();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, {once: true});
  } else init();
})();
