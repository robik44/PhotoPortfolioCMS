@php
    $backLink = \App\Support\GalleryBackLink::read($settings, $gallery->id);
    $galleryFonts = \App\Support\GalleryTypography::read($settings, $gallery->id);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.seo-meta', ['seo' => \App\Support\Seo::meta($gallery)])

@include('components.site-typography')
<style>
    * { box-sizing: border-box; }
    body { margin: 0; color: #222; background: {{ $settings['background_color'] ?? '#ffffff' }}; }
    a { color: inherit; text-decoration: none; }
    #lightbox .lightbox-title, #lightbox .lightbox-description { font-family: {{ \App\Support\GalleryTypography::css($galleryFonts, 'caption_font_family', $siteFonts) }}; }
    @if(isset($galleryFonts['caption_font_size']))
        .gallery-page .gallery-caption strong, .gallery-page .gallery-caption span,
        #lightbox .lightbox-title, #lightbox .lightbox-description { font-size: {{ (float) $galleryFonts['caption_font_size'] }}px; }
    @endif
    .gallery-page {
        min-height: 100vh;
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
</head>
<body class="site-typography">

<div class="gallery-page">

    @include('components.site-header', ['settings' => $globalHeaderSettings, 'menuItems' => $globalHeaderMenuItems])

    <section class="gallery-heading">
        <h1 style="font-family:{{ \App\Support\GalleryTypography::css($galleryFonts, 'title_font_family', $siteFonts) }};@if(isset($galleryFonts['title_font_size']))font-size:{{ (float) $galleryFonts['title_font_size'] }}px;@endif">{{ $gallery->title }}</h1>

        @if($gallery->description)
            <p style="font-family:{{ \App\Support\GalleryTypography::css($galleryFonts, 'description_font_family', $siteFonts) }};@if(isset($galleryFonts['description_font_size']))font-size:{{ (float) $galleryFonts['description_font_size'] }}px;@endif">{{ $gallery->description }}</p>
        @endif

        <a href="{{ url('/') }}#portfolio" class="back-link" @if($backLink) style="{{ \App\Support\TypographySettings::css($backLink, 'back', $siteFonts) }}@if(!empty($backLink['back_color']))color:{{ $backLink['back_color'] }};@endif" @endif>
            ← {{ $backLink['back_text'] ?? 'Powrót do galerii' }}
        </a>
    </section>

    @if($gallery->photos->count())
        <section class="gallery-grid">
            @foreach($gallery->photos as $photo)
                @php($photoTypography = \App\Support\TypographySettings::read($settings, 'photo_'.$photo->id.'_typography'))
                <article
                    class="gallery-item"
                    role="button"
                    tabindex="0"
                    aria-label="{{ 'Otwórz zdjęcie: ' . ($photo->title ?: $photo->alt ?: $gallery->title) }}"
                    @include('components.photo-typography-attributes', ['typography' => $photoTypography])
                    data-photo-index="{{ $loop->index }}"
                    data-photo-url="{{ $photo->imageUrl() }}"
                    data-photo-alt="{{ $photo->alt ?: $photo->title ?: $gallery->title }}"
                    data-photo-title="{{ $photo->title ?? '' }}"
                    data-photo-description="{{ $photo->description ?? '' }}"
                >
                    <img
                        src="{{ asset('storage/photos/' . basename($photo->thumbnail ?: $photo->filename)) }}"
                        alt="{{ $photo->alt ?: $photo->title ?: $gallery->title }}"
                        loading="lazy"
                    >

                    @if($photo->title || $photo->description)
                        <div class="gallery-caption" style="font-family:{{ \App\Support\GalleryTypography::css($galleryFonts, 'caption_font_family', $siteFonts) }};">
                            @if($photo->title)
                                <strong @if($css = \App\Support\TypographySettings::css($photoTypography, 'title', $siteFonts)) style="{{ $css }}" @endif>{{ $photo->title }}</strong>
                            @endif

                            @if($photo->description)
                                <span @if($css = \App\Support\TypographySettings::css($photoTypography, 'description', $siteFonts)) style="{{ $css }}" @endif>{{ $photo->description }}</span>
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

@include('components.gallery-lightbox')
</body>
</html>
