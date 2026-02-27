<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OmdbService
{
    /** @var Client */
    protected $client;

    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $baseUrl;

    public function __construct(Client $client)
    {
        $this->client  = $client;
        $this->apiKey  = config('omdb.api_key');
        $this->baseUrl = config('omdb.base_url');
    }

    /**
     * Search movies by title with optional filters.
     *
     * @param  string  $query
     * @param  int     $page
     * @param  string  $type   (movie|series|episode)
     * @param  string  $year
     * @return array
     */
    public function searchMovies(string $query, int $page = 1, ?string $type = null, ?string $year = null): array
    {
        try {
            $params = [
                's'      => $query,
                'page'   => $page,
                'apikey' => $this->apiKey,
            ];

            $type = (string) ($type ?? '');
            $year = (string) ($year ?? '');

            if (!empty($type)) {
                $params['type'] = $type;
            }

            if (!empty($year)) {
                $params['y'] = $year;
            }

            $response = $this->client->get($this->baseUrl, ['query' => $params]);
            $data     = json_decode($response->getBody()->getContents(), true);

            if ($data['Response'] === 'True') {
                return [
                    'success'     => true,
                    'movies'      => $data['Search'] ?? [],
                    'total'       => (int) ($data['totalResults'] ?? 0),
                    'currentPage' => $page,
                    'totalPages'  => ceil((int) ($data['totalResults'] ?? 0) / 10),
                ];
            }

            return [
                'success' => false,
                'movies'  => [],
                'total'   => 0,
                'error'   => $data['Error'] ?? 'No results found.',
            ];
        } catch (GuzzleException $e) {
            Log::error('OMDb API search error: ' . $e->getMessage());

            return [
                'success' => false,
                'movies'  => [],
                'total'   => 0,
                'error'   => 'Failed to connect to OMDb API.',
            ];
        }
    }

    /**
     * Get movie detail by IMDb ID.
     *
     * @param  string $imdbId
     * @return array
     */
    public function getMovieDetail(string $imdbId): array
    {
        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => [
                    'i'      => $imdbId,
                    'plot'   => 'full',
                    'apikey' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['Response'] === 'True') {
                return [
                    'success' => true,
                    'movie'   => $data,
                ];
            }

            return [
                'success' => false,
                'movie'   => null,
                'error'   => $data['Error'] ?? 'Movie not found.',
            ];
        } catch (GuzzleException $e) {
            Log::error('OMDb API detail error: ' . $e->getMessage());

            return [
                'success' => false,
                'movie'   => null,
                'error'   => 'Failed to connect to OMDb API.',
            ];
        }
    }
}
