<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AldmicMovies') — AldmicMovies</title>

    {{-- Tailwind CSS Play CDN (no build step needed) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark:    { DEFAULT: '#0a0a0f', 2: '#12121a', card: '#16161f', hover: '#1c1c28' },
                        accent:  { DEFAULT: '#e94560', dark: '#c73652', glow: 'rgba(233,69,96,0.25)' },
                        muted:   '#5a5a7a',
                    },
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
                },
            },
        };
    </script>

    {{-- Font Awesome & Google Fonts --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Custom CSS (animations, transitions, non-Tailwind) --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @yield('styles')
</head>
<body class="bg-dark font-sans text-gray-100 min-h-screen flex flex-col antialiased">

    {{-- ────── NAVBAR ────── --}}
    <nav class="navbar-blur sticky top-0 z-50 h-16 bg-dark/95 border-b border-white/5 flex items-center px-4 md:px-6 gap-4">

        {{-- Brand --}}
        <a href="{{ route('movies.index') }}" class="flex items-center gap-2 mr-auto shrink-0">
            <span class="w-9 h-9 bg-gradient-to-br from-accent to-pink-400 rounded-lg flex items-center justify-center text-sm text-white shadow-lg shadow-accent-glow">
                <i class="fas fa-film"></i>
            </span>
            <span class="text-accent font-extrabold text-xl tracking-tight hidden sm:inline">AldmicMovies</span>
        </a>

        {{-- Nav Links --}}
        <a href="{{ route('movies.index') }}"
           class="flex items-center gap-1.5 text-sm font-medium px-3 py-1.5 rounded-lg transition-all
                  {{ request()->routeIs('movies.*') ? 'bg-accent/10 text-accent' : 'text-gray-400 hover:text-gray-200 hover:bg-white/5' }}">
            <i class="fas fa-film text-xs"></i>
            <span class="hidden sm:inline">{{ __('app.movies') }}</span>
        </a>

        <a href="{{ route('favorites.index') }}"
           class="flex items-center gap-1.5 text-sm font-medium px-3 py-1.5 rounded-lg transition-all
                  {{ request()->routeIs('favorites.*') ? 'bg-accent/10 text-accent' : 'text-gray-400 hover:text-gray-200 hover:bg-white/5' }}">
            <i class="fas fa-heart text-xs"></i>
            <span class="hidden sm:inline">{{ __('app.favorites') }}</span>
        </a>

        {{-- Language Switcher --}}
        <div class="flex items-center gap-1">
            <form action="{{ route('locale.switch') }}" method="POST">
                @csrf<input type="hidden" name="locale" value="en">
                <button type="submit" class="text-xs font-bold px-2.5 py-1 rounded-md border transition-all
                    {{ app()->getLocale() === 'en' ? 'bg-accent border-accent text-white' : 'border-white/10 text-gray-500 hover:text-gray-300 hover:border-white/20' }}">EN</button>
            </form>
            <form action="{{ route('locale.switch') }}" method="POST">
                @csrf<input type="hidden" name="locale" value="id">
                <button type="submit" class="text-xs font-bold px-2.5 py-1 rounded-md border transition-all
                    {{ app()->getLocale() === 'id' ? 'bg-accent border-accent text-white' : 'border-white/10 text-gray-500 hover:text-gray-300 hover:border-white/20' }}">ID</button>
            </form>
        </div>

        {{-- User chip + Logout --}}
        <div class="flex items-center gap-2 shrink-0">
            <div class="hidden sm:flex items-center gap-2 bg-white/5 border border-white/5 rounded-full pl-1.5 pr-3 py-1">
                <span class="w-6 h-6 bg-accent/20 text-accent rounded-full flex items-center justify-center text-xs font-bold">
                    {{ strtoupper(substr(session('username', 'A'), 0, 1)) }}
                </span>
                <span class="text-xs font-medium text-gray-300">{{ session('username', 'User') }}</span>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-accent transition-colors px-2 py-1.5 rounded-lg hover:bg-accent/5">
                    <i class="fas fa-right-from-bracket"></i>
                    <span class="hidden md:inline">{{ __('app.logout') }}</span>
                </button>
            </form>
        </div>
    </nav>

    {{-- ────── MAIN CONTENT ────── --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- ────── TOAST CONTAINER ────── --}}
    <div id="toast-container"></div>

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    {{-- Global JS --}}
    <script>
        /* CSRF for all AJAX */
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        /* Toast helper */
        function showToast(message, type) {
            type = type || 'success';
            var icon = type === 'success' ? 'fa-check-circle' : 'fa-circle-xmark';
            var el = $('<div class="toast-item toast-' + type + '">')
                .html('<i class="fas ' + icon + '"></i>' + message)
                .on('click', function(){ $(this).remove(); });
            $('#toast-container').append(el);
            setTimeout(function(){ el.fadeOut(300, function(){ $(this).remove(); }); }, 3500);
        }

        /* Lazy image loader */
        function initLazyLoad() {
            var imgs = document.querySelectorAll('img.lazy:not(.loaded)');
            if (!('IntersectionObserver' in window)) {
                imgs.forEach(function(img){ img.src = img.dataset.src; img.classList.add('loaded'); });
                return;
            }
            var obs = new IntersectionObserver(function(entries){
                entries.forEach(function(entry){
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        obs.unobserve(img);
                    }
                });
            }, { rootMargin: '100px' });
            imgs.forEach(function(img){ obs.observe(img); });
        }
    </script>

    @yield('scripts')
</body>
</html>
