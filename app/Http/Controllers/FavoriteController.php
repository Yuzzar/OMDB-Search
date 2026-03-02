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

    public function index()
    {
        $sessionId = session()->getId();
        $favorites = $this->favoriteRepository->getAll($sessionId);

        return view('favorites.index', ['favorites' => $favorites]);
    }

    public function store(StoreFavoriteRequest $request)
    {
        $sessionId = session()->getId();

        if ($this->favoriteRepository->isFavorited($sessionId, $request->input('imdb_id'))) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => __('app.already_favorited')]);
            }

            return back()->with('error', __('app.already_favorited'));
        }

        $this->favoriteRepository->add(
            $sessionId,
            $request->only(['imdb_id', 'title', 'year', 'poster', 'type'])
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('app.added_to_favorites')]);
        }

        return back()->with('success', __('app.added_to_favorites'));
    }

    public function destroy(Request $request, string $imdbId)
    {
        $sessionId = session()->getId();
        $removed = $this->favoriteRepository->remove($sessionId, $imdbId);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $removed,
                'message' => $removed
                    ? __('app.removed_from_favorites')
                    : __('app.favorite_not_found'),
            ]);
        }

        return $removed
            ? back()->with('success', __('app.removed_from_favorites'))
            : back()->with('error', __('app.favorite_not_found'));
    }
}
