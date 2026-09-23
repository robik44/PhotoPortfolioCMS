@include('components.header-font-faces', ['fonts' => $siteFonts['fonts']])
<style>
    .site-typography { font-family: {{ \App\Services\SiteFontLibrary::css($siteFonts['defaults']['site_body_font_family'], $siteFonts) }}; }
    .site-typography h1, .site-typography h2, .site-typography h3 {
        font-family: {{ \App\Services\SiteFontLibrary::css($siteFonts['defaults']['site_heading_font_family'], $siteFonts) }};
    }
</style>
