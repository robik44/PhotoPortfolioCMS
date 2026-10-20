<x-app-layout>
<x-slot name="header">Nagłówek — NOWY (testowy)</x-slot>
<div class="cms-card" style="max-width:960px;padding:24px;">
<h1 class="cms-dashboard-title">Nowy nagłówek — wersja robocza</h1>
<p>Zmiany zapisujesz niezależnie od obecnego nagłówka. <strong>Nie są publikowane na stronie FoodFoto.</strong></p>
@if($errors->any())<div class="cms-alert cms-alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<a class="cms-button cms-button-primary" href="{{ route('draft-header.preview') }}" target="_blank" rel="noopener">Otwórz podgląd całej góry strony ↗</a>
<form method="POST" action="{{ route('draft-header.update') }}" style="display:grid;gap:16px;max-width:580px;margin-top:24px;">
@csrf @method('PUT')
<label>Prześwit między logo a menu (px)
<input style="display:block;width:100%" type="number" name="draft_header_gap" min="0" max="240" value="{{ old('draft_header_gap',$settings['draft_header_gap']) }}" required></label>
<label>Zaokrąglenie prawego menu (px)
<input style="display:block;width:100%" type="number" name="draft_header_radius" min="0" max="100" value="{{ old('draft_header_radius',$settings['draft_header_radius']) }}" required></label>
<label>Wysokość nagłówka (px)
<input style="display:block;width:100%" type="number" name="draft_header_height" min="60" max="180" value="{{ old('draft_header_height',$settings['draft_header_height']) }}" required></label>
<label>Tło pod logo
<input type="color" name="draft_header_logo_background" value="{{ old('draft_header_logo_background',$settings['draft_header_logo_background']) }}"></label>
<label>Tło pod menu
<input type="color" name="draft_header_menu_background" value="{{ old('draft_header_menu_background',$settings['draft_header_menu_background']) }}"></label>
<button class="cms-button cms-button-primary" type="submit">Zapisz wersję testową</button>
</form>
<p style="margin-top:20px">Po zapisaniu otwórz podgląd ponownie. Publiczna strona oraz obecny edytor nagłówka pozostają nietknięte.</p>
</div>
</x-app-layout>