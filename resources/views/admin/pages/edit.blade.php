<x-app-layout>
    <x-slot name="header">Edytuj stronę</x-slot>

    <div class="cms-dashboard-intro">
        <div>
            <div class="cms-dashboard-eyebrow">Treść</div>

            <h1 class="cms-dashboard-title">
                Edytuj stronę
            </h1>

            <p class="cms-dashboard-subtitle">
                Zmień treść, adres, zdjęcie lub status publikacji tej podstrony.
            </p>
        </div>

        <a href="{{ route("pages.builder", $page) }}" class="cms-button">
            Otwórz builder
        </a>
    </div>


    @if($errors->any())

        <div class="cms-alert cms-alert-error">

            <strong>Wystąpiły błędy:</strong>

            <ul style="margin:10px 0 0 20px;">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    <div class="cms-card">

        <div class="cms-card-header">

            <h2 class="cms-card-title">
                {{ $page->title }}
            </h2>

            <a href="{{ route("page.public", $page) }}"
               target="_blank"
               class="cms-card-link">
                Zobacz stronę ↗
            </a>

        </div>


        <form method="POST"
              action="{{ route("pages.update", $page) }}"
              style="padding:28px;">

            @include('admin.seo.fields', ['entity' => $page])

            @csrf
            @method("PUT")


            <div style="display:flex; flex-direction:column; gap:24px;">


                <div>

                    <label for="title"
                           style="
                                display:block;
                                margin-bottom:8px;
                                font-size:12px;
                                font-weight:600;
                                color:#303030;
                           ">
                        Tytuł strony
                    </label>

                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old("title", $page->title) }}"
                        required
                        style="
                            width:100%;
                            padding:13px 14px;
                            border:1px solid #e5e5e1;
                            border-radius:6px;
                            background:#fff;
                            color:#202020;
                        "
                    >

                </div>


                <div>

                    <label for="slug"
                           style="
                                display:block;
                                margin-bottom:8px;
                                font-size:12px;
                                font-weight:600;
                                color:#303030;
                           ">
                        Adres strony
                    </label>

                    <input
                        id="slug"
                        type="text"
                        name="slug"
                        value="{{ old("slug", $page->slug) }}"
                        required
                        style="
                            width:100%;
                            padding:13px 14px;
                            border:1px solid #e5e5e1;
                            border-radius:6px;
                            background:#fff;
                            color:#202020;
                        "
                    >

                    <div style="
                        margin-top:7px;
                        color:#858585;
                        font-size:11px;
                    ">
                        Publiczny adres:
                        /strona/{{ $page->slug }}
                    </div>

                </div>


                <x-photo-picker name="featured_photo_id" label="Zdjęcie wyróżniające"
                    :selected="$page->featured_photo_id"
                    :legacy-url="$page->featured_image ? asset('storage/'.$page->featured_image) : null"
                    clear-name="remove_featured_image" />

                <div>

                    <label for="content"
                           style="
                                display:block;
                                margin-bottom:8px;
                                font-size:12px;
                                font-weight:600;
                                color:#303030;
                           ">
                        Treść strony
                    </label>

                    <textarea
                        id="content"
                        name="content"
                        rows="18"
                        style="
                            width:100%;
                            padding:13px 14px;
                            border:1px solid #e5e5e1;
                            border-radius:6px;
                            background:#fff;
                            color:#202020;
                            resize:vertical;
                            line-height:1.6;
                        "
                    >{{ old("content", $page->content) }}</textarea>

                </div>


                <label style="
                    display:flex;
                    align-items:center;
                    gap:10px;
                    color:#303030;
                    font-size:12px;
                ">

                    <input
                        type="checkbox"
                        name="published"
                        value="1"
                        @checked(old("published", $page->published))
                    >

                    Opublikowana

                </label>


                <div style="
                    display:flex;
                    align-items:center;
                    gap:12px;
                    padding-top:8px;
                ">

                    <button
                        type="submit"
                        style="
                            padding:13px 24px;
                            border:0;
                            border-radius:6px;
                            background:#202020;
                            color:#fff;
                            font-size:12px;
                            font-weight:600;
                            cursor:pointer;
                        "
                    >
                        Zapisz zmiany
                    </button>


                    <a href="{{ route("pages.index") }}"
                       style="
                            padding:12px 20px;
                            border:1px solid #e5e5e1;
                            border-radius:6px;
                            color:#555;
                            font-size:12px;
                       ">
                        Wróć do stron
                    </a>

                </div>

            </div>

        </form>

    </div>

</x-app-layout>
