<fieldset style="margin:24px 0;padding:16px;border:1px solid #ddd;">
    <legend>Typografia tekstów galerii</legend>
    @foreach (['title' => 'Tytuł galerii', 'description' => 'Opis galerii', 'caption' => 'Podpisy zdjęć'] as $field => $label)
        @include('components.typography-fields', ['field' => $field, 'label' => $label, 'typography' => $galleryFonts ?? [], 'withSize' => true])
    @endforeach
    <a href="{{ route('fonts.index') }}">Biblioteka czcionek</a>
</fieldset>
@include('components.header-font-faces', ['fonts' => $siteFonts['fonts']])
