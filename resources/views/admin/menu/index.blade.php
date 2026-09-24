<x-app-layout>

    <x-slot name="header">
        Menu strony
    </x-slot>

    <style>
        .cms-menu-list [data-menu-id] { position: relative; }
        .cms-menu-list [data-menu-dragging] { opacity: .55; }
        .cms-menu-list [data-menu-drop]::after {
            content: ''; position: absolute; left: 0; right: 0; height: 4px;
            background: #2563eb; border-radius: 2px; pointer-events: none; z-index: 2;
        }
        .cms-menu-list [data-menu-drop="before"]::after { top: -4px; }
        .cms-menu-list [data-menu-drop="after"]::after { bottom: -4px; }
        .cms-menu-list[aria-busy="true"] .cms-menu-drag { cursor: wait !important; }
    </style>

    <div class="cms-page">

        <div class="cms-page-header">

            <div>
                <h1>Menu strony</h1>
                <p>Zarządzaj pozycjami menu i strukturą nawigacji strony.</p>
            </div>

            <a href="{{ route('menu.create') }}" class="cms-button cms-button-primary">
                + Dodaj pozycję
            </a>

        </div>

        <div class="cms-card">

            @if($menuItems->count())

                <p id="menu-order-status" role="status" aria-live="polite"></p>
                <div class="cms-menu-list" data-menu-sort data-parent="" data-url="{{ route('menu.reorder') }}">

                    @foreach($menuItems as $item)
                        <div data-menu-id="{{ $item->id }}">

                        <div class="cms-menu-item">

                            <div class="cms-menu-item-main">

                                <div class="cms-menu-drag" draggable="true" title="Przeciągnij, aby zmienić kolejność" style="cursor:grab;" role="button" tabindex="0" aria-label="Zmień kolejność: przeciągnij lub użyj strzałek góra/dół">
                                    ⋮⋮
                                </div>

                                <div>

                                    <div class="cms-menu-title">
                                        {{ $item->title }}
                                    </div>

                                    <div class="cms-menu-meta">

                                        @if($item->type === 'page')
                                            Strona
                                            @if($item->page)
                                                — {{ $item->page->title }}
                                            @endif
                                        @elseif($item->type === 'gallery')
                                            Galeria
                                            @if($item->gallery)
                                                — {{ $item->gallery->title }}
                                            @endif
                                        @else
                                            Link
                                            @if($item->url)
                                                — {{ $item->url }}
                                            @endif
                                        @endif

                                    </div>

                                </div>

                            </div>

                            <div class="cms-menu-item-actions">

                                @if($item->published)
                                    <span class="cms-status cms-status-published">
                                        Opublikowane
                                    </span>
                                @else
                                    <span class="cms-status cms-status-draft">
                                        Ukryte
                                    </span>
                                @endif

                                <a href="{{ route('menu.edit', $item) }}"
                                   class="cms-button cms-button-small">
                                    Edytuj
                                </a>

                                <form method="POST"
                                      action="{{ route('menu.destroy', $item) }}"
                                      onsubmit="return confirm('Czy na pewno usunąć tę pozycję menu?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="cms-button cms-button-small cms-button-danger">
                                        Usuń
                                    </button>
                                </form>

                            </div>

                        </div>

                        @if($item->children->count())

                            <div class="cms-menu-children" data-menu-sort data-parent="{{ $item->id }}">

                                @foreach($item->children as $child)

                                    <div class="cms-menu-item cms-menu-child" data-menu-id="{{ $child->id }}">

                                        <div class="cms-menu-item-main">

                                            <div class="cms-menu-drag" draggable="true" title="Przeciągnij, aby zmienić kolejność" style="cursor:grab;" role="button" tabindex="0" aria-label="Zmień kolejność: przeciągnij lub użyj strzałek góra/dół">
                                                ☰
                                            </div>

                                            <div>

                                                <div class="cms-menu-title">
                                                    {{ $child->title }}
                                                </div>

                                                <div class="cms-menu-meta">

                                                    @if($child->type === 'page')
                                                        Strona
                                                        @if($child->page)
                                                            — {{ $child->page->title }}
                                                        @endif
                                                    @elseif($child->type === 'gallery')
                                                        Galeria
                                                        @if($child->gallery)
                                                            — {{ $child->gallery->title }}
                                                        @endif
                                                    @else
                                                        Link
                                                        @if($child->url)
                                                            — {{ $child->url }}
                                                        @endif
                                                    @endif

                                                </div>

                                            </div>

                                        </div>

                                        <div class="cms-menu-item-actions">

                                            @if($child->published)
                                                <span class="cms-status cms-status-published">
                                                    Opublikowane
                                                </span>
                                            @else
                                                <span class="cms-status cms-status-draft">
                                                    Ukryte
                                                </span>
                                            @endif

                                            <a href="{{ route('menu.edit', $child) }}"
                                               class="cms-button cms-button-small">
                                                Edytuj
                                            </a>

                                            <form method="POST"
                                                  action="{{ route('menu.destroy', $child) }}"
                                                  onsubmit="return confirm('Czy na pewno usunąć tę pozycję menu?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                        class="cms-button cms-button-small cms-button-danger">
                                                    Usuń
                                                </button>
                                            </form>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        @endif

                        </div>
                    @endforeach

                </div>

            @else

                <div class="cms-empty-state">

                    <div class="cms-empty-icon">
                        ☰
                    </div>

                    <h2>Menu jest jeszcze puste</h2>

                    <p>
                        Dodaj pierwszą pozycję menu, aby rozpocząć budowanie nawigacji strony.
                    </p>

                    <a href="{{ route('menu.create') }}"
                       class="cms-button cms-button-primary">
                        + Dodaj pierwszą pozycję
                    </a>

                </div>

            @endif

        </div>

    </div>

<script src="{{ asset('js/menu-order.js') }}?v={{ filemtime(public_path('js/menu-order.js')) }}" defer></script>
</x-app-layout>
