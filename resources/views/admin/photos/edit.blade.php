<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edytuj zdjęcie</h2>
    </x-slot>

    <div style="padding:32px 0;">
        <div style="max-width:900px;margin:0 auto;padding:0 24px;">
            <div style="background:#fff;padding:32px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.08);">
                <img
                    src="{{ $photo->imageUrl() }}"
                    alt="{{ $photo->alt ?: $photo->title ?: $photo->filename }}"
                    style="display:block;max-width:100%;max-height:420px;margin:0 auto 24px;object-fit:contain;"
                >

                <p style="margin:0 0 12px;color:#555;overflow-wrap:anywhere;">{{ basename($photo->filename) }}</p>
                <p style="margin:0 0 20px;color:#555;">
                    Tytuł, tekst alternatywny i opis są wspólne dla wszystkich galerii korzystających z tego zdjęcia.
                </p>

                <div style="margin-bottom:24px;font-size:14px;">
                    @if ($photo->galleries->isNotEmpty())
                        <p style="margin:0 0 6px;">Używane w galeriach:</p>
                        <ul style="margin:0;padding-left:18px;">
                            @foreach ($photo->galleries as $gallery)
                                <li>
                                    <a href="{{ route('galleries.show', $gallery) }}" style="color:#171717;text-decoration:underline;">
                                        {{ $gallery->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        Nie jest używane w żadnej galerii.
                    @endif
                </div>

                <form method="POST" action="{{ route('photos.update', $photo) }}">
                    @csrf
                    @method('PUT')

                    <div style="margin-bottom:24px;">
                        <label for="title" style="display:block;margin-bottom:8px;font-weight:600;">Tytuł</label>
                        <input id="title" name="title" type="text" maxlength="255" value="{{ old('title', $photo->title) }}"
                               style="width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;">
                        @error('title')<p role="alert" style="color:#991b1b;margin-top:6px;">{{ $message }}</p>@enderror
                    </div>

                    <div style="margin-bottom:24px;">
                        <label for="alt" style="display:block;margin-bottom:8px;font-weight:600;">Tekst alternatywny (alt)</label>
                        <input id="alt" name="alt" type="text" maxlength="255" value="{{ old('alt', $photo->alt) }}"
                               style="width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;">
                        @error('alt')<p role="alert" style="color:#991b1b;margin-top:6px;">{{ $message }}</p>@enderror
                    </div>

                    <div style="margin-bottom:24px;">
                        <label for="description" style="display:block;margin-bottom:8px;font-weight:600;">Opis</label>
                        <textarea id="description" name="description" rows="5"
                                  style="width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;">{{ old('description', $photo->description) }}</textarea>
                        @error('description')<p role="alert" style="color:#991b1b;margin-top:6px;">{{ $message }}</p>@enderror
                    </div>

                    <div style="display:flex;align-items:center;gap:18px;">
                        <button type="submit" style="padding:13px 20px;border:0;border-radius:6px;background:#171717;color:#fff;cursor:pointer;">
                            Zapisz zmiany
                        </button>
                        <a href="{{ route('photos.index') }}" style="color:#555;text-decoration:none;">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
