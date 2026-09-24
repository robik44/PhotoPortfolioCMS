<style>
    /* LIGHTBOX */

    .lightbox {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 40px;
        background: rgba(0,0,0,.94);
    }

    .lightbox.is-open {
        display: flex;
    }

    .lightbox-image {
        position: relative;
        z-index: 1;
        max-width: calc(100vw - 150px);
        max-height: calc(100vh - 150px);
        width: auto;
        height: auto;
        object-fit: contain;
        object-position: center;
        user-select: none;
        display: block;
    }

    #lightbox .lightbox-close {
        position: fixed !important;
        top: max(22px, env(safe-area-inset-top)) !important;
        right: max(28px, env(safe-area-inset-right)) !important;
        left: auto !important;
        transform: none !important;
        z-index: 2147483647 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        width: 56px !important;
        height: 56px !important;
        padding: 0 !important;
        border: 2px solid rgba(255,255,255,.8) !important;
        border-radius: 50% !important;
        background: #222 !important;
        color: #fff !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 40px !important;
        font-weight: 400 !important;
        line-height: 1 !important;
        text-align: center !important;
        cursor: pointer !important;
    }

    #lightbox .lightbox-close:hover {
        background: #444 !important;
    }

    #lightbox .lightbox-close:focus-visible {
        outline: 3px solid #fff;
        outline-offset: 4px;
    }

    .lightbox-prev,
    .lightbox-next {
        position: fixed !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        z-index: 2147483647 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: 56px !important;
        height: 56px !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 50% !important;
        background: rgba(255,255,255,.18) !important;
        color: #fff !important;
        font-family: Arial, sans-serif !important;
        font-size: 38px !important;
        font-weight: 300 !important;
        line-height: 1 !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: background .2s ease, transform .2s ease !important;
    }

    .lightbox-prev:hover,
    .lightbox-next:hover {
        background: rgba(255,255,255,.32) !important;
    }

    .lightbox-prev {
        left: 6vw !important;
        right: auto !important;
    }

    .lightbox-next {
        right: 6vw !important;
        left: auto !important;
    }

    .lightbox-info {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 22px;
        text-align: center;
        color: #fff;
        font-size: 13px;
    }

    .lightbox-title {
        font-weight: 500;
    }

    .lightbox-description {
        margin-top: 5px;
        opacity: .7;
    }


    @media (max-width: 800px) {
        .lightbox {
            padding: 20px;
        }

        .lightbox-image {
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 140px);
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .lightbox-prev,
        .lightbox-next {
            font-size: 32px;
            padding: 10px;
        }
    }
</style>
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Podgląd zdjęcia" aria-hidden="true">
    <button class="lightbox-close" id="lightboxClose" type="button" aria-label="Zamknij">×</button>

    <img class="lightbox-image" id="lightboxImage" src="" alt="">

    <button
    class="lightbox-prev"
    id="lightboxPrev"
    type="button"
    aria-label="Poprzednie zdjęcie"
>‹</button>

    <button
    class="lightbox-next"
    id="lightboxNext"
    type="button"
    aria-label="Następne zdjęcie"
>›</button>

    <div class="lightbox-info">
        <div class="lightbox-title" id="lightboxTitle"></div>
        <div class="lightbox-description" id="lightboxDescription"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const allItems = Array.from(document.querySelectorAll('.gallery-item'));
    let items = allItems;
    const lightbox = document.getElementById('lightbox');
    const image = document.getElementById('lightboxImage');
    const title = document.getElementById('lightboxTitle');
    const description = document.getElementById('lightboxDescription');
    const closeButton = document.getElementById('lightboxClose');
    const prevButton = document.getElementById('lightboxPrev');
    const nextButton = document.getElementById('lightboxNext');

    let currentIndex = 0;
    let openingItem = null;
    let previousOverflow = '';

    function showPhoto(index) {
        if (!items.length) return;

        currentIndex = (index + items.length) % items.length;

        const item = items[currentIndex];

        const isOpening = !lightbox.classList.contains('is-open');
        if (isOpening) {
            openingItem = item;
            previousOverflow = document.body.style.overflow;
        }

        image.src = item.dataset.photoUrl;
        image.alt = item.dataset.photoAlt || item.dataset.photoTitle || 'Zdjęcie';
        title.textContent = item.dataset.photoTitle || '';
        description.textContent = item.dataset.photoDescription || '';

        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');

        document.body.style.overflow = 'hidden';
        if (isOpening) {
            closeButton.focus({ preventScroll: true });
        }
    }

    function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        image.src = '';
        document.body.style.overflow = previousOverflow;
        openingItem?.focus({ preventScroll: true });
    }

    allItems.forEach(function (item) {
        function openItem() {
            items = allItems.filter(candidate => candidate.dataset.galleryGroup === item.dataset.galleryGroup);
            showPhoto(items.indexOf(item));
        }
        item.addEventListener('click', function () {
            openItem();
        });
        item.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openItem();
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (event.target === closeButton || closeButton.contains(event.target)) {
            event.preventDefault();
            event.stopPropagation();
            closeLightbox();
        }
    }, true);

    prevButton.addEventListener('click', function () {
        showPhoto(currentIndex - 1);
    });

    nextButton.addEventListener('click', function () {
        showPhoto(currentIndex + 1);
    });

    lightbox.addEventListener('click', function (event) {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (!lightbox.classList.contains('is-open')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeLightbox();
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            showPhoto(currentIndex - 1);
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            showPhoto(currentIndex + 1);
        }

        if (event.key === 'Tab') {
            const controls = [closeButton, prevButton, nextButton];
            const current = controls.indexOf(document.activeElement);
            const next = (current + (event.shiftKey ? -1 : 1) + controls.length) % controls.length;
            event.preventDefault();
            controls[next].focus();
        }
    });
});
</script>
