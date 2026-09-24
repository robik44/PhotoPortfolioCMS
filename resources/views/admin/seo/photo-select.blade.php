{{-- Compatibility wrapper; all picker behavior lives in the shared component. --}}
<x-photo-picker :name="$name" :label="$label ?? 'Zdjęcie social'" :selected="$selected ?? null" fallback />
