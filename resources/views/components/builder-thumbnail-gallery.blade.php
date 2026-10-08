@php
    $ids = collect($element['photo_ids'] ?? [])->filter(fn ($id) => is_numeric($id) && $id > 0)->unique()->values();
    $photos = \App\Models\Photo::whereIn('id', $ids)->get()->keyBy('id');
    $desktop = max(1, min(12, (int) ($element['columns_desktop'] ?? 5)));
    $tablet = max(1, min(12, (int) ($element['columns_tablet'] ?? 3)));
    $mobile = max(1, min(12, (int) ($element['columns_mobile'] ?? 2)));
    $gap = max(0, min(100, (int) ($element['gap'] ?? 20)));
    $thumbnailHeight = max(0, min(1200, (int) ($element['thumbnail_height'] ?? 0)));
    $thumbnailRadius = max(0, min(200, (int) ($element['thumbnail_radius'] ?? 0)));
    $thumbnailRatio = in_array(($element['thumbnail_ratio'] ?? 'auto'), ['auto', '1 / 1', '4 / 3', '3 / 2', '16 / 9'], true)
        ? ($element['thumbnail_ratio'] ?? 'auto') : 'auto';
    $thumbnailFit = ($element['thumbnail_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
    $photoSettings = is_array($element['photo_settings'] ?? null) ? $element['photo_settings'] : [];
    $groupAlign = in_array(($element['group_align'] ?? 'left'), ['left', 'center', 'right'], true)
        ? ($element['group_align'] ?? 'left') : 'left';
    $justify = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'][$groupAlign];
@endphp
<div class="thumbnail-gallery-grid" style="--tg-desktop:{{ $desktop }};--tg-tablet:{{ $tablet }};--tg-mobile:{{ $mobile }};--tg-gap:{{ $gap }}px;--tg-justify:{{ $justify }};">
    @foreach($ids as $id)
        @if($photo = $photos->get($id))
            @php
                $individual = is_array($photoSettings[(string) $id] ?? null) ? $photoSettings[(string) $id] : [];
                $individualWidth = max(0, min(100, (float) ($individual['width'] ?? 0)));
                $individualHeight = max(0, min(1600, (int) ($individual['height'] ?? 0)));
                $individualFit = ($individual['fit'] ?? $thumbnailFit) === 'contain' ? 'contain' : 'cover';
            @endphp
            <div class="thumbnail-gallery-item"
                 @if($individualWidth > 0) style="flex-basis:{{ $individualWidth }}%;" @endif>
                <img
                    src="{{ $photo->thumbnailUrl() }}"
                    alt="{{ $photo->alt ?: $photo->title ?: '' }}"
                    loading="lazy"
                    decoding="async"
                    draggable="false"
                    style="
                        display:block;
                        width:100%;
                        @if($individualHeight > 0)
                            height:{{ $individualHeight }}px;
                            object-fit:{{ $individualFit }};
                        @elseif($thumbnailHeight > 0)
                            height:{{ $thumbnailHeight }}px;
                            object-fit:{{ $individualFit }};
                        @elseif($thumbnailRatio !== 'auto')
                            aspect-ratio:{{ $thumbnailRatio }};
                            height:auto;
                            object-fit:{{ $individualFit }};
                        @else
                            height:auto;
                            object-fit:contain;
                        @endif
                        border-radius:{{ $thumbnailRadius }}px;
                    "
                >
            </div>
        @endif
    @endforeach
</div>
