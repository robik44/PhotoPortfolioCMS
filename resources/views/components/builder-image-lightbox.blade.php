<style>
    .builder-zoom-trigger {
        display: block;
        width: 100%;
        padding: 0;
        border: 0;
        background: transparent;
        cursor: zoom-in;
        overflow: hidden;
        text-align: inherit;
    }

    .builder-zoom-trigger img {
        transition: transform .22s ease, filter .22s ease;
        will-change: transform;
    }

    .builder-zoom-trigger:hover img {
        transform: translateY(-2px) scale(1.012);
        filter: brightness(.97);
    }

    .builder-zoom-trigger:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 4px;
    }

    .builder-image-lightbox {
        position: fixed;
        inset: 0;
        z-index: 2147483600;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 40px;
        background: rgba(0,0,0,.28);
        overscroll-behavior: contain;
    }

    .builder-image-lightbox.is-open {
        display: flex;
    }

    .builder-image-lightbox-frame {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: 72vw;
        max-height: 72vh;
    }

    .builder-image-lightbox-image {
        display: block;
        width: auto;
        height: auto;
        max-width: 72vw;
        max-height: 72vh;
        object-fit: contain;
        object-position: center;
        user-select: none;
        box-shadow: 0 16px 50px rgba(0,0,0,.28);
    }

    .builder-image-lightbox-close {
        position: absolute;
        top: 0;
        right: 0;
        transform: translate(45%, -45%);
        z-index: 2147483647;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 54px;
        padding: 0;
        border: 2px solid rgba(255,255,255,.82);
        border-radius: 50%;
        background: #222;
        color: #fff;
        font: 400 38px/1 Arial, Helvetica, sans-serif;
        cursor: pointer;
    }

    .builder-image-lightbox-close:hover {
        background: #444;
    }

    @media (max-width: 700px) {
        .builder-image-lightbox {
            padding: 20px;
        }

        .builder-image-lightbox-frame {
            max-width: 86vw;
            max-height: 70vh;
        }

        .builder-image-lightbox-image {
            max-width: 86vw;
            max-height: 70vh;
        }

        .builder-image-lightbox-close {
            width: 46px;
            height: 46px;
            font-size: 32px;
        }
    }
</style>

<div class="builder-image-lightbox" id="builderImageLightbox" role="dialog" aria-modal="true" aria-label="Powiększone zdjęcie" aria-hidden="true">
    <div class="builder-image-lightbox-frame">
        <img class="builder-image-lightbox-image" id="builderImageLightboxImage" alt="Podgląd zdjęcia" aria-hidden="true">
        <button type="button" class="builder-image-lightbox-close" id="builderImageLightboxClose" aria-label="Zamknij">×</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('builderImageLightbox');
    const modalImage = document.getElementById('builderImageLightboxImage');
    const close = document.getElementById('builderImageLightboxClose');
    if (!modal || !modalImage || !close) return;

    let opener = null;
    let previousOverflow = '';

    function openModal(trigger) {
        const src = trigger.dataset.zoomUrl;
        if (!src) return;

        opener = trigger;
        previousOverflow = document.body.style.overflow;
        modalImage.src = src;
        modalImage.alt = trigger.dataset.zoomAlt || '';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        close.focus({preventScroll: true});
    }

    function closeModal() {
        if (!modal.classList.contains('is-open')) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modalImage.removeAttribute('src');
        modalImage.alt = 'Podgląd zdjęcia';
        document.body.style.overflow = previousOverflow;
        opener?.focus({preventScroll: true});
    }

    document.querySelectorAll('[data-builder-image-zoom]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            openModal(trigger);
        });
    });

    close.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            event.preventDefault();
            closeModal();
        }
    });
});
</script>
