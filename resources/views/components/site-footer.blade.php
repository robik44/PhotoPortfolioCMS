@php
    $footerSettings = $settings ?? [];
    $footerText = $footerText ?? ($footerSettings['footer_text'] ?? 'Fotografia');
@endphp

<style>
    .site-footer {
        width: 100%;
        box-sizing: border-box;
        border-top: 1px solid #eee;
        padding: 30px 28px;
        text-align: center;
        color: #888;
        font-size: 12px;
        background: {{ $footerSettings['background_color'] ?? '#ffffff' }};
    }
</style>

<footer class="site-footer site-typography">
    {{ $footerText }}
    <span aria-hidden="true"> · </span>
    <a href="{{ route('privacy') }}" style="text-decoration:underline;text-underline-offset:3px;">Polityka prywatności</a>
</footer>
