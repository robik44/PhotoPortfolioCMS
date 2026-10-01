<?php

namespace Tests\Feature;

use App\Models\{Gallery, Page, Photo, SiteSetting, User};
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function settings(array $values): void
    {
        foreach ($values as $key => $value) SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    private function page(array $values = []): Page
    {
        return Page::create($values + ['title' => 'Klienci', 'slug' => 'klienci', 'published' => true]);
    }

    private function gallery(array $values = []): Gallery
    {
        return Gallery::create($values + ['title' => 'Żywność', 'slug' => 'zywnosc']);
    }

    public function test_authentication_and_cms_pages_are_noindex(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->actingAs(User::factory()->create());
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_public_pages_declare_polish_document_language(): void
    {
        $this->assertSame('pl', config('app.locale'));

        $page = $this->page();
        $gallery = $this->gallery();

        $this->get('/')->assertOk()->assertSee('<html lang="pl">', false);
        $this->get(Seo::pageUrl($page))->assertOk()->assertSee('<html lang="pl">', false);
        $this->get(route('portfolio.gallery', $gallery))->assertOk()->assertSee('<html lang="pl">', false);
    }

    public function test_global_settings_save_references_in_existing_settings_and_render_home_meta(): void
    {
        $this->actingAs(User::factory()->create());
        $photo = Photo::create(['filename' => 'social.jpg']);
        $this->settings(['logo' => 'Existing logo']);
        $data = ['seo_site_name' => 'Studio', 'seo_default_title' => 'Fotografia reklamowa', 'seo_default_description' => 'Opis domyślny', 'seo_social_photo_id' => $photo->id, 'seo_indexable' => '1'];
        $this->put(route('seo.update'), $data)->assertSessionHasNoErrors()->assertRedirect();
        foreach ($data as $key => $value) $this->assertDatabaseHas('site_settings', compact('key', 'value'));
        $this->assertDatabaseHas('site_settings', ['key' => 'logo', 'value' => 'Existing logo']);
        $this->assertDatabaseCount('photos', 1);
        $this->get('/')->assertOk()->assertSee('<title>Fotografia reklamowa</title>', false)
            ->assertSee('<meta name="description" content="Opis domyślny">', false)
            ->assertSee('<meta property="og:image" content="'.$photo->imageUrl().'">', false);
        $this->get(route('seo.edit'))->assertOk()->assertSee('SEO — KONTROLA WITRYNY')->assertSee('social.jpg');
    }

    public function test_page_title_description_and_social_fallbacks_and_no_duplicate_site_name(): void
    {
        $photo = Photo::create(['filename' => 'default.jpg']);
        $this->settings(['seo_site_name' => 'Studio', 'seo_default_title' => 'Home title', 'seo_default_description' => 'Default description', 'seo_social_photo_id' => $photo->id]);
        $page = $this->page();
        $this->get(Seo::pageUrl($page))->assertOk()->assertSee('<title>Klienci — Studio</title>', false)->assertSee('content="Default description"', false)->assertSee($photo->imageUrl());
        $page->update(['title' => 'Klienci Studio']);
        $this->assertSame('Klienci Studio', Seo::meta($page)['title']);
    }

    public function test_empty_global_seo_uses_existing_settings_and_omits_empty_optional_meta(): void
    {
        $this->settings(['site_title' => 'Fotograf', 'hero_text' => 'Dotychczasowy opis']);
        $this->get('/')->assertOk()->assertSee('<title>Fotograf</title>', false)->assertSee('content="Dotychczasowy opis"', false)->assertDontSee('property="og:image"', false);
        SiteSetting::where('key', 'hero_text')->delete();
        $this->get('/')->assertDontSee('name="description"', false)->assertDontSee('property="og:description"', false);
    }

    public function test_page_seo_editing_and_public_tags_are_escaped_and_not_duplicated(): void
    {
        $this->actingAs(User::factory()->create());
        $photo = Photo::create(['filename' => 'page-social.jpg']);
        $page = $this->page();
        $data = ['title' => $page->title, 'slug' => $page->slug, 'published' => '1', 'seo_title' => 'Własny <tytuł>', 'seo_description' => 'Opis "strony" & więcej', 'social_photo_id' => $photo->id, 'indexable' => '1'];
        $this->put(route('pages.update', $page), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($photo->id, $page->fresh()->social_photo_id);
        $response = $this->get(Seo::pageUrl($page).'?tracking=abc')->assertOk()->assertSee('<title>Własny &lt;tytuł&gt;</title>', false)
            ->assertSee('<meta name="description" content="Opis &quot;strony&quot; &amp; więcej">', false)
            ->assertSee('<link rel="canonical" href="'.Seo::pageUrl($page).'">', false)
            ->assertSee('<meta name="robots" content="index, follow">', false)->assertSee($photo->imageUrl());
        foreach (['<title>', 'name="description"', 'rel="canonical"', 'name="robots"', 'property="og:title"', 'property="og:description"', 'property="og:url"', 'property="og:image"', 'property="og:type"'] as $tag) $this->assertSame(1, substr_count($response->getContent(), $tag));
        $this->get(route('pages.edit', $page))->assertOk()->assertSee('Podgląd w wyszukiwarce')->assertSee(Seo::pageUrl($page));
    }

    public function test_page_noindex_and_static_content_pages_use_shared_seo(): void
    {
        $page = $this->page(['title' => 'O mnie', 'slug' => 'o-mnie', 'seo_title' => 'Autor zdjęć', 'indexable' => false]);
        $this->get('/o-mnie')->assertOk()->assertSee('<title>Autor zdjęć</title>', false)->assertSee('content="noindex, nofollow"', false)->assertSee('href="'.url('/o-mnie').'"', false);
        $this->get('/sitemap.xml')->assertDontSee(url('/o-mnie'));
        $page->update(['published' => false]);
        $this->get('/o-mnie')->assertNotFound();
    }

    public function test_gallery_slug_canonical_legacy_redirect_and_seo_edit_preserves_slug_and_photos(): void
    {
        $this->actingAs(User::factory()->create());
        $gallery = $this->gallery();
        $photo = Photo::create(['filename' => 'gallery-social.jpg']);
        $gallery->photos()->attach($photo, ['is_cover' => true, 'sort_order' => 5]);
        $this->put(route('galleries.update', $gallery), ['title' => 'Nowy tytuł', 'seo_title' => 'SEO galerii', 'seo_description' => 'Opis SEO galerii', 'social_photo_id' => $photo->id, 'indexable' => '1'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('zywnosc', $gallery->fresh()->slug);
        $this->assertSame($photo->id, $gallery->fresh()->social_photo_id);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $gallery->id, 'photo_id' => $photo->id, 'sort_order' => 5, 'is_cover' => 1]);
        $this->assertSame(url('/portfolio/zywnosc'), route('portfolio.gallery', $gallery));
        $this->get('/portfolio/'.$gallery->id)->assertStatus(301)->assertRedirect('/portfolio/zywnosc');
        $this->get('/portfolio/zywnosc?x=1')->assertOk()->assertSee('<title>SEO galerii</title>', false)->assertSee('content="Opis SEO galerii"', false)->assertSee('<link rel="canonical" href="'.url('/portfolio/zywnosc').'">', false);
        $this->get(route('galleries.edit', $gallery))->assertOk()->assertSee('name="slug"', false);
    }

    public function test_public_meta_declares_polish_locale_and_language(): void
    {
        $page = $this->page(['seo_title' => 'Klienci', 'seo_description' => 'Opis']);

        $this->get(Seo::pageUrl($page))->assertOk()
            ->assertSee('<meta property="og:locale" content="pl_PL">', false)
            ->assertSee('<link rel="alternate" hreflang="pl" href="'.Seo::pageUrl($page).'">', false)
            ->assertSee('"inLanguage":"pl"', false);
    }

    public function test_social_image_alt_and_schema_types_match_page_kind(): void
    {
        $photo = Photo::create(['filename' => 'social.jpg', 'alt' => 'Apetyczne danie']);
        $this->settings(['seo_social_photo_id' => $photo->id, 'seo_default_title' => 'Studio', 'seo_default_description' => 'Opis']);

        $about = $this->page(['title' => 'O mnie', 'slug' => 'o-mnie']);
        $contact = $this->page(['title' => 'Kontakt', 'slug' => 'kontakt']);
        $gallery = $this->gallery();

        $this->assertSame('AboutPage', Seo::meta($about)['schema_type']);
        $this->assertSame('ContactPage', Seo::meta($contact)['schema_type']);
        $this->assertSame('ImageGallery', Seo::meta($gallery)['schema_type']);
        $this->assertSame('WebPage', Seo::meta(null)['schema_type']);

        $this->get('/o-mnie')->assertOk()
            ->assertSee('<meta property="og:image:alt" content="Apetyczne danie">', false)
            ->assertSee('<meta name="twitter:image:alt" content="Apetyczne danie">', false)
            ->assertSee('"@type":"AboutPage"', false)
            ->assertSee('"caption":"Apetyczne danie"', false);

        $this->get('/kontakt')->assertOk()->assertSee('"@type":"ContactPage"', false);
        $this->get(route('portfolio.gallery', $gallery))->assertOk()->assertSee('"@type":"ImageGallery"', false);
    }

    public function test_gallery_social_priority_is_own_then_pivot_cover_then_global(): void
    {
        $global = Photo::create(['filename' => 'global.jpg']);
        $cover = Photo::create(['filename' => 'cover.jpg', 'is_cover' => false]);
        $own = Photo::create(['filename' => 'own.jpg']);
        $legacy = Photo::create(['filename' => 'legacy.jpg', 'is_cover' => true]);
        $gallery = $this->gallery();
        $this->settings(['seo_social_photo_id' => $global->id]);
        $gallery->photos()->attach($legacy, ['sort_order' => 0, 'is_cover' => false]);
        $this->assertSame($global->imageUrl(), Seo::meta($gallery->fresh())['image']);
        $gallery->photos()->attach($cover, ['sort_order' => 2, 'is_cover' => true]);
        $this->assertSame($cover->imageUrl(), Seo::meta($gallery->fresh())['image']);
        $gallery->update(['social_photo_id' => $own->id]);
        $this->assertSame($own->imageUrl(), Seo::meta($gallery->fresh())['image']);
    }

    public function test_gallery_noindex_and_private_gallery_are_excluded_from_sitemap(): void
    {
        $gallery = $this->gallery(['indexable' => false]);
        $this->get(route('portfolio.gallery', $gallery))->assertOk()->assertSee('content="noindex, nofollow"', false);
        $this->get('/sitemap.xml')->assertDontSee('/portfolio/zywnosc');
        $gallery->forceFill(['published' => false, 'indexable' => true])->save();
        $this->get(route('portfolio.gallery', $gallery))->assertNotFound();
        $this->get('/portfolio/'.$gallery->id)->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/portfolio/zywnosc');
    }

    public function test_sitemap_has_only_canonical_public_indexable_content(): void
    {
        $page = $this->page();
        $gallery = $this->gallery();
        $this->page(['slug' => 'hidden', 'published' => false]);
        $this->page(['slug' => 'noindex', 'indexable' => false]);
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(Seo::pageUrl($page))->assertSee(route('portfolio.gallery', $gallery))->assertDontSee('/portfolio/'.$gallery->id.'<', false)->assertDontSee('/strona/hidden')->assertDontSee('/strona/noindex')->assertDontSee('/admin')
            ->assertSee('<lastmod>'.$page->updated_at->toAtomString().'</lastmod>', false)
            ->assertSee('<lastmod>'.$gallery->updated_at->toAtomString().'</lastmod>', false);
        $this->assertNotFalse(simplexml_load_string($response->getContent()));
    }

    public function test_sitemap_includes_gallery_images_for_image_search(): void
    {
        $gallery = $this->gallery();
        $photo = Photo::create([
            'filename' => 'dish.jpg',
            'webp' => 'dish-web.webp',
            'title' => 'Fotografia dania',
            'alt' => 'Danie na talerzu',
            'description' => 'Zdjęcie reklamowe potrawy',
        ]);
        $gallery->photos()->attach($photo, ['sort_order' => 0, 'is_cover' => true]);

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"', false)
            ->assertSee('<image:loc>'.$photo->imageUrl().'</image:loc>', false)
            ->assertSee('<image:title>Fotografia dania</image:title>', false)
            ->assertSee('<image:caption>Zdjęcie reklamowe potrawy</image:caption>', false);
    }

    public function test_global_noindex_overrides_pages_galleries_robots_and_sitemap(): void
    {
        $page = $this->page(); $gallery = $this->gallery();
        $this->settings(['seo_indexable' => '0']);
        foreach (['/', Seo::pageUrl($page), route('portfolio.gallery', $gallery)] as $url) $this->get($url)->assertSee('content="noindex, nofollow"', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>', false);
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertSee("Disallow: /\n", false)->assertSee(url('/sitemap.xml'));
        $this->settings(['seo_indexable' => '1']);
        $this->get('/robots.txt')->assertSee("Allow: /\n", false)->assertDontSee("Disallow: /\n", false);
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function test_library_filters_match_null_empty_and_whitespace_without_changing_metadata(): void
    {
        $this->actingAs(User::factory()->create());
        $complete = Photo::create(['filename' => 'complete.jpg', 'alt' => 'ALT', 'title' => 'Title', 'description' => 'Description']);
        $missing = Photo::create(['filename' => 'missing.jpg', 'alt' => ' ', 'title' => '', 'description' => null]);
        $before = Photo::all()->toJson();
        foreach (['missing_alt', 'missing_title', 'missing_description'] as $filter) {
            $this->get(route('photos.index', ['seo_filter' => $filter]))->assertOk()->assertSee('missing.jpg')->assertDontSee('complete.jpg');
        }
        $this->get(route('photos.index'))->assertOk()->assertSee('complete.jpg')->assertSee('missing.jpg');
        $this->assertSame($before, Photo::all()->toJson());
        $this->get(route('seo.edit'))->assertOk()->assertSee('Wszystkie Photo: 2')->assertSee('Posiadające ALT: 1')->assertSee('Brak ALT: 1')->assertSee('Brak tytułu: 1')->assertSee('Brak opisu: 1');
    }

    public function test_seo_audit_warns_about_unusually_long_effective_metadata(): void
    {
        $title = str_repeat('T', 66);
        $description = str_repeat('O', 171);

        $page = $this->page([
            'seo_title' => $title,
            'seo_description' => $description,
            'indexable' => true,
        ]);

        $issues = Seo::issues($page);

        $this->assertContains('Długi tytuł SEO (66 znaków)', $issues);
        $this->assertContains('Długi opis SEO (171 znaków)', $issues);

        $this->settings([
            'home_seo_title' => $title,
            'home_seo_description' => $description,
        ]);

        $this->assertContains('Długi tytuł SEO (66 znaków)', Seo::homeIssues());
        $this->assertContains('Długi opis SEO (171 znaków)', Seo::homeIssues());
    }

    public function test_audit_reports_facts_with_edit_links_and_effective_social_fallback(): void
    {
        $this->actingAs(User::factory()->create());
        $page = $this->page(); $gallery = $this->gallery(['indexable' => false]);
        $this->get(route('seo.edit'))->assertOk()->assertSee('Brak tytułu SEO')->assertSee('Brak opisu SEO')->assertSee('Brak zdjęcia social')->assertSee('noindex')->assertSee(route('pages.edit', $page))->assertSee(route('galleries.edit', $gallery));
        $photo = Photo::create(['filename' => 'global.jpg']);
        $this->settings(['seo_social_photo_id' => $photo->id]);
        $page->update(['seo_title' => 'Tytuł', 'seo_description' => 'Opis']);
        $this->assertSame([], Seo::issues($page->fresh()));
        $this->get(route('seo.edit'))->assertSee('Kompletne');
    }

    public function test_builder_heading_levels_and_legacy_rendering_are_safe_and_preserve_styles(): void
    {
        $this->actingAs(User::factory()->create());
        $page = $this->page();
        foreach (['h1', 'h2', 'h3', null] as $level) {
            $element = ['type' => 'heading', 'content' => 'Nagłówek testowy', 'style' => ['font_size' => 42]];
            if ($level) $element['heading_level'] = $level;
            $this->postJson(route('pages.builder.save', $page), ['content' => ['sections' => [$element]]])->assertOk();
            $html = $this->get(Seo::pageUrl($page))->assertOk()->assertSee('font-size:42px;', false)->getContent();
            $this->assertMatchesRegularExpression('/<'.($level ?? 'div').'\s+class="builder-public-text"/', $html);
        }
        $this->postJson(route('pages.builder.save', $page), ['content' => ['sections' => [['type' => 'heading', 'heading_level' => 'script']]]])->assertStatus(422);
        $this->get(route('pages.builder', $page))->assertOk()->assertSee('Poziom nagłówka');
    }

    public function test_heading_audit_reports_empty_h1_and_skipped_levels(): void
    {
        $sections = [
            ['type' => 'heading', 'content' => '   ', 'heading_level' => 'h1'],
            ['type' => 'heading', 'content' => 'Sekcja', 'heading_level' => 'h3'],
        ];

        $issues = Seo::headingIssues($sections);

        $this->assertContains('Pusty H1', $issues);
        $this->assertContains('Pominięty poziom nagłówka (np. H1 → H3)', $issues);
        $this->assertNotContains('Brak H1', $issues);
    }

    public function test_seo_audit_reports_missing_or_duplicate_h1_for_builder_pages_and_home(): void
    {
        $this->actingAs(User::factory()->create());

        $page = $this->page(['seo_title' => 'SEO', 'seo_description' => 'Opis', 'indexable' => true]);
        $page->builder()->create([
            'type' => 'page',
            'published' => true,
            'content' => ['sections' => [
                ['type' => 'heading', 'content' => 'Sekcja', 'heading_level' => 'h2'],
            ]],
        ]);

        $this->assertContains('Brak H1', Seo::issues($page->fresh()->load('builder')));

        $page->builder->update(['content' => ['sections' => [
            ['type' => 'heading', 'content' => 'Pierwszy', 'heading_level' => 'h1'],
            ['type' => 'heading', 'content' => 'Drugi', 'heading_level' => 'h1'],
        ]]]);
        $this->assertContains('Więcej niż jeden H1 (2)', Seo::issues($page->fresh()->load('builder')));

        \App\Models\PageBuilder::create([
            'page_id' => null,
            'type' => 'home',
            'published' => true,
            'content' => ['sections' => [
                ['type' => 'heading', 'content' => 'Home', 'heading_level' => 'h1'],
            ]],
        ]);
        $this->settings([
            'seo_default_title' => 'Domyślny tytuł',
            'seo_default_description' => 'Domyślny opis',
        ]);

        $this->assertNotContains('Brak H1', Seo::homeIssues());
        $this->get(route('seo.edit'))->assertOk()->assertSee('Audyt sprawdza też strukturę nagłówków')->assertSee('Sprawdź H1');
    }

    public function test_gallery_uses_global_title_and_description_then_existing_gallery_data(): void
    {
        $gallery = $this->gallery(['description' => 'Dotychczasowy opis galerii']);
        $this->settings(['seo_site_name' => 'Studio']);
        $meta = Seo::meta($gallery);
        $this->assertSame('Żywność — Studio', $meta['title']);
        $this->assertSame('Dotychczasowy opis galerii', $meta['description']);
        $this->settings(['seo_default_title' => 'Domyślny tytuł', 'seo_default_description' => 'Domyślny opis']);
        $meta = Seo::meta($gallery);
        $this->assertSame('Domyślny tytuł', $meta['title']);
        $this->assertSame('Domyślny opis', $meta['description']);
    }

    public function test_static_content_preview_starts_with_real_h1(): void
    {
        $page = $this->page(['title' => 'O mnie', 'slug' => 'o-mnie']);
        $content = \App\Support\ContentPages::initialContent($page);

        $this->assertSame('h1', $content['sections'][0]['heading_level']);
    }

    public function test_seo_dashboard_reports_duplicate_metadata_and_broken_menu_targets(): void
    {
        $this->actingAs(User::factory()->create());

        $first = $this->page([
            'title' => 'Pierwsza',
            'slug' => 'pierwsza',
            'seo_title' => 'Wspólny tytuł',
            'seo_description' => 'Wspólny opis',
            'indexable' => true,
        ]);
        $second = $this->page([
            'title' => 'Druga',
            'slug' => 'druga',
            'seo_title' => 'Wspólny tytuł',
            'seo_description' => 'Wspólny opis',
            'indexable' => true,
        ]);
        $hidden = $this->page([
            'title' => 'Ukryta',
            'slug' => 'ukryta',
            'published' => false,
            'indexable' => true,
        ]);

        \App\Models\MenuItem::create([
            'title' => 'Ukryta strona',
            'type' => 'page',
            'page_id' => $hidden->id,
            'published' => true,
        ]);

        $this->get(route('seo.edit'))->assertOk()
            ->assertSee('Powtarzający się tytuł SEO')
            ->assertSee('Powtarzający się opis SEO')
            ->assertSee('Strona: Pierwsza')
            ->assertSee('Strona: Druga')
            ->assertSee('Menu „Ukryta strona” prowadzi do brakującej lub nieopublikowanej strony.');
    }

    public function test_create_forms_save_seo_and_admin_seo_requires_authentication(): void
    {
        $this->get(route('seo.edit'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        foreach (['pages', 'galleries'] as $resource) {
            $this->get(route($resource.'.create'))->assertOk()->assertSee('name="seo_title"', false)->assertSee('name="social_photo_id"', false);
            $this->post(route($resource.'.store'), ['title' => 'Nowa', 'slug' => 'nowa', 'seo_title' => 'Nowa SEO', 'seo_description' => 'Nowy opis', 'indexable' => '0'])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertDatabaseHas($resource, ['slug' => 'nowa', 'seo_title' => 'Nowa SEO', 'indexable' => false]);
        }
    }

    public function test_homepage_portfolio_heading_exposes_anchor_target(): void
    {
        \App\Models\PageBuilder::create([
            'page_id' => null,
            'type' => 'home',
            'published' => true,
            'content' => ['sections' => [
                [
                    'id' => 'portfolio-heading',
                    'type' => 'heading',
                    'content' => 'Portfolio',
                    'heading_level' => 'h2',
                ],
            ]],
        ]);

        $this->get('/')->assertOk()
            ->assertSee('id="portfolio"', false)
            ->assertSee('data-builder-id="portfolio-heading"', false);
    }

    public function test_homepage_has_independent_seo_and_indexing_controls(): void
    {
        $this->actingAs(User::factory()->create());
        $photo = Photo::create(['filename' => 'home-social.jpg']);
        $this->put(route('seo.update'), [
            'seo_site_name' => 'Studio',
            'seo_default_title' => 'Domyślny tytuł',
            'seo_default_description' => 'Domyślny opis',
            'seo_social_photo_id' => '',
            'seo_indexable' => '1',
            'home_seo_title' => 'Fotografia kulinarna Warszawa',
            'home_seo_description' => 'Autorska fotografia żywności i produktów.',
            'home_seo_social_photo_id' => $photo->id,
            'home_seo_indexable' => '1',
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertOk()
            ->assertSee('<title>Fotografia kulinarna Warszawa</title>', false)
            ->assertSee('content="Autorska fotografia żywności i produktów."', false)
            ->assertSee($photo->imageUrl(), false);

        SiteSetting::updateOrCreate(['key' => 'home_seo_indexable'], ['value' => '0']);
        $this->get('/')->assertSee('content="noindex, nofollow"', false);
        $this->get('/sitemap.xml')->assertDontSee('<loc>'.url('/').'</loc>', false);
    }

    public function test_under_construction_hides_public_site_even_when_authenticated_but_keeps_cms_available(): void
    {
        SiteSetting::updateOrCreate(['key' => 'site_under_construction'], ['value' => '1']);

        $this->app['auth']->logout();
        $this->get('/')->assertStatus(503)->assertSee('Strona w budowie')->assertSee('noindex, nofollow', false);
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>', false);

        $this->actingAs(User::factory()->create());
        $this->get('/')->assertStatus(503)->assertSee('Strona w budowie');
        $this->get(route('seo.edit'))->assertOk();
        $this->get(route('site-settings.edit'))->assertOk();
    }

}
