<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Zdjęcia
        </h2>
    </x-slot>

    <div style="padding:32px 0;">

        <div style="
            max-width:1400px;
            margin:0 auto;
            padding:0 24px;
        ">

            <nav aria-label="Filtry SEO Biblioteki" style="display:flex;gap:12px;margin-bottom:20px;">
                @foreach(['' => 'Wszystkie', 'missing_alt' => 'Brak ALT', 'missing_title' => 'Brak tytułu', 'missing_description' => 'Brak opisu'] as $filter => $label)
                    <a class="cms-button" href="{{ route('photos.index', ['seo_filter' => $filter]) }}" @if(request('seo_filter', '') === $filter) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            @if (session('success'))
                <div style="
                    margin-bottom:24px;
                    padding:14px 18px;
                    border-radius:6px;
                    background:#dcfce7;
                    color:#166534;
                ">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div role="alert" style="margin-bottom:24px;padding:14px 18px;border-radius:6px;background:#fee2e2;color:#991b1b;">
                    {{ session('error') }}
                </div>
            @endif


            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:30px;
            ">

                <h1 style="
                    margin:0;
                    font-size:30px;
                    font-weight:400;
                ">
                    Zdjęcia
                </h1>

                <a
                    href="{{ route('photos.create') }}"
                    style="
                        display:inline-block;
                        background:#171717;
                        color:#fff;
                        padding:13px 20px;
                        border-radius:6px;
                        text-decoration:none;
                        font-size:14px;
                    "
                >
                    + Dodaj fotografie
                </a>

            </div>


            @if ($photos->count())

                <div style="
                    display:grid;
                    grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
                    gap:24px;
                ">

                    @foreach ($photos as $photo)

                        <div style="
                            background:#fff;
                            border-radius:10px;
                            overflow:hidden;
                            box-shadow:0 2px 12px rgba(0,0,0,0.07);
                        ">

                            <div style="
                                position:relative;
                                background:#e9e7e2;
                            ">

                                <img
                                    src="{{ $photo->imageUrl() }}"
                                    alt="{{ $photo->alt ?: $photo->title ?: $photo->filename }}"
                                    style="
                                        width:100%;
                                        aspect-ratio:1 / 0.8;
                                        object-fit:cover;
                                        display:block;
                                    "
                                >

                            </div>


                            <div style="padding:18px;">

                                <h3 style="
                                    margin:0 0 8px;
                                    font-size:17px;
                                    font-weight:500;
                                ">
                                    {{ $photo->title ?: $photo->filename }}
                                </h3>


                                <div style="margin:0 0 16px;font-size:13px;color:#777;">
                                    @if ($photo->galleries->isNotEmpty())
                                        <p style="margin:0 0 6px;">Używane w galeriach:</p>
                                        <ul style="margin:0;padding-left:18px;">
                                            @foreach ($photo->galleries as $gallery)
                                                <li>
                                                    <a href="{{ route('galleries.show', $gallery) }}" style="color:#171717;text-decoration:underline;">
                                                        {{ $gallery->title }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        Nie jest używane w żadnej galerii.
                                    @endif
                                </div>

                                <div style="
                                    display:flex;
                                    gap:18px;
                                    align-items:center;
                                ">

                                    <a
                                        href="{{ route('photos.edit', $photo) }}"
                                        style="
                                            font-size:13px;
                                            color:#171717;
                                            text-decoration:none;
                                        "
                                    >
                                        Edytuj
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('photos.destroy', $photo) }}"
                                        style="display:inline;"
                                        onsubmit="return confirm('Zdjęcie zostanie trwale usunięte z Biblioteki i wszystkich galerii, a jego plik zostanie skasowany. Kontynuować?');"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            style="
                                                padding:0;
                                                border:0;
                                                background:none;
                                                color:#b91c1c;
                                                cursor:pointer;
                                                font-size:13px;
                                            "
                                        >
                                            Usuń z Biblioteki
                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div style="
                    background:#fff;
                    padding:60px 30px;
                    text-align:center;
                    border-radius:10px;
                ">
                    <p style="
                        margin:0 0 20px;
                        color:#777;
                    ">
                        Nie ma jeszcze żadnych zdjęć.
                    </p>

                    <a
                        href="{{ route('photos.create') }}"
                        style="
                            display:inline-block;
                            background:#171717;
                            color:#fff;
                            padding:13px 20px;
                            border-radius:6px;
                            text-decoration:none;
                        "
                    >
                        Dodaj pierwsze fotografie
                    </a>
                </div>

            @endif

        </div>

    </div>
</x-app-layout>
