<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edytuj galerię
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div style="padding:14px 17px;background:#f3f5f7;border:1px solid #d4dae3;border-radius:7px;margin-bottom:22px;">
                        <strong style="display:block;margin-bottom:6px;">Wizualny builder galerii</strong>
                        <p style="font-size:13px;color:#555;margin-bottom:10px;">Projektuj wygląd tytułu, opisu i fotografii bezpośrednio na podglądzie. Możesz użyć wspólnego szablonu dla wszystkich galerii lub zmieniać tylko tę jedną.</p>
                        <a href="{{ route('galleries.builder', ['gallery' => $gallery->id, 'scope' => 'gallery']) }}" style="display:inline-block;background:#171717;color:#fff;padding:11px 15px;text-decoration:none;border-radius:5px;">Otwórz builder galerii →</a>
                    </div>

                    <form method="POST" action="{{ route('galleries.update', $gallery) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-6">
                            <label for="gallery_collection_id" class="block font-medium text-sm text-gray-700">Galeria nadrzędna</label>
                            <select id="gallery_collection_id" name="gallery_collection_id" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
                                @foreach($collections as $module)
                                    <option value="{{ $module->id }}" @selected((int) old('gallery_collection_id', $gallery->gallery_collection_id) === (int) $module->id)>{{ $module->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6">
                            <label for="title" class="block font-medium text-sm text-gray-700">
                                Tytuł galerii
                            </label>

                            <input
                                id="title"
                                name="title"
                                type="text"
                                value="{{ old('title', $gallery->title) }}"
                                required
                                style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:6px;"
                            >

                            @error('title')
                                <p style="color:#dc2626; margin-top:5px;">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block font-medium text-sm text-gray-700">
                                Opis galerii
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:6px;"
                            >{{ old('description', $gallery->description) }}</textarea>

                            @error('description')
                                <p style="color:#dc2626; margin-top:5px;">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <label style="display:block;margin:16px 0;">Slug galerii
                            <input name="slug" value="{{ old('slug', $gallery->slug) }}" style="display:block;width:100%;padding:10px;border:1px solid #ddd;">
                        </label>
                        @include('admin.seo.fields', ['entity' => $gallery])

                        @include('admin.galleries.typography')
                        @include('admin.galleries.back-link')

                        <div>
                            <button
                                type="submit"
                                style="padding:10px 16px; background:#2563eb; color:white; border:0; border-radius:6px; cursor:pointer;"
                            >
                                Zapisz zmiany
                            </button>

                            <a
                                href="{{ route('galleries.index', ['collection' => $gallery->gallery_collection_id]) }}"
                                style="margin-left:15px; color:#2563eb; text-decoration:none;"
                            >
                                Anuluj
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
