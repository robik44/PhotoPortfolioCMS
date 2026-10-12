<x-app-layout>
    <x-slot name="header">SEO — Przekierowania 301</x-slot>
    <h1>Przekierowania 301</h1>
    <p>Podaj ścieżki zaczynające się od / (bez domeny). Nie przekierowuj na nieistniejące strony.</p>
    <p><a href="{{ route('seo.edit') }}">← Wróć do SEO</a></p>
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <section class="cms-card" style="padding:20px;margin:20px 0;">
        <h2>Dodaj przekierowanie</h2>
        <form method="POST" action="{{ route('seo.redirects.store') }}">
            @csrf
            <label>Stary adres <input name="source_path" placeholder="/stara-strona.html" required maxlength="255"></label>
            <label>Nowy adres <input name="destination_path" placeholder="/kontakt" required maxlength="255"></label>
            <input type="hidden" name="enabled" value="1">
            <button class="cms-button cms-button-primary" type="submit">Dodaj 301</button>
        </form>
    </section>
    <h2>Własne przekierowania</h2>
    @forelse($redirects as $redirect)
        <section class="cms-card" style="padding:16px;margin:12px 0;">
            <form method="POST" action="{{ route('seo.redirects.update', $redirect) }}">
                @csrf @method('PUT')
                <label>Stary adres <input name="source_path" value="{{ $redirect->source_path }}" required maxlength="255"></label>
                <label>Nowy adres <input name="destination_path" value="{{ $redirect->destination_path }}" required maxlength="255"></label>
                <input type="hidden" name="enabled" value="0">
                <label><input type="checkbox" name="enabled" value="1" @checked($redirect->enabled)> Aktywne</label>
                <button class="cms-button" type="submit">Zapisz</button>
            </form>
            <form method="POST" action="{{ route('seo.redirects.destroy', $redirect) }}" onsubmit="return confirm('Usunąć przekierowanie?')">
                @csrf @method('DELETE')
                <button class="cms-button" type="submit">Usuń</button>
            </form>
        </section>
    @empty
        <p>Brak własnych przekierowań. Domyślne przekierowania starej witryny są nadal obsługiwane automatycznie.</p>
    @endforelse
</x-app-layout>
