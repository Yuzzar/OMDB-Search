@php
    $poster = (isset($movie['Poster']) && $movie['Poster'] && $movie['Poster'] !== 'N/A') ? $movie['Poster'] : null;
    $isFav = $movie['is_favorited'] ?? false;
    $imdbId = $movie['imdbID'] ?? '';
    $title = $movie['Title'] ?? '';
    $year = $movie['Year'] ?? '';
    $type = strtolower($movie['Type'] ?? 'movie');
    $detailUrl = route('movies.detail', $imdbId);
    $typeColor = $type === 'series' ? 'bg-blue-500/20 text-blue-300 border-blue-500/30'
        : ($type === 'episode' ? 'bg-purple-500/20 text-purple-300 border-purple-500/30'
            : 'bg-accent/15 text-pink-300 border-accent/30');
@endphp

<div class="movie-card bg-[#16161f] border border-white/5 rounded-xl overflow-hidden flex flex-col group">
    {{-- Poster --}}
    <a href="{{ $detailUrl }}" class="block relative overflow-hidden group/poster bg-[#12121a]"
        style="aspect-ratio:2/3;">
        {{-- Fallback Background (behind) --}}
        <div
            class="absolute inset-0 flex items-center justify-center text-gray-700 text-5xl group-hover/poster:bg-[#1a1a24] transition-colors duration-500 z-0">
            <i class="fas fa-film transition-transform duration-500 group-hover/poster:scale-110"></i>
        </div>

        @if($poster)
            <img class="movie-card-img lazy absolute inset-0 w-full h-full object-cover transition-all duration-500 group-hover/poster:scale-110 opacity-0 z-10 relative"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                data-src="{{ $poster }}" alt="{{ $title }}" onload="this.classList.remove('opacity-0');"
                onerror="this.style.display='none';">
        @endif

        {{-- Hover Overlay "View Details" --}}
        <div
            class="absolute inset-0 bg-black/60 opacity-0 group-hover/poster:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-sm z-20">
            <div
                class="translate-y-4 group-hover/poster:translate-y-0 transition-all duration-300 flex flex-col items-center gap-2">
                <div
                    class="w-10 h-10 rounded-full bg-accent text-white flex items-center justify-center shadow-lg shadow-accent/40">
                    <i class="fas fa-eye"></i>
                </div>
                <span class="text-white text-xs font-medium tracking-wider uppercase">View Details</span>
            </div>
        </div>

        {{-- Bottom gradient overlay (always visible for metadata readability if any) --}}
        <div
            class="absolute bottom-0 inset-x-0 h-1/3 bg-gradient-to-t from-[#16161f] to-transparent pointer-events-none z-10">
        </div>
    </a>

    {{-- Meta --}}
    <div class="p-3 flex flex-col gap-1.5 flex-1">
        <div class="flex items-center justify-between gap-1">
            <span
                class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-md border {{ $typeColor }}">
                {{ ucfirst($type) }}
            </span>
            <button
                class="fav-btn w-7 h-7 flex items-center justify-center rounded-lg transition-all
                           {{ $isFav ? 'bg-accent text-white shadow-md shadow-accent/30' : 'bg-white/5 text-gray-500 hover:bg-accent/15 hover:text-accent' }}"
                data-imdb="{{ $imdbId }}" data-title="{{ addslashes($title) }}" data-year="{{ $year }}"
                data-poster="{{ $poster ?? '' }}" data-type="{{ $type }}"
                title="{{ $isFav ? __('app.remove_from_favorites') : __('app.add_to_favorites') }}">
                <i class="{{ $isFav ? 'fas' : 'far' }} fa-heart text-xs"></i>
            </button>
        </div>
        <a href="{{ $detailUrl }}" class="block">
            <h3
                class="text-sm font-semibold text-gray-100 leading-snug line-clamp-2 group-hover:text-accent transition-colors">
                {{ $title }}
            </h3>
        </a>
        <span class="text-xs text-gray-600">{{ $year }}</span>
    </div>
</div>