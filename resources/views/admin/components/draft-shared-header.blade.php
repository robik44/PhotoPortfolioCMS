@php
$p = json_decode($builderHeaderSettings['draft_header_builder_positions'] ?? '{}', true) ?: [];
$logo = $p['brand-image'] ?? []; $name = $p['brand-text'] ?? []; $line = $p['brand-divider'] ?? []; $menu = $p['menu-panel'] ?? [];
$radius = implode(' ', array_map(static fn($n) => max(0,min(120,(int)$n)).'px', [$menu['rtl']??10,$menu['rtr']??10,$menu['rbr']??24,$menu['rbl']??40]));
$photo = $draftPreviewImage ?: ($builderHeaderSettings['preview_hero_image'] ?? null);
$full = $headerVariantValue === 'full';
@endphp
<div id="fve-header-variant-preview" data-variant="{{ $headerVariantValue }}">
<section class="ff-shared-stage {{ $full ? 'ff-full' : 'ff-simple' }}" style="--ff-radius:{{ $radius }};--ff-logo:{{ max(30,min(200,(int)($logo['w']??100))) }}px;--ff-name:{{ max(15,min(100,(float)($name['font']??36))) }}px;--ff-line:{{ max(15,min(180,(int)($line['h']??85))) }}px;--ff-menu-scale:{{ max(.6,min(2,(float)($menu['w']??780)/780)) }};--ff-brand-x:{{ (int)($p['brand']['x']??0) }}px;--ff-brand-y:{{ (int)($p['brand']['y']??0) }}px;--ff-menu-x:{{ (int)($menu['x']??0) }}px;--ff-menu-y:{{ (int)($menu['y']??0) }}px">
@if($photo)<img class="ff-shared-photo" src="{{ asset('storage/'.$photo) }}" alt="">@endif
<div class="ff-shared-bar">
<div class="ff-shared-brand">
@if(is_file(storage_path('app/public/branding/foodfoto-transparent-v2.png')))
<img src="{{ asset('storage/branding/foodfoto-transparent-v2.png') }}" alt="FoodFoto">
@else<strong>food<br>foto</strong>@endif
<span class="ff-shared-rule"></span>
<span class="ff-shared-name"><strong>Magda Gugała</strong><small>FOTOGRAFIA I STYLIZACJA ŻYWNOŚCI</small></span>
</div>
<div class="ff-shared-menu">
@foreach($builderHeaderMenuItems as $item)<span>{{ $item->label ?? $item->title ?? 'Menu' }}</span>@endforeach
<span class="ff-shared-contact">{{ $builderHeaderSettings['draft_header_contact_text'] ?? 'POROZMAWIAJMY' }}</span>
</div></div>
<div class="ff-shared-hero">
<div class="ff-eyebrow">{{ $builderHeaderSettings['draft_header_eyebrow'] ?? 'APETYT NA PIĘKNE HISTORIE' }}</div>
<h1>{{ $builderHeaderSettings['draft_header_hero_title'] ?? "Fotografia\ni stylizacja żywności" }}</h1>
<p>{{ $builderHeaderSettings['draft_header_hero_description'] ?? '' }}</p>
<span class="ff-cta">{{ $builderHeaderSettings['draft_header_cta_text'] ?? 'ZOBACZ PORTFOLIO' }}　→</span>
</div>
</section>
</div>
<style>
#fve-header-variant-preview .ff-shared-stage{position:relative;isolation:isolate;overflow:hidden;background:#f8f7f3;min-height:110px;color:#242421}
#fve-header-variant-preview .ff-shared-stage.ff-full{height:540px}
#fve-header-variant-preview .ff-shared-photo{display:none}
#fve-header-variant-preview .ff-full .ff-shared-photo{display:block;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-2}
#fve-header-variant-preview .ff-full:before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#f8f7f3d9,#f8f7f355 48%,transparent 90%);z-index:-1}
#fve-header-variant-preview .ff-shared-bar{display:flex;justify-content:space-between;align-items:center;gap:25px;padding:23px 4%}
#fve-header-variant-preview .ff-shared-brand{display:flex;align-items:center;gap:15px;transform:translate(var(--ff-brand-x),var(--ff-brand-y));flex-shrink:0}
#fve-header-variant-preview .ff-shared-brand img{width:var(--ff-logo);height:auto}
#fve-header-variant-preview .ff-shared-brand>strong{font:900 32px/.8 Arial}
#fve-header-variant-preview .ff-shared-rule{height:var(--ff-line);width:2px;background:#aaa}
#fve-header-variant-preview .ff-shared-name strong{display:block;font:normal var(--ff-name) Georgia,serif;white-space:nowrap}
#fve-header-variant-preview .ff-shared-name small{display:block;font:11px Arial;letter-spacing:.12em;white-space:nowrap}
#fve-header-variant-preview .ff-shared-menu{display:flex;align-items:center;gap:15px;padding:11px 17px;border:1px solid #dedbd5;border-radius:var(--ff-radius);background:white;white-space:nowrap;flex-shrink:0;transform-origin:right center;transform:translate(var(--ff-menu-x),var(--ff-menu-y)) scale(var(--ff-menu-scale))}
#fve-header-variant-preview .ff-shared-menu span{font:12px Arial;letter-spacing:.04em}
#fve-header-variant-preview .ff-shared-contact{border:1px solid #344639;border-radius:40px;padding:13px 16px}
#fve-header-variant-preview .ff-shared-hero{position:absolute;left:5%;top:180px;max-width:900px}
#fve-header-variant-preview .ff-eyebrow{font:13px Arial;letter-spacing:4px;color:#596656}
#fve-header-variant-preview .ff-shared-hero h1{white-space:pre-line;font:normal 80px/1 Georgia,serif;margin:22px 0 25px}
#fve-header-variant-preview .ff-shared-hero p{font:20px/1.4 Arial;max-width:730px}
#fve-header-variant-preview .ff-cta{display:inline-block;background:#597649;border-radius:40px;padding:18px 30px;color:white;font:14px Arial;letter-spacing:.1em;margin-top:12px}
#fve-header-variant-preview .ff-simple .ff-shared-hero{display:none}
#fve-header-variant-preview .ff-simple .ff-shared-bar{padding-top:12px;padding-bottom:12px}
#fve-header-variant-preview .ff-simple .ff-shared-brand img{width:55px}
#fve-header-variant-preview .ff-simple .ff-shared-rule{height:55px}
#fve-header-variant-preview .ff-simple .ff-shared-name strong{font-size:25px}
#fve-header-variant-preview .ff-simple .ff-shared-menu{transform:none}
</style>