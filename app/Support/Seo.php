<?php

namespace App\Support;

use App\Models\{Gallery, Page, Photo, SiteSetting};

class Seo
{
    public static function rules(): array
    {
        return [
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:2000'],
            'social_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'indexable' => ['sometimes', 'boolean'],
        ];
    }

    public static function settings(): array
    {
        return SiteSetting::pluck('value', 'key')->all();
    }

    public static function indexing(?array $settings = null): bool
    {
        return (string) (($settings ?? self::settings())['seo_indexable'] ?? '1') !== '0';
    }

    public static function pageUrl(Page $page): string
    {
        return isset(ContentPages::PAGES[$page->slug]) ? url('/'.$page->slug) : route('page.public', $page);
    }

    public static function siteName(?array $settings = null): string
    {
        $settings ??= self::settings();
        return self::firstText($settings['seo_site_name'] ?? null, $settings['site_title'] ?? null, $settings['logo'] ?? null, 'Fotografia');
    }

    private static function firstText(...$values): string
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') return trim((string) $value);
        }
        return '';
    }

    public static function meta(Page|Gallery|null $entity = null, ?string $title = null, ?string $url = null): array
    {
        $settings = self::settings();
        $site = self::siteName($settings);
        $baseTitle = self::firstText($entity?->title, $title);
        $fallbackTitle = $baseTitle !== '' ? (mb_stripos($baseTitle, $site) !== false ? $baseTitle : $baseTitle.' — '.$site) : self::firstText($settings['seo_default_title'] ?? null, $site);
        $resolvedTitle = self::firstText($entity?->seo_title, $entity instanceof Gallery ? ($settings['seo_default_title'] ?? null) : null, $fallbackTitle);
        $description = self::firstText($entity?->seo_description, $settings['seo_default_description'] ?? null,
            $entity instanceof Gallery ? $entity->description : null, $entity instanceof Page ? strip_tags($entity->content ?? '') : null,
            $settings['hero_text'] ?? null, $settings['site_subtitle'] ?? null);
        $photo = $entity?->socialPhoto;
        if (!$photo && $entity instanceof Gallery) {
            $photo = $entity->photos->first(fn ($photo) => (bool) $photo->pivot->is_cover);
        }
        $photo ??= Photo::find($settings['seo_social_photo_id'] ?? null);
        $canonical = $entity instanceof Gallery ? route('portfolio.gallery', $entity)
            : ($entity instanceof Page ? self::pageUrl($entity) : ($url ?? url('/')));
        return [
            'title' => $resolvedTitle, 'description' => $description, 'canonical' => $canonical,
            'image' => $photo?->imageUrl(), 'type' => 'website',
            'robots' => self::indexing($settings) && ($entity?->indexable ?? true) ? 'index, follow' : 'noindex, nofollow',
        ];
    }

    public static function issues(Page|Gallery $entity): array
    {
        $issues = [];
        if (!trim($entity->seo_title ?? '')) $issues[] = 'Brak tytułu SEO';
        if (!trim($entity->seo_description ?? '')) $issues[] = 'Brak opisu SEO';
        if (!self::meta($entity)['image']) $issues[] = 'Brak zdjęcia social (także w fallbackach)';
        if (!$entity->indexable) $issues[] = 'noindex';
        return $issues;
    }
}
