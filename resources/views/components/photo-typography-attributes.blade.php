@foreach(['title', 'description'] as $textField)
    @if(!empty($typography[$textField.'_font_family']))
        data-{{ $textField }}-font-family="{{ \App\Services\SiteFontLibrary::css($typography[$textField.'_font_family'], $siteFonts) }}"
    @endif
    @if(isset($typography[$textField.'_font_size']))
        data-{{ $textField }}-font-size="{{ (float) $typography[$textField.'_font_size'] }}px"
    @endif
@endforeach
