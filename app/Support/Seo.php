<?php

namespace App\Support;

use App\Models\{Gallery, Page, PageBuilder, Photo, SiteSetting};

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
        $settings ??= self::settings();
        return (string) ($settings['seo_indexable'] ?? '1') !== '0'
            && (string) ($settings['site_under_construction'] ?? '0') !== '1';
    }

    public static function homeIndexing(?array $settings = null): bool
    {
        $settings ??= self::settings();
        return self::indexing($settings) && (string) ($settings['home_seo_indexable'] ?? '1') !== '0';
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
        $resolvedTitle = $entity === null
            ? self::firstText($settings['home_seo_title'] ?? null, $settings['seo_default_title'] ?? null, $fallbackTitle)
            : self::firstText($entity?->seo_title, $entity instanceof Gallery ? ($settings['seo_default_title'] ?? null) : null, $fallbackTitle);
        $description = self::firstText($entity === null ? ($settings['home_seo_description'] ?? null) : null, $entity?->seo_description, $settings['seo_default_description'] ?? null,
            $entity instanceof Gallery ? $entity->description : null, $entity instanceof Page ? strip_tags($entity->content ?? '') : null,
            $settings['hero_text'] ?? null, $settings['site_subtitle'] ?? null);
        $photo = $entity?->socialPhoto;
        if (!$photo && $entity instanceof Gallery) {
            $photo = $entity->photos->first(fn ($photo) => (bool) $photo->pivot->is_cover);
        }
        if (!$photo && $entity === null) $photo = Photo::find($settings['home_seo_social_photo_id'] ?? null);
        $photo ??= Photo::find($settings['seo_social_photo_id'] ?? null);
        $canonical = $entity instanceof Gallery ? route('portfolio.gallery', $entity)
            : ($entity instanceof Page ? self::pageUrl($entity) : ($url ?? url('/')));
        $schemaType = match (true) {
            $entity instanceof Gallery => 'ImageGallery',
            $entity instanceof Page && $entity->slug === 'o-mnie' => 'AboutPage',
            $entity instanceof Page && $entity->slug === 'kontakt' => 'ContactPage',
            default => 'WebPage',
        };

        return [
            'title' => $resolvedTitle, 'description' => $description, 'canonical' => $canonical,
            'image' => $photo?->imageUrl(),
            'image_alt' => self::firstText($photo?->alt, $photo?->title, $resolvedTitle),
            'type' => 'website', 'site_name' => $site, 'schema_type' => $schemaType,
            'language' => 'pl', 'og_locale' => 'pl_PL',
            'robots' => ($entity === null ? self::homeIndexing($settings) : self::indexing($settings) && ($entity?->indexable ?? true)) ? 'index, follow' : 'noindex, nofollow',
        ];
    }

    public static function issues(Page|Gallery $entity): array
    {
        $issues = [];
        if (!trim($entity->seo_title ?? '')) $issues[] = 'Brak tytułu SEO';
        if (!trim($entity->seo_description ?? '')) $issues[] = 'Brak opisu SEO';

        $meta = self::meta($entity);
        $issues = array_merge($issues, self::metaLengthIssues($meta['title'], $meta['description']));

        if (!$meta['image']) $issues[] = 'Brak zdjęcia social (także w fallbackach)';
        if (!$entity->indexable) $issues[] = 'noindex';

        if ($entity instanceof Page && $entity->builder) {
            $sections = $entity->builder->content['sections'] ?? [];
            $issues = array_merge($issues, self::headingIssues($sections));
        }

        return $issues;
    }

    public static function homeIssues(): array
    {
        $settings = self::settings();
        $issues = [];
        if (!trim((string) ($settings['home_seo_title'] ?? '')) && !trim((string) ($settings['seo_default_title'] ?? ''))) {
            $issues[] = 'Brak tytułu SEO';
        }
        if (!trim((string) ($settings['home_seo_description'] ?? '')) && !trim((string) ($settings['seo_default_description'] ?? ''))) {
            $issues[] = 'Brak opisu SEO';
        }
        $meta = self::meta(null);
        $issues = array_merge($issues, self::metaLengthIssues($meta['title'], $meta['description']));
        if (!$meta['image']) $issues[] = 'Brak zdjęcia social (także w fallbackach)';
        if (!self::homeIndexing($settings)) $issues[] = 'noindex';

        $builder = PageBuilder::whereNull('page_id')->where('type', 'home')->where('published', true)->first();
        $sections = collect($builder?->content['sections'] ?? [])
            ->map(function ($section) {
                if (!empty($section['semantic_tag'])) {
                    return $section;
                }

                $section['semantic_tag'] = match ($section['id'] ?? null) {
                    'hero-heading' => 'h1',
                    'hero-text' => 'p',
                    'portfolio-heading' => 'h2',
                    default => $section['semantic_tag'] ?? null,
                };

                return $section;
            })
            ->all();

        return array_merge($issues, self::headingIssues($sections));
    }

    /**
     * These are editorial warnings, not search-engine hard limits.
     *
     * @return list<string>
     */
    public static function metaLengthIssues(string $title, string $description): array
    {
        $issues = [];
        $titleLength = mb_strlen(trim($title));
        $descriptionLength = mb_strlen(trim($description));

        if ($titleLength > 65) $issues[] = 'Długi tytuł SEO ('.$titleLength.' znaków)';
        if ($descriptionLength > 170) $issues[] = 'Długi opis SEO ('.$descriptionLength.' znaków)';

        return $issues;
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     * @return list<string>
     */
    public static function headingIssues(array $sections): array
    {
        $headings = collect($sections)
            ->map(function ($section) {
                $tag = $section['semantic_tag']
                    ?? (($section['type'] ?? null) === 'heading' ? ($section['heading_level'] ?? null) : null);

                if (!in_array($tag, ['h1', 'h2', 'h3'], true)) {
                    return null;
                }

                return [
                    'tag' => $tag,
                    'content' => (string) ($section['content'] ?? ''),
                ];
            })
            ->filter()
            ->values();

        $issues = [];
        $h1Headings = $headings->filter(fn ($heading) => $heading['tag'] === 'h1');
        $h1Count = $h1Headings->count();

        if ($h1Count === 0) $issues[] = 'Brak H1';
        if ($h1Count > 1) $issues[] = 'Więcej niż jeden H1 ('.$h1Count.')';
        if ($h1Headings->contains(fn ($heading) => trim($heading['content']) === '')) {
            $issues[] = 'Pusty H1';
        }

        $levels = $headings
            ->map(fn ($heading) => match ($heading['tag']) {
                'h1' => 1,
                'h2' => 2,
                'h3' => 3,
            })
            ->values();

        for ($i = 1; $i < $levels->count(); $i++) {
            if ($levels[$i] > $levels[$i - 1] + 1) {
                $issues[] = 'Pominięty poziom nagłówka (np. H1 → H3)';
                break;
            }
        }

        return $issues;
    }
}
