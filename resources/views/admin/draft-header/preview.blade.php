<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive"><title>Testowy nagłówek FoodFoto</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f5f3ee;color:#26241f;font-family:Georgia,serif}
.preview-alert{padding:12px 4vw;background:#233b30;color:#fff;font:14px/1.4 Arial,sans-serif;display:flex;gap:24px;justify-content:space-between;align-items:center}
.preview-alert a{color:white}.scene{position:relative;min-height:650px;background:#ebe7df;overflow:hidden}
.scene>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.scene:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(245,243,238,.84),rgba(245,243,238,.38) 44%,transparent 75%);pointer-events:none}
.site-head{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:var(--gap);padding:12px 4vw;min-height:var(--height)}
.brand{display:flex;align-items:center;gap:14px;flex-shrink:0;text-decoration:none;color:#171717;background:rgba(var(--logo-rgb),var(--logo-opacity));padding:12px 20px;border-radius:8px}
.brand img{max-height:56px;width:auto;max-width:80px;object-fit:contain}.brand .divider{width:1px;align-self:stretch;background:#aaa;min-height:36px}.brand strong{font:400 24px Georgia,serif;white-space:nowrap}.brand small{display:block;font:9px Arial,sans-serif;letter-spacing:.16em;margin-top:5px}
nav{display:flex;gap:20px;align-items:center;flex-wrap:wrap;background:var(--menu-bg);padding:21px 28px;border-radius:var(--radius)}
nav a{font:11px Arial,sans-serif;text-transform:uppercase;letter-spacing:.1em;color:#222;text-decoration:none;white-space:nowrap}
.hero-copy{position:relative;z-index:1;margin:75px 0 0 8%;max-width:650px}
.hero-copy h1{font:400 clamp(45px,5vw,83px)/.98 Georgia,serif;letter-spacing:-.04em;margin:0 0 24px}
.hero-copy p{font:16px/1.65 Arial,sans-serif;max-width:420px}
@media(max-width:1050px){.site-head{gap:12px;flex-wrap:wrap}.brand{padding:9px}.brand strong{font-size:20px}nav{gap:12px;padding:14px}.hero-copy{margin:70px 6vw}}
@media(max-width:600px){.site-head{background:rgba(255,255,255,.94)}.brand strong{font-size:17px}nav{width:100%;justify-content:center}nav a{font-size:10px}.scene{min-height:700px}.hero-copy{margin-top:80px}.hero-copy h1{font-size:48px}}
</style></head>
<body>
<div class="preview-alert"><span>WERSJA TESTOWA — widoczna tylko po zalogowaniu. Strona publiczna nie jest zmieniona.</span><a href="{{ route('draft-header.edit') }}">← Edytuj nagłówek</a></div>
@php
    $logoHex = ltrim($settings['draft_header_logo_background'], '#');
    $logoRgb = implode(',', array_map('hexdec', str_split($logoHex, 2)));
@endphp
<div class="scene" style="--logo-rgb:{{ $logoRgb }};--logo-opacity:{{ (int)$settings['draft_header_logo_opacity'] / 100 }};--gap:{{ (int)$settings['draft_header_gap'] }}px;--radius:{{ (int)$settings['draft_header_radius'] }}px;--height:{{ (int)$settings['draft_header_height'] }}px;--logo-bg:{{ $settings['draft_header_logo_background'] }};--menu-bg:{{ $settings['draft_header_menu_background'] }}">
<img src="{{ $heroUrl }}" alt="Zdjęcie górnego banera">
<header class="site-head"><div class="brand">
@if(!empty($header['header_brand_image']))<img src="{{ asset('storage/'.$header['header_brand_image']) }}" alt="FoodFoto"><span class="divider"></span>@else <strong>food<br>foto</strong><span class="divider"></span>@endif
<span><strong>{{ $header['logo'] }}</strong><small>{{ $header['logo_subtitle'] }}</small></span></div>
<nav aria-label="Menu (podgląd)">
@forelse($menuItems as $item)<a href="#" onclick="return false">{{ $item->label ?? $item->title ?? 'Menu' }}</a>@empty<a href="#">Portfolio</a><a href="#">O mnie</a><a href="#">Kontakt</a>@endforelse
</nav></header>
<div class="hero-copy"><h1>Fotografia<br>i stylizacja żywności</h1><p>Tworzę apetyczne obrazy, które budują marki i opowiadają historie.</p></div>
</div>
</body></html>