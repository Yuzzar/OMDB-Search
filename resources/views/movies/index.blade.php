@extends('layouts.app')

@section('title', __('app.movies'))

@section('content')
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6">

        {{-- ════════════════════════════════════════════════
        SEARCH PANEL
        ════════════════════════════════════════════════ --}}
        <div class="relative bg-[#12121a] border border-white/5 rounded-2xl p-4 sm:p-5 mb-8 shadow-xl">

            {{-- Progress bar (top of card) --}}
            <div id="search-progress" class="search-progress-bar"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1fr_180px_160px] gap-3 items-end">

                {{-- ── Text search ── --}}
                <div>
                    <label class="filter-label">SEARCH MOVIES</label>
                    <div class="relative">
                        <span id="search-icon" class="input-icon-left pointer-events-none">
                            <i class="fas fa-search"></i>
                        </span>
                        <input id="q-input" type="text" value="{{ old('query', $query) }}"
                            placeholder="{{ __('app.search_placeholder') }}" autocomplete="off" spellcheck="false"
                            class="filter-input pl-10 pr-9">
                        <button id="q-clear"
                            class="input-icon-right text-gray-600 hover:text-gray-300 transition-colors hidden"
                            title="Clear">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                    <p id="q-hint" class="hidden mt-1.5 text-xs flex items-center gap-1">
                        <i class="fas fa-info-circle"></i>
                        <span id="q-hint-text">{{ __('app.min_chars') }}</span>
                    </p>
                    <p id="q-tip" class="hidden mt-1 text-xs text-blue-400/80 flex items-center gap-1">
                        <i class="fas fa-lightbulb"></i>
                        <span>{{ __('app.search_word_tip') }}</span>
                    </p>
                </div>

                {{-- ── Custom Type picker ── --}}
                <div class="relative" id="type-wrapper">
                    <label class="filter-label">TYPE</label>
                    <button type="button" id="type-btn"
                        class="filter-input w-full text-left flex items-center justify-between gap-2 cursor-pointer"
                        aria-haspopup="listbox" aria-expanded="false">
                        <span id="type-label" class="truncate">
                            @if($type === 'movie') {{ __('app.movie') }}
                            @elseif($type === 'series') {{ __('app.series') }}
                            @else {{ __('app.all_types') }}
                            @endif
                        </span>
                        <i class="fas fa-chevron-down text-xs text-gray-500 transition-transform duration-200"
                            id="type-chevron"></i>
                    </button>
                    {{-- Hidden value --}}
                    <input type="hidden" id="type-val" value="{{ $type }}">
                    {{-- Dropdown panel --}}
                    <ul id="type-menu" class="custom-dropdown hidden" role="listbox">
                        <li class="dropdown-item {{ !$type ? 'active' : '' }}" data-val="" role="option">
                            {{ __('app.all_types') }}</li>
                        <li class="dropdown-item {{ $type === 'movie' ? 'active' : '' }}" data-val="movie" role="option">
                            <span class="type-dot bg-pink-400"></span>{{ __('app.movie') }}
                        </li>
                        <li class="dropdown-item {{ $type === 'series' ? 'active' : '' }}" data-val="series" role="option">
                            <span class="type-dot bg-blue-400"></span>{{ __('app.series') }}
                        </li>
                    </ul>
                </div>

                {{-- ── Custom Year picker ── --}}
                <div class="relative" id="year-wrapper">
                    <label class="filter-label">YEAR</label>
                    <button type="button" id="year-btn"
                        class="filter-input w-full text-left flex items-center justify-between gap-2 cursor-pointer"
                        aria-haspopup="listbox" aria-expanded="false">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-gray-500 text-sm"></i>
                            <span id="year-label">{{ $year ?: __('app.any_year') }}</span>
                        </span>
                        <i class="fas fa-chevron-down text-xs text-gray-500 transition-transform duration-200"
                            id="year-chevron"></i>
                    </button>
                    <input type="hidden" id="year-val" value="{{ $year }}">
                    {{-- Year panel --}}
                    <div id="year-menu" class="custom-dropdown hidden year-picker-panel" style="min-width:240px;">
                        {{-- Manual input row --}}
                        <div class="px-3 pt-3 pb-2 border-b border-white/5">
                            <div class="relative">
                                <input id="year-manual" type="text" maxlength="4" value="{{ $year }}"
                                    placeholder="{{ __('app.year_placeholder') }}" class="filter-input text-sm w-full py-2">
                            </div>
                        </div>
                        {{-- Quick picks --}}
                        <div class="px-2 pt-2 pb-2">
                            <p class="text-[10px] text-gray-600 uppercase tracking-wider px-1 mb-1.5">
                                {{ __('app.quick_pick') }}</p>
                            <div id="year-quick" class="grid grid-cols-4 gap-1"></div>
                        </div>
                        {{-- Clear --}}
                        <div class="px-3 pb-3 border-t border-white/5 pt-2">
                            <button id="year-clear-btn"
                                class="w-full text-xs text-gray-500 hover:text-[#e94560] transition-colors py-1">
                                {{ __('app.clear_year') }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Filter chips --}}
            <div id="filter-chips" class="flex flex-wrap gap-2 mt-3"></div>
        </div>

        {{-- ════════════════════════════════════════════════
        RESULTS AREA
        ════════════════════════════════════════════════ --}}

        {{-- ── Landing: Movies + Series sections (no active filters) ── --}}
        <div id="sections-landing" class="{{ $isDefault ? '' : 'hidden' }}">

            {{-- Movies section --}}
            <section class="mb-12" id="landing-movies">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <span
                            class="w-1 h-6 bg-gradient-to-b from-[#e94560] to-[#c73652] rounded-full flex-shrink-0"></span>
                        <h2 class="text-lg font-bold text-gray-100">{{ __('app.movies_section') }}</h2>
                        @if($moviesYear)
                            <span
                                class="text-xs font-semibold bg-white/5 text-gray-400 border border-white/10 px-2.5 py-0.5 rounded-full">
                                {{ $moviesYear }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('movies.index') }}?type=movie"
                        class="text-sm text-[#e94560] hover:text-[#ff7c98] transition-colors font-medium flex items-center gap-1.5">
                        {{ __('app.view_all') }} <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
                @if(!empty($moviesSection))
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        @foreach($moviesSection as $movie)
                            @include('partials.movie-card', ['movie' => $movie])
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-600 py-10 text-center">{{ __('app.no_movies_found') }}</p>
                @endif
            </section>

            {{-- Series section --}}
            <section id="landing-series">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <span class="w-1 h-6 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full flex-shrink-0"></span>
                        <h2 class="text-lg font-bold text-gray-100">{{ __('app.series_section') }}</h2>
                        @if($seriesYear)
                            <span
                                class="text-xs font-semibold bg-white/5 text-gray-400 border border-white/10 px-2.5 py-0.5 rounded-full">
                                {{ $seriesYear }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('movies.index') }}?type=series"
                        class="text-sm text-blue-400 hover:text-blue-300 transition-colors font-medium flex items-center gap-1.5">
                        {{ __('app.view_all') }} <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
                @if(!empty($seriesSection))
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        @foreach($seriesSection as $movie)
                            @include('partials.movie-card', ['movie' => $movie])
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-600 py-10 text-center">{{ __('app.no_movies_found') }}</p>
                @endif
            </section>

        </div>

        {{-- ── Search / filter results grid (JS-controlled) ── --}}
        <div id="results-area" class="{{ $isDefault ? 'hidden' : '' }}">

            {{-- Section heading --}}
            <div id="results-header"
                class="flex items-center justify-between mb-6 {{ (isset($error) && $error) || empty($movies) ? 'hidden' : '' }}">

                <div class="flex items-center gap-3">
                    <span class="w-1 h-6 bg-gradient-to-b from-[#e94560] to-[#c73652] rounded-full flex-shrink-0"></span>
                    <h2 id="results-title" class="text-lg font-bold text-gray-100">
                        @if($type === 'movie') {{ __('app.movies_section') }}
                        @elseif($type === 'series') {{ __('app.series_section') }}
                        @else {{ __('app.results') }}
                        @endif
                    </h2>
                    <span id="year-badge" class="text-xs font-semibold bg-white/5 text-gray-400 border border-white/10 px-2.5 py-0.5 rounded-full
                                 {{ $defaultYear ? '' : 'hidden' }}">
                        {{ $defaultYear ?? '' }}
                    </span>
                    <span id="total-badge" class="text-xs font-semibold bg-[#e94560]/15 text-[#e94560] border border-[#e94560]/25 px-2.5 py-0.5 rounded-full
                                 {{ (isset($total) && $total > 0) ? '' : 'hidden' }}">
                        {{ isset($total) ? number_format($total) : '' }}
                    </span>
                </div>

                {{-- Back to Home Button --}}
                <a href="{{ route('movies.index') }}"
                    class="text-sm bg-white/5 hover:bg-white/10 text-gray-300 px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-home"></i> {{ __('app.back_to_home', ['default' => 'Back to Home']) }}
                </a>
            </div>

            {{-- Error state --}}
            <div id="error-state"
                class="{{ (isset($error) && $error) ? '' : 'hidden' }} flex flex-col items-center py-20 text-center">
                <div
                    class="w-20 h-20 bg-yellow-500/10 text-yellow-400 text-4xl rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-200 mb-2">Oops!</h3>
                <p id="error-msg" class="text-gray-500 max-w-xs">{{ $error ?? '' }}</p>
            </div>

            {{-- Empty state --}}
            <div id="empty-state"
                class="{{ (!isset($error) || !$error) && !$isDefault && empty($movies) ? '' : 'hidden' }} flex flex-col items-center py-20 text-center">
                <div
                    class="w-20 h-20 bg-[#e94560]/10 text-[#e94560] text-4xl rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-film"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-200 mb-2">{{ __('app.no_movies_found') }}</h3>
                <p id="empty-msg" class="text-gray-500 max-w-xs">{{ __('app.try_different_keyword') }}</p>
                <p id="empty-word-hint" class="hidden mt-2 text-xs text-blue-400/70 max-w-xs">
                    {{ __('app.no_results_hint') }}</p>
            </div>

            {{-- Skeleton grid (shown during AJAX transition) --}}
            <div id="skeleton-grid"
                class="hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 mb-6">
                @for($s = 0; $s < 12; $s++)
                    <div class="bg-[#16161f] border border-white/5 rounded-xl overflow-hidden">
                        <div class="bg-white/[0.04] animate-pulse" style="aspect-ratio:2/3;"></div>
                        <div class="p-3 space-y-2">
                            <div class="h-3 bg-white/[0.04] rounded w-1/3 animate-pulse"></div>
                            <div class="h-3.5 bg-white/[0.04] rounded w-full animate-pulse" style="animation-delay:.1s"></div>
                            <div class="h-3.5 bg-white/[0.04] rounded w-2/3 animate-pulse" style="animation-delay:.15s"></div>
                            <div class="h-3 bg-white/[0.04] rounded w-1/4 animate-pulse" style="animation-delay:.2s"></div>
                        </div>
                    </div>
                @endfor
            </div>

            {{-- Results grid --}}
            <div id="movies-grid"
                class="{{ !$isDefault && !empty($movies) ? '' : 'hidden' }} grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @foreach($movies as $movie)
                    @include('partials.movie-card', ['movie' => $movie])
                @endforeach
            </div>

            {{-- Load-more spinner --}}
            <div id="loading-spinner" class="hidden py-8">
                <div class="spinner-dots"><span></span><span></span><span></span></div>
            </div>

            {{-- Scroll sentinel --}}
            <div id="scroll-sentinel"
                class="{{ (!$isDefault && isset($totalPages) && $totalPages > 1) ? '' : 'hidden' }} h-1 mt-10"></div>
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        /**
         * Server-side config passed from Blade to the external JS file.
         * Keeps all dynamic/Blade-specific values here; logic lives in public/js/movies-list.js.
         */
        window.MovieConfig = {
            csrfToken: '{{ csrf_token() }}',
            favUrl: '{{ route("favorites.store") }}',
            unfavUrl: '{{ url("/favorites") }}',
            loadUrl: '{{ route("movies.load-more") }}',
            homeUrl: '{{ route("movies.index") }}',
            maxYear: {{ date('Y') + 1 }},
            state: {
                page: 1,
                totalPages: {{ $isDefault ? 0 : (isset($totalPages) ? (int) $totalPages : 0) }},
                defYear: @json($defaultYear ?? null),
                defTerm: @json($defaultTerm ?? null),
                isDefault: {{ $isDefault ? 'true' : 'false' }},
                loading: false,
                xhr: null,
            },
            i18n: {
                moviesSection: '{{ __("app.movies_section") }}',
                seriesSection: '{{ __("app.series_section") }}',
                results: '{{ __("app.results") }}',
                anyYear: '{{ __("app.any_year") }}',
                allTypes: '{{ __("app.all_types") }}',
            },
        };
    </script>
    <script src="{{ asset('js/movies-list.js') }}"></script>
@endsection