<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFavoriteRequest extends FormRequest
{
    /**
     * Any logged-in (session-authenticated) user may store a favourite.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return session()->has('authenticated');
    }

    /**
     * Validation rules for adding a movie to favourites.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'imdb_id' => ['required', 'string', 'max:20'],
            'title'   => ['required', 'string', 'max:255'],
            'year'    => ['nullable', 'string', 'max:10'],
            'poster'  => ['nullable', 'string'],
            'type'    => ['nullable', 'string', 'max:20'],
        ];
    }
}
