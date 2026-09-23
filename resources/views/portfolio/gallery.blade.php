@extends('layouts.app')

@section('content')
@php
    $galleryFonts = \App\Support\GalleryTypography::read($settings, $gallery->id);
@endphp
@include('components.site-typography')
<style>
    #lightbox .lightbox-title, #lightbox .lightbox-description { font-family: {{ \App\Support\GalleryTypography::css($galleryFonts, 'caption_font_family', $siteFonts) }}; }
    .gallery-page {
        min-height: 100vh;
        background: #fff;
        color: #222;
    }

    .site-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: rgba(255,255,255,.96);
        border-bottom: 1px solid #eee;
    }

    .header-inner {
        max-width: 1400px;
        min-height: 76px;
        margin: auto;
        padding: 0 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }

    .logo {
        font-size: 17px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .logo span {
        display: block;
        margin-top: 4px;
        font-size: 10px;
        font-weight: 400;
        letter-spacing: .14em;
        color: #777;
    }

    .main-menu {
        display: flex;
        align-items: center;
        gap: 20px;
        font-size: 13px;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .main-menu a {
        color: #222;
        text-decoration: none;
        transition: opacity .2s;
    }

    .main-menu a:hover {
        opacity: .55;
    }

    .gallery-heading {
        max-width: 1400px;
        margin: 0 auto;
        padding: 70px 28px 35px;
    }

    .gallery-heading h1 {
        margin: 0 0 12px;
        font-size: clamp(30px, 5vw, 52px);
        font-weight: 400;
        letter-spacing: -.03em;
    }

    .gallery-heading p {
        margin: 0;
        color: #777;
        font-size: 15px;
    }

    .back-link {
        display: inline-block;
        margin-top: 24px;
        color: #222;
        font-size: 12px;
        letter-spacing: .1em;
        text-transform: uppercase;
        text-decoration: none;
    }

    .back-link:hover {
        opacity: .55;
    }

    .gallery-grid {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 28px 80px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 28px;
    }

    .gallery-item {
        position: relative;
        overflow: hidden;
        cursor: zoom-in;
        background: #f3f3f3;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .gallery-item img {
        width: 100%;
        height: auto;
        max-width: 100%;
        display: block;
        object-fit: contain;
        transition: transform .45s ease;
    }

    .gallery-item:hover img {
        transform: scale(1.04);
    }

    .gallery-caption {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 40px 18px 16px;
        color: #fff;
        background: linear-gradient(transparent, rgba(0,0,0,.65));
        opacity: 0;
        transition: opacity .25s;
    }

    .gallery-item:hover .gallery-caption {
        opacity: 1;
    }

    .gallery-caption strong {
        display: block;
        font-size: 14px;
        font-weight: 500;
    }

    .gallery-caption span {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        opacity: .85;
    }

    .empty-gallery {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 28px 80px;
        color: #777;
    }

    /* LIGHTBOX */

    .lightbox {
        position: fixed;
        inset: 0;
        z-index: 1000;
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

    .lightbox-close {
        position: fixed !important;
        top: 22px !important;
        right: 28px !important;
        left: auto !important;
        transform: none !important;
        z-index: 2147483647 !important;
        display: block !important;
        width: 50px !important;
        height: 50px !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
        color: #fff !important;
        font-size: 34px !important;
        line-height: 50px !important;
        text-align: center !important;
        cursor: pointer !important;
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
        .header-inner {
            padding: 18px 20px;
            align-items: flex-start;
            flex-direction: column;
            gap: 15px;
        }

        .main-menu {
            flex-wrap: wrap;
            gap: 12px;
            font-size: 11px;
        }

        .gallery-heading {
            padding: 45px 20px 28px;
        }

        .gallery-grid {
            padding: 0 20px 50px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

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

    @media (max-width: 480px) {
        .gallery-grid {
            grid-template-columns: 1fr;
        }

        .gallery-item {
            aspect-ratio: auto;
        }
    }
</style>

<div class="gallery-page">

    @include('components.site-header', ['settings' => $globalHeaderSettings, 'menuItems' => $globalHeaderMenuItems])

    <section class="gallery-heading">
        <h1 style="font-family:{{ \App\Support\GalleryTypography::css($galleryFonts, 'title_font_family', $siteFonts) }};">{{ $gallery->title }}</h1>

        @if($gallery->description)
            <p style="font-family:{{ \App\Support\GalleryTypography::css($galleryFonts, 'description_font_family', $siteFonts) }};">{{ $gallery->description }}</p>
        @endif

        <a href="{{ url('/') }}#portfolio" class="back-link">
            ← Powrót do portfolio
        </a>
    </section>

    @if($gallery->photos->count())
        <section class="gallery-grid">
            @foreach($gallery->photos->take(12) as $photo)
                <article
                    class="gallery-item"
                    data-photo-index="{{ $loop->index }}"
                    data-photo-url="{{ asset('storage/photos/' . basename($photo->filename)) }}"
                    data-photo-title="{{ e($photo->title ?? '') }}"
                    data-photo-description="{{ e($photo->description ?? '') }}"
                >
                    <img
                        src="{{ asset('storage/photos/' . basename($photo->thumbnail ?: $photo->filename)) }}"
                        alt="{{ $photo->alt ?: $photo->title ?: $gallery->title }}"
                        loading="lazy"
                    >

                    @if($photo->title || $photo->description)
                        <div class="gallery-caption" style="font-family:{{ \App\Support\GalleryTypography::css($galleryFonts, 'caption_font_family', $siteFonts) }};">
                            @if($photo->title)
                                <strong>{{ $photo->title }}</strong>
                            @endif

                            @if($photo->description)
                                <span>{{ $photo->description }}</span>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </section>
    @else
        <div class="empty-gallery">
            <p>Ta galeria nie zawiera jeszcze zdjęć.</p>
        </div>
    @endif

</div>

<div class="lightbox" id="lightbox" aria-hidden="true">
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
    const items = Array.from(document.querySelectorAll('.gallery-item'));
    const lightbox = document.getElementById('lightbox');
    const image = document.getElementById('lightboxImage');
    const title = document.getElementById('lightboxTitle');
    const description = document.getElementById('lightboxDescription');
    const closeButton = document.getElementById('lightboxClose');
    const prevButton = document.getElementById('lightboxPrev');
    const nextButton = document.getElementById('lightboxNext');

    let currentIndex = 0;

    function showPhoto(index) {
        if (!items.length) return;

        currentIndex = (index + items.length) % items.length;

        const item = items[currentIndex];

        image.src = item.dataset.photoUrl;
        image.alt = item.dataset.photoTitle || 'Zdjęcie';
        title.textContent = item.dataset.photoTitle || '';
        description.textContent = item.dataset.photoDescription || '';

        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');

        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        image.src = '';
        document.body.style.overflow = '';
    }

    items.forEach(function (item, index) {
        item.addEventListener('click', function () {
            showPhoto(index);
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
            closeLightbox();
        }

        if (event.key === 'ArrowLeft') {
            showPhoto(currentIndex - 1);
        }

        if (event.key === 'ArrowRight') {
            showPhoto(currentIndex + 1);
        }
    });
});
</script>
@endsection
