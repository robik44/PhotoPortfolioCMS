<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function index()
    {
        $menuItems = MenuItem::with([
            'page',
            'gallery',
            'children.page',
            'children.gallery',
        ])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.menu.index', compact('menuItems'));
    }

    public function create()
    {
        $pages = Page::where('published', true)
            ->orderBy('title')
            ->get();

        $galleries = Gallery::orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $parentItems = MenuItem::whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.menu.create', compact(
            'pages',
            'galleries',
            'parentItems'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:page,gallery,url'],
            'page_id' => ['nullable', 'exists:pages,id'],
            'gallery_id' => ['nullable', 'exists:galleries,id'],
            'url' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'published' => ['nullable', 'boolean'],
        ]);

        $data['published'] = $request->boolean('published');

        $data['sort_order'] = (
            MenuItem::where('parent_id', $data['parent_id'] ?? null)
                ->max('sort_order') ?? -1
        ) + 1;

        MenuItem::create($data);

        return redirect()
            ->route('menu.index')
            ->with('success', 'Pozycja menu została dodana.');
    }

    public function edit(MenuItem $menuItem)
    {
        $pages = Page::where('published', true)
            ->orderBy('title')
            ->get();

        $galleries = Gallery::orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $parentItems = MenuItem::whereNull('parent_id')
            ->where('id', '!=', $menuItem->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.menu.edit', compact(
            'menuItem',
            'pages',
            'galleries',
            'parentItems'
        ));
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:page,gallery,url'],
            'page_id' => ['nullable', 'exists:pages,id'],
            'gallery_id' => ['nullable', 'exists:galleries,id'],
            'url' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'published' => ['nullable', 'boolean'],
        ]);

        $data['published'] = $request->boolean('published');

        if (($data['parent_id'] ?? null) == $menuItem->id) {
            $data['parent_id'] = null;
        }

        $menuItem->update($data);

        return redirect()
            ->route('menu.index')
            ->with('success', 'Pozycja menu została zaktualizowana.');
    }

    public function destroy(MenuItem $menuItem)
    {
        MenuItem::where('parent_id', $menuItem->id)
            ->update(['parent_id' => null]);

        $menuItem->delete();

        return redirect()
            ->route('menu.index')
            ->with('success', 'Pozycja menu została usunięta.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['items'] as $item) {
            MenuItem::where('id', $item['id'])
                ->update([
                    'parent_id' => $item['parent_id'] ?? null,
                    'sort_order' => $item['sort_order'],
                ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
