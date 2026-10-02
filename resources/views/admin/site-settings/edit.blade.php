<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ustawienia witryny
        </h2>
    </x-slot>

    <div style="padding:32px 0;">
        <div style="max-width:900px;margin:0 auto;padding:0 24px;">

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

            @if ($errors->any())
                <div style="
                    margin-bottom:24px;
                    padding:14px 18px;
                    border-radius:6px;
                    background:#fee2e2;
                    color:#991b1b;
                ">
                    <strong>Wystąpiły błędy:</strong>

                    <ul style="margin:10px 0 0 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="
                background:#ffffff;
                border-radius:10px;
                padding:32px;
                box-shadow:0 2px 12px rgba(0,0,0,0.08);
            ">

                <form
                    method="POST"
                    action="{{ route('site-settings.update') }}"
                    enctype="multipart/form-data"
                >

                    @csrf
                    @method('PUT')

                    <div style="display:flex;flex-direction:column;gap:24px;">

                        <div style="padding:18px;border:1px solid #e5e7eb;border-radius:8px;background:#fafafa;">
                            <label for="site_under_construction" style="display:block;font-size:14px;font-weight:700;margin-bottom:8px;">
                                Widoczność witryny
                            </label>
                            <select id="site_under_construction" name="site_under_construction" style="width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;background:#fff;">
                                <option value="0" @selected((string) old('site_under_construction', $settings['site_under_construction'] ?? '0') === '0')>Witryna publiczna — WŁĄCZONA</option>
                                <option value="1" @selected((string) old('site_under_construction', $settings['site_under_construction'] ?? '0') === '1')>UNDER CONSTRUCTION — ukryj witrynę</option>
                            </select>
                            <p style="margin:8px 0 0;font-size:13px;color:#6b7280;">Po włączeniu niezalogowani odwiedzający zobaczą stronę „W budowie”, a wyszukiwarki otrzymają noindex. Po zalogowaniu nadal zobaczysz całą witrynę i CMS.</p>
                        </div>

                        <p>
                            Logo i podtytuł edytujesz w sekcji
                            <a href="{{ route('header-settings.edit') }}" style="text-decoration:underline;">Wygląd → Nagłówek</a>.
                        </p>

                        <div>
                            <label
                                for="menu_gallery"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Menu – Galerie
                            </label>

                            <input
                                id="menu_gallery"
                                type="text"
                                name="menu_gallery"
                                value="{{ old('menu_gallery', $settings['menu_gallery']) }}"
                                style="
                                    width:100%;
                                    padding:11px 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="menu_about"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Menu – O mnie
                            </label>

                            <input
                                id="menu_about"
                                type="text"
                                name="menu_about"
                                value="{{ old('menu_about', $settings['menu_about']) }}"
                                style="
                                    width:100%;
                                    padding:11px 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="menu_contact"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Menu – Kontakt
                            </label>

                            <input
                                id="menu_contact"
                                type="text"
                                name="menu_contact"
                                value="{{ old('menu_contact', $settings['menu_contact']) }}"
                                style="
                                    width:100%;
                                    padding:11px 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="background_color"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Kolor tła całej strony
                            </label>

                            <div style="display:flex;align-items:center;gap:12px;">
                                <input
                                    id="background_color"
                                    type="color"
                                    name="background_color"
                                    value="{{ old('background_color', $settings['background_color'] ?? '#ffffff') }}"
                                    style="
                                        width:64px;
                                        height:44px;
                                        padding:2px;
                                        border:1px solid #d1d5db;
                                        border-radius:6px;
                                        background:#fff;
                                        cursor:pointer;
                                    "
                                >

                                <span style="font-size:13px;color:#6b7280;">
                                    Ten kolor będzie używany jako tło wszystkich stron.
                                </span>
                            </div>
                        </div>

                        <div>
                            <label
                                for="hero_title"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Główny nagłówek
                            </label>

                            <textarea
                                id="hero_title"
                                name="hero_title"
                                rows="4"
                                style="
                                    width:100%;
                                    padding:11px 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                    resize:vertical;
                                "
                            >{{ old('hero_title', $settings['hero_title']) }}</textarea>
                        </div>

                        <div>
                            <label
                                for="hero_text"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Tekst pod nagłówkiem
                            </label>

                            <textarea
                                id="hero_text"
                                name="hero_text"
                                rows="3"
                                style="
                                    width:100%;
                                    padding:11px 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                    resize:vertical;
                                "
                            >{{ old('hero_text', $settings['hero_text']) }}</textarea>
                        </div>

                        <div>
                            <label
                                for="hero_photo_id"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Zdjęcie główne z galerii
                            </label>

                            <select
                                id="hero_photo_id"
                                name="hero_photo_id"
                                style="
                                    width:100%;
                                    padding:11px 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                "
                            >
                                <option value="">
                                    Automatycznie – pierwsze dostępne zdjęcie
                                </option>

                                @foreach ($photos->groupBy('gallery.title') as $galleryTitle => $galleryPhotos)

                                    <optgroup label="{{ $galleryTitle }}">

                                        @foreach ($galleryPhotos as $photo)

                                            <option
                                                value="{{ $photo->id }}"
                                                @selected(
                                                    (string) old(
                                                        'hero_photo_id',
                                                        $settings['hero_photo_id']
                                                    ) === (string) $photo->id
                                                )
                                            >
                                                {{ $photo->filename }}
                                            </option>

                                        @endforeach

                                    </optgroup>

                                @endforeach
                            </select>

                            <p style="
                                margin:8px 0 0;
                                font-size:13px;
                                color:#6b7280;
                            ">
                                Możesz użyć fotografii, która już znajduje się w galerii.
                            </p>
                        </div>

                        <div>
                            <label
                                for="hero_image"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Albo dodaj nowe zdjęcie z komputera
                            </label>

                            <input
                                id="hero_image"
                                type="file"
                                name="hero_image"
                                accept=".jpg,.jpeg,.png,.webp"
                                style="
                                    display:block;
                                    width:100%;
                                    padding:10px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                "
                            >

                            <p style="
                                margin:8px 0 0;
                                font-size:13px;
                                color:#6b7280;
                            ">
                                JPG, PNG lub WebP. Maksymalny rozmiar: 10 MB.
                            </p>
                        </div>

                        <div>
                            <label
                                for="galleries_title"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Tytuł sekcji galerii
                            </label>

                            <input
                                id="galleries_title"
                                type="text"
                                name="galleries_title"
                                value="{{ old('galleries_title', $settings['galleries_title']) }}"
                                style="
                                    width:100%;
                                    padding:11px 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="footer_text"
                                style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;"
                            >
                                Stopka
                            </label>

                            <input
                                id="footer_text"
                                type="text"
                                name="footer_text"
                                value="{{ old('footer_text', $settings['footer_text']) }}"
                                style="
                                    width:100%;
                                    padding:11px 12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    background:#fff;
                                "
                            >
                        </div>

                    </div>

                    <div style="margin-top:32px;padding-bottom:20px;">

                        <button
                            type="submit"
                            style="
                                display:block;
                                background:#171717;
                                color:#ffffff;
                                border:none;
                                padding:14px 28px;
                                border-radius:6px;
                                font-size:14px;
                                font-weight:600;
                                cursor:pointer;
                                min-width:160px;
                            "
                        >
                            Zapisz zmiany
                        </button>

                    </div>

                </form>

            </div>
        </div>

        <div style="max-width:900px;margin:28px auto 0;padding:0 24px;">
            <div style="background:#fff;border-radius:10px;padding:32px;box-shadow:0 2px 12px rgba(0,0,0,.08);">
                <h2 style="margin:0 0 8px;font-size:20px;font-weight:700;">Konto administratora</h2>
                <p style="margin:0 0 24px;color:#6b7280;line-height:1.6;">
                    Ten adres e-mail służy do logowania i to na niego zostanie wysłany link „Nie pamiętam hasła”.
                    Dane SMTP nadawcy pozostają w konfiguracji serwera, a nie w CMS.
                </p>

                @if (session('status') === 'profile-updated')
                    <div style="margin-bottom:20px;padding:12px 14px;border-radius:6px;background:#dcfce7;color:#166534;">
                        Adres e-mail administratora został zapisany.
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" style="margin-bottom:32px;">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <label for="admin_email" style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;">
                        Adres e-mail do logowania i resetu hasła
                    </label>
                    <input
                        id="admin_email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="username"
                        style="width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;background:#fff;"
                    >
                    @error('email')
                        <p style="margin:8px 0 0;color:#991b1b;">{{ $message }}</p>
                    @enderror
                    <button type="submit" style="margin-top:16px;background:#171717;color:#fff;border:0;padding:12px 20px;border-radius:6px;cursor:pointer;">
                        Zapisz adres e-mail
                    </button>
                </form>

                <hr style="border:0;border-top:1px solid #e5e7eb;margin:0 0 28px;">

                <h3 style="margin:0 0 8px;font-size:17px;font-weight:700;">Zmiana hasła</h3>
                <p style="margin:0 0 18px;color:#6b7280;">Zmiana wymaga podania aktualnego hasła.</p>

                @if (session('status') === 'password-updated')
                    <div style="margin-bottom:20px;padding:12px 14px;border-radius:6px;background:#dcfce7;color:#166534;">
                        Hasło zostało zmienione.
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" style="display:flex;flex-direction:column;gap:14px;">
                    @csrf
                    @method('PUT')

                    <label>
                        <span style="display:block;font-size:14px;font-weight:600;margin-bottom:6px;">Aktualne hasło</span>
                        <input type="password" name="current_password" autocomplete="current-password"
                            style="width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;">
                        @foreach($errors->updatePassword->get('current_password') as $message)
                            <span style="display:block;margin-top:6px;color:#991b1b;">{{ $message }}</span>
                        @endforeach
                    </label>

                    <label>
                        <span style="display:block;font-size:14px;font-weight:600;margin-bottom:6px;">Nowe hasło</span>
                        <input type="password" name="password" autocomplete="new-password"
                            style="width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;">
                        @foreach($errors->updatePassword->get('password') as $message)
                            <span style="display:block;margin-top:6px;color:#991b1b;">{{ $message }}</span>
                        @endforeach
                    </label>

                    <label>
                        <span style="display:block;font-size:14px;font-weight:600;margin-bottom:6px;">Powtórz nowe hasło</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            style="width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;">
                    </label>

                    <div>
                        <button type="submit" style="background:#171717;color:#fff;border:0;padding:12px 20px;border-radius:6px;cursor:pointer;">
                            Zmień hasło
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>