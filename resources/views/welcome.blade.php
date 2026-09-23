@php
    $defaultSettings = [
        'site_title' => 'Fotografia',
        'site_subtitle' => 'Fotografia kulinarna i artystyczna',
        'hero_title' => 'Fotografia to sposób patrzenia na świat',
        'hero_subtitle' => 'Obrazy, smaki i historie',
        'hero_button' => 'Zobacz portfolio',
        'about_title' => 'O mnie',
        'about_text' => 'Tworzę fotografie, które opowiadają historie poprzez światło, formę i detal.',
        'contact_title' => 'Kontakt',
        'contact_text' => 'Zapraszam do współpracy.',
        'contact_email' => '',
        'footer_text' => '© ' . date('Y') . ' Fotografia',
    ];

    $settings = array_merge($defaultSettings, $settings ?? []);

    $heroPhoto = $heroPhoto ?? null;

    $heroImageUrl = $heroImageUrl ?? null;

    if (!$heroImageUrl && $heroPhoto) {
        $heroImageUrl = asset('storage/photos/' . basename($heroPhoto->filename));
    }

    $heroImageUrl = $heroImageUrl ?: null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $settings['site_title'] }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            color: #222;
            background: {{ $settings['background_color'] ?? '#ffffff' }};
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
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
            gap: 28px;
            font-size: 13px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .main-menu-item {
            position: relative;
        }

        .main-menu-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 0;
            transition: opacity .2s;
        }

        .main-menu-link:hover {
            opacity: .55;
        }

        .main-menu-arrow {
            font-size: 9px;
            line-height: 1;
        }

        .main-submenu {
            position: absolute;
            top: calc(100% + 1px);
            left: -14px;
            min-width: 190px;
            padding: 10px 0;
            margin: 0;
            background: #fff;
            border: 1px solid #eee;
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
            list-style: none;
            opacity: 0;
            visibility: hidden;
            transform: translateY(6px);
            transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
            z-index: 200;
        }

        .main-menu-item:hover > .main-submenu,
        .main-menu-item:focus-within > .main-submenu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .main-submenu li {
            margin: 0;
            padding: 0;
        }

        .main-submenu a {
            display: block;
            padding: 9px 16px;
            white-space: nowrap;
            transition: background .18s, opacity .18s;
        }

        .main-submenu a:hover {
            opacity: 1;
            background: #f7f7f7;
        }

        .hero {
            position: relative;
            min-height: 430px;
            display: flex;
            align-items: center;
            background:
                linear-gradient(rgba(0,0,0,.32), rgba(0,0,0,.32)),
                url('{{ $heroImageUrl ?: asset('images/hero.jpg') }}')
                center / cover no-repeat;
        }

        .hero-content {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            padding: 80px 28px;
            color: #fff;
        }

        .hero-content h1 {
            max-width: 720px;
            margin: 0 0 24px;
            font-size: clamp(36px, 5vw, 70px);
            line-height: 1.05;
            font-weight: 400;
        }

        .hero-content p {
            max-width: 520px;
            margin: 0 0 34px;
            font-size: 18px;
            line-height: 1.6;
        }

        .button {
            display: inline-block;
            padding: 15px 25px;
            border: 1px solid currentColor;
            font-size: 12px;
            letter-spacing: .12em;
            text-transform: uppercase;
            transition: background .2s, color .2s;
        }

        .button:hover {
            background: #fff;
            color: #222;
        }

        .section {
            max-width: 1200px;
            margin: auto;
            padding: 55px 28px;
        }

        .section-heading {
            margin-bottom: 45px;
        }

        .section-heading h2 {
            margin: 0 0 14px;
            font-size: 34px;
            font-weight: 400;
        }

        .section-heading p {
            max-width: 650px;
            margin: 0;
            color: #777;
            line-height: 1.7;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .gallery-card {
            display: block;
            overflow: hidden;
            background: #f4f4f4;
        }

        .gallery-card-image {
            overflow: hidden;
            background: #f7f7f7;
        }

        .gallery-card-image img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: contain;
            transition: transform .45s ease;
        }

        .gallery-card:hover img {
            transform: scale(1.04);
        }

        .gallery-card-content {
            padding: 20px 2px 4px;
        }

        .gallery-card-content h3 {
            margin: 0 0 8px;
            font-size: 17px;
            font-weight: 400;
        }

        .gallery-card-content p {
            margin: 0;
            color: #777;
            font-size: 14px;
            line-height: 1.6;
        }

        .empty-gallery {
            padding: 50px;
            text-align: center;
            color: #777;
            background: #f7f7f7;
        }

        .about-section {
            background: transparent;
        }

        .about-inner {
            max-width: 1200px;
            margin: auto;
            padding: 55px 28px;
        }

        .about-inner h2,
        .contact-inner h2 {
            margin: 0 0 20px;
            font-size: 34px;
            font-weight: 400;
        }

        .about-inner p,
        .contact-inner p {
            max-width: 700px;
            color: #666;
            line-height: 1.8;
        }

        .contact-inner {
            max-width: 1200px;
            margin: auto;
            padding: 55px 28px;
        }

        .contact-email {
            display: inline-block;
            margin-top: 15px;
            font-size: 18px;
            border-bottom: 1px solid #222;
        }

        .site-footer {
            padding: 30px 28px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 800px) {
            .header-inner {
                min-height: 65px;
                padding: 0 18px;
            }

            .main-menu {
                gap: 14px;
                font-size: 10px;
            }

            .logo {
                font-size: 16px;
            }

            .hero {
                min-height: 520px;
            }

            .hero-content {
                padding: 60px 20px;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .section,
            .about-inner,
            .contact-inner {
                padding: 65px 20px;
            }
        }

        @media (max-width: 520px) {
            .header-inner {
                align-items: flex-start;
                padding-top: 18px;
                padding-bottom: 18px;
                flex-direction: column;
                gap: 12px;
            }

            .main-menu {
                flex-wrap: wrap;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .hero-content h1 {
                font-size: 40px;
            }
        }

        @media (max-width: 1400px) {
            .gallery-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .gallery-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
            }
        }

        @media (max-width: 600px) {
            .gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }
        }

        @media (max-width: 420px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>
</head>

<body style="background: {{ $settings['background_color'] ?? '#ffffff' }};">

@include("components.site-header", ["settings" => $settings, "menuItems" => $menuItems])

@include('components.site-typography')
<main class="site-typography">

    <section class="hero">
        <div class="hero-content">
            <h1>{{ $settings['hero_title'] }}</h1>

            <p>{{ $settings['hero_subtitle'] }}</p>

        </div>
    </section>

    <section class="section" id="portfolio">
        <div class="section-heading">
            <h2>Portfolio</h2>

            <p>
                {{ $settings['site_subtitle'] }}
            </p>
        </div>

        @if(isset($galleries) && $galleries->count())
            <div class="gallery-grid">
                @foreach($galleries as $gallery)
                    @php
                        $galleryFonts = \App\Support\GalleryTypography::read($settings, $gallery->id);
                        $cover = $gallery->photos->firstWhere('is_cover', true)
                            ?: $gallery->photos->first();
                    @endphp

                    <a
                        href="{{ route('portfolio.gallery', $gallery) }}"
                        class="gallery-card"
                    >
                        <div class="gallery-card-image">
                            @if($cover)
                                <img
                                    src="{{ asset('storage/photos/' . basename($cover->filename)) }}"
                                    alt="{{ $cover->alt ?: $gallery->title }}"
                                >
                            @else
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;">
                                    Brak zdjęcia
                                </div>
                            @endif
                        </div>

                        <div class="gallery-card-content">
                            <h3 style="font-family:{{ \App\Support\GalleryTypography::css($galleryFonts, 'title_font_family', $siteFonts) }};">{{ $gallery->title }}</h3>

                            @if($gallery->description)
                                <p style="font-family:{{ \App\Support\GalleryTypography::css($galleryFonts, 'description_font_family', $siteFonts) }};">{{ $gallery->description }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="empty-gallery">
                Galerie pojawią się tutaj po ich dodaniu w panelu administracyjnym.
            </div>
        @endif
    </section>

</main>

<footer class="site-footer site-typography">
    {{ $settings['footer_text'] }}
</footer>

</body>
</html>
EOF