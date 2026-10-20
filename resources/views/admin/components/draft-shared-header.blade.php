@php
$p = json_decode($builderHeaderSettings['draft_header_builder_positions'] ?? '{}', true) ?: [];
$logo = $p['brand-image'] ?? []; $name = $p['brand-text'] ?? []; $line = $p['brand-divider'] ?? []; $menu = $p['menu-panel'] ?? [];
$radius = implode(' ', array_map(static fn($n) => max(0,min(120,(int)$n)).'px', [$menu['rtl']??10,$menu['rtr']??10,$menu['rbr']??24,$menu['rbl']??40]));
$photo = $draftPreviewImage ?: ($builderHeaderSettings['preview_hero_image'] ?? null);
$full = $headerVariantValue === 'full';
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
<div id="fve-header-variant-preview" data-variant="{{ $headerVariantValue }}">
<section class="ff-shared-stage {{ $full ? 'ff-full' : 'ff-simple' }}" style="--ff-radius:{{ $radius }};--ff-logo:{{ max(30,min(200,(int)($logo['w']??100))) }}px;--ff-name:{{ max(15,min(100,(float)($name['font']??36))) }}px;--ff-line:{{ max(15,min(180,(int)($line['h']??85))) }}px;--ff-menu-scale:{{ max(.6,min(2,(float)($menu['w']??780)/780)) }};--ff-brand-x:{{ (int)($p['brand']['x']??0) }}px;--ff-brand-y:{{ (int)($p['brand']['y']??0) }}px;--ff-menu-x:{{ (int)($menu['x']??0) }}px;--ff-menu-y:{{ (int)($menu['y']??0) }}px;--ff-logo-x:{{ $xy($logo,'x') }}px;--ff-logo-y:{{ $xy($logo,'y') }}px;--ff-name-x:{{ $xy($name,'x') }}px;--ff-name-y:{{ $xy($name,'y') }}px;--ff-line-x:{{ $xy($line,'x') }}px;--ff-line-y:{{ $xy($line,'y') }}px">
@if($photo)<img class="ff-shared-photo" src="{{ asset('storage/'.$photo) }}" alt="">@endif
<div class="ff-shared-bar">
<div class="ff-shared-brand">
@if(is_file(storage_path('app/public/branding/foodfoto-transparent-v2.png')))
<img src="{{ asset('storage/branding/foodfoto-transparent-v2.png') }}" alt="FoodFoto">
@else<strong>food<br>foto</strong>@endif
<span class="ff-shared-rule"></span>
<span class="ff-shared-name"><strong>Magda Gugała</strong><small>FOTOGRAFIA I STYLIZACJA ŻYWNOŚCI</small></span>
</div>
<div class="ff-shared-menu"><span class="ff-menu-grip" aria-hidden="true">⠿</span><nav class="ff-menu-links">
@foreach($builderHeaderMenuItems as $item)<span>{{ $item->label ?? $item->title ?? 'Menu' }}</span>@endforeach
</nav><span class="ff-shared-contact">{{ $builderHeaderSettings['draft_header_contact_text'] ?? 'POROZMAWIAJMY' }}</span>
</div></div>
<div class="ff-shared-hero">

<div class="ff-eyebrow" style="{{ $styleFor($blocks['eyebrow'],11,500) }}">{{ $builderHeaderSettings['draft_header_eyebrow'] ?? 'APETYT NA PIĘKNE HISTORIE' }}</div>
<h1 style="{{ $styleFor($blocks['headline'],83,850) }}">{{ $builderHeaderSettings['draft_header_hero_title'] ?? "Fotografia\ni stylizacja żywności" }}</h1>
<p style="{{ $styleFor($blocks['description'],16,560) }}">{{ $builderHeaderSettings['draft_header_hero_description'] ?? '' }}</p>
<span class="ff-cta" style="{{ $styleFor($blocks['cta'],14,300) }}">{{ $builderHeaderSettings['draft_header_cta_text'] ?? 'ZOBACZ PORTFOLIO' }}　→</span>
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
#fve-header-variant-preview .ff-shared-menu{position:absolute;top:0;right:0;z-index:5;display:flex;align-items:center;gap:8px;padding:8px 10px 8px 26px;border:1px solid #dedbd5;border-radius:var(--ff-radius);background:white;white-space:nowrap;flex-shrink:0;transform-origin:right top;transform:translate(var(--ff-menu-x),var(--ff-menu-y)) scale(var(--ff-menu-scale))}
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
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const root = document.getElementById('fve-header-variant-preview');
    if (!root) return;
    const menu = root.querySelector('.ff-shared-menu');
    const stage = root.querySelector('.ff-shared-stage');
    const brand = root.querySelector('.ff-shared-brand');
    if (!menu || !stage || !brand) return;

    // The shared menu uses the upper-right of the banner as its origin,
    // matching the central builder rather than the text content's width.
    // Honor both positive and negative saved offsets, without clipping.
    let queued = false;
    function fitMenu() {
        queued = false;
        const bounds = stage.getBoundingClientRect();
        if (bounds.width < 500 || bounds.height < 70) return;
        const css = getComputedStyle(stage);
        const wanted = Number.parseFloat(css.getPropertyValue('--ff-menu-scale')) || 1;
        const x = Number.parseFloat(css.getPropertyValue('--ff-menu-x')) || 0;
        const y = Number.parseFloat(css.getPropertyValue('--ff-menu-y')) || 0;
        menu.style.transform = 'none';
        const naturalWidth = menu.getBoundingClientRect().width;
        if (naturalWidth < 100) { menu.style.transform = ''; return; }
        const brandRight = brand.getBoundingClientRect().right;
        const available = Math.max(0, bounds.right - Math.max(bounds.left, brandRight + 16));
        if (available < 160) { menu.style.transform = ''; return; }
        // Shrink only when necessary to keep the brand and menu apart.
        const scale = Math.max(0.1, Math.min(wanted, available / naturalWidth));
        menu.style.transform = 'scale(' + scale + ')';
        const scaled = menu.getBoundingClientRect();
        // Positive x should not be discarded: the central editor saves it.
        // At the right edge x=0 is the maximum valid offset.
        const proposedX = Math.min(x, Math.max(0, bounds.right - scaled.right));
        const minX = Math.max(bounds.left, brandRight + 12) - scaled.left;
        const finalX = Math.max(minX, proposedX);
        menu.style.transform = 'translate(' + finalX + 'px,' + y + 'px) scale(' + scale + ')';
    }
    function requestFit() {
        if (queued) return;
        queued = true;
        requestAnimationFrame(fitMenu);
    }
    const observer = new ResizeObserver(requestFit);
    observer.observe(stage);
    observer.observe(brand);
    window.addEventListener('resize', requestFit);
    window.addEventListener('load', requestFit);
    requestFit();
});
</script>