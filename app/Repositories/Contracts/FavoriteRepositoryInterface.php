<?php

namespace App\Repositories\Contracts;

use App\Models\Favorite;
use Illuminate\Database\Eloquent\Collection;

interface FavoriteRepositoryInterface
{
    public function getAll(string $sessionId): Collection;

    public function findByImdbId(string $sessionId, string $imdbId): ?Favorite;

    public function add(string $sessionId, array $data): Favorite;

    public function remove(string $sessionId, string $imdbId): bool;

    public function isFavorited(string $sessionId, string $imdbId): bool;
}
