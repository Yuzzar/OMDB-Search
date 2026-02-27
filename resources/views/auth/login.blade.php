<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.login') }} — AldmicMovies</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { accent: { DEFAULT: '#e94560', dark: '#c73652' } }, fontFamily: { sans: ['Inter','ui-sans-serif'] } } }
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-[#0a0a0f] font-sans min-h-screen flex items-center justify-center relative overflow-hidden antialiased">

    {{-- Animated background blobs --}}
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    {{-- Login Card --}}
    <div class="relative z-10 w-full max-w-sm mx-4">
        <div class="bg-[#16161f]/90 border border-white/5 rounded-2xl p-8 shadow-2xl shadow-black/60"
             style="backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);">

            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-accent to-pink-400 rounded-2xl flex items-center justify-center text-2xl text-white mx-auto mb-4 shadow-lg shadow-accent/30">
                    <i class="fas fa-film"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">AldmicMovies</h1>
                <p class="text-gray-500 text-sm mt-1">{{ __('app.sign_in') }} to continue</p>
            </div>

            {{-- Error Alert --}}
            @if($errors->has('credentials') || session('error'))
            <div class="flex items-center gap-2 bg-red-950/60 border border-red-500/30 border-l-2 border-l-accent rounded-lg p-3 mb-6 text-red-300 text-sm">
                <i class="fas fa-exclamation-circle shrink-0"></i>
                <span>{{ $errors->first('credentials') ?: session('error') }}</span>
            </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('login.submit') }}" method="POST" novalidate>
                @csrf

                {{-- Username --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1.5">{{ __('app.username') }}</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600 text-sm"><i class="fas fa-user"></i></span>
                        <input type="text" name="username"
                               value="{{ old('username') }}"
                               placeholder="{{ __('app.username') }}"
                               autocomplete="username" autofocus
                               class="w-full bg-white/[0.03] border {{ $errors->has('credentials') ? 'border-accent' : 'border-white/7' }}
                                      text-gray-100 placeholder-gray-600 rounded-xl pl-10 pr-4 py-2.5 text-sm
                                      focus:outline-none focus:border-accent focus:bg-accent/5 focus:ring-2 focus:ring-accent/20 transition-all">
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-6">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1.5">{{ __('app.password') }}</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600 text-sm"><i class="fas fa-lock"></i></span>
                        <input type="password" id="pw-field" name="password"
                               placeholder="{{ __('app.password') }}"
                               autocomplete="current-password"
                               class="w-full bg-white/[0.03] border {{ $errors->has('credentials') ? 'border-accent' : 'border-white/7' }}
                                      text-gray-100 placeholder-gray-600 rounded-xl pl-10 pr-10 py-2.5 text-sm
                                      focus:outline-none focus:border-accent focus:bg-accent/5 focus:ring-2 focus:ring-accent/20 transition-all">
                        <button type="button" id="toggle-pw"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-400 transition-colors text-sm">
                            <i class="fas fa-eye" id="pw-eye"></i>
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full bg-gradient-to-r from-accent to-accent-dark text-white font-bold py-2.5 rounded-xl text-sm
                               hover:brightness-110 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-accent/30
                               active:translate-y-0 transition-all duration-200">
                    <i class="fas fa-arrow-right-to-bracket mr-2"></i>{{ __('app.sign_in') }}
                </button>
            </form>

            {{-- Language Switcher --}}
            <div class="flex justify-center gap-2 mt-6">
                <form action="{{ route('locale.switch') }}" method="POST">
                    @csrf<input type="hidden" name="locale" value="en">
                    <button type="submit" class="text-xs font-bold px-3 py-1 rounded-md border transition-all
                        {{ app()->getLocale() === 'en' ? 'bg-accent border-accent text-white' : 'border-white/10 text-gray-500 hover:text-gray-300 hover:border-white/20' }}">EN</button>
                </form>
                <form action="{{ route('locale.switch') }}" method="POST">
                    @csrf<input type="hidden" name="locale" value="id">
                    <button type="submit" class="text-xs font-bold px-3 py-1 rounded-md border transition-all
                        {{ app()->getLocale() === 'id' ? 'bg-accent border-accent text-white' : 'border-white/10 text-gray-500 hover:text-gray-300 hover:border-white/20' }}">ID</button>
                </form>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        $('#toggle-pw').on('click', function () {
            var f = $('#pw-field'), e = $('#pw-eye');
            f.attr('type') === 'password'
                ? (f.attr('type', 'text'),  e.removeClass('fa-eye').addClass('fa-eye-slash'))
                : (f.attr('type', 'password'), e.removeClass('fa-eye-slash').addClass('fa-eye'));
        });
    </script>
</body>
</html>
