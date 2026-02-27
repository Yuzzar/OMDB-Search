/**
 * public/js/movies-list.js
 *
 * Movie list page — search, filters, infinite scroll, favourites.
 * Server-side config is injected by the Blade template via window.MovieConfig.
 *
 * Dependencies (loaded before this file):
 *   - jQuery 3.x          (global $)
 *   - showToast()         (defined in layouts/app.blade.php)
 *   - initLazyLoad()      (defined in layouts/app.blade.php)
 */
(function () {
    'use strict';

    var Cfg = window.MovieConfig;  // config injected from blade
    var S = Cfg.state;           // mutable runtime state

    /* ──────────────────────────────────────
       DOM REFERENCES
    ────────────────────────────────────── */
    var $qIn = $('#q-input');
    var $qClr = $('#q-clear');
    var $qHint = $('#q-hint');
    var $qHintTxt = $('#q-hint-text');
    var $qTip = $('#q-tip');
    var $tyBtn = $('#type-btn');
    var $tyVal = $('#type-val');
    var $tyLbl = $('#type-label');
    var $tyMenu = $('#type-menu');
    var $tyCaret = $('#type-chevron');
    var $yrBtn = $('#year-btn');
    var $yrVal = $('#year-val');
    var $yrLbl = $('#year-label');
    var $yrMenu = $('#year-menu');
    var $yrCaret = $('#year-chevron');
    var $yrManual = $('#year-manual');
    var $prog = $('#search-progress');
    var $icon = $('#search-icon');
    var $grid = $('#movies-grid');
    var $skel = $('#skeleton-grid');
    var $spin = $('#loading-spinner');
    var $sent = $('#scroll-sentinel');
    var $head = $('#results-header');
    var $rtitle = $('#results-title');
    var $yrBadge = $('#year-badge');
    var $totBadge = $('#total-badge');
    var $errSt = $('#error-state');
    var $errMsg = $('#error-msg');
    var $empSt = $('#empty-state');
    var $empWordHint = $('#empty-word-hint');
    var $chips = $('#filter-chips');
    var $sectLanding = $('#sections-landing');  // landing two-section container
    var $resultsArea = $('#results-area');      // AJAX results container

    /* ──────────────────────────────────────
       UTILITY: DEBOUNCE
    ────────────────────────────────────── */
    function debounce(fn, ms) {
        var timer;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    /* ──────────────────────────────────────
       PROGRESS BAR
    ────────────────────────────────────── */
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

    /* ──────────────────────────────────────
       SEARCH ICON SPINNER
    ────────────────────────────────────── */
    function iconBusy() {
        $icon.html('<i class="fas fa-circle-notch fa-spin" style="color:#e94560;font-size:13px"></i>');
    }

    function iconIdle() {
        $icon.html('<i class="fas fa-search"></i>');
    }

    /* ──────────────────────────────────────
       CLEAR (✕) BUTTON
    ────────────────────────────────────── */
    function syncClear() {
        $qClr.toggleClass('hidden', $qIn.val().trim() === '');
    }

    $qClr.on('click', function () {
        $qIn.val('').trigger('input').focus();
    });

    /* ──────────────────────────────────────
       CUSTOM TYPE DROPDOWN
    ────────────────────────────────────── */
    var typeOpen = false;

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

    /* ──────────────────────────────────────
       CUSTOM YEAR PICKER
    ────────────────────────────────────── */
    var yearOpen = false;

    // Build quick-pick pill grid on page load
    (function buildYearGrid() {
        var currentYear = new Date().getFullYear();
        var $pillGrid = $('#year-quick');

        var $anyPill = $('<button>').attr('type', 'button')
            .addClass('yr-pill')
            .text(Cfg.i18n.anyYear)
            .on('click', function () { setYear(''); yearClose(); doSearch(); });
        $pillGrid.append($anyPill);

        for (var y = currentYear + 1; y >= currentYear - 18; y--) {
            (function (yr) {
                var $pill = $('<button>').attr('type', 'button')
                    .addClass('yr-pill')
                    .text(yr)
                    .on('click', function () { setYear(String(yr)); yearClose(); doSearch(); });
                $pillGrid.append($pill);
            })(y);
        }

        highlightYearPills();
    })();

    function highlightYearPills() {
        var selected = $yrVal.val();
        $('#year-quick .yr-pill').each(function () {
            var label = $(this).text().trim();
            var active = (label === selected) || (label === Cfg.i18n.anyYear && selected === '');
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
            // Open to the left if near the right edge of the viewport
            var rect = document.getElementById('year-wrapper').getBoundingClientRect();
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

    // Debounced manual year text input
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

    /* ── Close all dropdowns on outside click ── */
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#type-wrapper').length) { typeToggle(false); }
        if (!$(e.target).closest('#year-wrapper').length) { yearClose(); }
    });

    /* ──────────────────────────────────────
       FILTER CHIPS
    ────────────────────────────────────── */
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

        var q = $qIn.val().trim();
        var tp = $tyVal.val();
        var yr = $yrVal.val();

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

    /* ──────────────────────────────────────
       BUILD MOVIE CARD HTML
    ────────────────────────────────────── */
    function buildCard(movie) {
        var posterVal = movie.Poster || '';
        var poster = (posterVal && posterVal.toUpperCase() !== 'N/A') ? posterVal : '';
        var isFav = !!movie.is_favorited;
        var type = (movie.Type || 'movie').toLowerCase();
        var typeLabel = type.charAt(0).toUpperCase() + type.slice(1);
        var detailUrl = '/movies/' + movie.imdbID;

        var typeBadgeCls = type === 'series' ? 'bg-blue-500/20 text-blue-300 border-blue-500/30'
            : 'bg-pink-500/15 text-pink-300 border-pink-500/30';

        // ── Fallback Icon (Behind) ──
        var $fallback = $('<div>', { class: 'absolute inset-0 flex items-center justify-center text-gray-700 text-5xl group-hover/poster:bg-[#1a1a24] transition-colors duration-500 z-0' })
            .append($('<i>', { class: 'fas fa-film transition-transform duration-500 group-hover/poster:scale-110' }));

        // ── Poster Image (Front) ──
        var $posterInner = poster
            ? $('<img>', {
                class: 'movie-card-img lazy absolute inset-0 w-full h-full object-cover transition-all duration-500 group-hover/poster:scale-110 opacity-0 z-10 relative',
                src: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
                'data-src': poster,
                alt: '',
                onload: "this.classList.remove('opacity-0');",
                onerror: "this.style.display='none';"
            })
            : null;

        var $hoverOverlay = $('<div>', { class: 'absolute inset-0 bg-black/60 opacity-0 group-hover/poster:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-sm z-20' })
            .append(
                $('<div>', { class: 'translate-y-4 group-hover/poster:translate-y-0 transition-all duration-300 flex flex-col items-center gap-2' })
                    .append($('<div>', { class: 'w-10 h-10 rounded-full bg-accent text-white flex items-center justify-center shadow-lg shadow-accent/40' })
                        .append($('<i>', { class: 'fas fa-eye' }))
                    )
                    .append($('<span>', { class: 'text-white text-xs font-medium tracking-wider uppercase', text: 'View Details' }))
            );

        var $posterLink = $('<a>', { href: detailUrl, class: 'block relative overflow-hidden group/poster bg-[#12121a]', css: { aspectRatio: '2/3' } })
            .append($fallback);

        if ($posterInner) {
            $posterLink.append($posterInner);
        }

        $posterLink
            .append($hoverOverlay)
            .append($('<div>', { class: 'absolute bottom-0 inset-x-0 h-1/3 bg-gradient-to-t from-[#16161f] to-transparent pointer-events-none z-10' }));

        // ── Type badge ──
        var $typeBadge = $('<span>', {
            class: 'inline-flex items-center text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-md border ' + typeBadgeCls,
            text: typeLabel,
        });

        // ── Favourite button ──
        var $favBtn = $('<button>', {
            class: 'fav-btn w-7 h-7 flex items-center justify-center rounded-lg transition-all '
                + (isFav ? 'is-favorited bg-accent text-white shadow-md shadow-accent/30'
                    : 'bg-white/5 text-gray-500 hover:bg-accent/15 hover:text-accent'),
            'data-imdb': movie.imdbID,
            'data-title': (movie.Title || '').replace(/"/g, '&quot;'),
            'data-year': movie.Year || '',
            'data-poster': poster,
            'data-type': type,
        }).append($('<i>', { class: (isFav ? 'fas' : 'far') + ' fa-heart text-xs' }));

        // ── Title link ──
        var $titleLink = $('<a>', { href: detailUrl, class: 'block' })
            .append($('<h3>', {
                class: 'text-sm font-semibold text-gray-100 leading-snug line-clamp-2 group-hover:text-[#e94560] transition-colors',
                text: movie.Title || '',
            }));

        // ── Assemble card ──
        var $body = $('<div>', { class: 'p-3 flex flex-col gap-1.5 flex-1' })
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

    // Stagger card entrance animation
    function animateIn(elements) {
        $(elements).each(function (index, el) {
            setTimeout(function () {
                $(el).removeClass('movie-card-new').addClass('movie-card-show');
            }, index * 30);
        });
    }

    /* ──────────────────────────────────────
       RENDER AJAX RESULTS
    ────────────────────────────────────── */
    function render(data) {
        // Read current filter state at render time (not captured from doSearch scope)
        var curQ = $qIn.val().trim();
        var curTp = $tyVal.val();

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

        var movies = data.movies || [];
        $errSt.addClass('hidden');

        if (!movies.length) {
            $grid.addClass('hidden');
            $head.addClass('hidden');
            $sent.addClass('hidden');
            // Show contextual hint: if user had a query that looks mid-word, guide them
            if (curQ) {
                $empWordHint.removeClass('hidden');
            } else {
                $empWordHint.addClass('hidden');
            }
            $empSt.removeClass('hidden');
            return;
        }

        $empSt.addClass('hidden');
        $empWordHint.addClass('hidden');
        $grid.empty();
        $.each(movies, function (i, movie) { $grid.append(buildCard(movie)); });
        $grid.removeClass('hidden');
        $head.removeClass('hidden');

        // Update result heading based on current active filters
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

        // Update state
        S.page = 1;
        S.totalPages = data.totalPages || 1;
        S.defTerm = data.defaultTerm || S.defTerm;
        S.defYear = data.defaultYear || S.defYear;
        S.isDefault = false; // render() is only ever called by AJAX (not landing)

        $sent.toggleClass('hidden', S.totalPages <= 1);

        initLazyLoad();
        animateIn($grid.find('.movie-card-new'));
        renderChips();
    }

    /* ──────────────────────────────────────
       LIVE SEARCH (debounced AJAX)
    ────────────────────────────────────── */
    var MIN_QUERY_LEN = 3; // OMDb rejects queries shorter than 3 chars

    /**
     * Detect whether the user is likely still mid-word.
     * A query is considered mid-word when it has no spaces (single token)
     * and ends in a letter — suggesting the user hasn't finished the word.
     * Used to show the word-tip hint without blocking the search.
     */
    function isMidWord(q) {
        return q.length >= MIN_QUERY_LEN && q.indexOf(' ') === -1 && /[a-zA-Z]$/.test(q);
    }

    function doSearch() {
        var q = $qIn.val().trim();
        var tp = $tyVal.val();
        var yr = $yrVal.val();

        syncClear();
        renderChips();

        // Too short — show min-chars hint, abort
        if (q.length > 0 && q.length < MIN_QUERY_LEN) {
            $qHint.removeClass('hidden').css('color', '#f59e0b');
            $qTip.addClass('hidden');
            if (S.xhr) { S.xhr.abort(); }
            return;
        }
        $qHint.addClass('hidden');

        // Mid-word — show the word-tip passively but still search
        if (isMidWord(q)) {
            $qTip.removeClass('hidden');
        } else {
            $qTip.addClass('hidden');
        }

        // All filters cleared — back to landing
        if (!q && !tp && !yr) {
            if (!S.isDefault) {
                window.location.href = Cfg.homeUrl;
            }
            return;
        }

        // Transition from landing mode to search mode
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

    var debouncedSearch = debounce(doSearch, 700); // 700ms gives users time to finish typing a complete word

    $qIn.on('input', function () {
        syncClear();

        // Auto-clear year and type when initiating a fresh text search from empty
        var prevLen = $qIn.data('prev-len') || 0;
        var curLen = $qIn.val().trim().length;

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
            if (q.length > 0 && q.length < MIN_QUERY_LEN) { return; } // too short
            if (S.xhr) { S.xhr.abort(); }
            doSearch();
        }
    });

    /* ──────────────────────────────────────
       INFINITE SCROLL
    ────────────────────────────────────── */
    function loadMore() {
        if (S.loading || S.page >= S.totalPages) { return; }

        S.loading = true;
        $spin.removeClass('hidden');

        var q = $qIn.val().trim();
        var tp = $tyVal.val();
        var yr = $yrVal.val();

        // Use default term/year when on the landing (no user query)
        var requestQuery = S.isDefault ? (S.defTerm || '') : q;
        var requestYear = yr || (S.isDefault ? S.defYear : '');

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
                    var $newCards = $($.map(data.movies, buildCard).join(''));
                    $grid.append($newCards);
                    initLazyLoad();
                    animateIn($newCards);

                    if (S.page >= S.totalPages) {
                        $sent.addClass('hidden');
                    } else {
                        // Re-check sentinel in case the newly appended cards still don't fill the vertical space
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

    // Trigger loadMore when scroll sentinel enters viewport
    var sentinel = document.getElementById('scroll-sentinel');
    if (sentinel) {
        new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { loadMore(); }
            });
        }, { rootMargin: '400px' }).observe(sentinel);
    }

    /* ──────────────────────────────────────
       FAVOURITE TOGGLE
    ────────────────────────────────────── */
    $(document).on('click', '.fav-btn', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var imdb = $btn.data('imdb');
        var isFav = $btn.hasClass('is-favorited');

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
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error';
                    showToast(msg, 'error');
                })
                .always(function () { $btn.prop('disabled', false); });
        }
    });

    /* ── Initialise ── */
    syncClear();
    renderChips();
    initLazyLoad();
    // Animate server-rendered cards on search/filter pages (safety net)
    if (!S.isDefault) {
        animateIn($grid.find('.movie-card-new'));
    }

})();
