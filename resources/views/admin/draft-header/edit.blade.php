<x-app-layout>
<x-slot name="header">Nagłówek — NOWY (testowy)</x-slot>
<div class="cms-card" style="max-width:960px;padding:24px;">
<h1 class="cms-dashboard-title">Nowy nagłówek — wersja robocza</h1>
<p>Zmiany zapisujesz niezależnie od obecnego nagłówka. <strong>Nie są publikowane na stronie FoodFoto.</strong></p>
@if($errors->any())<div class="cms-alert cms-alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<a class="cms-button cms-button-primary" href="{{ route('draft-header.preview') }}" target="_blank" rel="noopener">Otwórz wizualny builder — przeciągnij elementy ↗</a>
<form method="POST" action="{{ route('draft-header.export-hero') }}" style="margin:20px 0;padding:16px;border:1px solid #d7ddd5;border-radius:8px">
@csrf
<p><strong>Przenieś hero do osobnej wersji roboczej strony głównej</strong></p>
<p>Zapisze kopię czterech elementów (napis nad tytułem, tytuł, opis, przycisk), ich pozycji i zdjęcia w osobnym, nieopublikowanym zestawie. Nie zastąpi istniejącego buildera strony głównej.</p>
<button class="cms-button" type="submit">Utwórz / zaktualizuj robocze hero</button>
</form>
<form method="POST" enctype="multipart/form-data" action="{{ route('draft-header.update') }}" style="display:grid;gap:16px;max-width:580px;margin-top:24px;">
@csrf @method('PUT')
<label>Prześwit między logo a menu (px)
<input style="display:block;width:100%" type="number" name="draft_header_gap" min="0" max="240" value="{{ old('draft_header_gap',$settings['draft_header_gap']) }}" required></label>
<label>Zaokrąglenie prawego menu (px)
<input style="display:block;width:100%" type="number" name="draft_header_radius" min="0" max="100" value="{{ old('draft_header_radius',$settings['draft_header_radius']) }}" required></label>
<label>Wysokość nagłówka (px)
<input style="display:block;width:100%" type="number" name="draft_header_height" min="60" max="180" value="{{ old('draft_header_height',$settings['draft_header_height']) }}" required></label>
<label>Tło pod logo
<input type="color" name="draft_header_logo_background" value="{{ old('draft_header_logo_background',$settings['draft_header_logo_background']) }}"></label>
<label for="draft_header_logo_opacity">Krycie tła pod logo: <output id="draft-header-opacity-value">{{ old('draft_header_logo_opacity',$settings['draft_header_logo_opacity']) }}%</output>
<input id="draft_header_logo_opacity" style="display:block;width:100%" type="range" name="draft_header_logo_opacity" min="0" max="100" step="1" value="{{ old('draft_header_logo_opacity',$settings['draft_header_logo_opacity']) }}" oninput="document.getElementById('draft-header-opacity-value').textContent=this.value+'%'">
<small>100% — pełne tło; 90% — subtelny prześwit; 0% — całkowicie przezroczyste. Logo i tekst pozostają nieprzezroczyste.</small></label>
<label>Tło pod menu
<input type="color" name="draft_header_menu_background" value="{{ old('draft_header_menu_background',$settings['draft_header_menu_background']) }}"></label>
<fieldset style="border:1px solid #ddd;border-radius:8px;padding:16px;display:grid;gap:12px">
<legend>Fotografia wyłącznie do testów</legend>
<label for="draft_header_hero_x">Położenie zdjęcia w poziomie: <output id="draft-header-hero-x-value">{{ old('draft_header_hero_x',$settings['draft_header_hero_x']) }}%</output></label>
<input id="draft_header_hero_x" style="width:100%" type="range" name="draft_header_hero_x" min="0" max="100" step="1" value="{{ old('draft_header_hero_x',$settings['draft_header_hero_x']) }}" oninput="document.getElementById('draft-header-hero-x-value').textContent=this.value+'%'">
<small>0% — lewa strona zdjęcia, 50% — środek, 100% — prawa. Efekt będzie najbardziej widoczny, gdy proporcje obrazu różnią się od proporcji banera.</small>
<label for="draft_header_preview_image_file">Własne zdjęcie do podglądu (JPG, PNG lub WebP, maks. 8 MB)</label>
<input id="draft_header_preview_image_file" type="file" name="draft_header_preview_image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
@if(!empty($settings['draft_header_preview_image']))
<p>Aktualnie wybrano osobną fotografię testową.</p>
<label><input type="checkbox" name="draft_header_remove_preview_image" value="1"> Przywróć zdjęcie banera i usuń wybór zdjęcia testowego</label>
@endif
<small>Zdjęcie testowe nie zmienia banera na publicznej stronie.</small>
</fieldset>
<fieldset style="display:grid;gap:12px;border:1px solid #ddd;padding:16px;border-radius:8px">
<legend>Teksty sekcji hero — wersja testowa</legend>
<label>Tekst nad tytułem <input style="display:block;width:100%" type="text" name="draft_header_eyebrow" maxlength="120" value="{{ old('draft_header_eyebrow',$settings['draft_header_eyebrow']) }}" required></label>
<label>Tytuł <textarea style="display:block;width:100%;min-height:75px" name="draft_header_hero_title" maxlength="180" required>{{ old('draft_header_hero_title',$settings['draft_header_hero_title']) }}</textarea></label>
<label>Opis <textarea style="display:block;width:100%;min-height:100px" name="draft_header_hero_description" maxlength="500" required>{{ old('draft_header_hero_description',$settings['draft_header_hero_description']) }}</textarea></label>
<label>Tekst zielonego przycisku <input style="display:block;width:100%" name="draft_header_cta_text" maxlength="80" value="{{ old('draft_header_cta_text',$settings['draft_header_cta_text']) }}" required></label>
<label>Tekst przycisku kontaktowego <input style="display:block;width:100%" name="draft_header_contact_text" maxlength="80" value="{{ old('draft_header_contact_text',$settings['draft_header_contact_text']) }}" required></label>
</fieldset>
<button class="cms-button cms-button-primary" type="submit">Zapisz wersję testową</button>
</form>
<p style="margin-top:20px">Po zapisaniu otwórz podgląd ponownie. Publiczna strona oraz obecny edytor nagłówka pozostają nietknięte.</p>
</div>
</x-app-layout>