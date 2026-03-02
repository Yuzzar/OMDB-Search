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

    public function index(Request $request)
    {
        $query = (string) $request->input('query', '');
        $type = (string) $request->input('type', '');
        $year = (string) $request->input('year', '');

        if (empty($query) && empty($type) && empty($year)) {
            return $this->renderLandingView();
        }

        $context = $this->resolveSearchContext($query, $type, $year);
        $sessionId = session()->getId();
        $result = $this->omdbService->searchMovies(
            $context['searchTerm'],
            1,
            $context['type'],
            $context['year']
        );

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
            'year' => $context['year'],
            'defaultYear' => $context['defaultYear'],
            'defaultTerm' => $context['defaultTerm'],
            'error' => $result['error'] ?? null,
        ]);
    }

    public function loadMore(Request $request)
    {
        $query = (string) $request->input('query', '');
        $type = (string) $request->input('type', '');
        $year = (string) $request->input('year', '');
        $page = max(1, (int) $request->input('page', 1));
        $passedTerm = (string) $request->input('defaultTerm', '');
        $passedYear = (string) $request->input('defaultYear', '');

        $context = $this->resolveSearchContext($query, $type, $year, $passedTerm, $passedYear);
        $result = $this->omdbService->searchMovies(
            $context['searchTerm'],
            $page,
            $context['type'],
            $context['year']
        );

        if ($result['success'] && !empty($result['movies'])) {
            $result['movies'] = $this->markFavorited($result['movies'], session()->getId());
        }

        $result['defaultTerm'] = $context['defaultTerm'];
        $result['defaultYear'] = $context['defaultYear'] ?: ($context['year'] ?: null);

        return response()->json($result);
    }

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

    private function resolveSearchContext(
        string $query,
        string $type,
        string $year,
        string $passedTerm = '',
        string $passedYear = ''
    ): array {
        if (!empty($query)) {
            return [
                'searchTerm' => $query,
                'type' => $type,
                'year' => $year,
                'defaultTerm' => null,
                'defaultYear' => null,
            ];
        }

        if (!empty($passedTerm)) {
            $resolvedYear = !empty($year) ? $year : $passedYear;

            return [
                'searchTerm' => $passedTerm,
                'type' => $type,
                'year' => $resolvedYear,
                'defaultTerm' => $passedTerm,
                'defaultYear' => $resolvedYear ?: $passedYear,
            ];
        }

        $best = $this->findBestTerm($type);
        $resolvedYear = !empty($year) ? $year : $best['year'];

        return [
            'searchTerm' => $best['term'],
            'type' => $type,
            'year' => $resolvedYear,
            'defaultTerm' => $best['term'],
            'defaultYear' => $resolvedYear,
        ];
    }

    private function findBestTerm(string $type = ''): array
    {
        $terms = config('omdb.default_terms', ['dark']);
        $cacheKey = 'omdb_best_term_' . ($type ?: 'all') . '_' . date('Y-m-d');

        return Cache::remember($cacheKey, 86400, function () use ($type, $terms) {
            $years = [(string) date('Y'), (string) (date('Y') - 1)];
            $startIdx = (int) date('z') % count($terms);

            foreach ($years as $yr) {
                for ($i = 0; $i < count($terms); $i++) {
                    $term = $terms[($startIdx + $i) % count($terms)];
                    $result = $this->omdbService->searchMovies($term, 1, $type, $yr);

                    if ($result['success'] && !empty($result['movies']) && ($result['totalPages'] ?? 0) >= 2) {
                        return ['term' => $term, 'year' => $yr];
                    }
                }
            }

            return ['term' => $terms[$startIdx], 'year' => (string) date('Y')];
        });
    }

    private function getLatestByType(string $type = '', int $count = 12): array
    {
        $best = $this->findBestTerm($type);
        $term = $best['term'];
        $yr = $best['year'];
        $page1 = $this->omdbService->searchMovies($term, 1, $type, $yr);
        $movies = $page1['movies'] ?? [];

        if (count($movies) < $count && ($page1['totalPages'] ?? 0) >= 2) {
            $page2 = $this->omdbService->searchMovies($term, 2, $type, $yr);
            if (!empty($page2['movies'])) {
                $movies = array_merge($movies, $page2['movies']);
            }
        }

        $page1['movies'] = array_slice($movies, 0, $count);

        return ['term' => $term, 'year' => $yr, 'result' => $page1];
    }

    private function markFavorited(array $movies, string $sessionId): array
    {
        return array_map(function (array $movie) use ($sessionId): array {
            $movie['is_favorited'] = $this->favoriteRepository->isFavorited($sessionId, $movie['imdbID']);
            return $movie;
        }, $movies);
    }

    private function renderLandingView(): \Illuminate\View\View
    {
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
}
