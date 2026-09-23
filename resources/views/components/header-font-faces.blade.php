@if ($fonts)
    <style>
        @foreach ($fonts as $id => $font)
            @font-face {
                font-family: '{{ $id }}';
                src: url('{{ asset('storage/' . $font['path']) }}') format('{{ $font['format'] }}');
                font-display: swap;
            }
        @endforeach
    </style>
@endif
