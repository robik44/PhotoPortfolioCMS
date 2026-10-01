@php
    $ids = collect($element['photo_ids'] ?? [])->filter(fn ($id) => is_numeric($id) && $id > 0)->unique()->values();
    $photos = \App\Models\Photo::whereIn('id', $ids)->get()->keyBy('id');
    $desktop = max(1, min(12, (int) ($element['columns_desktop'] ?? 5)));
    $tablet = max(1, min(12, (int) ($element['columns_tablet'] ?? 3)));
    $mobile = max(1, min(12, (int) ($element['columns_mobile'] ?? 2)));
    $gap = max(0, min(100, (int) ($element['gap'] ?? 20)));
@endphp
<div class="thumbnail-gallery-grid" style="--tg-desktop:{{ $desktop }};--tg-tablet:{{ $tablet }};--tg-mobile:{{ $mobile }};--tg-gap:{{ $gap }}px;">
    @foreach($ids as $id)
        @if($photo = $photos->get($id))
            <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->alt ?: $photo->title ?: '' }}" loading="lazy" decoding="async" draggable="false">
        @endif
    @endforeach
</div>
