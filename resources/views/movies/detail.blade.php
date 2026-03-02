@extends('layouts.app')

@section('title', isset($movie['Title']) ? $movie['Title'] : __('app.movies'))

@section('content')
<div class="max-w-screen-lg mx-auto px-4 sm:px-6 py-8">

    {{-- Back --}}
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('movies.index') }}"
       class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-gray-200 transition-colors mb-8">
        <i class="fas fa-arrow-left"></i> {{ __('app.back') }}
    </a>

    @if(!isset($movie) || !$movie)
        <div class="flex flex-col items-center py-20 text-center">
            <div class="w-20 h-20 bg-[#e94560]/10 text-[#e94560] text-4xl rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-film"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-200">{{ __('app.movie_not_found') }}</h3>
        </div>

    @else
    @php
        $poster  = (isset($movie['Poster']) && $movie['Poster'] !== 'N/A') ? $movie['Poster'] : null;
        $isFav   = $movie['is_favorited'] ?? false;
        $imdbId  = $movie['imdbID'] ?? '';
        $title   = $movie['Title'] ?? '';
        $year    = $movie['Year'] ?? '';
        $type    = strtolower($movie['Type'] ?? 'movie');
        $rating  = $movie['imdbRating'] ?? 'N/A';
        $votes   = $movie['imdbVotes'] ?? 'N/A';
        $typeColor = $type === 'series'  ? 'bg-blue-500/20 text-blue-300 border-blue-500/30'
                   : ($type === 'episode' ? 'bg-purple-500/20 text-purple-300 border-purple-500/30'
                   : 'bg-pink-500/15 text-pink-300 border-pink-500/30');
    @endphp

    {{-- Hero Section --}}
    <div class="detail-hero flex gap-8 items-start mb-8">

        {{-- Poster --}}
        <div class="detail-poster-wrap shrink-0 w-56 sm:w-64 md:w-72">
            @if($poster)
                <img src="{{ $poster }}" alt="{{ $title }}"
                     class="w-full rounded-2xl shadow-2xl shadow-black/60">
            @else
                <div class="w-full rounded-2xl bg-[#12121a] border border-white/5 flex items-center justify-center text-gray-700 text-6xl"
                     style="aspect-ratio:2/3;">
                    <i class="fas fa-film"></i>
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="flex-1 min-w-0">
            {{-- Badges --}}
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-lg border {{ $typeColor }}">
                    {{ ucfirst($type) }}
                </span>
                @if(isset($movie['Rated']) && $movie['Rated'] !== 'N/A')
                    <span class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-lg border border-white/10 text-gray-400">
                        {{ $movie['Rated'] }}
                    </span>
                @endif
            </div>

            {{-- Title --}}
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white leading-tight tracking-tight mb-1">{{ $title }}</h1>

            {{-- Year / Runtime --}}
            <p class="text-gray-500 text-sm mb-4">
                {{ $year }}
                @if(isset($movie['Runtime']) && $movie['Runtime'] !== 'N/A')
                    <span class="mx-1">·</span>{{ $movie['Runtime'] }}
                @endif
            </p>

            {{-- IMDb Rating --}}
            @if($rating !== 'N/A')
            <div class="inline-flex items-center gap-2 bg-yellow-500/10 border border-yellow-500/25 rounded-xl px-4 py-2 mb-5">
                <i class="fas fa-star text-yellow-400"></i>
                <strong class="text-white text-base">{{ $rating }}</strong>
                <span class="text-gray-500 text-sm">/10</span>
                @if($votes !== 'N/A')
                    <span class="text-gray-600 text-xs ml-1">({{ $votes }} votes)</span>
                @endif
            </div>
            @endif

            {{-- Fav Button --}}
            <div class="mb-6">
                <button id="fav-btn-detail"
                        class="{{ $isFav
                            ? 'bg-[#e94560] border-[#e94560] text-white'
                            : 'bg-[#e94560]/10 border-[#e94560]/30 text-[#e94560] hover:bg-[#e94560]/20' }}
                               flex items-center gap-2 border rounded-xl px-5 py-2.5 text-sm font-semibold transition-all"
                        data-imdb="{{ $imdbId }}"
                        data-title="{{ addslashes($title) }}"
                        data-year="{{ $year }}"
                        data-poster="{{ $poster ?? '' }}"
                        data-type="{{ $type }}">
                    <i class="{{ $isFav ? 'fas' : 'far' }} fa-heart"></i>
                    <span id="fav-label">{{ $isFav ? __('app.remove_from_favorites') : __('app.add_to_favorites') }}</span>
                </button>
            </div>

            {{-- Plot --}}
            @if(isset($movie['Plot']) && $movie['Plot'] !== 'N/A')
                <p class="text-gray-400 text-sm leading-relaxed mb-6 max-w-xl">{{ $movie['Plot'] }}</p>
            @endif

            {{-- Metadata --}}
            <div class="flex flex-col gap-3">
                @foreach([
                    'Genre'    => __('app.genre'),
                    'Director' => __('app.director'),
                    'Writer'   => __('app.writer'),
                    'Actors'   => __('app.actors'),
                    'Language' => __('app.film_language'),
                    'Country'  => __('app.country'),
                    'Released' => __('app.released'),
                    'Awards'   => __('app.awards'),
                    'BoxOffice'=> __('app.box_office'),
                ] as $key => $label)
                    @if(isset($movie[$key]) && $movie[$key] !== 'N/A' && $movie[$key] !== '')
                    <div class="flex gap-3 text-sm">
                        <span class="shrink-0 w-24 text-[10px] font-bold text-gray-500 uppercase tracking-wider pt-0.5">{{ $label }}</span>
                        <span class="text-gray-300">{{ $movie[$key] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    const csrf      = '{{ csrf_token() }}';
    const favUrl    = '{{ route("favorites.store") }}';
    const unfavBase = '{{ url("/favorites") }}';
    const i18n = {
        addToFavorites:     '{{ __("app.add_to_favorites") }}',
        removeFromFavorites:'{{ __("app.remove_from_favorites") }}',
        added:              '{{ __("app.added_to_favorites") }}',
        removed:            '{{ __("app.removed_from_favorites") }}',
    };

    $('#fav-btn-detail').on('click', function () {
        const $btn   = $(this);
        const imdbId = $btn.data('imdb');
        const isFav  = $btn.hasClass('bg-[#e94560]') || $btn.hasClass('is-favorited');

        $btn.prop('disabled', true);

        if (isFav) {
            $.ajax({
                url:  unfavBase + '/' + imdbId,
                type: 'POST',
                data: { _method: 'DELETE', _token: csrf },
            })
                .done(function (r) {
                    $btn.removeClass('bg-[#e94560] border-[#e94560] text-white is-favorited')
                        .addClass('bg-[#e94560]/10 border-[#e94560]/30 text-[#e94560] hover:bg-[#e94560]/20');
                    $btn.find('i').removeClass('fas').addClass('far');
                    $('#fav-label').text(i18n.addToFavorites);
                    showToast(r.message || i18n.removed, 'success');
                })
                .fail(function () { showToast('Error', 'error'); })
                .always(function () { $btn.prop('disabled', false); });
        } else {
            $.ajax({
                url:  favUrl,
                type: 'POST',
                data: {
                    _token:  csrf,
                    imdb_id: imdbId,
                    title:   $btn.data('title'),
                    year:    $btn.data('year'),
                    poster:  $btn.data('poster'),
                    type:    $btn.data('type'),
                },
            })
                .done(function (r) {
                    $btn.addClass('bg-[#e94560] border-[#e94560] text-white is-favorited')
                        .removeClass('bg-[#e94560]/10 border-[#e94560]/30 text-[#e94560] hover:bg-[#e94560]/20');
                    $btn.find('i').removeClass('far').addClass('fas');
                    $('#fav-label').text(i18n.removeFromFavorites);
                    showToast(r.message || i18n.added, 'success');
                })
                .fail(function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Error';
                    showToast(msg, 'error');
                })
                .always(function () { $btn.prop('disabled', false); });
        }
    });
})();
</script>
@endsection
