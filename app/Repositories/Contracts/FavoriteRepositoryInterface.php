<?php

namespace App\Repositories\Contracts;

interface FavoriteRepositoryInterface
{
    /**
     * Get all favorites for a session user.
     *
     * @param  string $sessionId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(string $sessionId);

    /**
     * Find a favorite by session ID and IMDb ID.
     *
     * @param  string $sessionId
     * @param  string $imdbId
     * @return \App\Models\Favorite|null
     */
    public function findByImdbId(string $sessionId, string $imdbId);

    /**
     * Add a movie to favorites.
     *
     * @param  string $sessionId
     * @param  array  $data
     * @return \App\Models\Favorite
     */
    public function add(string $sessionId, array $data);

    /**
     * Remove a favorite by session ID and IMDb ID.
     *
     * @param  string $sessionId
     * @param  string $imdbId
     * @return bool
     */
    public function remove(string $sessionId, string $imdbId): bool;

    /**
     * Check if a movie is already favorited.
     *
     * @param  string $sessionId
     * @param  string $imdbId
     * @return bool
     */
    public function isFavorited(string $sessionId, string $imdbId): bool;
}
