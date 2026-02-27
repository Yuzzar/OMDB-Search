@extends('layouts.app')

@section('title', __('app.favorites'))

@section('content')
<div class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6">

    {{-- Heading --}}
    <div class="flex items-center gap-3 mb-6">
        <span class="w-1 h-6 bg-gradient-to-b from-[#e94560] to-[#c73652] rounded-full"></span>
        <h2 class="text-lg font-bold text-gray-100">{{ __('app.your_favorites') }}</h2>
        @if(isset($favorites) && count($favorites) > 0)
            <span class="text-xs font-semibold bg-[#e94560]/15 text-[#e94560] border border-[#e94560]/25 px-2.5 py-0.5 rounded-full ml-1">
                {{ count($favorites) }}
            </span>
        @endif
    </div>

    {{-- Success Flash --}}
    @if(session('success'))
        <div class="flex items-center gap-2 bg-green-950/60 border border-green-500/30 text-green-300 rounded-xl p-3 mb-6 text-sm">
            <i class="fas fa-check-circle shrink-0"></i>{{ session('success') }}
        </div>
    @endif

    {{-- Empty --}}
    @if(!isset($favorites) || count($favorites) === 0)
        <div class="flex flex-col items-center py-20 text-center">
            <div class="w-20 h-20 bg-[#e94560]/10 text-[#e94560] text-4xl rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-heart-crack"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-200 mb-2">{{ __('app.no_favorites') }}</h3>
            <p class="text-gray-500 max-w-xs mb-6">{{ __('app.search_prompt') }}</p>
            <a href="{{ route('movies.index') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-[#e94560] to-[#c73652] text-white font-semibold rounded-xl px-5 py-2.5 text-sm hover:brightness-110 transition-all">
                <i class="fas fa-film"></i>{{ __('app.movies') }}
            </a>
        </div>

    {{-- Grid --}}
    @else
        <div id="fav-grid"
             class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($favorites as $fav)
            @php
                $movie = [
                    'imdbID'       => $fav->imdb_id,
                    'Title'        => $fav->title,
                    'Year'         => $fav->year,
                    'Poster'       => $fav->poster,
                    'Type'         => $fav->type,
                    'is_favorited' => true,
                ];
            @endphp
            @include('partials.movie-card', ['movie' => $movie])
            @endforeach
        </div>
    @endif

</div>
@endsection

@section('scripts')
<script>
(function () {
    var csrf      = '{{ csrf_token() }}';
    var unfavBase = '{{ url("/favorites") }}';

    $(document).on('click', '.fav-btn', function (e) {
        e.preventDefault();
        var btn    = $(this);
        var imdbId = btn.data('imdb');
        var card   = btn.closest('.movie-card');

        $.ajax({
            url:  unfavBase + '/' + imdbId,
            type: 'POST',
            data: { _method: 'DELETE', _token: csrf },
            success: function (r) {
                card.fadeOut(300, function () {
                    $(this).remove();
                    if ($('#fav-grid .movie-card').length === 0) location.reload();
                });
                showToast(r.message || '{{ __("app.removed_from_favorites") }}', 'success');
            },
            error: function () { showToast('Error', 'error'); }
        });
    });

    initLazyLoad();
})();
</script>
@endsection
