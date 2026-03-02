(function () {
    'use strict';

    const Cfg = window.MovieConfig;
    const S = Cfg.state;

    // DOM references
    const $qIn = $('#q-input');
    const $qClr = $('#q-clear');
    const $qHint = $('#q-hint');
    const $qHintTxt = $('#q-hint-text');
    const $qTip = $('#q-tip');
    const $tyBtn = $('#type-btn');
    const $tyVal = $('#type-val');
    const $tyLbl = $('#type-label');
    const $tyMenu = $('#type-menu');
    const $tyCaret = $('#type-chevron');
    const $yrBtn = $('#year-btn');
    const $yrVal = $('#year-val');
    const $yrLbl = $('#year-label');
    const $yrMenu = $('#year-menu');
    const $yrCaret = $('#year-chevron');
    const $yrManual = $('#year-manual');
    const $prog = $('#search-progress');
    const $icon = $('#search-icon');
    const $grid = $('#movies-grid');
    const $skel = $('#skeleton-grid');
    const $spin = $('#loading-spinner');
    const $sent = $('#scroll-sentinel');
    const $head = $('#results-header');
    const $rtitle = $('#results-title');
    const $yrBadge = $('#year-badge');
    const $totBadge = $('#total-badge');
    const $errSt = $('#error-state');
    const $errMsg = $('#error-msg');
    const $empSt = $('#empty-state');
    const $empWordHint = $('#empty-word-hint');
    const $chips = $('#filter-chips');
    const $sectLanding = $('#sections-landing');
    const $resultsArea = $('#results-area');

    function debounce(fn, ms) {
        let timer;
        return function () {
            const args = arguments;
            const ctx = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function progStart() {
        $prog.css({ opacity: 1, width: '65%', transition: 'width 1.6s cubic-bezier(0.1,0.7,0.3,1)' });
    }

    function progDone() {
        $prog.css({ width: '100%', transition: 'width 0.18s ease' });
        setTimeout(function () {
            $prog.css({ opacity: 0, transition: 'opacity 0.28s ease' });
            setTimeout(function () { $prog.css({ width: '0%', transition: 'none' }); }, 320);
        }, 180);
    }

    function iconBusy() {
        $icon.html('<i class="fas fa-circle-notch fa-spin search-spinner-icon"></i>');
    }

    function iconIdle() {
        $icon.html('<i class="fas fa-search"></i>');
    }

    function syncClear() {
        $qClr.toggleClass('hidden', $qIn.val().trim() === '');
    }

    $qClr.on('click', function () {
        $qIn.val('').trigger('input').focus();
    });

    // Type dropdown
    let typeOpen = false;

    function typeToggle(open) {
        typeOpen = open;
        if (open) {
            $tyMenu.removeClass('hidden').addClass('dropdown-enter');
            $tyCaret.css('transform', 'rotate(180deg)');
            $tyBtn.attr('aria-expanded', 'true');
        } else {
            $tyMenu.addClass('hidden').removeClass('dropdown-enter');
            $tyCaret.css('transform', 'rotate(0deg)');
            $tyBtn.attr('aria-expanded', 'false');
        }
    }

    $tyBtn.on('click', function (e) {
        e.stopPropagation();
        typeToggle(!typeOpen);
        yearClose();
    });

    $tyMenu.on('click', 'li', function () {
        var val = $(this).data('val');
        var lbl = $(this).text().trim();
        $tyVal.val(val);
        $tyLbl.text(lbl);
        $tyMenu.find('li').removeClass('active');
        $(this).addClass('active');
        typeToggle(false);
        doSearch();
    });

    // Year picker
    let yearOpen = false;

    (function buildYearGrid() {
        const currentYear = new Date().getFullYear();
        const $pillGrid = $('#year-quick');

        $('<button>').attr('type', 'button')
            .addClass('yr-pill')
            .text(Cfg.i18n.anyYear)
            .on('click', function () { setYear(''); yearClose(); doSearch(); })
            .appendTo($pillGrid);

        for (let y = currentYear + 1; y >= currentYear - 18; y--) {
            (function (yr) {
                $('<button>').attr('type', 'button')
                    .addClass('yr-pill')
                    .text(yr)
                    .on('click', function () { setYear(String(yr)); yearClose(); doSearch(); })
                    .appendTo($pillGrid);
            })(y);
        }

        highlightYearPills();
    })();

    function highlightYearPills() {
        const selected = $yrVal.val();
        $('#year-quick .yr-pill').each(function () {
            const label = $(this).text().trim();
            const active = (label === selected) || (label === Cfg.i18n.anyYear && selected === '');
            $(this).toggleClass('yr-pill-active', active);
        });
    }

    function setYear(val) {
        $yrVal.val(val);
        $yrManual.val(val);
        $yrLbl.text(val || Cfg.i18n.anyYear);
        highlightYearPills();
    }

    function yearToggle(open) {
        yearOpen = open;
        if (open) {
            $yrMenu.removeClass('hidden').addClass('dropdown-enter');
            $yrCaret.css('transform', 'rotate(180deg)');
            $yrBtn.attr('aria-expanded', 'true');
            const rect = document.getElementById('year-wrapper').getBoundingClientRect();
            if (rect.right + 240 > window.innerWidth) {
                $yrMenu.css({ left: 'auto', right: 0 });
            }
        } else {
            $yrMenu.addClass('hidden').removeClass('dropdown-enter');
            $yrCaret.css('transform', 'rotate(0deg)');
            $yrBtn.attr('aria-expanded', 'false');
        }
    }

    function yearClose() { yearToggle(false); }

    $yrBtn.on('click', function (e) {
        e.stopPropagation();
        yearToggle(!yearOpen);
        typeToggle(false);
    });

    $yrManual.on('input', debounce(function () {
        var val = $yrManual.val().replace(/\D/g, '').slice(0, 4);
        $yrManual.val(val);
        if (val.length === 4) {
            var num = parseInt(val, 10);
            if (num >= 1900 && num <= Cfg.maxYear) {
                setYear(val);
                doSearch();
            }
        } else if (val === '') {
            setYear('');
            doSearch();
        }
    }, 600));

    $yrManual.on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); yearClose(); doSearch(); }
    });

    $('#year-clear-btn').on('click', function () {
        setYear('');
        yearClose();
        doSearch();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#type-wrapper').length) { typeToggle(false); }
        if (!$(e.target).closest('#year-wrapper').length) { yearClose(); }
    });

    // Filter chips
    function renderChips() {
        $chips.empty();

        function makeChip(icon, label, onDismiss) {
            return $('<span class="chip">')
                .append($('<i class="' + icon + ' mr-1.5 opacity-70"></i>'))
                .append($('<span>').text(label))
                .append(
                    $('<button class="chip-x"><i class="fas fa-times text-[10px]"></i></button>')
                        .on('click', onDismiss)
                );
        }

        const q = $qIn.val().trim();
        const tp = $tyVal.val();
        const yr = $yrVal.val();

        if (q) {
            $chips.append(makeChip('fas fa-search', '\u201c' + q + '\u201d', function () {
                $qIn.val('').trigger('input');
            }));
        }

        if (tp) {
            $chips.append(makeChip('fas fa-film', tp.charAt(0).toUpperCase() + tp.slice(1), function () {
                $tyVal.val('');
                $tyLbl.text(Cfg.i18n.allTypes);
                $tyMenu.find('li').removeClass('active');
                $tyMenu.find('[data-val=""]').addClass('active');
                doSearch();
            }));
        }

        if (yr) {
            $chips.append(makeChip('fas fa-calendar-alt', yr, function () {
                setYear('');
                doSearch();
            }));
        }
    }

    // Build a single movie card
    function buildCard(movie) {
        const posterVal = movie.Poster || '';
        const poster = (posterVal && posterVal.toUpperCase() !== 'N/A') ? posterVal : '';
        const isFav = !!movie.is_favorited;
        const type = (movie.Type || 'movie').toLowerCase();
        const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);
        const detailUrl = '/movies/' + movie.imdbID;
        const typeBadgeCls = type === 'series'
            ? 'bg-blue-500/20 text-blue-300 border-blue-500/30'
            : 'bg-pink-500/15 text-pink-300 border-pink-500/30';

        const $fallback = $('<div>', {
            class: 'absolute inset-0 flex items-center justify-center text-gray-700 text-5xl group-hover/poster:bg-[#1a1a24] transition-colors duration-500 z-0',
        }).append($('<i>', { class: 'fas fa-film transition-transform duration-500 group-hover/poster:scale-110' }));

        const $posterInner = poster
            ? $('<img>', {
                class: 'movie-card-img lazy absolute inset-0 w-full h-full object-cover transition-all duration-500 group-hover/poster:scale-110 opacity-0 z-10 relative',
                src: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
                'data-src': poster,
                alt: '',
                onload: "this.classList.remove('opacity-0');",
                onerror: "this.style.display='none';",
            })
            : null;

        const $hoverOverlay = $('<div>', {
            class: 'absolute inset-0 bg-black/60 opacity-0 group-hover/poster:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-sm z-20',
        }).append(
            $('<div>', { class: 'translate-y-4 group-hover/poster:translate-y-0 transition-all duration-300 flex flex-col items-center gap-2' })
                .append($('<div>', { class: 'w-10 h-10 rounded-full bg-accent text-white flex items-center justify-center shadow-lg shadow-accent/40' })
                    .append($('<i>', { class: 'fas fa-eye' }))
                )
                .append($('<span>', { class: 'text-white text-xs font-medium tracking-wider uppercase', text: Cfg.i18n.viewDetails }))
        );

        const $posterLink = $('<a>', { href: detailUrl, class: 'block relative overflow-hidden group/poster bg-[#12121a]', css: { aspectRatio: '2/3' } })
            .append($fallback);

        if ($posterInner) { $posterLink.append($posterInner); }

        $posterLink
            .append($hoverOverlay)
            .append($('<div>', { class: 'absolute bottom-0 inset-x-0 h-1/3 bg-gradient-to-t from-[#16161f] to-transparent pointer-events-none z-10' }));

        const $typeBadge = $('<span>', {
            class: 'inline-flex items-center text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-md border ' + typeBadgeCls,
            text: typeLabel,
        });

        const $favBtn = $('<button>', {
            class: 'fav-btn w-7 h-7 flex items-center justify-center rounded-lg transition-all '
                + (isFav ? 'is-favorited bg-accent text-white shadow-md shadow-accent/30'
                    : 'bg-white/5 text-gray-500 hover:bg-accent/15 hover:text-accent'),
            'data-imdb': movie.imdbID,
            'data-title': (movie.Title || '').replace(/"/g, '&quot;'),
            'data-year': movie.Year || '',
            'data-poster': poster,
            'data-type': type,
        }).append($('<i>', { class: (isFav ? 'fas' : 'far') + ' fa-heart text-xs' }));

        const $titleLink = $('<a>', { href: detailUrl, class: 'block' })
            .append($('<h3>', {
                class: 'text-sm font-semibold text-gray-100 leading-snug line-clamp-2 group-hover:text-[#e94560] transition-colors',
                text: movie.Title || '',
            }));

        const $body = $('<div>', { class: 'p-3 flex flex-col gap-1.5 flex-1' })
            .append(
                $('<div>', { class: 'flex items-center justify-between gap-1' })
                    .append($typeBadge)
                    .append($favBtn)
            )
            .append($titleLink)
            .append($('<span>', { class: 'text-xs text-gray-600', text: movie.Year || '' }));

        return $('<div>', {
            class: 'movie-card-new movie-card bg-[#16161f] border border-white/5 rounded-xl overflow-hidden flex flex-col group',
            'data-imdb': movie.imdbID,
        })
            .append($posterLink)
            .append($body)
            .prop('outerHTML');
    }

    function animateIn(elements) {
        $(elements).each(function (index, el) {
            setTimeout(function () {
                $(el).removeClass('movie-card-new').addClass('movie-card-show');
            }, index * 30);
        });
    }

    // Render AJAX search results
    function render(data) {
        const curQ = $qIn.val().trim();
        const curTp = $tyVal.val();

        $skel.addClass('hidden');
        iconIdle();
        progDone();

        if (!data || data.error || data.success === false) {
            $grid.addClass('hidden');
            $empSt.addClass('hidden');
            $head.addClass('hidden');
            $sent.addClass('hidden');
            $errMsg.text(data && data.error ? data.error : 'Something went wrong.');
            $errSt.removeClass('hidden');
            return;
        }

        const movies = data.movies || [];
        $errSt.addClass('hidden');

        if (!movies.length) {
            $grid.addClass('hidden');
            $head.addClass('hidden');
            $sent.addClass('hidden');
            $empWordHint.toggleClass('hidden', !curQ);
            $empSt.removeClass('hidden');
            return;
        }

        $empSt.addClass('hidden');
        $empWordHint.addClass('hidden');
        $grid.empty();
        $.each(movies, function (i, movie) { $grid.append(buildCard(movie)); });
        $grid.removeClass('hidden');
        $head.removeClass('hidden');

        if (!curQ && curTp) {
            $rtitle.text(curTp === 'series' ? Cfg.i18n.seriesSection : Cfg.i18n.moviesSection);
            $yrBadge.text(data.defaultYear || '').toggleClass('hidden', !data.defaultYear);
        } else {
            $rtitle.text(Cfg.i18n.results);
            $yrBadge.addClass('hidden');
        }

        $totBadge
            .text(data.total > 0 ? Number(data.total).toLocaleString() : '')
            .toggleClass('hidden', !(data.total > 0));

        S.page = 1;
        S.totalPages = data.totalPages || 1;
        S.defTerm = data.defaultTerm || S.defTerm;
        S.defYear = data.defaultYear || S.defYear;
        S.isDefault = false;

        $sent.toggleClass('hidden', S.totalPages <= 1);

        initLazyLoad();
        animateIn($grid.find('.movie-card-new'));
        renderChips();
    }

    // Live search
    const MIN_QUERY_LEN = 3;

    function isMidWord(q) {
        return q.length >= MIN_QUERY_LEN && q.indexOf(' ') === -1 && /[a-zA-Z]$/.test(q);
    }

    function doSearch() {
        const q = $qIn.val().trim();
        const tp = $tyVal.val();
        const yr = $yrVal.val();

        syncClear();
        renderChips();

        if (q.length > 0 && q.length < MIN_QUERY_LEN) {
            $qHint.removeClass('hidden').css('color', '#f59e0b');
            $qTip.addClass('hidden');
            if (S.xhr) { S.xhr.abort(); }
            return;
        }
        $qHint.addClass('hidden');

        $qTip.toggleClass('hidden', !isMidWord(q));

        if (!q && !tp && !yr) {
            if (!S.isDefault) { window.location.href = Cfg.homeUrl; }
            return;
        }

        if (S.isDefault) {
            $sectLanding.addClass('hidden');
            $resultsArea.removeClass('hidden');
            S.isDefault = false;
        }

        iconBusy();
        progStart();
        $skel.removeClass('hidden');
        $grid.addClass('hidden');
        $errSt.addClass('hidden');
        $empSt.addClass('hidden');
        $head.addClass('hidden');

        S.xhr = $.get(Cfg.loadUrl, {
            query: q,
            type: tp,
            year: yr,
            defaultTerm: S.defTerm,
            defaultYear: S.defYear,
            page: 1,
            live: 1,
        })
            .done(function (data) { render(data); })
            .fail(function (xhr) {
                if (xhr.statusText === 'abort') { return; }
                render({ error: 'Network error.' });
            });
    }

    const debouncedSearch = debounce(doSearch, 700);

    $qIn.on('input', function () {
        syncClear();

        const prevLen = $qIn.data('prev-len') || 0;
        const curLen = $qIn.val().trim().length;

        // Clear filters when user starts a new text search from empty
        if (curLen > 0 && prevLen === 0 && ($yrVal.val() !== '' || $tyVal.val() !== '')) {
            $yrVal.val('');
            $tyVal.val('');
            $tyLbl.text(Cfg.i18n.allTypes);
            $('#year-label').text(Cfg.i18n.anyYear);
            highlightYearPills();
            renderChips();
        }
        $qIn.data('prev-len', curLen);

        debouncedSearch();
    });

    $qIn.on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var q = $qIn.val().trim();
            if (q.length > 0 && q.length < MIN_QUERY_LEN) { return; }
            if (S.xhr) { S.xhr.abort(); }
            doSearch();
        }
    });

    // Infinite scroll
    function loadMore() {
        if (S.loading || S.page >= S.totalPages) { return; }

        S.loading = true;
        $spin.removeClass('hidden');

        const q = $qIn.val().trim();
        const tp = $tyVal.val();
        const yr = $yrVal.val();

        const requestQuery = S.isDefault ? (S.defTerm || '') : q;
        const requestYear = yr || (S.isDefault ? S.defYear : '');

        $.get(Cfg.loadUrl, {
            query: requestQuery,
            type: tp,
            year: requestYear,
            defaultTerm: S.defTerm,
            defaultYear: S.defYear,
            page: S.page + 1,
        })
            .done(function (data) {
                if (data.success && data.movies && data.movies.length) {
                    S.page++;
                    const $newCards = $($.map(data.movies, buildCard).join(''));
                    $grid.append($newCards);
                    initLazyLoad();
                    animateIn($newCards);

                    if (S.page >= S.totalPages) {
                        $sent.addClass('hidden');
                    } else {
                        setTimeout(function () {
                            if (!$sent.hasClass('hidden') && sentinel.getBoundingClientRect().top < window.innerHeight + 400) {
                                loadMore();
                            }
                        }, 500);
                    }
                } else if (S.page >= S.totalPages) {
                    $sent.addClass('hidden');
                }
            })
            .always(function () {
                S.loading = false;
                $spin.addClass('hidden');
            });
    }

    const sentinel = document.getElementById('scroll-sentinel');
    if (sentinel) {
        new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { loadMore(); }
            });
        }, { rootMargin: '400px' }).observe(sentinel);
    }

    // Favourite toggle
    $(document).on('click', '.fav-btn', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const imdb = $btn.data('imdb');
        const isFav = $btn.hasClass('is-favorited');

        $btn.prop('disabled', true);

        if (isFav) {
            $.ajax({
                url: Cfg.unfavUrl + '/' + imdb,
                type: 'POST',
                data: { _method: 'DELETE', _token: Cfg.csrfToken },
            })
                .done(function (res) {
                    $btn.removeClass('is-favorited bg-accent text-white shadow-md shadow-accent/30')
                        .addClass('bg-white/5 text-gray-500 hover:bg-accent/15 hover:text-accent');
                    $btn.find('i').removeClass('fas').addClass('far');
                    showToast(res.message || 'Removed from favorites', 'success');
                })
                .fail(function () { showToast('Error', 'error'); })
                .always(function () { $btn.prop('disabled', false); });
        } else {
            $.ajax({
                url: Cfg.favUrl,
                type: 'POST',
                data: {
                    _token: Cfg.csrfToken,
                    imdb_id: imdb,
                    title: $btn.data('title'),
                    year: $btn.data('year'),
                    poster: $btn.data('poster'),
                    type: $btn.data('type'),
                },
            })
                .done(function (res) {
                    $btn.addClass('is-favorited bg-accent text-white shadow-md shadow-accent/30')
                        .removeClass('bg-white/5 text-gray-500 hover:bg-accent/15 hover:text-accent');
                    $btn.find('i').removeClass('far').addClass('fas');
                    showToast(res.message || 'Added to favorites', 'success');
                })
                .fail(function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error';
                    showToast(msg, 'error');
                })
                .always(function () { $btn.prop('disabled', false); });
        }
    });

    // Init
    syncClear();
    renderChips();
    initLazyLoad();
    if (!S.isDefault) {
        animateIn($grid.find('.movie-card-new'));
    }

})();
