# AldmicMovies

A Laravel 5.8 movie search and favorites application powered by the [OMDb API](http://www.omdbapi.com/).

---

## Ketentuan & Implementasi

Berikut ini adalah daftar ketentuan teknis test beserta status implementasinya:

| # | Ketentuan | Status |
|---|---|---|
| 1 | Halaman Login (username: `aldmic`, password: `123abc123`) | ✅ Implemented |
| 2 | Halaman List Movie | ✅ Implemented |
| 3 | Halaman Detail Movie | ✅ Implemented |
| 4 | Login wajib sebelum akses List / Detail Movie | ✅ `AuthCheckMiddleware` |
| 5 | Pesan error jika credential salah | ✅ Validation error shown on login |
| 6 | Pencarian movie berdasarkan judul, tipe, dan tahun | ✅ Search + filter |
| 7 | Klik movie → tampil halaman detail | ✅ Route `/movies/{imdbId}` |
| 8 | Tambah Favorite dari halaman List Movie | ✅ Favorite button per card |
| 9 | Tambah Favorite dari halaman Detail Movie | ✅ Add/Remove button on detail |
| 10 | Halaman Daftar Favorite tersendiri | ✅ Route `/favorites` |
| 11 | Hapus Favorite dari halaman Favorites | ✅ Implemented |
| 12 | Infinite Scroll pada halaman List Movie | ✅ `IntersectionObserver` API |
| 13 | Lazy Load pada gambar/poster Movie | ✅ `data-src` lazy loading |
| 14 | Multi Language ID / EN (default EN) | ✅ `LocaleMiddleware` + `lang/` |
| 15 | Lokalisasi hanya untuk teks statis (bukan data API) | ✅ Semua teks pakai `__('app.xxx')` |
| 16 | Empty state jika data kosong | ✅ Empty state UI |
| 17 | README dengan library, arsitektur, dan screenshot | ✅ Dokumen ini |

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

### Alibaba Cloud (Docker)

Aplikasi di-deploy menggunakan Docker di Alibaba Cloud ECS.

```bash
# 1. Clone project ke server
git clone <repo-url>
cd <project-folder>

# 2. Copy dan isi konfigurasi environment
cp .env.example .env
# Edit .env: isi DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD, OMDB_API_KEY, APP_URL

# 3. Jalankan dengan Docker Compose
docker-compose up -d --build

# 4. Jalankan migrasi database
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
```

Akses aplikasi melalui IP atau domain server.

---

## Screenshots

### Login Page
![Login Page](screenshots/1-login.png)

### Movie List
![Movie List](screenshots/2-movies-list.png)

### Filter by Type
![Filter by Type](screenshots/3-filter-type.png)

### Filter by Year
![Filter by Year](screenshots/4-filter-year.png)

### Movie Detail
![Movie Detail](screenshots/5-movie-detail.png)

### Movie Detail — Added to Favorites
![Movie Detail Favorited](screenshots/6-movie-detail-favorited.png)

### Favorites Page
![Favorites](screenshots/7-favorites.png)

---

## Author

Built for **PT. Aldmic Indonesia** Technical Test — February 2026
