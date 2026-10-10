<style>
    /* LIGHTBOX */

    .lightbox {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 40px;
        background: rgba(0,0,0,.28);
    }

    .lightbox.is-open {
        display: flex;
    }

    .lightbox {
        touch-action: pan-y pinch-zoom;
    }

    .lightbox-frame {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: 72vw;
        max-height: 72vh;
    }

    .lightbox-image {
        position: relative;
        z-index: 1;
        max-width: 72vw;
        max-height: 72vh;
        width: auto;
        height: auto;
        object-fit: contain;
        object-position: center;
        user-select: none;
        display: block;
        box-shadow: 0 16px 50px rgba(0,0,0,.28);
    }

    #lightbox .lightbox-close {
        position: absolute !important;
        top: 0 !important;
        right: 0 !important;
        left: auto !important;
        transform: translate(45%, -45%) !important;
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
        position: absolute !important;
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
        left: 0 !important;
        right: auto !important;
        transform: translate(-130%, -50%) !important;
    }

    .lightbox-next {
        right: 0 !important;
        left: auto !important;
        transform: translate(130%, -50%) !important;
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

        .lightbox-frame {
            max-width: 86vw;
            max-height: 70vh;
        }

        .lightbox-image {
            max-width: 86vw;
            max-height: 70vh;
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
    <div class="lightbox-frame">
        <img class="lightbox-image" id="lightboxImage" alt="Podgląd zdjęcia" aria-hidden="true">

        <button class="lightbox-close" id="lightboxClose" type="button" aria-label="Zamknij">×</button>

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
    </div>

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
    let touchStartX = null;
    let touchStartY = null;

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
        const applyResponsiveTypography = function (target, prefix) {
            const viewport = typeof window !== 'undefined' && window.innerWidth ? window.innerWidth : 1200;
            let css = item.dataset[prefix + 'TypographyDesktop'] || '';
            if (viewport <= 900) css += item.dataset[prefix + 'TypographyTablet'] || '';
            if (viewport <= 520) css += item.dataset[prefix + 'TypographyMobile'] || '';

            target.style.cssText = css;
            if (!css) {
                target.style.fontFamily = item.dataset[prefix + 'FontFamily'] || '';
                target.style.fontSize = item.dataset[prefix + 'FontSize'] || '';
            }
        };

        title.textContent = item.dataset.photoTitle || '';
        applyResponsiveTypography(title, 'title');
        description.textContent = item.dataset.photoDescription || '';
        applyResponsiveTypography(description, 'description');

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
        image.removeAttribute('src');
        image.alt = 'Podgląd zdjęcia';
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

    window.addEventListener('resize', function () {
        if (lightbox.classList.contains('is-open') && items[currentIndex]) {
            showPhoto(currentIndex);
        }
    });

    lightbox.addEventListener('touchstart', function (event) {
        if (event.touches.length !== 1) {
            touchStartX = null;
            touchStartY = null;
            return;
        }

        touchStartX = event.touches[0].clientX;
        touchStartY = event.touches[0].clientY;
    }, { passive: true });

    lightbox.addEventListener('touchend', function (event) {
        if (touchStartX === null || touchStartY === null || event.changedTouches.length !== 1) return;

        const dx = event.changedTouches[0].clientX - touchStartX;
        const dy = event.changedTouches[0].clientY - touchStartY;

        touchStartX = null;
        touchStartY = null;

        if (Math.abs(dx) < 45 || Math.abs(dx) <= Math.abs(dy)) return;

        if (dx < 0) {
            showPhoto(currentIndex + 1);
        } else {
            showPhoto(currentIndex - 1);
        }
    }, { passive: true });

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
