<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=1400">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Podgląd nowego FoodFoto — tylko administrator</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#fff;color:#202019;font-family:Georgia,'Times New Roman',serif}a{color:inherit;text-decoration:none}
.preview-top{position:relative;background:#25352a;color:white;z-index:50;padding:12px 38px;font:14px Arial,sans-serif;display:flex;align-items:center;justify-content:space-between}
.preview-top a{border:1px solid #d4dfd2;padding:8px 16px}.preview-top strong{letter-spacing:.06em}
.wrap{max-width:1400px;margin:auto}.header{padding:24px 75px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #ecece8}
.brand{display:flex;align-items:center;gap:15px}.brand-mark{font:900 35px/.79 Arial,Helvetica,sans-serif;letter-spacing:-.095em;display:flex;flex-direction:column;min-width:69px}.brand-mark span{display:block}.brand-name{padding-left:18px;border-left:1px solid #b0ada7;font-size:24px;white-space:nowrap}
.nav{display:flex;gap:26px;font:12px Arial,sans-serif;letter-spacing:.1em;text-transform:uppercase}
.hero{position:relative;min-height:550px;display:flex;align-items:center;background:#f4f1eb;overflow:hidden}
.hero .hero-photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.hero:before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(250,249,246,.92),rgba(250,249,246,.75) 38%,transparent 80%);z-index:1}
.hero-copy{position:relative;z-index:2;padding:60px 85px;width:60%}.hero h1{font-size:80px;font-weight:400;line-height:.98;letter-spacing:-.045em;margin:0 0 25px}
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
.process-intro h2{font-size:60px;font-weight:400;line-height:1.03;letter-spacing:-.04em;margin:0 0 22px}
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
</style>
</head>
<body>
<div class="preview-top"><strong>WERSJA ROBOCZA — tylko podgląd, bez zapisu do CMS</strong><div><a href="{{ route('home-builder.edit') }}">Otwórz builder</a> <a href="{{ url('/') }}" target="_blank" rel="noopener">Aktualna strona ↗</a></div></div>
<div class="wrap">
<header class="header">
 <a class="brand" href="{{ url('/') }}"><span class="brand-mark"><span>food</span><span>foto</span></span><span class="brand-name">Magda Gugała</span></a>
 <nav class="nav"><a href="{{ url('/') }}">Start</a><a href="{{ url('/o-mnie') }}">O mnie</a><a href="#uslugi">Oferta</a><a href="#portfolio">Portfolio</a><a href="{{ url('/kontakt') }}">Kontakt</a></nav>
</header>
<section class="hero">
 @if(!empty($previewDecorations['preview_hero_image']))
 <img class="hero-photo" src="{{ asset('storage/'.$previewDecorations['preview_hero_image']) }}" alt="Fotografia kulinarna — baner FoodFoto">
 @elseif(is_file(public_path('images/foodfoto-carrots-hero.jpg')))
 <img class="hero-photo" src="{{ asset('images/foodfoto-carrots-hero.jpg') }}" alt="Pieczona marchewka z kremem i ziołami">
 @elseif($photo = $photos->get((int)($sections[0]['photo_id'] ?? 0)))
 <img class="hero-photo" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: 'Fotografia kulinarna' }}">
 @endif
 <img class="hero-basil" src="{{ !empty($previewDecorations['preview_basil_hero']) ? asset('storage/'.$previewDecorations['preview_basil_hero']) : asset('images/foodfoto-basil.svg') }}" alt="" aria-hidden="true">
 <div class="hero-copy"><h1 data-preview-field="preview_hero_title" style="white-space:pre-line">{{ $previewDecorations['preview_hero_title'] ?? 'Apetyt na dobre kadry' }}</h1><p data-preview-field="preview_hero_description">{{ $previewDecorations['preview_hero_description'] ?? 'Fotografia i stylizacja żywności. Fotografia kulinarna i reklamowa dla marek, które chcą smakować obrazem.' }}</p><a class="btn" href="{{ $previewDecorations['preview_hero_button_url'] ?? '#portfolio' }}"><span data-preview-field="preview_hero_button_label">{{ $previewDecorations['preview_hero_button_label'] ?? 'Zobacz portfolio' }}</span> →</a></div>
</section>
<section class="section services-section" id="uslugi">
 <h2 class="sr-only">{{ $sections[3]['content'] ?? 'Fotografia i stylizacja żywności' }}</h2>
 <div class="lead">{{ $sections[4]['content'] ?? '' }}</div>
 <div class="services">
 @foreach([ [5,8,11], [6,9,12], [7,10,13], [14,17,20], [15,18,21], [16,19,22] ] as $indices)
 @php($imageIndex = $indices[0])
 @php($titleIndex = $indices[1])
 @php($textIndex = $indices[2])
 @php($name=trim($sections[$titleIndex]['content'] ?? ''))
 @php($url=$seoLinks[mb_strtolower($name)] ?? null)
 <article class="service">
 @if($url)<a href="{{ $url }}">@endif
 @if($photo=$photos->get((int)($sections[$imageIndex]['photo_id']??0)))<img loading="lazy" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: $name }}">@else<div class="placeholder" style="height:230px">Zdjęcie w bibliotece</div>@endif
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
 <a href="{{ route('portfolio.gallery',$gallery) }}">
 @if($cover)<img loading="lazy" src="{{ $cover->imageUrl() }}" alt="{{ $cover->alt ?: $gallery->title }}">@endif
 <h3>{{ $gallery->title }} →</h3></a>
 @endforeach
 </div>
</section>
<section class="editorial">
 @if($photo=$photos->get((int)(($previewDecorations['preview_editorial_photo_id'] ?? null) ?: ($sections[25]['photo_id'] ?? 0))))<img class="photo" loading="lazy" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: 'Fotografia żywności' }}">@endif
 <img class="editorial-basil" src="{{ !empty($previewDecorations['preview_basil_editorial']) ? asset('storage/'.$previewDecorations['preview_basil_editorial']) : asset('images/foodfoto-basil.svg') }}" alt="" aria-hidden="true"><div class="copy"><h2 data-preview-field="preview_editorial_title">{{ $previewDecorations['preview_editorial_title'] ?? 'Więcej niż zwykłe zdjęcia' }}</h2><p data-preview-field="preview_editorial_description">{{ $previewDecorations['preview_editorial_description'] ?? 'Światło, kolor i detal tworzą obrazy, które nie tylko pobudzają apetyt, ale też podkreślają charakter produktu i opowiadają historię marki.' }}</p><a class="btn" href="{{ url('/o-mnie') }}">Poznaj mnie →</a></div>
</section>
<section class="process-section" aria-labelledby="foodfoto-process-heading">
 <div class="process-intro">
  <h2 id="foodfoto-process-heading">Od pomysłu<br>do kadru</h2>
  <p>Każda sesja zaczyna się od pomysłu. Potem przychodzą stylizacja, światło i dopracowanie obrazu, który opowiada historię produktu.</p>
 </div>
 <div class="process-steps">
  @foreach([
   ['01', 'Koncepcja', 'Pomysł, kompozycja i kierunek wizualny.', (int) ($sections[5]['photo_id'] ?? 0)],
   ['02', 'Realizacja', 'Stylizacja produktu i praca ze światłem.', (int) ($sections[14]['photo_id'] ?? 0)],
   ['03', 'Rezultaty', 'Zdjęcia przygotowane do publikacji i kampanii.', (int) ($sections[16]['photo_id'] ?? 0)]
  ] as $stage)
  <article class="process-step">
   @if($processPhoto = $photos->get($stage[3]))
    <img loading="lazy" src="{{ $processPhoto->imageUrl() }}" alt="{{ $processPhoto->alt ?: $stage[1] }}">
   @else
    <div class="placeholder" style="height:195px">Fotografia</div>
   @endif
   <div class="number">{{ $stage[0] }}</div>
   <h3>{{ $stage[1] }}</h3>
   <p>{{ $stage[2] }}</p>
  </article>
  @endforeach
 </div>
 <img class="process-basil" src="{{ !empty($previewDecorations['preview_basil_process']) ? asset('storage/'.$previewDecorations['preview_basil_process']) : asset('images/foodfoto-basil.svg') }}" alt="" aria-hidden="true">
</section>
<section class="about-experience" aria-labelledby="experience-heading">
 <div class="visual">
  @if($aboutPhoto = $photos->get((int)(($previewDecorations['preview_experience_photo_id'] ?? null) ?: ($sections[6]['photo_id'] ?? 0))))
   <img loading="lazy" src="{{ $aboutPhoto->imageUrl() }}" alt="{{ $aboutPhoto->alt ?: 'Fotografia kulinarna — realizacja' }}">
  @endif
 </div>
 <div class="experience-copy">
  <div class="eyebrow">Doświadczenie, które widać</div>
  <h2 id="experience-heading" data-preview-field="preview_experience_title">{{ $previewDecorations['preview_experience_title'] ?? 'Od niemal 30 lat z aparatem przy stole' }}</h2>
  <p data-preview-field="preview_experience_description">{{ $previewDecorations['preview_experience_description'] ?? 'Fotografia i stylizacja żywności to połączenie doświadczenia, uważności na detal i umiejętności pokazania charakteru produktu. Każdy kadr powstaje z myślą o konkretnym zastosowaniu.' }}</p>
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

<footer class="foot"><span>FoodFoto — Magda Gugała</span><span><a href="{{ url('/kontakt') }}">Kontakt</a> · <a href="{{ url('/o-mnie') }}">O mnie</a></span></footer>
</div>

<button id="foodfoto-edit-open" type="button" aria-controls="foodfoto-editor" aria-expanded="false">Edytuj teksty podglądu ✎</button>
<aside id="foodfoto-editor" aria-label="Edycja tekstów nowej strony">
 <button class="close" type="button" id="foodfoto-edit-close">Zamknij ×</button>
 <h2 style="margin:0 0 12px;font:26px Georgia,serif;">Edycja na podglądzie</h2>
 <p>Zmiany widać natychmiast. Zapis dotyczy tylko nowej wersji FoodFoto. Zdjęcia zmienisz w <a href="{{ route('site-settings.edit') }}" style="text-decoration:underline;">Ustawieniach witryny</a>.</p>
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
  'preview_experience_description' => ['Doświadczenie — opis','Fotografia i stylizacja żywności to połączenie doświadczenia, uważności na detal i umiejętności pokazania charakteru produktu. Każdy kadr powstaje z myślą o konkretnym zastosowaniu.']
 ] as $name => $field)
 <label for="edit-{{ $name }}">{{ $field[0] }}</label>
 @if(str_contains($name,'description'))
 <textarea id="edit-{{ $name }}" name="{{ $name }}" required maxlength="600" rows="3">{{ old($name, $previewDecorations[$name] ?? $field[1]) }}</textarea>
 @else
 <input id="edit-{{ $name }}" type="text" name="{{ $name }}" required maxlength="{{ $name === 'preview_hero_title' ? 160 : ($name === 'preview_hero_button_label' ? 80 : 600) }}" value="{{ old($name, $previewDecorations[$name] ?? $field[1]) }}">
 @endif
 @endforeach
 <p><button type="submit">Zapisz zmiany</button></p>
 </form>
</aside>
<script>
(function(){
 const open=document.getElementById('foodfoto-edit-open'),panel=document.getElementById('foodfoto-editor');
 open.addEventListener('click',()=>{panel.classList.add('open');open.setAttribute('aria-expanded','true')});
 document.getElementById('foodfoto-edit-close').addEventListener('click',()=>{panel.classList.remove('open');open.setAttribute('aria-expanded','false')});
 document.querySelectorAll('#foodfoto-content-form [name]').forEach(input=>{
  const output=document.querySelector('[data-preview-field="'+input.name+'"]');
  if(output)input.addEventListener('input',()=>{output.textContent=input.value});
 });
})();
</script>
</body></html>
