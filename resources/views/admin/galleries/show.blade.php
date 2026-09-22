@extends('layouts.app')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>{{ $gallery->title }}</h1>

            @if($gallery->description)
                <p class="description">{{ $gallery->description }}</p>
            @endif
        </div>

        <div class="actions">
            <a href="{{ route('galleries.index') }}" class="btn btn-secondary">
                Powrót do galerii
            </a>

            <a href="{{ route('photos.create', ['gallery_id' => $gallery->id]) }}" class="btn btn-primary">
                Dodaj zdjęcia
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert success">
            {{ session('success') }}
        </div>
    @endif

    @if($gallery->photos->count())
        <div id="save-message" class="alert success" style="display:none;">
            Kolejność zdjęć została zapisana.
        </div>

        <div
            id="photo-grid"
            class="photo-grid"
        >
            @foreach($gallery->photos as $photo)
                <div
                    class="photo-card"
                    draggable="true"
                    data-id="{{ $photo->id }}"
                >
                    <div class="drag-handle">
                        ⋮⋮
                    </div>

                    <img
                        src="{{ asset('storage/photos/' . basename($photo->filename)) }}"
                        alt="{{ $photo->alt ?: $photo->title ?: 'Zdjęcie' }}"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <div class="image-error">
                        Nie udało się wczytać zdjęcia.
                    </div>

                    <div class="photo-info">
                        @if($photo->is_cover)
                            <span class="cover-badge">Okładka</span>
                        @else
                            <form method="POST" action="{{ route('photos.make-cover', $photo) }}">
                                @csrf
                                <button type="submit" class="cover-button">
                                    Ustaw jako miniaturkę
                                </button>
                            </form>
                        @endif

                        @if($photo->title)
                            <strong>{{ $photo->title }}</strong>
                        @endif

                        <small>{{ basename($photo->filename) }}</small>

                        <form method="POST" action="{{ route('photos.destroy', $photo) }}"
                              onsubmit="return confirm('Czy na pewno usunąć to zdjęcie?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="delete-button">
                                Usuń zdjęcie
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            Ta galeria nie zawiera jeszcze żadnych zdjęć.
        </div>
    @endif
</div>

<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 30px;
    }

    .page-header h1 {
        margin: 0 0 8px;
    }

    .description {
        color: #777;
        margin: 0;
    }

    .actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-block;
        padding: 10px 16px;
        border-radius: 6px;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-primary {
        background: #111;
        color: white;
    }

    .btn-secondary {
        background: #eee;
        color: #111;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .success {
        background: #e7f5e9;
        color: #246b2c;
    }

    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    .photo-card {
        position: relative;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        cursor: grab;
        transition: opacity .2s, transform .2s, box-shadow .2s;
    }

    .photo-card.dragging {
        opacity: .35;
    }

    .photo-card.drag-over {
        transform: scale(1.03);
        box-shadow: 0 0 0 3px #111;
    }

    .photo-card img {
        display: block;
        width: 100%;
        height: 220px;
        object-fit: cover;
    }

    .image-error {
        display: none;
        height: 220px;
        align-items: center;
        justify-content: center;
        color: #999;
        background: #f5f5f5;
        text-align: center;
        padding: 20px;
    }

    .drag-handle {
        position: absolute;
        z-index: 2;
        top: 8px;
        right: 8px;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.9);
        border-radius: 6px;
        font-size: 20px;
        letter-spacing: -5px;
    }

    .photo-info {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 12px;
    }

    .photo-info small {
        color: #888;
        word-break: break-all;
    }

    .cover-button {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #111;
        border-radius: 5px;
        background: white;
        color: #111;
        cursor: pointer;
        font-size: 12px;
    }

    .cover-button:hover {
        background: #111;
        color: white;
    }

    .delete-button {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #b00020;
        border-radius: 5px;
        background: white;
        color: #b00020;
        cursor: pointer;
        font-size: 12px;
    }

    .delete-button:hover {
        background: #b00020;
        color: white;
    }

    .cover-badge {
        display: inline-block;
        width: fit-content;
        padding: 4px 8px;
        border-radius: 4px;
        background: #111;
        color: white;
        font-size: 11px;
        text-transform: uppercase;
    }

    .empty-state {
        padding: 50px 20px;
        text-align: center;
        color: #777;
        border: 1px dashed #ccc;
        border-radius: 8px;
    }

    @media (max-width: 700px) {
        .page-header {
            flex-direction: column;
        }

        .photo-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .photo-card img {
            height: 150px;
        }

        .image-error {
            height: 150px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('photo-grid');

    if (!grid) {
        return;
    }

    let dragged = null;

    grid.querySelectorAll('.photo-card').forEach(function (card) {
        card.addEventListener('dragstart', function () {
            dragged = card;
            card.classList.add('dragging');
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('dragging');

            grid.querySelectorAll('.photo-card').forEach(function (item) {
                item.classList.remove('drag-over');
            });

            dragged = null;
            saveOrder();
        });

        card.addEventListener('dragover', function (event) {
            event.preventDefault();

            if (!dragged || dragged === card) {
                return;
            }

            card.classList.add('drag-over');

            const rect = card.getBoundingClientRect();
            const insertAfter = event.clientY > rect.top + rect.height / 2;

            if (insertAfter) {
                card.after(dragged);
            } else {
                card.before(dragged);
            }
        });

        card.addEventListener('dragleave', function () {
            card.classList.remove('drag-over');
        });

        card.addEventListener('drop', function (event) {
            event.preventDefault();
            card.classList.remove('drag-over');
        });
    });

    function saveOrder() {
        const photos = Array.from(
            grid.querySelectorAll('.photo-card')
        ).map(function (card) {
            return Number(card.dataset.id);
        });

        fetch('{{ route('photos.reorder') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                photos: photos
            })
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Błąd zapisu');
            }

            return response.json();
        })
        .then(function () {
            const message = document.getElementById('save-message');

            if (message) {
                message.style.display = 'block';

                setTimeout(function () {
                    message.style.display = 'none';
                }, 2000);
            }
        })
        .catch(function (error) {
            console.error(error);
            alert('Nie udało się zapisać kolejności zdjęć.');
        });
    }
});
</script>
@endsection