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
                                    src="{{ asset('storage/photos/' . $photo->filename) }}"
                                    alt="{{ $photo->alt ?: $photo->title ?: $photo->filename }}"
                                    style="
                                        width:100%;
                                        aspect-ratio:1 / 0.8;
                                        object-fit:cover;
                                        display:block;
                                    "
                                >

                                @if ($photo->is_cover)

                                    <div style="
                                        position:absolute;
                                        top:12px;
                                        left:12px;
                                        background:#171717;
                                        color:#fff;
                                        padding:7px 10px;
                                        border-radius:4px;
                                        font-size:11px;
                                        letter-spacing:0.08em;
                                        text-transform:uppercase;
                                    ">
                                        Okładka
                                    </div>

                                @endif

                            </div>


                            <div style="padding:18px;">

                                <h3 style="
                                    margin:0 0 8px;
                                    font-size:17px;
                                    font-weight:500;
                                ">
                                    {{ $photo->title ?: $photo->filename }}
                                </h3>


                                <p style="
                                    margin:0 0 16px;
                                    font-size:13px;
                                    color:#777;
                                ">
                                    {{ $photo->gallery?->title }}
                                </p>


                                @if (!$photo->is_cover)

                                    <form
                                        method="POST"
                                        action="{{ route('photos.make-cover', $photo) }}"
                                        style="margin-bottom:14px;"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            style="
                                                width:100%;
                                                padding:10px 12px;
                                                background:#f3f3f3;
                                                color:#171717;
                                                border:1px solid #d5d5d5;
                                                border-radius:5px;
                                                cursor:pointer;
                                                font-size:13px;
                                            "
                                        >
                                            Ustaw jako okładkę
                                        </button>

                                    </form>

                                @else

                                    <div style="
                                        margin-bottom:14px;
                                        padding:10px 12px;
                                        background:#f3f3f3;
                                        border-radius:5px;
                                        color:#555;
                                        text-align:center;
                                        font-size:13px;
                                    ">
                                        To jest okładka galerii
                                    </div>

                                @endif


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
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Czy na pewno usunąć to zdjęcie?')"
                                            style="
                                                padding:0;
                                                border:0;
                                                background:none;
                                                color:#b91c1c;
                                                cursor:pointer;
                                                font-size:13px;
                                            "
                                        >
                                            Usuń
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