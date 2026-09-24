@php
    $single = ($element['gallery_mode'] ?? 'all') === 'single';
    $galleries = $single
        ? \App\Models\Gallery::with('photos')->whereKey($element['gallery_id'] ?? 0)->get()
        : \App\Models\Gallery::with('photos')->orderBy('sort_order')->orderBy('title')->get();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;width:100%;">
    @if($single)
        @foreach($galleries->first()?->photos ?? [] as $photo)
            <article class="gallery-item" role="button" tabindex="0" style="cursor:zoom-in;background:#fff;overflow:hidden;"
                aria-label="{{ 'Otwórz zdjęcie: '.($photo->title ?: $photo->alt) }}"
                data-gallery-group="{{ $group }}"
                data-photo-url="{{ $photo->imageUrl() }}" data-photo-alt="{{ $photo->alt ?? '' }}"
                data-photo-title="{{ $photo->title ?? '' }}" data-photo-description="{{ $photo->description ?? '' }}">
                <img src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?? '' }}" loading="lazy" style="display:block;width:100%;aspect-ratio:1 / .7;object-fit:cover;">
            </article>
        @endforeach
    @else
        @foreach($galleries as $gallery)
            @php($cover = $gallery->photos->first(fn ($photo) => (bool) $photo->pivot->is_cover) ?? $gallery->photos->first())
            <a href="{{ route('portfolio.gallery', $gallery) }}" style="background:#fff;border:1px solid #e5e5e5;overflow:hidden;">
                @if($cover)
                    <img src="{{ $cover->imageUrl() }}" alt="{{ $cover->alt ?: $gallery->title }}" style="display:block;width:100%;aspect-ratio:1 / .7;object-fit:cover;">
                @else
                    <div style="height:120px;display:flex;align-items:center;justify-content:center;background:#f3f3f3;color:#999;">Brak zdjęcia</div>
                @endif
                <div style="padding:12px;">{{ $gallery->title }}</div>
            </a>
        @endforeach
    @endif
</div>
