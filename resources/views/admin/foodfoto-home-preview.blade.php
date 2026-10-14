<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=1400">
@if($public)
@include('components.seo-meta', ['seo' => \App\Support\Seo::meta(null)])
@else
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Podgląd nowego FoodFoto — tylko administrator</title>
@endif
<style>
*{box-sizing:border-box}body{margin:0;background:#fff;color:#202019;font-family:Georgia,'Times New Roman',serif}a{color:inherit;text-decoration:none}
.preview-top{position:relative;background:#25352a;color:white;z-index:50;padding:12px 38px;font:14px Arial,sans-serif;display:flex;align-items:center;justify-content:space-between}
.preview-top a{border:1px solid #d4dfd2;padding:8px 16px}.preview-top strong{letter-spacing:.06em}
.wrap{max-width:1400px;margin:auto}.header{padding:24px 75px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #ecece8}
.brand{display:flex;align-items:center;gap:15px}.brand-mark{font:900 35px/.79 Arial,Helvetica,sans-serif;letter-spacing:-.095em;display:flex;flex-direction:column;min-width:69px}.brand-mark span{display:block}.brand-name{padding-left:18px;border-left:1px solid #b0ada7;font-size:24px;white-space:nowrap}
.nav{display:flex;gap:26px;font:12px Arial,sans-serif;letter-spacing:.1em;text-transform:uppercase}
.hero{position:relative;min-height:550px;display:flex;align-items:center;background:#f4f1eb;overflow:hidden}
.hero .hero-photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.hero:before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(250,249,246,.92),rgba(250,249,246,.75) 38%,transparent 80%);z-index:1}
.hero-copy{position:relative;z-index:2;padding:60px var(--hero-copy-x,85px);width:65%}.hero h1{font-size:var(--hero-heading-size,80px);font-weight:400;line-height:.98;letter-spacing:-.045em;margin:0 0 25px}
.hero p{font:15px/1.7 Arial,sans-serif;max-width:480px;letter-spacing:.08em;text-transform:uppercase}
.btn{display:inline-block;margin-top:25px;padding:13px 22px;border:1px solid #202019;font:12px Arial,sans-serif;letter-spacing:.08em;text-transform:uppercase}
.section{padding:92px 80px}.section h2{font-size:55px;font-weight:400;line-height:1.1;margin:0 0 18px;letter-spacing:-.035em}.lead{font:18px/1.7 Arial,sans-serif;max-width:850px;color:#53564c}
.services{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:48px 28px;margin-top:46px}.service img{width:100%;height:265px;object-fit:cover;background:#edf0e7}
.service h3{font-size:27px;font-weight:400;margin:18px 0 8px}.service p{font:15px/1.65 Arial,sans-serif;color:#65685e;margin:0}
.cards-gallery{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}.cards-gallery img{width:100%;aspect-ratio:4/3;object-fit:cover}.cards-gallery h3{font:14px Arial,sans-serif;letter-spacing:.06em;text-transform:uppercase}
.editorial{display:grid;grid-template-columns:1.08fr .92fr;align-items:center;background:#faf8f4}.editorial .photo{height:540px;object-fit:cover;width:100%}.editorial .copy{padding:65px 75px}.editorial p{font:18px/1.75 Arial,sans-serif;white-space:pre-line}.editorial h2{white-space:pre-line;font-size:58px;font-weight:400;line-height:1.08;margin:0 0 25px}
.three{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:35px}.three img{width:100%;height:275px;object-fit:cover}.three h3{font-size:27px;font-weight:400}
.thumbs{display:flex;flex-wrap:wrap;gap:12px;margin-top:40px}.thumbs img{width:calc(20% - 10px);height:180px;object-fit:cover}
.contact{position:relative;min-height:410px;background:#e8e4db}.contact img{width:100%;display:block;height:auto}
.foot{padding:45px 80px;display:flex;justify-content:space-between;font:13px Arial,sans-serif;border-top:1px solid #ddd}
.hero-basil{position:absolute;z-index:3;width:58px;height:58px;pointer-events:none;opacity:.85;left:18px;top:58%;transform:rotate(-18deg)}.editorial{position:relative}.editorial-basil{position:absolute;z-index:2;right:16px;bottom:12px;width:66px;height:66px;pointer-events:none;opacity:.9;transform:rotate(22deg)}.placeholder{background:#eceee8;display:grid;place-items:center;color:#777;font:13px Arial,sans-serif}
/* Preserve the SEO/service heading for assistive technologies, without displaying it. */
.sr-only{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
.services-section{padding-top:58px}
/* Editorial three-stage process between the story and portfolio sections. */
.process-section{position:relative;display:grid;grid-template-columns:30% 1fr;gap:54px;align-items:start;padding:90px 80px 102px;background:#fff}
.process-intro h2{white-space:pre-line;font-size:60px;font-weight:400;line-height:1.03;letter-spacing:-.04em;margin:0 0 22px}
.process-intro p{max-width:300px;font:17px/1.65 Arial,sans-serif;color:#66675f}
.process-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}
.process-step img{display:block;width:100%;height:195px;object-fit:cover;background:#f2f0eb}
.process-step .number{font:31px/1.05 Georgia,'Times New Roman',serif;margin-top:14px}
.process-step h3{font-size:21px;font-weight:400;line-height:1.15;margin:6px 0}
.process-step p{font:13px/1.5 Arial,sans-serif;color:#71726b;margin:7px 0 0}
.process-basil{position:absolute;left:28px;bottom:18px;width:60px;height:60px;transform:rotate(-17deg);pointer-events:none}
.about-experience{display:grid;grid-template-columns:1fr 1fr;min-height:440px;background:#f8f7f3;align-items:center}
.about-experience .visual{height:440px;overflow:hidden;background:#eeece5}
.about-experience .visual img{width:100%;height:100%;object-fit:cover;display:block}
.about-experience .experience-copy{padding:54px 72px;position:relative}
.about-experience .eyebrow{font:11px/1.7 Arial,sans-serif;letter-spacing:.28em;text-transform:uppercase;color:#8a887e}
.about-experience h2{white-space:pre-line;font:400 59px/1.08 Georgia,'Times New Roman',serif;letter-spacing:-.035em;margin:18px 0}
.about-experience p{font:18px/1.7 Arial,sans-serif;color:#53554d;max-width:440px}
.about-experience .about-basil{position:absolute;right:12px;bottom:12px;width:65px;height:65px;pointer-events:none}
/* FOODFOTO-BASIL-START */
/* Three restrained photographic accents; only preview changes. */
.hero-basil{position:absolute;z-index:3;width:102px;height:auto;left:-34px;top:56%;transform:rotate(-22deg);pointer-events:none;opacity:.96}
.editorial-basil{position:absolute;z-index:2;width:164px;height:auto;right:-42px;bottom:9px;transform:rotate(9deg);pointer-events:none;opacity:1}
.process-basil{position:absolute;z-index:2;width:92px;height:auto;left:12px;top:22px;bottom:auto;transform:rotate(-20deg);pointer-events:none;opacity:.96}
.about-experience .about-basil{display:none}
/* FOODFOTO-BASIL-END */

/* Editing overlay exists only in authenticated, noindex preview. */
#foodfoto-edit-open{position:fixed;bottom:22px;right:24px;z-index:80;background:#26392b;color:#fff;border:0;padding:14px 20px;font:600 14px Arial,sans-serif;cursor:pointer;box-shadow:0 6px 22px #0003}
#foodfoto-editor{display:none;position:fixed;z-index:85;right:20px;top:20px;bottom:20px;width:380px;max-width:calc(100vw - 40px);background:#fff;box-shadow:0 8px 50px #0005;overflow-y:auto;padding:24px;font:14px/1.5 Arial,sans-serif;color:#202019}
#foodfoto-editor.open{display:block}#foodfoto-editor label{display:block;margin:12px 0 5px;font-weight:600}
#foodfoto-editor input,#foodfoto-editor textarea{width:100%;padding:9px;border:1px solid #bbb;font:14px Arial,sans-serif;box-sizing:border-box}
#foodfoto-editor button{background:#26392b;color:#fff;border:0;padding:10px 14px;cursor:pointer}
#foodfoto-editor .close{float:right;background:#ddd;color:#222}

/* Private layout editor: rulers are overlay guides, not live content. */
[data-foodfoto-layout]{position:relative}
.ff-layout-editing [data-move-id]{outline:1px dashed rgba(37,70,44,.6);cursor:grab;touch-action:none}
.ff-layout-editing [data-move-id].ff-selected{outline:3px solid #376e46;outline-offset:2px}
.ff-dragging,.ff-dragging *{cursor:grabbing!important;user-select:none!important}
.ff-guide-x,.ff-guide-y{position:absolute;z-index:70;pointer-events:none;background:#26a663}
.ff-guide-x{top:0;bottom:0;width:1px}.ff-guide-y{left:0;right:0;height:1px}
.ff-guide-x[hidden],.ff-guide-y[hidden]{display:none}
#ff-layout-toolbar{position:fixed;left:20px;bottom:20px;z-index:90;display:flex;gap:6px;align-items:center;flex-wrap:wrap;background:white;border:1px solid #aaa;padding:10px;max-width:800px;box-shadow:0 4px 18px #0003;font:13px Arial,sans-serif}
#ff-layout-toolbar button{border:1px solid #ccc;background:#f5f5f5;padding:8px 11px;cursor:pointer;color:#222}
#ff-layout-toolbar button.primary{background:#26392b;color:white}
#ff-layout-controls[hidden]{display:none}
#ff-layout-controls{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
#ff-layout-status{max-width:300px;color:#444}
.ff-ruler-top{position:absolute;left:35px;right:0;top:0;height:25px;z-index:65;pointer-events:none;background:repeating-linear-gradient(to right,#889689 0,#889689 1px,transparent 1px,transparent 10px),linear-gradient(#f4f7f2,#f4f7f2);background-size:100% 7px,100% 100%;background-position:left bottom;opacity:.7}
.ff-ruler-left{position:absolute;top:25px;bottom:0;left:0;width:25px;z-index:65;pointer-events:none;background:repeating-linear-gradient(to bottom,#889689 0,#889689 1px,transparent 1px,transparent 10px),#f4f7f2;background-size:7px 100%,100% 100%;background-position:right top;opacity:.7}
.ff-ruler-top,.ff-ruler-left{display:none}.ff-layout-editing .ff-ruler-top,.ff-layout-editing .ff-ruler-left{display:block}
#ff-size{padding:5px;border:1px solid #bbb}
</style>
@foreach($editorPositions as $id => $pos)
@if(($canvas || in_array($id, ['hero-text','hero-photo','hero-basil','editorial-photo','editorial-copy','editorial-basil','process-intro','process-steps','process-basil','experience-photo','experience-copy'], true)) && preg_match('/^[a-z0-9_-]{1,80}$/', $id) && is_array($pos))
<style>
[data-move-id="{{ $id }}"] {
 translate: {{ max(-350,min(350,(int)($pos['x'] ?? 0))) }}px {{ max(-350,min(350,(int)($pos['y'] ?? 0))) }}px;
 @if((int)($pos['w'] ?? 0) > 0)width:{{ min(1200,(int)$pos['w']) }}px;@endif
 @if((int)($pos['z'] ?? 0) !== 0)z-index:{{ max(-5,min(25,(int)$pos['z'])) }};@endif
}
</style>
@endif
@endforeach
@if($canvas)
@foreach($canvasTypography as $key => $style)
@if(preg_match('/^(preview_[a-z0-9_]+|section-[0-9]{1,2})$/', (string)$key) && is_array($style))
<style>[data-canvas-text="{{ $key }}"]{
font-size:{{ max(8,min(160,(float)($style['font_size'] ?? 18))) }}px!important;
line-height:{{ max(.5,min(3,(float)($style['line_height'] ?? 1.4))) }}!important;
letter-spacing:{{ max(-5,min(20,(float)($style['letter_spacing'] ?? 0))) }}px!important;
}</style>
@endif
@endforeach
@endif
@if($canvas)
<style>.ff-layout-editing [data-move-id]{position:relative;cursor:grab}.ff-layout-editing [data-move-id] a{pointer-events:none}.ff-layout-editing [data-move-id]:hover{outline:2px dashed #1e774a;outline-offset:2px}</style>
@endif
</head>
<body>
@unless($public)
<div class="preview-top"><strong>{{ $canvas ? 'EDYTOR NA PRAWDZIWYM UKŁADZIE — tylko wersja robocza' : 'WERSJA ROBOCZA — dostęp administratora' }}</strong><div>@if($canvas)<a href="{{ route('foodfoto.builder.edit') }}">Pełna typografia i elementy ↗</a>@else<a href="{{ route('foodfoto.canvas') }}">Edytuj rzeczywistą stronę ↗</a>@endif <a href="{{ route('home-builder.edit') }}">Stary builder</a> <a href="{{ url('/') }}" target="_blank" rel="noopener">Aktualna strona ↗</a></div></div>
@endunless
@if($canvas && $canvasUsingFixture)
<div role="status" style="background:#fff3ca;color:#423400;padding:14px 22px;font:14px Arial,sans-serif;position:relative;z-index:51">
 <strong>Tryb testowy Codespaces:</strong> ta baza zawiera inny układ niż produkcyjny. Widzisz roboczą kompozycję z dostępnych zdjęć, nie dokładną kopię www.foodfoto.pl. Zapisy działają wyłącznie w szkicu i nie zmieniają publicznej strony.
</div>
@endif
<div class="wrap" data-foodfoto-layout data-positions='@json($editorPositions)'>
@unless($public)
<div class="ff-ruler-top" aria-hidden="true"></div><div class="ff-ruler-left" aria-hidden="true"></div><div id="ff-guide-x" class="ff-guide-x" hidden></div><div id="ff-guide-y" class="ff-guide-y" hidden></div>
@endunless
<header class="header">
 <a class="brand" href="{{ url('/') }}"><span class="brand-mark"><span>food</span><span>foto</span></span><span class="brand-name">Magda Gugała</span></a>
 <nav class="nav"><a href="{{ url('/') }}">Start</a><a href="{{ url('/o-mnie') }}">O mnie</a><a href="#uslugi">Oferta</a><a href="#portfolio">Portfolio</a><a href="{{ url('/kontakt') }}">Kontakt</a></nav>
</header>
<section class="hero" style="--hero-copy-x:{{ (int)($previewDecorations['preview_hero_copy_x'] ?? 85) }}px;--hero-heading-size:{{ (int)($previewDecorations['preview_hero_heading_size'] ?? 80) }}px">
 @if(!empty($previewDecorations['preview_hero_image']))
 <img class="hero-photo" data-move-id="hero-photo" @if($canvas) data-canvas-photo="hero-photo" @endif src="{{ asset('storage/'.$previewDecorations['preview_hero_image']) }}" alt="Fotografia kulinarna — baner FoodFoto">
 @elseif(is_file(public_path('images/foodfoto-carrots-hero.jpg')))
 <img class="hero-photo" data-move-id="hero-photo" @if($canvas) data-canvas-photo="hero-photo" @endif src="{{ asset('images/foodfoto-carrots-hero.jpg') }}" alt="Pieczona marchewka z kremem i ziołami">
 @elseif($photo = $photos->get((int)($sections[0]['photo_id'] ?? 0)))
 <img class="hero-photo" data-move-id="hero-photo" @if($canvas) data-canvas-photo="hero-photo" @endif src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: 'Fotografia kulinarna' }}">
 @endif
 <img class="hero-basil" data-move-id="hero-basil" src="{{ !empty($previewDecorations['preview_basil_hero']) ? asset('storage/'.$previewDecorations['preview_basil_hero']) : asset('images/foodfoto-basil.svg') }}" alt="" aria-hidden="true">
 <div class="hero-copy" data-move-id="hero-text"><h1 data-preview-field="preview_hero_title" @if($canvas) data-canvas-text="preview_hero_title" @endif style="white-space:pre-line">{{ $canvas ? ($canvasTexts['preview_hero_title'] ?? ($previewDecorations['preview_hero_title'] ?? 'Apetyt na dobre kadry')) : ($previewDecorations['preview_hero_title'] ?? 'Apetyt na dobre kadry') }}</h1><p data-preview-field="preview_hero_description" @if($canvas) data-canvas-text="preview_hero_description" @endif>{{ $canvas ? ($canvasTexts['preview_hero_description'] ?? ($previewDecorations['preview_hero_description'] ?? 'Fotografia i stylizacja żywności. Fotografia kulinarna i reklamowa dla marek, które chcą smakować obrazem.')) : ($previewDecorations['preview_hero_description'] ?? 'Fotografia i stylizacja żywności. Fotografia kulinarna i reklamowa dla marek, które chcą smakować obrazem.') }}</p><a class="btn" href="{{ $previewDecorations['preview_hero_button_url'] ?? '#portfolio' }}"><span data-preview-field="preview_hero_button_label" @if($canvas) data-canvas-text="preview_hero_button_label" @endif>{{ $canvas ? ($canvasTexts['preview_hero_button_label'] ?? ($previewDecorations['preview_hero_button_label'] ?? 'Zobacz portfolio')) : ($previewDecorations['preview_hero_button_label'] ?? 'Zobacz portfolio') }}</span> →</a></div>
</section>
<section class="section services-section" id="uslugi">
 <h2 class="sr-only">{{ $sections[3]['content'] ?? 'Fotografia i stylizacja żywności' }}</h2>
 <div class="lead" @if($canvas) data-move-id="services-intro" data-canvas-text="section-4" @endif>{{ $canvas ? ($canvasTexts['section-4'] ?? ($sections[4]['content'] ?? '')) : ($sections[4]['content'] ?? '') }}</div>
 <div class="services">
 @foreach([ [5,8,11], [6,9,12], [7,10,13], [14,17,20], [15,18,21], [16,19,22] ] as $indices)
 @php($imageIndex = $indices[0])
 @php($titleIndex = $indices[1])
 @php($textIndex = $indices[2])
 @php($name=trim($sections[$titleIndex]['content'] ?? ''))
 @php($url=$seoLinks[mb_strtolower($name)] ?? null)
 <article class="service" @if($canvas) data-move-id="service-{{ $titleIndex }}" @endif>
 @if($url)<a href="{{ $url }}">@endif
 @if($photo=$photos->get((int)($sections[$imageIndex]['photo_id']??0)))<img loading="lazy" @if($canvas) data-canvas-photo="service-{{ $imageIndex }}" @endif src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: $name }}">@else<div class="placeholder" style="height:230px">Zdjęcie w bibliotece</div>@endif
 <h3>{{ $name }} @if($url)↗@endif</h3>
 @if($url)</a>@endif
 <p>{{ $sections[$textIndex]['content'] ?? '' }}</p></article>
 @endforeach
 </div>
</section>
<section id="portfolio" class="section" style="padding-top:30px">
 <div class="cards-gallery">
 @foreach($galleries as $gallery)
 @php($cover=$gallery->photos->first(fn($p)=>(bool)$p->pivot->is_cover)??$gallery->photos->first())
 <a href="{{ route('portfolio.gallery',$gallery) }}" @if($canvas) data-move-id="gallery-{{ $gallery->id }}" @endif>
 @if($cover)<img loading="lazy" src="{{ $cover->imageUrl() }}" alt="{{ $cover->alt ?: $gallery->title }}">@endif
 <h3>{{ $gallery->title }} →</h3></a>
 @endforeach
 </div>
</section>
<section class="editorial">
 @if($photo=$photos->get((int)(($previewDecorations['preview_editorial_photo_id'] ?? null) ?: ($sections[25]['photo_id'] ?? 0))))<img class="photo" data-move-id="editorial-photo" @if($canvas) data-canvas-photo="editorial-photo" @endif loading="lazy" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: 'Fotografia żywności' }}">@endif
 <img class="editorial-basil" data-move-id="editorial-basil" src="{{ !empty($previewDecorations['preview_basil_editorial']) ? asset('storage/'.$previewDecorations['preview_basil_editorial']) : asset('images/foodfoto-basil.svg') }}" alt="" aria-hidden="true"><div class="copy" data-move-id="editorial-copy"><h2 data-preview-field="preview_editorial_title" @if($canvas) data-canvas-text="preview_editorial_title" @endif>{{ $canvas ? ($canvasTexts['preview_editorial_title'] ?? ($previewDecorations['preview_editorial_title'] ?? 'Więcej niż zwykłe zdjęcia')) : ($previewDecorations['preview_editorial_title'] ?? 'Więcej niż zwykłe zdjęcia') }}</h2><p data-preview-field="preview_editorial_description" @if($canvas) data-canvas-text="preview_editorial_description" @endif>{{ $canvas ? ($canvasTexts['preview_editorial_description'] ?? ($previewDecorations['preview_editorial_description'] ?? 'Światło, kolor i detal tworzą obrazy, które nie tylko pobudzają apetyt, ale też podkreślają charakter produktu i opowiadają historię marki.')) : ($previewDecorations['preview_editorial_description'] ?? 'Światło, kolor i detal tworzą obrazy, które nie tylko pobudzają apetyt, ale też podkreślają charakter produktu i opowiadają historię marki.') }}</p><a class="btn" href="{{ url('/o-mnie') }}">Poznaj mnie →</a></div>
</section>
<section class="process-section" aria-labelledby="foodfoto-process-heading">
 <div class="process-intro" data-move-id="process-intro">
  <h2 id="foodfoto-process-heading" data-preview-field="preview_process_title" @if($canvas) data-canvas-text="preview_process_title" @endif>{{ $canvas ? ($canvasTexts['preview_process_title'] ?? ($previewDecorations['preview_process_title'] ?? 'Od pomysłu do kadru')) : ($previewDecorations['preview_process_title'] ?? 'Od pomysłu do kadru') }}</h2>
  <p data-preview-field="preview_process_description" @if($canvas) data-canvas-text="preview_process_description" @endif>{{ $canvas ? ($canvasTexts['preview_process_description'] ?? ($previewDecorations['preview_process_description'] ?? 'Każda sesja zaczyna się od pomysłu. Potem przychodzą stylizacja, światło i dopracowanie obrazu, który opowiada historię produktu.')) : ($previewDecorations['preview_process_description'] ?? 'Każda sesja zaczyna się od pomysłu. Potem przychodzą stylizacja, światło i dopracowanie obrazu, który opowiada historię produktu.') }}</p>
 </div>
 <div class="process-steps" data-move-id="process-steps">
  @foreach([
   ['01', 'Koncepcja', 'Pomysł, kompozycja i kierunek wizualny.', (int) (($previewDecorations['preview_process_1_photo_id'] ?? null) ?: ($sections[5]['photo_id'] ?? 0))],
   ['02', 'Realizacja', 'Stylizacja produktu i praca ze światłem.', (int) (($previewDecorations['preview_process_2_photo_id'] ?? null) ?: ($sections[14]['photo_id'] ?? 0))],
   ['03', 'Rezultaty', 'Zdjęcia przygotowane do publikacji i kampanii.', (int) (($previewDecorations['preview_process_3_photo_id'] ?? null) ?: ($sections[16]['photo_id'] ?? 0))]
  ] as $stage)
  <article class="process-step" @if($canvas) data-move-id="process-step-{{ $stage[0] }}" @endif>
   @if($processPhoto = $photos->get($stage[3]))
    <img loading="lazy" @if($canvas) data-canvas-photo="process-photo-{{ $loop->index }}" @endif src="{{ $processPhoto->imageUrl() }}" alt="{{ $processPhoto->alt ?: $stage[1] }}">
   @else
    <div class="placeholder" style="height:195px">Fotografia</div>
   @endif
   <div class="number">{{ $stage[0] }}</div>
   <h3>{{ $stage[1] }}</h3>
   <p>{{ $stage[2] }}</p>
  </article>
  @endforeach
 </div>
 <img class="process-basil" data-move-id="process-basil" src="{{ !empty($previewDecorations['preview_basil_process']) ? asset('storage/'.$previewDecorations['preview_basil_process']) : asset('images/foodfoto-basil.svg') }}" alt="" aria-hidden="true">
</section>
<section class="about-experience" aria-labelledby="experience-heading">
 <div class="visual" data-move-id="experience-photo">
  @if($aboutPhoto = $photos->get((int)(($previewDecorations['preview_experience_photo_id'] ?? null) ?: ($sections[6]['photo_id'] ?? 0))))
   <img loading="lazy" @if($canvas) data-canvas-photo="experience-photo" @endif src="{{ $aboutPhoto->imageUrl() }}" alt="{{ $aboutPhoto->alt ?: 'Fotografia kulinarna — realizacja' }}">
  @endif
 </div>
 <div class="experience-copy" data-move-id="experience-copy">
  <div class="eyebrow">Doświadczenie, które widać</div>
  <h2 id="experience-heading" data-preview-field="preview_experience_title" @if($canvas) data-canvas-text="preview_experience_title" @endif>{{ $canvas ? ($canvasTexts['preview_experience_title'] ?? ($previewDecorations['preview_experience_title'] ?? 'Od niemal 30 lat z aparatem przy stole')) : ($previewDecorations['preview_experience_title'] ?? 'Od niemal 30 lat z aparatem przy stole') }}</h2>
  <p data-preview-field="preview_experience_description" @if($canvas) data-canvas-text="preview_experience_description" @endif>{{ $canvas ? ($canvasTexts['preview_experience_description'] ?? ($previewDecorations['preview_experience_description'] ?? 'Fotografia i stylizacja żywności to połączenie doświadczenia, uważności na detal i umiejętności pokazania charakteru produktu. Każdy kadr powstaje z myślą o konkretnym zastosowaniu.')) : ($previewDecorations['preview_experience_description'] ?? 'Fotografia i stylizacja żywności to połączenie doświadczenia, uważności na detal i umiejętności pokazania charakteru produktu. Każdy kadr powstaje z myślą o konkretnym zastosowaniu.') }}</p>
  <a class="btn" href="{{ url('/o-mnie') }}">Poznaj mnie →</a>
  <img class="about-basil" src="{{ !empty($previewDecorations['preview_basil_editorial']) ? asset('storage/'.$previewDecorations['preview_basil_editorial']) : asset('images/foodfoto-basil.svg') }}" alt="" aria-hidden="true">
 </div>
</section>
<section class="section">
 <h2>Publikacje i realizacje</h2>
 <p class="lead">Fotografie przygotowane do publikacji, kampanii i materiałów reklamowych.</p>
 <div class="three">
 @foreach([ [29,'Edytorial','Fotografia dla redakcji i magazynów.'], [28,'Napoje','Zdjęcia pokazujące kolor i świeżość.'], [27,'Kulinarne inspiracje','Fotografia potraw i gotowych dań.'] ] as $step)
 <article>@if($photo=$photos->get((int)($sections[$step[0]]['photo_id']??0)))<img loading="lazy" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: $step[1] }}">@endif<h3>{{ $step[1] }}</h3><p class="lead" style="font-size:15px">{{ $step[2] }}</p></article>
 @endforeach
 </div>
</section>
<section class="section" style="background:#f4f7ed">
 <h2>Wybrane realizacje</h2>
 <p class="lead">Fotografia żywności, stylizacja i zdjęcia na opakowania.</p>
 <div class="thumbs">@foreach($thumbs as $photo)<img loading="lazy" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: 'Realizacja fotograficzna' }}">@endforeach</div>
</section>
<section class="contact">
 @if($photo=$photos->get((int)($sections[31]['photo_id']??0)))<img loading="lazy" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: 'Kontakt — fotografia żywności' }}">@endif
</section>

@if(count($customBlocks))
<section class="section" id="foodfoto-custom-elements" aria-label="Dodatkowe elementy strony">
@foreach($customBlocks as $block)
  <div @if($canvas) data-move-id="custom-{{ $loop->index }}" @endif>
  @if(($block['type'] ?? '') === 'heading')
    <h2>{{ $block['content'] ?? '' }}</h2>
  @elseif(($block['type'] ?? '') === 'text')
    <p style="font:18px/1.7 Arial,sans-serif;white-space:pre-line">{{ $block['content'] ?? '' }}</p>
  @elseif(($block['type'] ?? '') === 'image')
    @if($customPhoto = $photos->get((int)($block['photo_id'] ?? 0)))
      <img @if($canvas) data-canvas-photo="custom-{{ $loop->index }}" @endif style="display:block;max-width:100%;height:auto;margin:20px 0" src="{{ $customPhoto->imageUrl() }}" alt="{{ $customPhoto->alt ?: 'Fotografia żywności' }}">
    @endif
  @elseif(($block['type'] ?? '') === 'button')
    <a class="btn" href="{{ Str::startsWith($block['url'] ?? '', ['/', '#', 'https://', 'http://']) ? ($block['url'] ?: '#kontakt') : '#kontakt' }}">{{ $block['content'] ?? 'Dowiedz się więcej' }}</a>
  @endif
  </div>
@endforeach
</section>
@endif
<footer class="foot"><span>FoodFoto — Magda Gugała</span><span><a href="{{ url('/kontakt') }}">Kontakt</a> · <a href="{{ url('/o-mnie') }}">O mnie</a></span></footer>
</div>


@unless($public)
@if($canvas)
<aside id="ff-type-panel" style="position:fixed;right:18px;top:235px;z-index:101;background:white;border:1px solid #aaa;padding:14px;width:275px;font:13px Arial">
 <strong>Typografia — kliknij tekst</strong>
 <p id="ff-type-selected" style="font-size:12px">Nie wybrano tekstu</p>
 <label>Rozmiar (px, krok 0,1) <input id="ff-type-size" type="number" min="8" max="160" step=".1" value="18" style="width:100%"></label>
 <input id="ff-type-slider" type="range" min="8" max="160" step=".1" value="18" style="width:100%">
 <label>Interlinia <input id="ff-type-line" type="number" min=".5" max="3" step=".05" value="1.4" style="width:100%"></label>
 <label>Odstępy liter (px) <input id="ff-type-space" type="number" min="-5" max="20" step=".01" value="0" style="width:100%"></label>
 <label>Krój pisma <select id="ff-type-family"><option value="inherit">Dziedziczony</option><option>Georgia</option><option>Arial</option><option>Helvetica</option><option>Verdana</option><option>Tahoma</option><option>Trebuchet MS</option><option>Times New Roman</option><option>Courier New</option></select></label>
 <label>Grubość <select id="ff-type-weight">@foreach(range(100,900,100) as $w)<option value="{{ $w }}">{{ $w }}</option>@endforeach</select></label>
 <label>Wyrównanie <select id="ff-type-align"><option value="inherit">Dziedziczone</option><option value="left">Do lewej</option><option value="center">Wyśrodkuj</option><option value="right">Do prawej</option><option value="justify">Wyjustuj</option></select></label>
 <label>Wielkość liter <select id="ff-type-transform"><option value="none">Normalna</option><option value="uppercase">Wersaliki</option><option value="lowercase">Małe</option><option value="capitalize">Pierwsze Litery Wielkie</option></select></label>
 <label>Odstępy słów (px, krok 0,1) <input id="ff-type-word" type="number" min="-20" max="50" step=".1" value="0"></label>
 <label>Margines górny (px) <input id="ff-type-margin-top" type="number" min="-100" max="200" step="1" value="0"></label>
 <label>Margines dolny (px) <input id="ff-type-margin-bottom" type="number" min="-100" max="200" step="1" value="0"></label>
 <label>Maksymalna szerokość tekstu (px; 0 = bez limitu) <input id="ff-type-max-width" type="number" min="0" max="1200" step="1" value="0"></label>
 <p style="margin:10px 0">Boks tekstowy: <button id="ff-type-select-box" type="button">Zaznacz i rozciągaj ↘</button></p>
 <form id="ff-type-form" method="POST" action="{{ route('foodfoto.canvas.typography', [], false) }}">@csrf<input type="hidden" name="typography" id="ff-type-json"><button type="submit">Zapisz typografię</button></form>
 <small>Ustawienia dotyczą tylko wersji roboczej.</small>
</aside>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const nodes=[...document.querySelectorAll('[data-canvas-text]')];
 const form=document.getElementById('ff-type-form');
 const size=document.getElementById('ff-type-size'),slider=document.getElementById('ff-type-slider');
 const line=document.getElementById('ff-type-line'),space=document.getElementById('ff-type-space');
 const extraInputs={
  font_family:document.getElementById('ff-type-family'),
  font_weight:document.getElementById('ff-type-weight'),
  text_align:document.getElementById('ff-type-align'),
  text_transform:document.getElementById('ff-type-transform'),
  word_spacing:document.getElementById('ff-type-word'),
  margin_top:document.getElementById('ff-type-margin-top'),
  margin_bottom:document.getElementById('ff-type-margin-bottom'),
  max_width:document.getElementById('ff-type-max-width')
 };
 const extraDefaults={font_family:'inherit',font_weight:400,text_align:'inherit',text_transform:'none',word_spacing:0,margin_top:0,margin_bottom:0,max_width:0};
 const values={...@json($canvasTypography)};
 let selected=null;
 function read(el){
  const css=getComputedStyle(el);
  const id=el.dataset.canvasText;
  return values[id] || {font_size:parseFloat(css.fontSize)||18,line_height:parseFloat(css.lineHeight)/(parseFloat(css.fontSize)||18)||1.4,letter_spacing:parseFloat(css.letterSpacing)||0};
 }
 function apply(){
  if(!selected)return;
  let v={font_size:Number(size.value),line_height:Number(line.value),letter_spacing:Number(space.value)};
  for(const [key,input] of Object.entries(extraInputs))v[key]=['font_weight','word_spacing','margin_top','margin_bottom','max_width'].includes(key)?Number(input.value):input.value;
  if(!(v.font_size>=8&&v.font_size<=160&&v.line_height>=.5&&v.line_height<=3&&v.letter_spacing>=-5&&v.letter_spacing<=20))return;
  values[selected.dataset.canvasText]=v;
  selected.style.setProperty('font-size',v.font_size+'px','important');
  selected.style.setProperty('line-height',String(v.line_height),'important');
  selected.style.setProperty('letter-spacing',v.letter_spacing+'px','important');
  const cssProperties={'font-family':v.font_family,'font-weight':v.font_weight,'text-align':v.text_align,
   'text-transform':v.text_transform,'word-spacing':v.word_spacing+'px',
   'margin-top':v.margin_top+'px','margin-bottom':v.margin_bottom+'px',
   'max-width':v.max_width?v.max_width+'px':'none'};
  for(const [prop,val] of Object.entries(cssProperties))selected.style.setProperty(prop,String(val),'important');
  slider.value=v.font_size;
 }
 nodes.forEach(el=>el.addEventListener('click',()=>{
  if(selected) selected.style.outline='';
  selected=el;selected.style.outline='2px solid #1b8058';
  document.getElementById('ff-type-selected').textContent=el.innerText.slice(0,65);
  const v=read(el);
  size.value=v.font_size;slider.value=v.font_size;line.value=v.line_height;space.value=v.letter_spacing;
  for(const [key,input] of Object.entries(extraInputs))input.value=v[key]??extraDefaults[key];
 }));
 [size,line,space,...Object.values(extraInputs)].forEach(input=>input.addEventListener('input',apply));
 slider.addEventListener('input',()=>{size.value=slider.value;apply()});
 document.getElementById('ff-type-select-box').addEventListener('click',()=>{
  if(!selected){alert('Najpierw wybierz nagłówek lub tekst na stronie.');return;}
  const block=selected.closest('[data-move-id]');
  if(!block){alert('Brak osobnego boksu dla tego tekstu.');return;}
  document.dispatchEvent(new CustomEvent('ff-select-text-box',{detail:{id:block.dataset.moveId}}));
 });
 form.addEventListener('submit',async event=>{
  event.preventDefault();
  apply();
  document.getElementById('ff-type-json').value=JSON.stringify(values);
  const status=document.getElementById('ff-save-status');
  status.hidden=false;
  status.textContent='Trwa zapisywanie typografii…';
  try {
   const response=await fetch('/admin/foodfoto-canvas/typography',{
    method:'POST',credentials:'same-origin',redirect:'manual',
    headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
    body:new FormData(form)
   });
   if(!response.ok){
    status.textContent='Nie zapisano typografii: HTTP '+response.status;
    return;
   }
   const result=await response.json();
   const saved=result.typography||{};
   if(JSON.stringify(saved)!==JSON.stringify(values)){
    status.textContent='Błąd: zapisane dane różnią się od ustawień edytora.';
   }else{
    status.textContent='Zapisano typografię. Wartości pozostaną po odświeżeniu.';
   }
  }catch(error){status.textContent='Błąd zapisu typografii: '+error.message}
 });
});
</script>
<aside id="ff-unified-panel" aria-label="Narzędzia buildera FoodFoto" style="position:fixed;right:18px;top:55px;bottom:18px;width:340px;max-width:calc(100vw - 36px);z-index:110;background:#fff;box-shadow:0 6px 30px #0003;border:1px solid #b7b7b7;padding:14px;overflow:auto;font:13px Arial,sans-serif">
 <h2 style="font:22px Georgia,serif;margin:0 0 10px">Builder FoodFoto</h2>
 <p id="ff-unified-selection" role="status">Kliknij tekst lub zdjęcie na stronie, aby je edytować.</p>
 <form id="ff-save-all-form" method="POST" action="{{ route('foodfoto.canvas.save-all', [], false) }}" style="margin:12px 0;border:1px solid #c9d9ce;padding:10px">
 @csrf
 <input name="layout" id="ff-all-layout" type="hidden">
 <input name="texts" id="ff-all-texts" type="hidden">
 <input name="typography" id="ff-all-typography" type="hidden">
 <input name="photos" id="ff-all-photos" type="hidden">
 <button type="submit" style="padding:12px;width:100%;background:#244e36;color:white;border:0;font-size:15px;cursor:pointer">Zapisz wszystkie zmiany</button>
</form>
@if(session('foodfoto_all_saved'))<p role="status" style="color:#216235">Zapisano wszystkie zmiany w wersji roboczej.</p>@endif
<p id="ff-save-status" role="status" aria-live="polite" hidden style="position:fixed;top:64px;left:50%;transform:translateX(-50%);z-index:300;padding:15px 22px;border:2px solid #23613e;background:#e9f6ed;color:#123d25;box-shadow:0 6px 24px #0003;font:bold 15px Arial,sans-serif;max-width:80vw;text-align:center"></p>
<div id="ff-unified-tools"></div>
 <p style="color:#666;font-size:11px">Wersja robocza — każdą część zapisuj odpowiednim przyciskiem. Strona publiczna nie zmienia się.</p>
</aside>
<div id="ff-photo-panel" style="position:fixed;left:18px;top:55px;z-index:102;background:white;border:1px solid #aaa;padding:12px;font:13px Arial;max-width:330px">
 <button type="button" id="ff-photo-open">Biblioteka zdjęć <span aria-hidden="true">▦</span></button>
 <form id="ff-photo-form" action="{{ route('foodfoto.canvas.photos') }}" method="POST" style="display:inline">@csrf<input type="hidden" name="photos" id="ff-photo-json"><button type="submit">Zapisz zdjęcia</button></form>
 <small style="display:block;margin-top:7px">Kliknij fotografię na stronie, a następnie wybierz miniaturę. Zmiany dotyczą tylko projektu roboczego.</small>
</div>
<div id="ff-photo-actions-panel" style="position:fixed;left:18px;top:140px;width:310px;max-width:calc(100vw - 36px);z-index:103;background:white;border:1px solid #aaa;box-shadow:0 4px 18px #0002;padding:12px;font:13px Arial,sans-serif">
 <strong>Akcja po kliknięciu zdjęcia</strong>
 <p style="margin:7px 0"><button id="ff-action-choose" type="button">Wybierz zdjęcie do ustawienia ↖</button></p>
 <div id="ff-action-editor" hidden>
  <p id="ff-action-selection"></p>
  <label for="ff-action-mode">Po kliknięciu:</label>
  <select id="ff-action-mode" style="width:100%;padding:7px">
   <option value="none">Brak akcji</option>
   <option value="lightbox">Powiększ zdjęcie</option>
   <option value="link">Otwórz galerię lub podstronę</option>
  </select>
  <label id="ff-action-url-wrap" hidden>Adres podstrony (np. /portfolio/nazwa-galerii)
   <input id="ff-action-url" type="text" placeholder="/portfolio/nazwa-galerii" style="width:100%;padding:7px;box-sizing:border-box">
  </label>
  <button id="ff-action-save" type="button" style="margin-top:8px">Zapisz akcję zdjęcia</button>
 </div>
 <p id="ff-action-status" role="status" style="font-size:12px"></p>
</div>
<div id="ff-photo-lightbox" hidden role="dialog" aria-label="Powiększenie zdjęcia" style="position:fixed;inset:0;background:#000e;z-index:350;align-items:center;justify-content:center;padding:40px;box-sizing:border-box">
 <button type="button" id="ff-lightbox-close" aria-label="Zamknij powiększenie" style="position:absolute;top:16px;right:24px;color:white;background:none;border:0;font:36px Arial;cursor:pointer">×</button>
 <img id="ff-lightbox-image" alt="" style="max-width:100%;max-height:90vh;object-fit:contain">
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const saved={...@json($canvasPhotoActions)};
 const images=[...document.querySelectorAll('img[data-canvas-photo]')];
 const chooser=document.getElementById('ff-action-choose');
 const editor=document.getElementById('ff-action-editor');
 const mode=document.getElementById('ff-action-mode');
 const url=document.getElementById('ff-action-url');
 const urlWrap=document.getElementById('ff-action-url-wrap');
 const status=document.getElementById('ff-action-status');
 const box=document.getElementById('ff-photo-lightbox');
 const zoom=document.getElementById('ff-lightbox-image');
 let selecting=false, selected=null;
 function choose(img){
  selected=img;
  const slot=img.dataset.canvasPhoto;
  const action=saved[slot]||{mode:'none',url:''};
  document.getElementById('ff-action-selection').textContent='Zdjęcie: '+slot;
  mode.value=action.mode||'none';
  url.value=action.url||'';
  urlWrap.hidden=mode.value!=='link';
  editor.hidden=false;
  status.textContent='';
  selecting=false;
  chooser.textContent='Wybierz inne zdjęcie ↖';
 }
 chooser.addEventListener('click',()=>{selecting=true;chooser.textContent='Kliknij zdjęcie na stronie…';});
 mode.addEventListener('change',()=>urlWrap.hidden=mode.value!=='link');
 images.forEach(img=>img.addEventListener('click',event=>{
  if(document.body.classList.contains('ff-layout-editing')||document.getElementById('ff-photo-panel').dataset.choosing)return;
  if(selecting){event.preventDefault();event.stopPropagation();choose(img);return;}
  const action=saved[img.dataset.canvasPhoto];
  if(!action||action.mode==='none')return;
  if(action.mode==='lightbox'){
   event.preventDefault();event.stopPropagation();
   zoom.src=img.currentSrc||img.src;zoom.alt=img.alt;
   box.hidden=false;box.style.display='flex';
  }else if(action.mode==='link'&&action.url){
   event.preventDefault();event.stopPropagation();
   window.location.assign(action.url);
  }
 },true));
 function close(){box.hidden=true;box.style.display='none';zoom.removeAttribute('src');}
 document.getElementById('ff-lightbox-close').addEventListener('click',close);
 box.addEventListener('click',event=>{if(event.target===box)close()});
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!box.hidden)close()});
 document.getElementById('ff-action-save').addEventListener('click',async()=>{
  if(!selected){status.textContent='Najpierw wybierz zdjęcie.';return;}
  const action={mode:mode.value,url:mode.value==='link'?url.value.trim():''};
  if(action.mode==='link'&&!/^\/(?!\/)[a-zA-Z0-9/_?=&%.#-]*$/.test(action.url)){
   status.textContent='Podaj adres wewnętrzny zaczynający się od /, np. /portfolio/nazwa.';return;
  }
  const slot=selected.dataset.canvasPhoto;
  const next={...saved,[slot]:action};
  const body=new FormData();
  body.append('_token',document.querySelector('#ff-save-all-form input[name="_token"]').value);
  body.append('actions',JSON.stringify(next));
  status.textContent='Trwa zapisywanie…';
  try{
   const response=await fetch('/admin/foodfoto-canvas/photo-actions',{
    method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body
   });
   if(!response.ok){status.textContent='Błąd zapisu HTTP '+response.status;return;}
   const result=await response.json();
   if(!result.success){status.textContent='Nie potwierdzono zapisu.';return;}
   Object.assign(saved,result.actions);
   status.textContent='Zapisano akcję zdjęcia.';
  }catch(error){status.textContent='Błąd połączenia: '+error.message;}
 });
});
</script>
<style>
 body.ff-canvas-compact #ff-unified-panel{right:0!important;top:0!important;bottom:0!important;max-height:100vh!important;width:345px!important;max-width:min(345px,90vw)!important;padding:14px!important;box-sizing:border-box!important;z-index:115!important;overflow-y:auto!important}
 #ff-unified-panel .ff-tool-section{border:1px solid #d5ddd7;border-radius:7px;margin:10px 0;background:#fff}
 #ff-unified-panel .ff-tool-section>summary{cursor:pointer;font:bold 14px Arial;padding:12px;background:#f3f6f3}
 #ff-unified-panel .ff-tool-section>div{padding:12px;max-width:100%;box-sizing:border-box}
 #ff-unified-panel .ff-tool-section input,#ff-unified-panel .ff-tool-section select{max-width:100%;box-sizing:border-box}
 body.ff-canvas-compact #ff-layout-toolbar,body.ff-canvas-compact #ff-type-panel,body.ff-canvas-compact #ff-text-toolbar,body.ff-canvas-compact #ff-photo-panel,body.ff-canvas-compact #ff-photo-actions-panel{position:static!important;top:auto!important;left:auto!important;right:auto!important;bottom:auto!important;z-index:auto!important;display:block!important;width:100%!important;max-width:100%!important;max-height:none!important;box-shadow:none!important;border:none!important;padding:0!important;margin:0!important;box-sizing:border-box!important;background:transparent!important;transform:none!important}
 body.ff-canvas-compact #ff-layout-toolbar{flex-direction:column;align-items:stretch}
 body.ff-canvas-compact #ff-layout-controls{flex-direction:column;align-items:stretch}
 body.ff-canvas-compact #ff-unified-panel.ff-hidden{transform:translateX(101%)!important;overflow:visible!important}
 #ff-sidebar-toggle{position:fixed;right:355px;top:12px;z-index:160;background:#244e36;color:white;border:0;border-radius:6px 0 0 6px;padding:12px;cursor:pointer;font:13px Arial}
 body.ff-canvas-compact #ff-unified-panel.ff-hidden~#ff-sidebar-toggle{right:0}
</style>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const panel=document.getElementById('ff-unified-panel');
 if(!panel)return;
 document.body.classList.add('ff-canvas-compact');
 const groups=[
  ['ff-layout-toolbar','Układ i prowadnice'],
  ['ff-type-panel','Typografia'],
  ['ff-text-toolbar','Treść i teksty'],
  ['ff-photo-panel','Biblioteka fotografii'],
  ['ff-photo-actions-panel','Kliknięcie zdjęcia'],
 ];
 // Move the original functional controls, keeping IDs, state and event handlers.
 const holder=document.createElement('div');
 holder.id='ff-organized-tools';
 for(const [id,title] of groups){
  const existing=document.getElementById(id);
  if(!existing)continue;
  const details=document.createElement('details');
  details.className='ff-tool-section';
  const summary=document.createElement('summary');
  summary.textContent=title;
  const inside=document.createElement('div');
  inside.appendChild(existing);
  details.append(summary,inside);
  holder.appendChild(details);
 }
 panel.appendChild(holder);
 const toggle=document.createElement('button');
 toggle.id='ff-sidebar-toggle';toggle.type='button';toggle.textContent='Ukryj narzędzia ◀';
 panel.after(toggle);
 toggle.addEventListener('click',()=>{
  const hidden=panel.classList.toggle('ff-hidden');
  toggle.textContent=hidden?'Pokaż narzędzia ▶':'Ukryj narzędzia ◀';
  toggle.style.right=hidden?'0':'min(345px,90vw)';
 });
});
</script>
<div id="ff-photo-library" hidden role="dialog" aria-modal="true" aria-label="Biblioteka zdjęć" style="position:fixed;inset:7% 10%;background:#fff;z-index:200;box-shadow:0 0 0 200vmax #0008;padding:20px;overflow:auto;font:14px Arial">
 <button type="button" id="ff-photo-close">Zamknij ×</button>
 <label style="margin-left:15px">Znajdź fotografię <input type="search" id="ff-photo-search" placeholder="Wpisz nazwę lub numer" style="padding:8px"></label>
 <div id="ff-photo-results" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(135px,1fr));gap:12px;margin-top:20px"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const photos=@json($photoPickerOptions);
 const saved=@json($canvasPhotos);
 const choices={...saved};
 const nodes=[...document.querySelectorAll('img[data-canvas-photo]')];
 const byId=new Map(photos.map(p=>[String(p.id),p]));
 let selected=null;
 const modal=document.getElementById('ff-photo-library'),grid=document.getElementById('ff-photo-results');
 function usePhoto(slot,id){const item=byId.get(String(id));if(!item)return;const el=nodes.find(img=>img.dataset.canvasPhoto===slot);if(el){el.src=item.url;el.alt=item.title;}choices[slot]=id;}
 Object.entries(choices).forEach(([slot,id])=>usePhoto(slot,id));
 function results(search=''){
  const query=search.toLocaleLowerCase('pl').trim();grid.replaceChildren();
  photos.filter(p=>!query||(String(p.id)+' '+p.title).toLocaleLowerCase('pl').includes(query)).slice(0,200).forEach(p=>{
   const button=document.createElement('button');button.type='button';button.style.cssText='padding:6px;border:1px solid #ccc;background:#fff;text-align:left;cursor:pointer';
   const img=document.createElement('img');img.src=p.url;img.alt=p.title;img.loading='lazy';img.style.cssText='width:100%;height:100px;object-fit:cover';
   const label=document.createElement('span');label.textContent='#'+p.id+' — '+p.title;label.style.cssText='display:block;font-size:11px';
   button.append(img,label);button.addEventListener('click',()=>{if(selected)usePhoto(selected.dataset.canvasPhoto,p.id);modal.hidden=true});
   grid.append(button);
  });
 }
 nodes.forEach(img=>{img.addEventListener('click',e=>{if(!document.getElementById('ff-photo-panel').dataset.choosing)return;e.preventDefault();selected=img;modal.hidden=false;results(document.getElementById('ff-photo-search').value)})});
 document.getElementById('ff-photo-open').addEventListener('click',()=>{
  const panel=document.getElementById('ff-photo-panel');panel.dataset.choosing=panel.dataset.choosing?'':'yes';
  document.getElementById('ff-photo-open').textContent=panel.dataset.choosing?'Wybierz zdjęcie na stronie ↖':'Biblioteka zdjęć ▦';
 });
 document.getElementById('ff-photo-close').addEventListener('click',()=>modal.hidden=true);
 document.getElementById('ff-photo-search').addEventListener('input',e=>results(e.target.value));
 document.getElementById('ff-photo-form').addEventListener('submit',()=>document.getElementById('ff-photo-json').value=JSON.stringify(choices));
});
</script>
<div id="ff-text-toolbar" style="position:fixed;right:18px;top:55px;z-index:100;background:#fff;border:1px solid #bbb;padding:12px;font:13px Arial;max-width:330px">
 <button type="button" id="ff-text-enable">Edytuj teksty ✎</button>
 <form action="{{ route('foodfoto.canvas.texts') }}" id="ff-text-form" method="POST" style="display:inline">@csrf<input type="hidden" name="texts" id="ff-text-json"><button type="submit">Zapisz teksty</button></form>
 <small style="display:block;margin-top:8px">Po kliknięciu „Edytuj teksty” możesz zmienić zawartość zaznaczonych nagłówków i opisów bezpośrednio na stronie. Ten zapis dotyczy tylko wersji roboczej.</small>
</div>
@if(session('foodfoto_texts_saved'))<p role="status" style="position:fixed;right:20px;top:180px;background:#e6f3e6;z-index:99;padding:8px">Teksty zapisane w wersji roboczej.</p>@endif
<script>
document.addEventListener('DOMContentLoaded',function(){
 const btn=document.getElementById('ff-text-enable');
 const form=document.getElementById('ff-text-form');
 const nodes=[...document.querySelectorAll('[data-canvas-text]')];
 let active=false;
 btn.addEventListener('click',function(){
  active=!active;
  nodes.forEach(el=>{el.contentEditable=active?'true':'false';el.style.outline=active?'1px dashed #42774a':''});
  btn.textContent=active?'Zakończ edycję tekstów':'Edytuj teksty ✎';
 });
 form.addEventListener('submit',function(){
  const values={};nodes.forEach(el=>values[el.dataset.canvasText]=el.innerText);
  document.getElementById('ff-text-json').value=JSON.stringify(values);
 });
});
</script>
@endif
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const tools=document.getElementById('ff-unified-tools');
 const label=document.getElementById('ff-unified-selection');
 if(!tools)return;
 const typography=document.getElementById('ff-type-panel');
 const photo=document.getElementById('ff-photo-panel');
 const text=document.getElementById('ff-text-toolbar');
 [text,typography,photo].forEach(panel=>{
  if(!panel)return;
  tools.append(panel);
  panel.style.position='static';panel.style.width='100%';panel.style.maxWidth='none';
  panel.style.border='0';panel.style.borderTop='1px solid #ddd';
  panel.style.padding='12px 0';panel.style.boxShadow='none';
 });
 const all=[...document.querySelectorAll('[data-canvas-text],img[data-canvas-photo]')];
 function select(el){
  const isPhoto=el.matches('img[data-canvas-photo]');
  label.textContent=(isPhoto?'Zdjęcie: ':'Tekst: ')+(isPhoto?(el.alt||el.dataset.canvasPhoto):(el.innerText||'').slice(0,65));
  typography.hidden=isPhoto;
  text.hidden=isPhoto;
  photo.hidden=!isPhoto;
  if(isPhoto){
    // Activate the existing photo-selection flow when the user clicks an image.
    const picker=document.getElementById('ff-photo-open');
    if(!photo.dataset.choosing)picker.click();
  }
 }
 all.forEach(el=>el.addEventListener('click',()=>select(el)));
 photo.hidden=true;
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
 const form=document.getElementById('ff-save-all-form');
 if(!form)return;
 form.addEventListener('submit',async event=>{
  event.preventDefault();
  const status=document.getElementById('ff-save-status');
  status.hidden=false;
  status.textContent='Trwa zapisywanie…';
  for(const [source,target] of [
   ['ff-layout-form','ff-all-layout'],
   ['ff-text-form','ff-all-texts'],
   ['ff-type-form','ff-all-typography'],
   ['ff-photo-form','ff-all-photos'],
  ]){
   const child=document.getElementById(source);
   if(!child){event.preventDefault();alert('Brak narzędzia edycji: '+source);return}
   const collectOnly=new Event('submit',{bubbles:false,cancelable:true});
   collectOnly.preventDefault(); // Run each form's serializer without submitting that form.
   child.dispatchEvent(collectOnly);
   const value=child.querySelector('input[type="hidden"][name]:not([name="_token"])');
   if(!value || !value.value){event.preventDefault();alert('Nie udało się zebrać danych: '+source);return}
   document.getElementById(target).value=value.value;
  }
  const button=form.querySelector('button[type="submit"]');
  button.disabled=true;
  try {
   const response=await fetch('/admin/foodfoto-canvas/save-all',{
    method:'POST',
    credentials:'same-origin',
    redirect:'manual',
    headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
    body:new FormData(form)
   });
   if(response.status===0 || (response.status>=300&&response.status<400)){
    status.textContent='Serwer żąda przekierowania — sprawdź logowanie lub adres aplikacji. Zmiany nie zostały potwierdzone.';
   }else if(response.ok){
    status.textContent='Zapisano wszystkie zmiany w wersji roboczej. Możesz teraz odświeżyć stronę.';
   }else{
    let message='';
    try { const data=await response.json(); message=data.message||''; } catch(_){}
    status.textContent='Błąd zapisu HTTP '+response.status+(message?' — '+message:'');
   }
  }catch(error){
   status.textContent='Błąd połączenia podczas zapisu: '+error.message;
  }finally{button.disabled=false}
 });
});
</script>
<div id="ff-layout-toolbar" aria-label="Edytor położenia elementów">
 <button id="ff-layout-toggle" type="button" aria-pressed="false">Przesuwaj elementy ↔</button>
 <div id="ff-layout-controls" hidden>
  <button type="button" id="ff-layout-undo">Cofnij ↶</button>
  <button type="button" id="ff-layout-discard">Odrzuć zmiany</button>
  <label for="ff-size">Szerokość px</label><input id="ff-size" type="number" min="0" max="1200" step="1" value="0" title="0 = wymiar oryginalny" style="width:72px"><button type="button" id="ff-size-apply">Ustaw</button>
  <button type="button" id="ff-layer-up">Warstwa +</button><button type="button" id="ff-layer-down">Warstwa −</button>

  <button type="button" id="ff-layout-reset">Reset pozycji</button>
  <label><input id="ff-layout-snap" type="checkbox" checked> Przyciąganie i prowadnice</label>
  <form id="ff-layout-form" method="POST" action="{{ $canvas ? route('foodfoto.canvas.layout') : route('foodfoto.home.preview.layout') }}">
   @csrf
   <input id="ff-layout-json" type="hidden" name="layout">
   <button class="primary" type="submit">Zapisz układ</button>
  </form>
 </div>
 <span id="ff-layout-status" role="status"></span>
</div>
@if(session('foodfoto_layout_saved'))<p style="position:fixed;top:40px;left:20px;background:#e5f5e9;padding:8px;z-index:90;font:14px Arial,sans-serif">Pozycje zapisane.</p>@endif
<button id="foodfoto-edit-open" type="button" @if($canvas) hidden @endif aria-controls="foodfoto-editor" aria-expanded="false">Edytuj stronę ✎</button>
<aside id="foodfoto-editor" aria-label="Edycja tekstów nowej strony" @if($canvas) hidden @endif>
 <button class="close" type="button" id="foodfoto-edit-close">Zamknij ×</button>
 <h2 style="margin:0 0 12px;font:26px Georgia,serif;">Edytor nowej strony</h2>
 <p>Teksty i proporcje aktualizują się na żywo. Zdjęcia z biblioteki zmienią się po zapisaniu. Wszystko dotyczy wyłącznie niepublicznego podglądu.</p>
 @if(session('foodfoto_content_saved'))<p role="status" style="color:#286b35">Zapisano teksty podglądu.</p>@endif
 @if($errors->any())<p role="alert" style="color:#a22">Nie zapisano zmian: {{ $errors->first() }}</p>@endif
 <form id="foodfoto-content-form" method="POST" action="{{ route('foodfoto.home.preview.content') }}">
 @csrf
 @foreach([
  'preview_hero_title' => ['Baner — nagłówek','Apetyt na dobre kadry'],
  'preview_hero_description' => ['Baner — opis','Fotografia i stylizacja żywności. Fotografia kulinarna i reklamowa dla marek, które chcą smakować obrazem.'],
  'preview_hero_button_label' => ['Baner — przycisk','Zobacz portfolio'],
  'preview_editorial_title' => ['Więcej niż zwykłe zdjęcia — nagłówek','Więcej niż zwykłe zdjęcia'],
  'preview_editorial_description' => ['Więcej niż zwykłe zdjęcia — opis','Światło, kolor i detal tworzą obrazy, które nie tylko pobudzają apetyt, ale też podkreślają charakter produktu i opowiadają historię marki.'],
  'preview_experience_title' => ['Doświadczenie — nagłówek','Od niemal 30 lat z aparatem przy stole'],
  'preview_experience_description' => ['Doświadczenie — opis','Fotografia i stylizacja żywności to połączenie doświadczenia, uważności na detal i umiejętności pokazania charakteru produktu. Każdy kadr powstaje z myślą o konkretnym zastosowaniu.'],
  'preview_process_title' => ['Od pomysłu do kadru — nagłówek','Od pomysłu do kadru'],
  'preview_process_description' => ['Od pomysłu do kadru — opis','Każda sesja zaczyna się od pomysłu. Potem przychodzą stylizacja, światło i dopracowanie obrazu, który opowiada historię produktu.']
 ] as $name => $field)
 <label for="edit-{{ $name }}">{{ $field[0] }}</label>
 @if(str_contains($name,'description'))
 <textarea id="edit-{{ $name }}" name="{{ $name }}" required maxlength="600" rows="3">{{ old($name, $previewDecorations[$name] ?? $field[1]) }}</textarea>
 @else
 <input id="edit-{{ $name }}" type="text" name="{{ $name }}" required maxlength="{{ $name === 'preview_hero_title' ? 160 : ($name === 'preview_hero_button_label' ? 80 : 600) }}" value="{{ old($name, $previewDecorations[$name] ?? $field[1]) }}">
 @endif
 @endforeach
 <fieldset style="margin-top:18px;border:1px solid #ddd;padding:12px;">
 <legend>Fotografie z biblioteki</legend>
 @foreach([
  'preview_editorial_photo_id' => 'Więcej niż zwykłe zdjęcia',
  'preview_experience_photo_id' => 'Doświadczenie',
  'preview_process_1_photo_id' => 'Proces — koncepcja',
  'preview_process_2_photo_id' => 'Proces — realizacja',
  'preview_process_3_photo_id' => 'Proces — rezultat'
 ] as $photoKey => $photoLabel)
 <label for="{{ $photoKey }}">{{ $photoLabel }}</label>
 <select name="{{ $photoKey }}" id="{{ $photoKey }}" style="width:100%;padding:8px;">
 <option value="">Dotychczasowe zdjęcie</option>
 @foreach($photoOptions as $photoOption)
 <option value="{{ $photoOption->id }}" @selected((string) old($photoKey,$previewDecorations[$photoKey] ?? '') === (string)$photoOption->id)>#{{ $photoOption->id }} — {{ Str::limit($photoOption->title ?: $photoOption->alt ?: 'Fotografia', 48) }}</option>
 @endforeach
 </select>
 @endforeach
 </fieldset>
 <fieldset style="margin-top:18px;border:1px solid #ddd;padding:12px;">
 <legend>Układ banera głównego</legend>
 <label for="preview_hero_heading_size">Wielkość nagłówka (44–120 px): <output id="heading-size-val">{{ $previewDecorations['preview_hero_heading_size'] ?? 80 }}</output> px</label>
 <input id="preview_hero_heading_size" type="range" name="preview_hero_heading_size" min="44" max="120" step="1" value="{{ old('preview_hero_heading_size',$previewDecorations['preview_hero_heading_size'] ?? 80) }}">
 <label for="preview_hero_copy_x">Odstęp tekstu od lewej (30–220 px): <output id="copy-x-val">{{ $previewDecorations['preview_hero_copy_x'] ?? 85 }}</output> px</label>
 <input id="preview_hero_copy_x" type="range" name="preview_hero_copy_x" min="30" max="220" step="1" value="{{ old('preview_hero_copy_x',$previewDecorations['preview_hero_copy_x'] ?? 85) }}">
 </fieldset>
 <p><button type="submit">Zapisz zmiany</button></p>
 </form>
<section style="margin:28px 0 36px;padding:14px;border:1px solid #ddd" aria-label="Dodawanie elementów">
<h3 style="margin:0 0 8px">Dodatkowe elementy strony</h3>
<p>Dodaj nagłówek, tekst, zdjęcie albo przycisk. Elementy pojawią się nad stopką również na stronie publicznej.</p>
@if(session('foodfoto_blocks_saved'))<p role="status">Zapisano dodatkowe elementy.</p>@endif
<form id="ff-blocks-form" action="{{ route('foodfoto.home.preview.blocks') }}" method="POST">
@csrf
<div id="ff-blocks-list">
@foreach($customBlocks as $i => $block)
<div class="ff-block-entry" style="border-bottom:1px solid #ccc;padding:10px 0">
<label>Rodzaj elementu</label>
<select data-block-field="type" name="blocks[{{ $i }}][type]">
@foreach(['heading'=>'Nagłówek','text'=>'Tekst','image'=>'Zdjęcie','button'=>'Przycisk'] as $typeValue=>$typeLabel)
<option value="{{ $typeValue }}" @selected(($block['type'] ?? '') === $typeValue)>{{ $typeLabel }}</option>
@endforeach
</select>
<label>Tekst / podpis</label>
<textarea data-block-field="content" name="blocks[{{ $i }}][content]" rows="2" maxlength="2000">{{ $block['content'] ?? '' }}</textarea>
<label>Zdjęcie z biblioteki</label>
<select data-block-field="photo_id" name="blocks[{{ $i }}][photo_id]">
<option value="">Wybierz zdjęcie</option>
@foreach($photoOptions as $option)
<option value="{{ $option->id }}" @selected((int)($block['photo_id'] ?? 0) === (int)$option->id)>#{{ $option->id }} — {{ Str::limit($option->title ?: $option->alt ?: 'Zdjęcie',50) }}</option>
@endforeach
</select>
<label>Link przycisku</label><input data-block-field="url" name="blocks[{{ $i }}][url]" value="{{ $block['url'] ?? '' }}" maxlength="500">
<button type="button" class="ff-block-remove" style="margin-top:8px">Usuń element</button>
</div>
@endforeach
</div>
<button id="ff-block-add" type="button">+ Dodaj element</button>
<button type="submit">Zapisz elementy</button>
</form>
<template id="ff-block-template">
<div class="ff-block-entry" style="border-bottom:1px solid #ccc;padding:10px 0">
<label>Rodzaj elementu</label><select data-block-field="type"><option value="heading">Nagłówek</option><option value="text">Tekst</option><option value="image">Zdjęcie</option><option value="button">Przycisk</option></select>
<label>Tekst / podpis</label><textarea data-block-field="content" rows="2" maxlength="2000"></textarea>
<label>Zdjęcie z biblioteki</label><select data-block-field="photo_id"><option value="">Wybierz zdjęcie</option>@foreach($photoOptions as $option)<option value="{{ $option->id }}">#{{ $option->id }} — {{ Str::limit($option->title ?: $option->alt ?: 'Zdjęcie', 50) }}</option>@endforeach</select>
<label>Link przycisku</label><input data-block-field="url" maxlength="500">
<button type="button" class="ff-block-remove" style="margin-top:8px">Usuń element</button>
</div>
</template>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
 const list = document.getElementById('ff-blocks-list');
 const form = document.getElementById('ff-blocks-form');
 const refresh = () => [...list.querySelectorAll('.ff-block-entry')].forEach((entry,index) => {
  entry.querySelectorAll('[data-block-field]').forEach(input => input.name = 'blocks['+index+']['+input.dataset.blockField+']');
 });
 document.getElementById('ff-block-add').addEventListener('click', () => {
  if(list.querySelectorAll('.ff-block-entry').length >= 30) return alert('Maksymalnie 30 elementów');
  list.appendChild(document.getElementById('ff-block-template').content.cloneNode(true));
  refresh();
 });
 list.addEventListener('click', e => { if(e.target.closest('.ff-block-remove')) {e.target.closest('.ff-block-entry').remove();refresh();} });
 form.addEventListener('submit', e => {
  refresh();
  // Laravel accepts an empty array only when explicitly signaled.
  if (!list.children.length) {
   const empty = document.createElement('input');empty.type='hidden';empty.name='blocks';empty.value='[]';form.appendChild(empty);
  }
 });
 refresh();
});
</script>
</aside>
<script>
(function(){
 const open=document.getElementById('foodfoto-edit-open'),panel=document.getElementById('foodfoto-editor');
 open.addEventListener('click',()=>{panel.classList.add('open');open.setAttribute('aria-expanded','true')});
 document.getElementById('foodfoto-edit-close').addEventListener('click',()=>{panel.classList.remove('open');open.setAttribute('aria-expanded','false')});
 const hero=document.querySelector('.hero');
 const size=document.getElementById('preview_hero_heading_size'),offset=document.getElementById('preview_hero_copy_x');
 size.addEventListener('input',()=>{hero.style.setProperty('--hero-heading-size',size.value+'px');document.getElementById('heading-size-val').textContent=size.value});
 offset.addEventListener('input',()=>{hero.style.setProperty('--hero-copy-x',offset.value+'px');document.getElementById('copy-x-val').textContent=offset.value});
 document.querySelectorAll('#foodfoto-content-form [name]').forEach(input=>{
  const output=document.querySelector('[data-preview-field="'+input.name+'"]');
  if(output)input.addEventListener('input',()=>{output.textContent=input.value});
 });
})();
</script>
<script src="{{ asset('js/foodfoto-visual-editor.js') }}" defer></script>
@endunless
</body></html>
