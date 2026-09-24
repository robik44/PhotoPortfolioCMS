<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj galerię
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form method="POST" action="{{ route('galleries.store') }}">
                        @csrf

                        <div class="mb-6">
                            <label for="title" class="block font-medium text-sm text-gray-700">
                                Tytuł galerii
                            </label>

                            <input
                                id="title"
                                name="title"
                                type="text"
                                value="{{ old('title') }}"
                                required
                                autofocus
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
                            >{{ old('description') }}</textarea>

                            @error('description')
                                <p style="color:#dc2626; margin-top:5px;">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <label style="display:block;margin:16px 0;">Slug galerii
                            <input name="slug" value="{{ old('slug', '') }}" style="display:block;width:100%;padding:10px;border:1px solid #ddd;">
                        </label>
                        @include('admin.seo.fields', ['entity' => null])

                        @include('admin.galleries.typography')

                        <div>
                            <button
                                type="submit"
                                style="padding:10px 16px; background:#2563eb; color:white; border:0; border-radius:6px; cursor:pointer;"
                            >
                                Zapisz galerię
                            </button>

                            <a
                                href="{{ route('galleries.index') }}"
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
