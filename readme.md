# AldmicMovies

A Laravel 5.8 movie search and favorites application powered by the [OMDb API](http://www.omdbapi.com/).

---

## Features

- **Login Page** — Secure session-based authentication (credentials: `aldmic` / `123abc123`)
- **Movie List** — Search movies by title, filter by type and year
- **Infinite Scroll** — Automatically loads more results as user scrolls (via IntersectionObserver API)
- **Lazy Loading** — Movie poster images are lazily loaded for optimal performance
- **Movie Detail** — Full movie information from OMDb API
- **Favorites** — Add/remove favorites from List or Detail pages, persisted in MySQL
- **Multi Language** — English (EN) and Indonesian (ID) with runtime language switching
- **Empty State** — Friendly UI when no data is available

---

## Architecture

This project follows the **Repository Pattern** with **Service Layer** on top of standard Laravel MVC:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php        ← Handles login / logout
│   │   ├── MovieController.php       ← Movie list, search, detail, AJAX load-more
│   │   └── FavoriteController.php    ← CRUD favorites
│   ├── Middleware/
│   │   ├── AuthCheckMiddleware.php   ← Protects routes from unauthenticated access
│   │   └── LocaleMiddleware.php      ← Sets app locale from session
├── Models/
│   └── Favorite.php                  ← Eloquent model for favorite movies
├── Repositories/
│   ├── Contracts/
│   │   └── FavoriteRepositoryInterface.php  ← Interface (contract)
│   └── FavoriteRepository.php        ← Concrete DB implementation
├── Services/
│   └── OmdbService.php               ← Wraps OMDb API via GuzzleHTTP
└── Providers/
    └── AppServiceProvider.php         ← Binds interface → implementation, registers GuzzleHTTP

database/
└── migrations/
    └── 2026_02_26_081214_create_favorites_table.php

resources/
├── lang/
│   ├── en/app.php                    ← English translations
│   └── id/app.php                    ← Indonesian translations
└── views/
    ├── layouts/app.blade.php         ← Main layout (navbar, footer, toasts)
    ├── auth/login.blade.php          ← Login page
    ├── movies/
    │   ├── index.blade.php           ← Movie search & list with infinite scroll
    │   └── detail.blade.php          ← Movie detail page
    ├── favorites/
    │   └── index.blade.php           ← Favorites list page
    └── partials/
        └── movie-card.blade.php      ← Reusable movie card component
```

**Data Flow:**
```
Request → Controller → Service (OMDb API) or Repository (DB) → View
```

---

## Libraries Used

| Library | Version | Purpose |
|---|---|---|
| **Laravel** | 5.8.x | PHP MVC framework |
| **GuzzleHTTP** | ^7.0 | HTTP client for OMDb API requests |
| **Bootstrap** | 4.6.2 | CSS UI framework |
| **jQuery** | 3.6.0 | DOM manipulation & AJAX |
| **Font Awesome** | 5.15.4 | Icons |
| **Google Fonts (Inter)** | — | Typography |

**Native Browser APIs used:**
- `IntersectionObserver` — Infinite scroll & lazy loading (no external library needed)

---

## Setup & Installation

### Requirements
- PHP 7.4+
- Composer
- MySQL 5.7+
- Laragon / XAMPP / WAMP

### Steps

```bash
# 1. Install dependencies
composer install

# 2. Copy and configure environment
cp .env.example .env

# 3. Set your database in .env
DB_DATABASE=aldmic_movies
DB_USERNAME=root
DB_PASSWORD=

# 4. Generate application key
php artisan key:generate

# 5. Create database and run migrations
mysql -u root -e "CREATE DATABASE aldmic_movies"
php artisan migrate

# 6. Start development server
php artisan serve
```

Open browser at `http://localhost:8000`

---

## Login Credentials

| Field | Value |
|---|---|
| Username | `aldmic` |
| Password | `123abc123` |

---

## API Used

- **OMDb API**: `http://www.omdbapi.com/`
- API Key: `d51d815f`
- Search endpoint: `?s={query}&page={page}&type={type}&y={year}&apikey={key}`
- Detail endpoint: `?i={imdbId}&plot=full&apikey={key}`

---

## Deployment

### Railway.app (Recommended — Free)

1. Push code to a **private** GitHub repository
2. Register at [railway.app](https://railway.app)
3. New Project → Deploy from GitHub repo
4. Add MySQL plugin: **+ New → Database → MySQL**
5. Set environment variables in Railway dashboard (APP_KEY, DB_*, OMDB_API_KEY, APP_URL)
6. Add start command: `php artisan migrate && php artisan serve --host=0.0.0.0 --port=$PORT`

### InfinityFree / 000webhost (FTP Upload)

1. Register at [infinityfree.com](https://infinityfree.net)
2. Upload all files via FTP
3. Set document root to `/public`
4. Import MySQL dump via phpMyAdmin
5. Update `.env` with hosting DB credentials

---

## Screenshots

> *(Add screenshots here)*

- Login Page
- Movie Search List
- Movie Detail Page
- Favorites Page

---

## Author

Built for **PT. Aldmic Indonesia** Technical Test — February 2026


## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[British Software Development](https://www.britishsoftware.co)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- [UserInsights](https://userinsights.com)
- [Fragrantica](https://www.fragrantica.com)
- [SOFTonSOFA](https://softonsofa.com/)
- [User10](https://user10.com)
- [Soumettre.fr](https://soumettre.fr/)
- [CodeBrisk](https://codebrisk.com)
- [1Forge](https://1forge.com)
- [TECPRESSO](https://tecpresso.co.jp/)
- [Runtime Converter](http://runtimeconverter.com/)
- [WebL'Agence](https://weblagence.com/)
- [Invoice Ninja](https://www.invoiceninja.com)
- [iMi digital](https://www.imi-digital.de/)
- [Earthlink](https://www.earthlink.ro/)
- [Steadfast Collective](https://steadfastcollective.com/)
- [We Are The Robots Inc.](https://watr.mx/)
- [Understand.io](https://www.understand.io/)
- [Abdel Elrafa](https://abdelelrafa.com)
- [Hyper Host](https://hyper.host)

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
