<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=1400">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Podgląd nowego FoodFoto — tylko administrator</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#fff;color:#202019;font-family:Georgia,'Times New Roman',serif}a{color:inherit;text-decoration:none}
.preview-top{position:sticky;top:0;background:#25352a;color:white;z-index:50;padding:12px 38px;font:14px Arial,sans-serif;display:flex;align-items:center;justify-content:space-between}
.preview-top a{border:1px solid #d4dfd2;padding:8px 16px}.preview-top strong{letter-spacing:.06em}
.wrap{max-width:1400px;margin:auto}.header{padding:22px 75px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #ecece8}
.brand{display:flex;align-items:center;gap:20px}.brand-mark{font:bold 39px/0.75 Arial,sans-serif;letter-spacing:-.09em;display:flex;flex-direction:column}.brand-mark span{display:block}.brand-name{padding-left:20px;border-left:1px solid #999;font-size:26px}
.nav{display:flex;gap:26px;font:12px Arial,sans-serif;letter-spacing:.1em;text-transform:uppercase}
.hero{position:relative;min-height:550px;display:flex;align-items:center;background:#f4f1eb;overflow:hidden}
.hero img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.hero:before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(250,249,246,.92),rgba(250,249,246,.75) 38%,transparent 80%);z-index:1}
.hero-copy{position:relative;z-index:2;padding:60px 85px;width:60%}.hero h1{font-size:86px;font-weight:400;line-height:.98;letter-spacing:-.045em;margin:0 0 25px}
.hero p{font:15px/1.7 Arial,sans-serif;max-width:480px;letter-spacing:.08em;text-transform:uppercase}
.btn{display:inline-block;margin-top:25px;padding:13px 22px;border:1px solid #202019;font:12px Arial,sans-serif;letter-spacing:.08em;text-transform:uppercase}
.section{padding:80px 80px}.section h2{font-size:55px;font-weight:400;line-height:1.1;margin:0 0 18px;letter-spacing:-.035em}.lead{font:18px/1.7 Arial,sans-serif;max-width:850px;color:#53564c}
.services{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:38px 25px;margin-top:42px}.service img{width:100%;height:230px;object-fit:cover;background:#edf0e7}
.service h3{font-size:27px;font-weight:400;margin:18px 0 8px}.service p{font:15px/1.65 Arial,sans-serif;color:#65685e;margin:0}
.cards-gallery{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}.cards-gallery img{width:100%;aspect-ratio:4/3;object-fit:cover}.cards-gallery h3{font:14px Arial,sans-serif;letter-spacing:.06em;text-transform:uppercase}
.editorial{display:grid;grid-template-columns:1fr 1fr;align-items:center;background:#faf8f4}.editorial .photo{height:500px;object-fit:cover;width:100%}.editorial .copy{padding:65px 70px}.editorial p{font:19px/1.8 Arial,sans-serif;white-space:pre-line}
.three{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:35px}.three img{width:100%;height:275px;object-fit:cover}.three h3{font-size:27px;font-weight:400}
.thumbs{display:flex;flex-wrap:wrap;gap:12px;margin-top:40px}.thumbs img{width:calc(20% - 10px);height:180px;object-fit:cover}
.contact{position:relative;min-height:410px;background:#e8e4db}.contact img{width:100%;display:block;height:auto}.contact-note{padding:22px 75px;background:#f4f4ee;font:15px/1.6 Arial,sans-serif}
.foot{padding:45px 80px;display:flex;justify-content:space-between;font:13px Arial,sans-serif;border-top:1px solid #ddd}
.placeholder{background:#eceee8;display:grid;place-items:center;color:#777;font:13px Arial,sans-serif}
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
 @if($photo = $photos->get((int)($sections[0]['photo_id'] ?? 0)))<img src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: 'Fotografia kulinarna' }}">@endif
 <div class="hero-copy"><h1>Apetyt<br>na dobre kadry</h1><p>Fotografia i stylizacja żywności. Fotografia kulinarna i reklamowa dla marek, które chcą smakować obrazem.</p><a class="btn" href="#portfolio">Zobacz portfolio →</a></div>
</section>
<section class="section" id="uslugi">
 <h2>{{ $sections[3]['content'] ?? 'Co tworzę' }}</h2>
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
 @if($photo=$photos->get((int)($sections[25]['photo_id']??0)))<img class="photo" loading="lazy" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: 'Fotografia żywności' }}">@endif
 <div class="copy"><h2 style="font-size:59px;font-weight:400;line-height:1.08">Więcej niż<br>zwykłe zdjęcia</h2><p>{{ $sections[26]['content'] ?? '' }}</p><a class="btn" href="{{ url('/o-mnie') }}">Poznaj mnie →</a></div>
</section>
<section class="section">
 <h2>Od pomysłu do kadru</h2>
 <p class="lead">Od kompozycji i stylizacji żywności po gotowe zdjęcia reklamowe.</p>
 <div class="three">
 @foreach([ [29,'Koncepcja','Pomysł, kompozycja i przygotowanie kadru.'], [28,'Stylizacja','Produkty, światło i detal.'], [27,'Realizacja','Gotowe fotografie do publikacji i kampanii.'] ] as $step)
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
<div class="contact-note">Zachowano oryginalny baner kontaktowy (zdjęcie ID 96). Jeśli napisy są częścią zdjęcia, pozostaną bez zmian.</div>
<footer class="foot"><span>FoodFoto — Magda Gugała</span><span><a href="{{ url('/kontakt') }}">Kontakt</a> · <a href="{{ url('/o-mnie') }}">O mnie</a></span></footer>
</div>
</body></html>
