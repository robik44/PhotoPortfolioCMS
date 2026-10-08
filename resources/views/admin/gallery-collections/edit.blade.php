<x-app-layout>
    <x-slot name="header">Ustawienia galerii</x-slot>
    <div style="max-width:720px;margin:32px auto;padding:0 24px;">
        <div class="cms-card" style="padding:28px;">
            <h1 style="margin:0 0 24px;">{{ $galleryCollection->name }}</h1>
            <form method="POST" action="{{ route('gallery-collections.update', $galleryCollection) }}">
                @csrf @method('PUT')
                <label style="display:block;margin-bottom:18px;">Nazwa w menu CMS
                    <input name="name" value="{{ old('name', $galleryCollection->name) }}" required style="display:block;width:100%;margin-top:7px;padding:11px;border:1px solid #d1d5db;border-radius:6px;">
                </label>
                <label style="display:block;margin-bottom:22px;">Slug
                    <input name="slug" value="{{ old('slug', $galleryCollection->slug) }}" required style="display:block;width:100%;margin-top:7px;padding:11px;border:1px solid #d1d5db;border-radius:6px;">
                </label>
                <button class="cms-button cms-button-primary" type="submit">Zapisz</button>
            </form>

            <form method="POST" action="{{ route('gallery-collections.destroy', $galleryCollection) }}" style="margin-top:28px;padding-top:22px;border-top:1px solid #eee;" onsubmit="return confirm('Usunąć ten moduł galerii? Można to zrobić tylko, gdy nie ma w nim podgalerii.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="cms-button cms-button-danger">Usuń ten moduł</button>
            </form>
        </div>
    </div>
</x-app-layout>
