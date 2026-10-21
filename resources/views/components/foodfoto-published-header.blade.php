@php
    // Shared renderer, gated by foodfoto_draft_header_live in site-header.
    // Draft data are read-only here: publishing does not copy or overwrite live content.
    $builderHeaderSettings = is_array($settings ?? null)
        ? $settings
        : \App\Models\SiteSetting::pluck('value', 'key')->all();
    $builderHeaderMenuItems = $menuItems ?? \App\Models\MenuItem::query()
        ->whereNull('parent_id')->where('published', true)
        ->with(['page', 'gallery', 'children.page', 'children.gallery'])
        ->orderBy('sort_order')->get();
    $isPublicDraftHeader = true;
    $draftHeaderHome = request()->path() === '/';
    $draftPageId = isset($page) && $page instanceof \App\Models\Page ? $page->id : null;
    $draftHeaderKey = $draftHeaderHome
        ? 'draft_page_header_variant_home'
        : ($draftPageId ? 'draft_page_header_variant_'.$draftPageId : null);
    $headerVariantValue = $draftHeaderKey
        ? ($builderHeaderSettings[$draftHeaderKey] ?? ($draftHeaderHome ? 'full' : 'simple'))
        : 'simple';
    $headerVariantValue = $headerVariantValue === 'full' ? 'full' : 'simple';

    $pageBannerDraft = [];
    $draftPreviewImage = $builderHeaderSettings['draft_header_preview_image'] ?? null;
    if ($draftHeaderHome) {
        $homeDraft = \App\Models\PageBuilder::whereNull('page_id')
            ->where('type', 'home_hero_draft')->first()?->content ?? [];
        $heroBlocks = collect($homeDraft['sections'] ?? [])->keyBy('id');
        $pageBannerDraft = [
            'eyebrow' => $heroBlocks->get('hero-eyebrow')['content'] ?? ($builderHeaderSettings['draft_header_eyebrow'] ?? ''),
            'title' => $heroBlocks->get('hero-heading')['content'] ?? ($builderHeaderSettings['draft_header_hero_title'] ?? ''),
            'description' => $heroBlocks->get('hero-text')['content'] ?? ($builderHeaderSettings['draft_header_hero_description'] ?? ''),
            'button' => $heroBlocks->get('hero-portfolio-cta')['content'] ?? ($builderHeaderSettings['draft_header_cta_text'] ?? ''),
        ];
        $draftPreviewImage = ($homeDraft['preview_image'] ?? null) ?: $draftPreviewImage;
    } elseif ($draftPageId) {
        $raw = $builderHeaderSettings['draft_page_banner_'.$draftPageId] ?? null;
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $pageBannerDraft = is_array($decoded) ? $decoded : [];
        $draftPreviewImage = ($pageBannerDraft['photo'] ?? null) ?: $draftPreviewImage;
    }
@endphp

@include('admin.components.draft-shared-header')

@if($headerVariantValue === 'full')
    {{-- Sticky compact version after scrolling: same real menu items. --}}
    <div class="ff-public-sticky" id="ff-public-sticky" aria-hidden="true">
        <div class="ff-sticky-slot"></div>
    </div>
    <style>
        .ff-public-sticky{position:fixed;top:0;left:0;right:0;z-index:5500;display:none;background:#f8f7f3;border-bottom:1px solid #dedbd5;box-shadow:0 4px 12px #0001}
        .ff-public-sticky.is-visible{display:block}
        .ff-public-sticky .ff-shared-bar{display:flex;align-items:center;justify-content:space-between;gap:22px;padding:12px 3vw;min-height:78px;box-sizing:border-box}
        .ff-public-sticky .ff-shared-brand{display:inline-flex;align-items:center;justify-content:flex-start;flex:0 0 auto;align-self:center;gap:12px;color:#222;text-decoration:none;transform:none!important;white-space:nowrap}
        .ff-public-sticky .ff-shared-brand img{display:block;width:var(--ff-compact-logo,58px)!important;height:auto;transform:none!important;flex:0 0 auto}
        .ff-public-sticky .ff-shared-rule{display:block;position:relative;flex:0 0 22px!important;width:22px!important;min-width:22px!important;max-width:22px!important;height:58px!important;margin:0!important;align-self:center;transform:none!important;background:none!important}
.ff-public-sticky .ff-shared-rule::before{content:"";display:block;position:absolute;left:50%;top:0;width:1px;height:100%;background:#aaa;transform:translateX(-50%)}
        .ff-public-sticky .ff-shared-name{display:block;flex:0 0 auto!important;width:auto!important;margin:0;transform:none!important}
        .ff-public-sticky .ff-shared-name strong{display:block;font-family:Georgia,serif;font-size:var(--ff-compact-name,28px)!important;font-weight:var(--ff-title-weight,400);letter-spacing:var(--ff-title-spacing,0px);word-spacing:var(--ff-title-word-spacing,0px);color:var(--ff-title-color,#222)}
        .ff-public-sticky .ff-shared-name small{display:block;font-size:var(--ff-compact-subtitle,9px)!important;letter-spacing:var(--ff-subtitle-spacing,1.4px);word-spacing:var(--ff-subtitle-word-spacing,0px);color:var(--ff-subtitle-color,#222)}
        .ff-public-sticky .ff-shared-menu{display:flex;align-items:center;gap:10px;border:1px solid #dedbd5;background:white;border-radius:8px 8px 24px 40px;padding:10px 16px;margin-left:auto;zoom:1!important;transform:none!important}
        .ff-public-sticky .ff-menu-links{display:flex;gap:17px;align-items:center}
        .ff-public-sticky a{color:#222;text-decoration:none;font:12px Arial,sans-serif}
        .ff-public-sticky .ff-shared-contact{padding:12px 16px;border:1px solid #344639;border-radius:40px}
        .ff-public-sticky .ff-nav-item{position:relative}
        .ff-public-sticky .ff-subnav{display:none;position:absolute;top:100%;left:0;background:#fff;padding:12px;min-width:160px;box-shadow:0 6px 14px #0002}
        .ff-public-sticky .ff-nav-item:hover .ff-subnav,.ff-public-sticky .ff-nav-item:focus-within .ff-subnav{display:flex;flex-direction:column;gap:9px}
        @media(max-width:900px) and (hover:hover){.ff-public-sticky .ff-shared-bar{flex-wrap:wrap;gap:5px}.ff-public-sticky .ff-menu-links{flex-wrap:wrap;gap:8px}.ff-public-sticky .ff-shared-menu{max-width:100%;margin:0;flex-wrap:wrap}.ff-public-sticky .ff-shared-name strong{font-size:var(--ff-compact-name,22px)!important}}
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const compact = document.getElementById('ff-public-sticky');
            const original = document.querySelector('#fve-header-variant-preview.ff-public-header .ff-shared-bar');
            if (!compact || !original) return;
            const duplicate = original.cloneNode(true);
            duplicate.querySelectorAll('[id]').forEach(node => node.removeAttribute('id'));
            compact.querySelector('.ff-sticky-slot').replaceWith(duplicate);
            const refresh = () => {
                const shown = window.scrollY > 120;
                compact.classList.toggle('is-visible', shown);
                compact.setAttribute('aria-hidden', shown ? 'false' : 'true');
                if (shown) duplicate.querySelectorAll('a').forEach(a => a.removeAttribute('tabindex'));
            };
            window.addEventListener('scroll', refresh, {passive:true});
            refresh();
        });
    </script>
@endif
