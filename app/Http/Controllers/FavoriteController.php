<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFavoriteRequest;
use App\Repositories\Contracts\FavoriteRepositoryInterface;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /** @var FavoriteRepositoryInterface */
    protected $favoriteRepository;

    public function __construct(FavoriteRepositoryInterface $favoriteRepository)
    {
        $this->favoriteRepository = $favoriteRepository;
    }

    /**
     * Display the list of favorite movies.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $sessionId = session()->getId();
        $favorites = $this->favoriteRepository->getAll($sessionId);

        return view('favorites.index', ['favorites' => $favorites]);
    }

    /**
     * Add a movie to favorites.
     *
     * @param  StoreFavoriteRequest $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(StoreFavoriteRequest $request)
    {
        $sessionId = session()->getId();

        if ($this->favoriteRepository->isFavorited($sessionId, $request->input('imdb_id'))) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.already_favorited'),
                ]);
            }

            return back()->with('error', __('app.already_favorited'));
        }

        $this->favoriteRepository->add($sessionId, $request->only(['imdb_id', 'title', 'year', 'poster', 'type']));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('app.added_to_favorites'),
            ]);
        }

        return back()->with('success', __('app.added_to_favorites'));
    }

    /**
     * Remove a movie from favorites.
     *
     * @param  Request $request
     * @param  string  $imdbId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, string $imdbId)
    {
        $sessionId = session()->getId();
        $removed   = $this->favoriteRepository->remove($sessionId, $imdbId);

        if ($request->ajax()) {
            return response()->json([
                'success' => $removed,
                'message' => $removed ? __('app.removed_from_favorites') : __('app.favorite_not_found'),
            ]);
        }

        if ($removed) {
            return back()->with('success', __('app.removed_from_favorites'));
        }

        return back()->with('error', __('app.favorite_not_found'));
    }
}

