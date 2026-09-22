<header class="site-header">
    <div class="header-inner">

        <a href="{{ url('/') }}" class="logo">
            <span style="font-size:28px; font-weight:700; letter-spacing:.08em; color:#222;">
                {{ $settings['logo'] ?? 'ROBERT WOŹNIAK' }}
            </span>

            <span style="font-size:10px; font-weight:400; letter-spacing:.14em; color:#777;">
                {{ $settings['logo_subtitle'] ?? 'FOTOGRAFIA' }}
            </span>
        </a>

        <nav class="main-menu">
            @forelse($menuItems ?? collect() as $menuItem)

                @php
                    if ($menuItem->type === "page" && $menuItem->page) {
                        $menuUrl = route("page.public", $menuItem->page);
                    } elseif ($menuItem->type === "gallery" && $menuItem->gallery) {
                        $menuUrl = route("portfolio.gallery", $menuItem->gallery);
                    } elseif ($menuItem->type === "url" && $menuItem->url) {
                        $menuUrl = $menuItem->url;
                    } else {
                        $menuUrl = "#";
                    }
                @endphp

                <div class="main-menu-item">
                    <a href="{{ $menuUrl }}" class="main-menu-link">
                        {{ $menuItem->title }}
                        @if($menuItem->children->count())
                            <span class="main-menu-arrow">▼</span>
                        @endif
                    </a>

                    @if($menuItem->children->count())
                        <ul class="main-submenu">
                            @foreach($menuItem->children as $child)

                                @php
                                    if ($child->type === "page" && $child->page) {
                                        $childUrl = route("page.public", $child->page);
                                    } elseif ($child->type === "gallery" && $child->gallery) {
                                        $childUrl = route("portfolio.gallery", $child->gallery);
                                    } elseif ($child->type === "url" && $child->url) {
                                        $childUrl = $child->url;
                                    } else {
                                        $childUrl = "#";
                                    }
                                @endphp

                                <li>
                                    <a href="{{ $childUrl }}">{{ $child->title }}</a>
                                </li>

                            @endforeach
                        </ul>
                    @endif
                </div>

            @empty

                <a href="{{ url('/') }}" class="main-menu-link">Start</a>
                <a href="{{ url('/#portfolio') }}" class="main-menu-link">Portfolio</a>
                <a href="{{ route('about') }}" class="main-menu-link">O mnie</a>
                <a href="{{ route('contact') }}" class="main-menu-link">Kontakt</a>

            @endforelse
        </nav>

    </div>
</header>
