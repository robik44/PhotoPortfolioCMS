@php
$p = json_decode($builderHeaderSettings['draft_header_builder_positions'] ?? '{}', true) ?: [];
$logo = $p['brand-image'] ?? []; $name = $p['brand-text'] ?? []; $line = $p['brand-divider'] ?? []; $menu = $p['menu-panel'] ?? [];
$radius = implode(' ', array_map(static fn($n) => max(0,min(120,(int)$n)).'px', [$menu['rtl']??10,$menu['rtr']??10,$menu['rbr']??24,$menu['rbl']??40]));
$photo = $draftPreviewImage ?: ($builderHeaderSettings['preview_hero_image'] ?? null);
$full = $headerVariantValue === 'full';
$isPublicDraftHeader = (bool) ($isPublicDraftHeader ?? false);
$brand = $p['brand'] ?? [];
$eyebrow = $p['eyebrow'] ?? [];
$headline = $p['headline'] ?? [];
$description = $p['description'] ?? [];
$cta = $p['cta'] ?? [];
$font = static fn ($item, $default) => max(8,min(120,(float)($item['font'] ?? $default)));
$xy = static fn ($item,$axis) => max(-400,min(400,(int)($item[$axis] ?? 0)));

$blocks = ['eyebrow' => $p['eyebrow'] ?? [], 'headline' => $p['headline'] ?? [], 'description' => $p['description'] ?? [], 'cta' => $p['cta'] ?? []];
$styleFor = static function (array $item, int $defaultSize, int $defaultWidth) {
  $x = max(-400,min(400,(int)($item['x']??0)));
  $y = max(-300,min(300,(int)($item['y']??0)));
  $font = max(8,min(120,(float)($item['font']??$defaultSize)));
  $w = max(40,min(1400,(int)($item['w']??$defaultWidth)));
  $spacing = isset($item['spacing']) ? max(-2,min(20,(float)$item['spacing'])) : null;
  $line = isset($item['line']) ? max(.7,min(3,(float)$item['line'])) : null;
  $optional = ($spacing === null ? '' : "--ls:{$spacing}px;").($line === null ? '' : "--lh:{$line};");
  return "--dx:{$x}px;--dy:{$y}px;--fs:{$font}px;--lw:{$w}px;{$optional}";
};
@endphp
<div id="fve-header-variant-preview" class="{{ $isPublicDraftHeader ? 'ff-public-header' : 'ff-private-header' }}" data-variant="{{ $headerVariantValue }}">
<section class="ff-shared-stage {{ $full ? 'ff-full' : 'ff-simple' }}" data-menu-width="{{ (int)($menu['w'] ?? 0) }}" style="--ff-radius:{{ $radius }};--ff-logo:{{ max(30,min(200,(int)($logo['w']??100))) }}px;--ff-name:{{ max(15,min(100,(float)($name['font']??36))) }}px;--ff-line:{{ max(15,min(180,(int)($line['h']??85))) }}px;--ff-menu-scale:{{ max(.6,min(2,(float)($menu['w']??780)/780)) }};--ff-brand-x:{{ (int)($p['brand']['x']??0) }}px;--ff-brand-y:{{ (int)($p['brand']['y']??0) }}px;--ff-menu-x:{{ (int)($menu['x']??0) }}px;--ff-menu-y:{{ (int)($menu['y']??0) }}px;--ff-logo-x:{{ $xy($logo,'x') }}px;--ff-logo-y:{{ $xy($logo,'y') }}px;--ff-name-x:{{ $xy($name,'x') }}px;--ff-name-y:{{ $xy($name,'y') }}px;--ff-line-x:{{ $xy($line,'x') }}px;--ff-line-y:{{ $xy($line,'y') }}px">
@if($photo)<img class="ff-shared-photo" src="{{ asset('storage/'.$photo) }}" alt="">@endif
<div class="ff-shared-bar">
@if($isPublicDraftHeader)
<a class="ff-shared-brand" href="{{ url('/') }}" aria-label="FoodFoto — strona główna">
@else
<div class="ff-shared-brand">
@endif
@if(is_file(storage_path('app/public/branding/foodfoto-transparent-v2.png')))
<img src="{{ asset('storage/branding/foodfoto-transparent-v2.png') }}" alt="FoodFoto">
@else<strong>food<br>foto</strong>@endif
<span class="ff-shared-rule"></span>
<span class="ff-shared-name"><strong>Magda Gugała</strong><small>FOTOGRAFIA I STYLIZACJA ŻYWNOŚCI</small></span>
@if($isPublicDraftHeader)
</a>
@else
</div>
@endif
<div class="ff-shared-menu">
@if(!$isPublicDraftHeader)<span class="ff-menu-grip" aria-hidden="true">⠿</span>@endif
<nav class="ff-menu-links" aria-label="Menu główne">
@forelse($builderHeaderMenuItems as $item)
    @php
        if ($item->type === 'page' && $item->page) {
            $draftMenuUrl = \App\Support\Seo::pageUrl($item->page);
        } elseif ($item->type === 'gallery' && $item->gallery) {
            $draftMenuUrl = route('portfolio.gallery', $item->gallery);
        } elseif ($item->type === 'url' && $item->url) {
            $draftMenuUrl = \App\Support\BuilderButton::href($item->url) ?? '#';
        } else {
            $draftMenuUrl = '#';
        }
    @endphp
    @if($isPublicDraftHeader)
        <span class="ff-nav-item"><a href="{{ $draftMenuUrl }}">{{ $item->title }}</a>
        @if($item->children?->count())
            <span class="ff-subnav">
                @foreach($item->children as $child)
                    @php
                        $childDraftUrl = $child->type === 'page' && $child->page
                            ? \App\Support\Seo::pageUrl($child->page)
                            : ($child->type === 'gallery' && $child->gallery
                                ? route('portfolio.gallery', $child->gallery)
                                : ($child->type === 'url' && $child->url
                                    ? (\App\Support\BuilderButton::href($child->url) ?? '#') : '#'));
                    @endphp
                    <a href="{{ $childDraftUrl }}">{{ $child->title }}</a>
                @endforeach
            </span>
        @endif
        </span>
    @else
        <span>{{ $item->title }}</span>
    @endif
@empty
    @if($isPublicDraftHeader)
        <a href="{{ url('/') }}">Start</a><a href="{{ url('/#portfolio') }}">Portfolio</a>
        <a href="{{ route('about') }}">O mnie</a><a href="{{ route('contact') }}">Kontakt</a>
    @endif
@endforelse
</nav>
@if($isPublicDraftHeader)
<a class="ff-shared-contact" href="{{ route('contact') }}">{{ $builderHeaderSettings['draft_header_contact_text'] ?? 'POROZMAWIAJMY' }}</a>
@else
<span class="ff-shared-contact">{{ $builderHeaderSettings['draft_header_contact_text'] ?? 'POROZMAWIAJMY' }}</span>
@endif
</div></div>
<div class="ff-shared-hero">

<div class="ff-eyebrow" style="{{ $styleFor($blocks['eyebrow'],11,500) }}">{{ $pageBannerDraft['eyebrow'] ?? ($builderHeaderSettings['draft_header_eyebrow'] ?? 'APETYT NA PIĘKNE HISTORIE') }}</div>
<h1 style="{{ $styleFor($blocks['headline'],83,850) }}">{{ $pageBannerDraft['title'] ?? ($builderHeaderSettings['draft_header_hero_title'] ?? "Fotografia\ni stylizacja żywności") }}</h1>
<p style="{{ $styleFor($blocks['description'],16,560) }}">{{ $pageBannerDraft['description'] ?? ($builderHeaderSettings['draft_header_hero_description'] ?? 'Tworzę apetyczne obrazy, które budują marki, opowiadają historie i inspirują do dobrego jedzenia.') }}</p>
@if($isPublicDraftHeader)
<a href="{{ url('/#portfolio') }}" class="ff-cta" style="{{ $styleFor($blocks['cta'],14,300) }}">
@else
<span class="ff-cta" style="{{ $styleFor($blocks['cta'],14,300) }}">
@endif{{ $pageBannerDraft['button'] ?? ($builderHeaderSettings['draft_header_cta_text'] ?? 'ZOBACZ PORTFOLIO') }}　→@if($isPublicDraftHeader)</a>@else</span>@endif
</div>
</section>
</div>
<style>
#fve-header-variant-preview .ff-shared-stage{position:relative;isolation:isolate;overflow:hidden;background:#f8f7f3;min-height:110px;color:#242421}
#fve-header-variant-preview .ff-shared-stage.ff-full{height:540px}
#fve-header-variant-preview .ff-shared-photo{display:none}
#fve-header-variant-preview .ff-full .ff-shared-photo{display:block;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-2}
#fve-header-variant-preview .ff-full:before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#f8f7f3d9,#f8f7f355 48%,transparent 90%);z-index:-1}
#fve-header-variant-preview .ff-shared-bar{display:flex;justify-content:space-between;align-items:center;gap:30px;padding:12px 4vw;min-height:84px}
#fve-header-variant-preview .ff-shared-brand{display:flex;align-items:center;gap:14px;padding:8px 4px 8px 0;transform:translate(var(--ff-brand-x),var(--ff-brand-y));flex-shrink:0}
#fve-header-variant-preview .ff-shared-brand img{width:var(--ff-logo);height:auto;transform:translate(var(--ff-logo-x),var(--ff-logo-y))}
#fve-header-variant-preview .ff-shared-brand>strong{font:900 32px/.8 Arial}
#fve-header-variant-preview .ff-shared-rule{height:var(--ff-line);width:22px;position:relative;flex-shrink:0;transform:translate(var(--ff-line-x),var(--ff-line-y))}.ff-shared-rule:before{content:"";position:absolute;left:50%;top:0;height:100%;width:1px;background:#aaa}
#fve-header-variant-preview .ff-shared-name{transform:translate(var(--ff-name-x),var(--ff-name-y))}.ff-shared-name strong{display:block;font:normal var(--ff-name) Georgia,serif;white-space:nowrap}
#fve-header-variant-preview .ff-shared-name small{display:block;font:11px Arial;letter-spacing:.12em;white-space:nowrap}
#fve-header-variant-preview .ff-shared-menu{position:relative;top:auto;right:auto;margin-left:auto;z-index:5;display:flex;align-items:center;gap:8px;padding:8px 10px 8px 26px;border:1px solid #dedbd5;border-radius:var(--ff-radius);background:white;white-space:nowrap;flex-shrink:0;transform-origin:right center;transform:translate(var(--ff-menu-x),var(--ff-menu-y))}
#fve-header-variant-preview .ff-shared-menu span{font:11px Arial;letter-spacing:.1em}
#fve-header-variant-preview .ff-menu-links{display:flex;align-items:center;gap:22px;margin:0;padding:12px 6px;background:transparent;border:0;flex-wrap:nowrap}
#fve-header-variant-preview .ff-menu-links span{font:11px Arial;letter-spacing:.1em;white-space:nowrap}
#fve-header-variant-preview .ff-menu-grip{font:20px Arial;color:#7c8379;letter-spacing:0}
#fve-header-variant-preview .ff-shared-contact{border:1px solid #344639;border-radius:999px;padding:14px 22px;font-size:11px!important;letter-spacing:.12em!important}
#fve-header-variant-preview .ff-shared-hero{position:absolute;left:4%;top:177px;margin:0;max-width:820px;z-index:2}
#fve-header-variant-preview .ff-eyebrow{font:11px/1.4 Arial;letter-spacing:.38em;color:#4f584b;margin:0 0 20px}
#fve-header-variant-preview .ff-shared-hero h1{white-space:pre-line;font:normal 83px/1.04 Georgia,serif;letter-spacing:-.04em;margin:0 0 18px}
#fve-header-variant-preview .ff-shared-hero p{font:16px/1.65 Arial;max-width:560px}
#fve-header-variant-preview .ff-cta{display:inline-block;background:#597649;border-radius:100px;padding:17px 32px;color:white;font:12px Arial;letter-spacing:.12em;margin-top:19px}
#fve-header-variant-preview .ff-eyebrow{position:relative;font-size:var(--fs,11px);letter-spacing:var(--ls,.38em)}
#fve-header-variant-preview .ff-shared-hero h1{position:relative;font-size:var(--fs,83px);line-height:var(--lh,1.04);letter-spacing:var(--ls,-.04em);max-width:var(--lw,850px)}
#fve-header-variant-preview .ff-shared-hero p{position:relative;font-size:var(--fs,16px);line-height:var(--lh,1.65);letter-spacing:var(--ls,0px);max-width:var(--lw,560px);margin:0 0 8px}
#fve-header-variant-preview .ff-cta{position:relative;font-size:var(--fs,14px);margin-top:8px}
#fve-header-variant-preview .ff-simple .ff-shared-hero{display:none}
#fve-header-variant-preview .ff-simple .ff-shared-bar{padding-top:12px;padding-bottom:12px}
#fve-header-variant-preview .ff-simple .ff-shared-brand img{width:55px}
#fve-header-variant-preview .ff-simple .ff-shared-rule{height:55px}
#fve-header-variant-preview .ff-simple .ff-shared-name strong{font-size:25px}
#fve-header-variant-preview .ff-simple .ff-shared-menu{transform:none}
#fve-header-variant-preview .ff-shared-brand{color:inherit;text-decoration:none}
#fve-header-variant-preview .ff-shared-menu a{color:#222;text-decoration:none;font:11px Arial,sans-serif;letter-spacing:.1em}
#fve-header-variant-preview .ff-shared-menu a:hover{opacity:.65}
#fve-header-variant-preview .ff-shared-menu .ff-shared-contact{display:inline-flex;align-items:center;white-space:nowrap}
#fve-header-variant-preview .ff-cta{color:#fff;text-decoration:none}
#fve-header-variant-preview .ff-nav-item{position:relative;display:inline-flex;align-items:center}
#fve-header-variant-preview .ff-subnav{position:absolute;top:100%;left:0;min-width:180px;z-index:20;display:none;flex-direction:column;gap:10px;background:#fff;padding:12px 16px;box-shadow:0 8px 24px #0002;border:1px solid #ddd;border-radius:8px}
#fve-header-variant-preview .ff-nav-item:hover .ff-subnav,#fve-header-variant-preview .ff-nav-item:focus-within .ff-subnav{display:flex}
#fve-header-variant-preview.ff-public-header .ff-menu-grip{display:none}
#fve-header-variant-preview.ff-public-header .ff-shared-brand{cursor:pointer}
#fve-header-variant-preview.ff-public-header .ff-shared-stage{overflow:visible}
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-photo{object-position:center}
#fve-header-variant-preview.ff-public-header .ff-full{overflow:hidden}
@media(max-width:900px) and (hover:hover) {
 #fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full{height:auto;min-height:720px;overflow:hidden}
 #fve-header-variant-preview.ff-public-header .ff-shared-bar{display:flex;flex-wrap:wrap;justify-content:flex-start;gap:10px;padding:14px 4%;min-height:0}
 #fve-header-variant-preview.ff-public-header .ff-shared-brand{gap:7px;max-width:100%;transform:none}
 #fve-header-variant-preview.ff-public-header .ff-shared-brand img{width:54px;transform:none}
 #fve-header-variant-preview.ff-public-header .ff-shared-rule{height:50px;transform:none}
 #fve-header-variant-preview.ff-public-header .ff-shared-name{transform:none}
 #fve-header-variant-preview.ff-public-header .ff-shared-name strong{font-size:clamp(18px,5vw,25px)}
 #fve-header-variant-preview.ff-public-header .ff-shared-name small{font-size:8px}
 #fve-header-variant-preview.ff-public-header .ff-shared-menu{margin:4px 0 0!important;zoom:1!important;transform:none!important;flex:1 1 100%;flex-wrap:wrap;gap:5px;padding:10px!important;max-width:100%;justify-content:space-between;box-sizing:border-box}
 #fve-header-variant-preview.ff-public-header .ff-menu-links{flex-wrap:wrap;gap:12px;padding:6px 0}
 #fve-header-variant-preview.ff-public-header .ff-menu-links a{font-size:10px}
 #fve-header-variant-preview.ff-public-header .ff-shared-contact{padding:9px!important;font-size:9px!important}
 #fve-header-variant-preview.ff-public-header .ff-shared-hero{position:relative;top:auto;left:auto;margin:60px 5% 50px;max-width:90%}
 #fve-header-variant-preview.ff-public-header .ff-shared-hero h1{font-size:clamp(36px,8vw,65px);width:auto;max-width:100%;overflow-wrap:break-word}
 #fve-header-variant-preview.ff-public-header .ff-shared-hero p{font-size:16px;max-width:95%}
 #fve-header-variant-preview.ff-public-header .ff-simple .ff-shared-bar{padding:10px 4%}
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('fve-header-variant-preview');
    if (!root) return;
    const stage = root.querySelector('.ff-shared-stage');
    const bar = root.querySelector('.ff-shared-bar');
    const brand = root.querySelector('.ff-shared-brand');
    const menu = root.querySelector('.ff-shared-menu');
    if (!stage || !bar || !brand || !menu) return;

    // Match the central builder: a flex-positioned capsule, then its saved
    // translation and proportional zoom. Do not anchor the menu at top:0;
    // the saved Y offset is relative to the centered header row.
    let queued = false;
    function fitMenu() {
        queued = false;
        const stageRect = stage.getBoundingClientRect();
        if (stageRect.width < 350 || stageRect.height < 40) return;
        // Even on touch screens the menu needs collision-aware fitting.
        // Skipping this used to clip its right-hand contact button.

        const style = getComputedStyle(stage);
        const x = Number.parseFloat(style.getPropertyValue('--ff-menu-x')) || 0;
        const touchPublic = root.classList.contains('ff-public-header')
            && window.matchMedia('(hover: none) and (pointer: coarse)').matches;
        const y = touchPublic ? 0 : (Number.parseFloat(style.getPropertyValue('--ff-menu-y')) || 0);
        const targetWidth = Number.parseFloat(stage.dataset.menuWidth) || 0;
        const isSimple = stage.classList.contains('ff-simple');
        const viewScale = stageRect.width / (stage.offsetWidth || 1);
        if (!Number.isFinite(viewScale) || viewScale <= 0) return;

        menu.style.transform = 'none';
        menu.style.zoom = '1';
        const nativeWidth = menu.getBoundingClientRect().width / viewScale;
        if (nativeWidth < 100) return;

        let zoom = isSimple ? 1 : Math.max(.55, Math.min(1.8, targetWidth > 0 ? targetWidth / nativeWidth : 1));
        const barRect = bar.getBoundingClientRect();
        const brandRect = brand.getBoundingClientRect();
        const free = Math.max(160, (barRect.right - brandRect.right - 14) / viewScale);
        zoom = Math.min(zoom, free / nativeWidth);
        menu.style.zoom = String(Math.max(.45, zoom));
        menu.style.transform = 'none';

        const base = menu.getBoundingClientRect();
        const minX = (brandRect.right + 10 - base.left) / viewScale;
        const maxX = (stageRect.right - 4 - base.right) / viewScale;
        const finalX = minX <= maxX ? Math.min(maxX, Math.max(minX, isSimple ? 0 : x)) : maxX;
        const minY = (stageRect.top + 2 - base.top) / viewScale;
        const finalY = Math.max(minY, isSimple ? 0 : y);
        menu.style.transform = 'translate(' + finalX + 'px,' + finalY + 'px)';
    }
    function requestFit() {
        if (queued) return;
        queued = true;
        requestAnimationFrame(fitMenu);
    }
    const observer = new ResizeObserver(requestFit);
    observer.observe(stage);
    window.addEventListener('resize', requestFit);
    window.addEventListener('load', requestFit);
    requestFit();
});
</script>