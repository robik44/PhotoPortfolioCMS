@php
    $defaultSettings = [
        'site_title' => 'Fotografia',
        'site_subtitle' => 'Fotografia kulinarna i artystyczna',
        'contact_title' => 'Kontakt',
        'contact_text' => 'Zapraszam do współpracy.',
        'contact_email' => '',
        'footer_text' => '© ' . date('Y') . ' Fotografia',
    ];

    $settings = array_merge($defaultSettings, $settings ?? []);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $settings['contact_title'] }} — {{ $settings['site_title'] }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #222;
            background: #fff;
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
            max-width: 1200px;
            min-height: 76px;
            margin: auto;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .logo {
            white-space: nowrap;
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
            transition: opacity .2s;
        }

        .main-menu a:hover {
            opacity: .55;
        }

        .contact-page {
            min-height: calc(100vh - 150px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 28px;
        }

        .contact-inner {
            max-width: 760px;
            width: 100%;
            text-align: center;
        }

        .contact-inner h1 {
            margin: 0 0 28px;
            font-size: 42px;
            font-weight: 400;
        }

        .contact-inner p {
            margin: 0 auto;
            max-width: 650px;
            color: #666;
            line-height: 1.8;
            white-space: pre-line;
        }

        .contact-email {
            display: inline-block;
            margin-top: 30px;
            color: #222;
            border-bottom: 1px solid #222;
            padding-bottom: 4px;
        }

        .site-footer {
            padding: 28px;
            text-align: center;
            color: #777;
            border-top: 1px solid #eee;
            font-size: 13px;
        }

        @media (max-width: 700px) {
            .header-inner {
                align-items: flex-start;
                flex-direction: column;
                padding-top: 18px;
                padding-bottom: 18px;
            }

            .main-menu {
                flex-wrap: wrap;
                gap: 12px 18px;
            }

            .contact-page {
                padding: 70px 20px;
            }

            .contact-inner h1 {
                font-size: 34px;
            }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="{{ url('/') }}" class="logo">
            <span style="display:block;font-size:28px;font-weight:700;letter-spacing:.08em;color:#222;">
                MAGDA GUGAŁA
            </span>
            <span style="display:block;font-size:10px;font-weight:400;letter-spacing:.14em;color:#777;margin-top:4px;">
                FOTOGRAFIA
            </span>
            <span style="display:block;font-size:10px;font-weight:400;letter-spacing:.14em;color:#777;margin-top:4px;">
                {{ $settings['site_subtitle'] }}
            </span>
        </a>

        <nav class="main-menu">
            <a href="{{ url('/') }}">Start</a>
            <a href="{{ url('/#portfolio') }}">Portfolio</a>
            <a href="{{ route('about') }}">O mnie</a>
            <a href="{{ route('contact') }}">Kontakt</a>

            @auth
                @else
                <a href="{{ route('login') }}">Logowanie</a>
            @endauth
        </nav>
    </div>
</header>

<main>
    <section class="contact-page">
        <div class="contact-inner">
            <h1>{{ $settings['contact_title'] }}</h1>

            <p>{{ $settings['contact_text'] }}</p>

            @if($settings['contact_email'])
                <a
                    class="contact-email"
                    href="mailto:{{ $settings['contact_email'] }}"
                >
                    {{ $settings['contact_email'] }}
                </a>
            @endif
        </div>
    </section>
</main>

<footer class="site-footer">
    {{ $settings['footer_text'] }}
</footer>

</body>
</html>
