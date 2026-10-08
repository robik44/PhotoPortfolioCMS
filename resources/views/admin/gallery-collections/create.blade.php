<x-app-layout>
    <x-slot name="header">Dodaj galerię</x-slot>
    <div style="max-width:720px;margin:32px auto;padding:0 24px;">
        <div class="cms-card" style="padding:28px;">
            <h1 style="margin:0 0 10px;">Nowa galeria</h1>
            <p style="color:#777;margin:0 0 24px;">To będzie niezależny moduł galerii w lewym menu CMS. W środku utworzysz własne podgalerie i dodasz do nich zdjęcia.</p>
            <form method="POST" action="{{ route('gallery-collections.store') }}">
                @csrf
                <label style="display:block;margin-bottom:18px;">Nazwa galerii
                    <input name="name" value="{{ old('name') }}" required placeholder="np. Klienci, Publikacje, Realizacje" style="display:block;width:100%;margin-top:7px;padding:11px;border:1px solid #d1d5db;border-radius:6px;">
                </label>
                <label style="display:block;margin-bottom:22px;">Slug (opcjonalnie)
                    <input name="slug" value="{{ old('slug') }}" placeholder="np. klienci" style="display:block;width:100%;margin-top:7px;padding:11px;border:1px solid #d1d5db;border-radius:6px;">
                </label>
                @if($errors->any())<div class="cms-alert cms-alert-error">{{ $errors->first() }}</div>@endif
                <button class="cms-button cms-button-primary" type="submit">Utwórz galerię</button>
            </form>
        </div>
    </div>
</x-app-layout>
