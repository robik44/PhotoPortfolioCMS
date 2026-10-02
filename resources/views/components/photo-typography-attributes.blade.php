@foreach(['title', 'description'] as $textField)
    @if(!empty($typography[$textField.'_font_family']))
        data-{{ $textField }}-font-family="{{ \App\Services\SiteFontLibrary::css($typography[$textField.'_font_family'], $siteFonts) }}"
    @endif
    @if(isset($typography[$textField.'_font_size']))
        data-{{ $textField }}-font-size="{{ (float) $typography[$textField.'_font_size'] }}px"
    @endif

    @foreach(['desktop', 'tablet', 'mobile'] as $breakpoint)
        @php($responsiveTypographyCss = \App\Support\TypographySettings::css($typography, $textField, $siteFonts, $breakpoint))
        @if($responsiveTypographyCss !== '')
            data-{{ $textField }}-typography-{{ $breakpoint }}="{{ $responsiveTypographyCss }}"
        @endif
    @endforeach
@endforeach
