<x-app-layout>
    <x-slot name="header">Strony</x-slot>

    <div class="cms-dashboard-intro">

        <div>
            <div class="cms-dashboard-eyebrow">Treść</div>

            <h1 class="cms-dashboard-title">
                Strony
            </h1>

            <p class="cms-dashboard-subtitle">
                Zarządzaj podstronami swojej witryny i twórz nowe elementy menu.
            </p>
        </div>

        <a href="{{ route("pages.create") }}"
           class="cms-action"
           style="padding:12px 18px; white-space:nowrap;">
            + Dodaj stronę
        </a>

    </div>


    <div class="cms-card">

        <div class="cms-card-header">
            <h2 class="cms-card-title">Wszystkie strony</h2>

            <span class="cms-card-link">
                {{ $pages->count() }} stron
            </span>
        </div>


        @if($pages->count())

            <div style="padding:0 24px 20px;">

                @foreach($pages as $page)

                    <div style="
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        gap:20px;
                        padding:18px 0;
                        border-bottom:1px solid #eeeeeb;
                    ">

                        <div>

                            <div style="
                                font-family:Georgia, 'Times New Roman', serif;
                                font-size:18px;
                                color:#202020;
                            ">
                                {{ $page->title }}
                            </div>

                            <div style="
                                margin-top:5px;
                                color:#858585;
                                font-size:11px;
                            ">
                                /strona/{{ $page->slug }}
                            </div>

                        </div>


                        <div style="
                            display:flex;
                            align-items:center;
                            gap:10px;
                        ">

                            @if($page->published)
                                <span style="
                                    padding:5px 9px;
                                    border-radius:20px;
                                    background:#e9f3e9;
                                    color:#315a35;
                                    font-size:10px;
                                ">
                                    Opublikowana
                                </span>
                            @else
                                <span style="
                                    padding:5px 9px;
                                    border-radius:20px;
                                    background:#eeeeeb;
                                    color:#777;
                                    font-size:10px;
                                ">
                                    Szkic
                                </span>
                            @endif


                            <a href="{{ route("pages.edit", $page) }}"
                               style="
                                    padding:9px 14px;
                                    border:1px solid #e5e5e1;
                                    border-radius:6px;
                                    font-size:11px;
                                    color:#303030;
                               ">
                                Edytuj
                            </a>

                            <a href="{{ route("pages.builder", $page) }}"
                               class="cms-button cms-button-small">
                                Builder
                            </a>

                            <form method="POST"
                                  action="{{ route("pages.destroy", $page) }}"
                                  onsubmit="return confirm('Czy na pewno usunąć tę stronę?');">

                                @csrf
                                @method("DELETE")

                                <button type="submit"
                                        style="
                                            padding:9px 14px;
                                            border:1px solid #ead8d5;
                                            border-radius:6px;
                                            background:#fff;
                                            color:#8a4038;
                                            font-size:11px;
                                            cursor:pointer;
                                        ">
                                    Usuń
                                </button>

                            </form>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="cms-photo-empty">
                Nie ma jeszcze żadnych podstron.
            </div>

        @endif

    </div>

</x-app-layout>
