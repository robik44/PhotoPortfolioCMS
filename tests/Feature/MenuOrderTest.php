<?php
namespace Tests\Feature;

use App\Models\{Gallery, MenuItem, Page, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuOrderTest extends TestCase
{
    use RefreshDatabase;

    private function item(string $title, ?int $parent = null): MenuItem
    {
        return MenuItem::create(['title' => $title, 'type' => 'url', 'url' => '/'.$title, 'parent_id' => $parent, 'published' => true, 'sort_order' => 10]);
    }

    public function test_root_and_submenu_order_is_saved_and_shared_by_public_views_without_data_loss(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        $a = $this->item('FIRST'); $b = $this->item('SECOND');
        $c = $this->item('CHILD-A', $a->id); $d = $this->item('CHILD-B', $a->id);
        $before = MenuItem::all()->map->only(['id', 'title', 'type', 'url', 'page_id', 'gallery_id', 'parent_id', 'published'])->all();
        foreach ([[null, [$b, $a]], [$a->id, [$d, $c]]] as [$parent, $items]) {
            $this->postJson(route('menu.reorder'), ['parent_id' => $parent, 'items' => array_map(fn ($item) => ['id' => $item->id], $items)])->assertOk()->assertJson(['success' => true]);
            foreach ($items as $order => $item) $this->assertSame($order, $item->fresh()->sort_order);
        }
        $this->assertSame($before, MenuItem::all()->map->only(['id', 'title', 'type', 'url', 'page_id', 'gallery_id', 'parent_id', 'published'])->all());
        $page = Page::create(['title' => 'Test', 'slug' => 'test', 'published' => true]);
        $gallery = Gallery::create(['title' => 'Test', 'slug' => 'test']);
        foreach (['/', '/o-mnie', '/kontakt', route('page.public', $page), route('portfolio.gallery', $gallery)] as $url) {
            $this->get($url)->assertOk()->assertSeeInOrder(['SECOND', 'FIRST', 'CHILD-B', 'CHILD-A']);
        }
        $this->get(route('menu.index'))->assertOk()->assertSee('data-menu-sort', false)->assertSee('menu-order.js')
            ->assertSeeInOrder(['SECOND', 'FIRST', 'CHILD-B', 'CHILD-A']);
    }

    public function test_custom_menu_url_rejects_unsafe_schemes(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('menu.store'), [
            'title' => 'Niebezpieczny',
            'type' => 'url',
            'url' => 'javascript:alert(1)',
            'published' => '1',
        ])->assertSessionHasErrors('url');

        $this->post(route('menu.store'), [
            'title' => 'Bezpieczny',
            'type' => 'url',
            'url' => 'https://example.com',
            'published' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('menu_items', ['title' => 'Bezpieczny', 'url' => 'https://example.com']);
    }

    public function test_sort_endpoint_rejects_parent_changes_partial_lists_and_duplicates_atomically(): void
    {
        $this->actingAs(User::factory()->create());
        $a = $this->item('A'); $b = $this->item('B');
        $c = $this->item('C', $a->id); $d = $this->item('D', $b->id);
        $before = MenuItem::all()->toJson();
        foreach ([
            ['parent_id' => null, 'items' => [['id' => $a->id], ['id' => $c->id]]],
            ['parent_id' => $a->id, 'items' => [['id' => $d->id]]],
            ['parent_id' => null, 'items' => [['id' => $a->id]]],
            ['parent_id' => null, 'items' => [['id' => $a->id], ['id' => $a->id]]],
            ['parent_id' => null, 'items' => [['id' => $a->id, 'parent_id' => $b->id], ['id' => $b->id]]],
        ] as $payload) {
            $response = $this->postJson(route('menu.reorder'), $payload);
            $this->assertSame(422, $response->status(), $response->getContent());
        }
        $this->assertSame($before, MenuItem::all()->toJson());
    }
}
