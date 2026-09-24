document.querySelectorAll('[data-seo-preview]').forEach(preview => {
    const form = preview.closest('form');
    const title = form.elements.namedItem('seo_title');
    const description = form.elements.namedItem('seo_description');
    const pageTitle = form.elements.namedItem('title');
    const slug = form.elements.namedItem('slug');
    function update() {
        const base = pageTitle.value.trim();
        const site = preview.dataset.site;
        const fallback = base.toLocaleLowerCase().includes(site.toLocaleLowerCase()) ? base : `${base} — ${site}`;
        preview.querySelector('[data-preview-title]').textContent = title.value.trim() || (preview.dataset.kind === 'gallery' ? preview.dataset.galleryDefaultTitle : '') || fallback;
        preview.querySelector('[data-preview-description]').textContent = description.value.trim() || preview.dataset.defaultDescription;
        const currentSlug = slug.value.trim();
        preview.querySelector('[data-preview-url]').textContent = preview.dataset.kind === 'page' && ['o-mnie', 'kontakt'].includes(currentSlug)
            ? `${preview.dataset.origin}/${currentSlug}` : `${preview.dataset.origin}/${preview.dataset.kind === 'page' ? 'strona' : 'portfolio'}/${encodeURIComponent(currentSlug)}`;
    }
    [title, description, pageTitle, slug].forEach(input => input.addEventListener('input', update));
});
