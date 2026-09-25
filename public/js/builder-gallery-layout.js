// Public thumbnail blocks are passive, including for delegated image handlers.
for (const type of ['click', 'dblclick', 'auxclick']) {
    window.addEventListener(type, event => {
        if (!event.target.closest?.('[data-builder-thumbnail-gallery]')) return;
        event.preventDefault();
        event.stopImmediatePropagation();
    }, true);
}

// Absolute PageBuilder elements do not expand their canvas by themselves.
// Keep a selected gallery's final rows above the footer, also after image loading.
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.querySelector('.page-canvas');
    const galleries = Array.from(document.querySelectorAll('[data-builder-selected-gallery], [data-builder-thumbnail-gallery]'));
    if (!canvas || !galleries.length) return;
    const minimum = canvas.offsetHeight;
    const positions = galleries.map(gallery => ({ gallery, top: parseFloat(gallery.style.top) || 0 }));
    positions.forEach(({ gallery, top }) => {
        if (top >= 100) gallery.style.top = `${minimum * top / 100}px`;
    });
    function resize() {
        const height = Math.max(minimum, ...positions.map(({ gallery, top }) => top < 100
            ? (gallery.offsetHeight + 20) / (1 - top / 100)
            : minimum * top / 100 + gallery.offsetHeight + 20));
        canvas.style.minHeight = `${Math.ceil(height)}px`;
    }
    galleries.forEach(gallery => gallery.querySelectorAll('img').forEach(image => image.addEventListener('load', resize)));
    if (typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(resize);
        galleries.forEach(gallery => observer.observe(gallery));
    }
    window.addEventListener('resize', resize);
    resize();
});
