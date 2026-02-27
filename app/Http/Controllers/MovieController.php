<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\FavoriteRepositoryInterface;
use App\Services\OmdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    /** @var OmdbService */
    protected $omdbService;

    /** @var FavoriteRepositoryInterface */
    protected $favoriteRepository;

    public function __construct(OmdbService $omdbService, FavoriteRepositoryInterface $favoriteRepository)
    {
        $this->omdbService = $omdbService;
        $this->favoriteRepository = $favoriteRepository;
    }

    /**
     * Rotating keywords for landing-page discovery.
     * Specific enough to avoid OMDb "Too many results" when combined with a year.
     * Rotates daily so visitors see variety.
     */
    const DEFAULT_TERMS = [
        'dark',
        'rise',
        'night',
        'fire',
        'war',
        'last',
        'dead',
        'black',
        'love',
        'new',
        'man',
        'fall',
        'iron',
        'wild',
        'lost',
    ];

    /**
     * Find the best (term, year) pair that yields real OMDb results for a given type.
     * Tries the current and previous year, rotating through DEFAULT_TERMS daily.
     *
     * @param  string $type  'movie' | 'series' | ''
     * @return array  ['term' => string, 'year' => string, 'result' => array]
     */
    /**
     * Find the best (term, year) pair that OMDb returns real results for a given type.
     * Iterates through DEFAULT_TERMS (rotating daily) and both the current and previous
     * year until a result set with totalPages >= 2 is found.
     * This is the single source of truth so landing and filter mode always agree.
     * Result is cached for 24 hours to prevent extreme slow load times.
     *
     * @param  string $type  'movie' | 'series' | ''
     * @return array ['term' => string, 'year' => string]
     */
    protected function findBestTerm(string $type = ''): array
    {
        $cacheKey = 'omdb_best_term_' . ($type ?: 'all') . '_' . date('Y-m-d');

        return Cache::remember($cacheKey, 86400, function () use ($type) {
            $years = [(string) date('Y'), (string) (date('Y') - 1)];
            $startIdx = (int) date('z') % count(self::DEFAULT_TERMS);

            foreach ($years as $yr) {
                for ($i = 0; $i < count(self::DEFAULT_TERMS); $i++) {
                    $term = self::DEFAULT_TERMS[($startIdx + $i) % count(self::DEFAULT_TERMS)];
                    $result = $this->omdbService->searchMovies($term, 1, $type, $yr);

                    if ($result['success'] && !empty($result['movies']) && ($result['totalPages'] ?? 0) >= 2) {
                        return ['term' => $term, 'year' => $yr];
                    }
                }
            }

            // Absolute fallback
            return ['term' => self::DEFAULT_TERMS[$startIdx], 'year' => (string) date('Y')];
        });
    }

    /**
     * Fetch up to $count landing cards for a given type by finding the best term
     * and combining page 1 + page 2 results.
     *
     * @param  string $type
     * @param  int    $count
     * @return array  ['term' => string, 'year' => string, 'result' => array]
     */
    protected function getLatestByType(string $type = '', int $count = 12): array
    {
        $best = $this->findBestTerm($type);
        $term = $best['term'];
        $yr = $best['year'];

        $page1 = $this->omdbService->searchMovies($term, 1, $type, $yr);
        $movies = $page1['movies'] ?? [];

        // Fetch page 2 to fill up to $count cards
        if (count($movies) < $count && ($page1['totalPages'] ?? 0) >= 2) {
            $page2 = $this->omdbService->searchMovies($term, 2, $type, $yr);
            if (!empty($page2['movies'])) {
                $movies = array_merge($movies, $page2['movies']);
            }
        }

        $page1['movies'] = array_slice($movies, 0, $count);

        return ['term' => $term, 'year' => $yr, 'result' => $page1];
    }

    /**
     * Annotate each movie with an is_favorited flag for the current session.
     *
     * @param  array  $movies
     * @param  string $sessionId
     * @return array
     */
    protected function markFavorited(array $movies, string $sessionId): array
    {
        return array_map(function (array $movie) use ($sessionId): array {
            $movie['is_favorited'] = $this->favoriteRepository->isFavorited($sessionId, $movie['imdbID']);
            return $movie;
        }, $movies);
    }

    /**
     * Show the movie list page.
     *
     * Landing (no active filters): renders two server-side sections — Movies and Series.
     * Filter/Search mode: renders a single paginated results grid via Blade.
     *
     * @param  Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = (string) $request->input('query', '');
        $type = (string) $request->input('type', '');
        $year = (string) $request->input('year', '');

        // ── Landing: no active filters → two-section view ──
        if (empty($query) && empty($type) && empty($year)) {
            $sessionId = session()->getId();
            $moviesBest = $this->getLatestByType('movie');
            $seriesBest = $this->getLatestByType('series');

            $moviesList = $moviesBest['result']['movies'] ?? [];
            $seriesList = $seriesBest['result']['movies'] ?? [];

            if (!empty($moviesList)) {
                $moviesList = $this->markFavorited($moviesList, $sessionId);
            }
            if (!empty($seriesList)) {
                $seriesList = $this->markFavorited($seriesList, $sessionId);
            }

            return view('movies.index', [
                'isDefault' => true,
                'moviesSection' => $moviesList,
                'moviesYear' => $moviesBest['year'],
                'moviesTerm' => $moviesBest['term'],
                'seriesSection' => $seriesList,
                'seriesYear' => $seriesBest['year'],
                'seriesTerm' => $seriesBest['term'],
                // Provide consistent keys even when unused, avoids undefined variable errors
                'movies' => [],
                'total' => 0,
                'totalPages' => 0,
                'query' => '',
                'type' => '',
                'year' => '',
                'defaultYear' => null,
                'defaultTerm' => null,
                'error' => null,
            ]);
        }

        // ── Search / filter mode: single results grid ──
        $sessionId = session()->getId();

        if (!empty($query)) {
            $searchTerm = $query;
            $defaultTerm = null;
            $defaultYear = null;

            // Allow user to filter search results by type/year if they explicitly picked them
            // The OMDb API expects $type to be movie, series, or episode.
            // If they are on a landing page section (e.g. view all series) and search "avengers",
            // we should probably clear the type/year to avoid "0 results" for Avengers series 2024.
            // However, we rely on the JS to clear/keep those filters.
        } else {
            // Type or year filter with no keyword.
            // Use findBestTerm() — the same logic used for the landing page — so
            // "View All Movies" / "View All Series" shows the exact same dataset
            // as the landing section, with NO keyword visible in the UI.
            $best = $this->findBestTerm($type);
            $searchTerm = $best['term'];
            $defaultTerm = $best['term'];  // passed to JS for loadMore continuity

            // If the user didn't explicitly select a year, borrow the best year.
            // If they DID select a year, use theirs and show theirs in the UI badge.
            if (empty($year)) {
                $year = $best['year'];
            }
            $defaultYear = $year;
        }

        $result = $this->omdbService->searchMovies($searchTerm, 1, $type, $year);

        if ($result['success'] && !empty($result['movies'])) {
            $result['movies'] = $this->markFavorited($result['movies'], $sessionId);
        }

        return view('movies.index', [
            'isDefault' => false,
            'moviesSection' => [],
            'moviesYear' => '',
            'moviesTerm' => '',
            'seriesSection' => [],
            'seriesYear' => '',
            'seriesTerm' => '',
            'movies' => $result['movies'] ?? [],
            'total' => $result['total'] ?? 0,
            'totalPages' => $result['totalPages'] ?? 0,
            'query' => $query,
            'type' => $type,
            'year' => $year,
            'defaultYear' => $defaultYear,
            'defaultTerm' => $defaultTerm,
            'error' => $result['error'] ?? null,
        ]);
    }

    /**
     * Load more results via AJAX (infinite scroll in search/filter mode).
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadMore(Request $request)
    {
        $query = (string) $request->input('query', '');
        $page = max(1, (int) $request->input('page', 1));
        $type = (string) $request->input('type', '');
        $year = (string) $request->input('year', '');

        if (!empty($query)) {
            $searchTerm = $query;
            $defaultTerm = null;
            $defaultYear = null;
        } else {
            // Re-use the term/year the JS already resolved for this filter session
            $passedTerm = (string) $request->input('defaultTerm', '');
            $passedYear = (string) $request->input('defaultYear', '');
            $searchTerm = !empty($passedTerm) ? $passedTerm : self::DEFAULT_TERMS[0];

            if (empty($year)) {
                $year = $passedYear; // Use passedYear directly, do not fallback to date('Y') if not intended
            }

            $defaultTerm = $searchTerm;
            $defaultYear = $passedYear;
        }

        $result = $this->omdbService->searchMovies($searchTerm, $page, $type, $year);

        if ($result['success'] && !empty($result['movies'])) {
            $result['movies'] = $this->markFavorited($result['movies'], session()->getId());
        }

        $result['defaultTerm'] = $defaultTerm;
        // Make sure the JSON response returns whatever year it eventually searched for
        $result['defaultYear'] = !empty($year) ? $year : $defaultYear;

        return response()->json($result);
    }

    /**
     * Show the movie detail page.
     *
     * @param  string $imdbId
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function detail(string $imdbId)
    {
        $result = $this->omdbService->getMovieDetail($imdbId);

        if (!$result['success']) {
            return redirect()->route('movies.index')
                ->with('error', $result['error'] ?? __('app.movie_not_found'));
        }

        $sessionId = session()->getId();
        $isFavorited = $this->favoriteRepository->isFavorited($sessionId, $imdbId);

        return view('movies.detail', [
            'movie' => $result['movie'],
            'isFavorited' => $isFavorited,
        ]);
    }
}
