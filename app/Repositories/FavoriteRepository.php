<?php

namespace App\Repositories;

use App\Models\Favorite;
use App\Repositories\Contracts\FavoriteRepositoryInterface;

class FavoriteRepository implements FavoriteRepositoryInterface
{
    /** @var Favorite */
    protected $model;

    public function __construct(Favorite $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(string $sessionId)
    {
        return $this->model
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByImdbId(string $sessionId, string $imdbId)
    {
        return $this->model
            ->where('session_id', $sessionId)
            ->where('imdb_id', $imdbId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $sessionId, array $data)
    {
        return $this->model->create([
            'session_id' => $sessionId,
            'imdb_id'    => $data['imdb_id'],
            'title'      => $data['title'],
            'year'       => $data['year'],
            'poster'     => $data['poster'],
            'type'       => $data['type'] ?? 'movie',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $sessionId, string $imdbId): bool
    {
        return (bool) $this->model
            ->where('session_id', $sessionId)
            ->where('imdb_id', $imdbId)
            ->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function isFavorited(string $sessionId, string $imdbId): bool
    {
        return $this->model
            ->where('session_id', $sessionId)
            ->where('imdb_id', $imdbId)
            ->exists();
    }
}
