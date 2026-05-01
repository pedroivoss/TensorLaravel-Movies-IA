<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Movie Recommendation AI — Blade</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <style>
        /* ── Theme (espelha Ant Design default) ─────────────────────────────── */
        :root {
            --primary:      #1677ff;
            --primary-bg:   #e6f4ff;
            --primary-bd:   #91caff;
            --bg-page:      #f5f6fa;
            --bg-card:      #ffffff;
            --text-primary: #000000e0;
            --text-second:  #00000073;
            --border:       #d9d9d9;
            --shadow-card:  0 1px 4px rgba(0,0,0,.06);
            --radius-card:  12px;
            --warning:      #fa8c16;
            --warning-bg:   #fff7e6;
            --warning-bd:   #ffd591;
            --success:      #52c41a;
            --success-bg:   #f6ffed;
            --success-bd:   #b7eb8f;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--bg-page);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-primary);
            margin: 0;
            font-size: 14px;
        }

        /* ── Header ─────────────────────────────────────────────────────────── */
        .ai-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--bg-card);
            border-bottom: 2px solid var(--primary);
            height: 56px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 28px;
        }
        .ai-header__title   { font-size: 18px; font-weight: 600; line-height: 1; margin: 0; }
        .ai-header__sub     { font-size: 12px; color: var(--text-second); }
        .ai-header__spacer  { margin-left: auto; }

        /* ── Tag / Badge ─────────────────────────────────────────────────────── */
        .ai-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            padding: 1px 8px;
            border-radius: 4px;
            border: 1px solid currentColor;
            line-height: 20px;
            white-space: nowrap;
        }
        .ai-tag--blue    { color: var(--primary);  background: var(--primary-bg); border-color: var(--primary-bd); }
        .ai-tag--orange  { color: var(--warning);  background: var(--warning-bg); border-color: var(--warning-bd); }
        .ai-tag--green   { color: var(--success);  background: var(--success-bg); border-color: var(--success-bd); }
        .ai-tag--neutral { color: #595959; background: #fafafa; border-color: var(--border); }

        /* ── Content ─────────────────────────────────────────────────────────── */
        .ai-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        /* ── Card ────────────────────────────────────────────────────────────── */
        .ai-card {
            background: var(--bg-card);
            border-radius: var(--radius-card);
            padding: 20px 24px;
            box-shadow: var(--shadow-card);
        }
        .ai-card__header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .ai-card__title { font-size: 15px; font-weight: 600; margin: 0; }
        .ai-card__desc  { font-size: 13px; color: var(--text-second); margin-bottom: 14px; }

        /* ── Input ───────────────────────────────────────────────────────────── */
        .ai-input {
            width: 100%;
            padding: 6px 11px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            background: #fff;
            color: var(--text-primary);
            transition: border-color .2s, box-shadow .2s;
        }
        .ai-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(22,119,255,.1);
        }

        /* ── Select2 override ────────────────────────────────────────────────── */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid var(--border);
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 11px;
            color: var(--text-primary);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(22,119,255,.1);
        }
        .select2-container--default .select2-results__option--highlighted {
            background-color: var(--primary-bg);
            color: var(--primary);
        }
        .select2-container--default .select2-results__option--selected {
            background-color: var(--primary-bg);
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 4px 8px;
            outline: none;
        }
        .select2-search--dropdown .select2-search__field:focus {
            border-color: var(--primary);
        }

        /* ── Alert ───────────────────────────────────────────────────────────── */
        .ai-alert {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            border: 1px solid;
        }
        .ai-alert--info    { background: var(--primary-bg); border-color: var(--primary-bd); color: #003eb3; }
        .ai-alert--warning { background: var(--warning-bg); border-color: var(--warning-bd); color: #874d00; }
        .ai-alert--success { background: var(--success-bg); border-color: var(--success-bd); color: #135200; }

        /* ── Button ──────────────────────────────────────────────────────────── */
        .ai-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 5px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all .15s;
            line-height: 1.4;
        }
        .ai-btn:disabled { opacity: .45; cursor: not-allowed; }
        .ai-btn--primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .ai-btn--primary:hover:not(:disabled) { background: #4096ff; border-color: #4096ff; }
        .ai-btn--default { background: #fff; color: var(--text-primary); border-color: var(--border); }
        .ai-btn--default:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
        .ai-btn--blue-outline { background: #fff; color: var(--primary); border-color: var(--primary); }
        .ai-btn--blue-outline:hover:not(:disabled) { background: var(--primary-bg); }

        /* ── Icons coloridos ──────────────────────────────────────────────────── */
        .icon-blue   { color: var(--primary) !important; }
        .icon-orange { color: var(--warning) !important; }
        .icon-green  { color: var(--success) !important; }
        .text-second { color: var(--text-second); }

        /* ── Avatar ──────────────────────────────────────────────────────────── */
        .ai-avatar {
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            flex-shrink: 0;
            font-size: 13px;
        }

        /* ── User profile card ───────────────────────────────────────────────── */
        #card-user-profile {
            border-left: 4px solid var(--primary);
            padding: 14px 20px;
        }

        /* ── Movie card ──────────────────────────────────────────────────────── */
        .movie-card {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            margin-bottom: 7px;
            background: #fff;
            transition: box-shadow .15s;
        }
        .movie-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .movie-card--highlight { border-left: 3px solid var(--primary); }

        .movie-card__left  { flex: 1; min-width: 0; }
        .movie-card__right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }

        .movie-card__title {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 3px;
        }
        .movie-card__meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 11px;
            color: var(--text-second);
            margin-bottom: 5px;
        }
        .movie-card__genres { display: flex; flex-wrap: wrap; gap: 3px; }

        .ai-genre-tag {
            display: inline-block;
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 3px;
            border: 1px solid;
            line-height: 16px;
        }

        .imdb-tag {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #fffbe6;
            border: 1px solid #ffe58f;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 11px;
            color: #7c5c00;
        }

        /* ── Stars ───────────────────────────────────────────────────────────── */
        .stars-row { display: flex; gap: 2px; }
        .star-icon  { font-size: 14px; cursor: pointer; transition: transform .1s; }
        .star-icon:hover { transform: scale(1.2); }

        /* ── Sort / Filter bar ───────────────────────────────────────────────── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .filter-bar label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            cursor: pointer;
            user-select: none;
        }
        .filter-bar select {
            padding: 3px 8px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 12px;
            color: var(--text-primary);
            background: #fff;
            outline: none;
            cursor: pointer;
        }

        /* ── Scrollable list ─────────────────────────────────────────────────── */
        .movie-list-scroll {
            max-height: 520px;
            overflow-y: auto;
            padding-right: 2px;
        }
        .movie-list-scroll::-webkit-scrollbar { width: 5px; }
        .movie-list-scroll::-webkit-scrollbar-track { background: #f0f0f0; border-radius: 3px; }
        .movie-list-scroll::-webkit-scrollbar-thumb { background: #d9d9d9; border-radius: 3px; }

        /* ── Empty / Loading states ──────────────────────────────────────────── */
        .ai-empty {
            text-align: center;
            padding: 36px 16px;
            color: var(--text-second);
            font-size: 13px;
        }
        .ai-empty i { font-size: 2.2rem; display: block; margin-bottom: 8px; opacity: .35; }

        /* ── Divider ─────────────────────────────────────────────────────────── */
        .ai-divider { border: none; border-top: 1px solid #f0f0f0; margin: 28px 0 8px; }
        .ai-footer  { text-align: center; font-size: 12px; color: var(--text-second); padding-bottom: 20px; }
        code        { background: rgba(0,0,0,.06); padding: 1px 5px; border-radius: 3px; font-size: 11px; }
    </style>
</head>
<body>

<!-- ── Header ──────────────────────────────────────────────────────────────── -->
<header class="ai-header">
    <i class="bi bi-play-circle-fill fs-5 icon-blue"></i>
    <h1 class="ai-header__title">Movie Recommendation AI</h1>
    <span class="ai-header__sub">· Laravel + Blade</span>
    <div class="ai-header__spacer"></div>
    <button class="ai-btn ai-btn--blue-outline"
            data-bs-toggle="modal" data-bs-target="#modal-treinar">
        <i class="bi bi-cpu-fill"></i> Treinar Modelo
    </button>
</header>

<!-- ── Content ─────────────────────────────────────────────────────────────── -->
<div class="ai-content">

    <!-- ── 1. User selector card ──────────────────────────────────────────── -->
    <div class="ai-card mb-3">
        <div class="ai-card__header">
            <i class="bi bi-person fs-5 icon-blue"></i>
            <h2 class="ai-card__title">Selecione um Usuário</h2>
            <span id="users-count" class="ai-tag ai-tag--blue d-none"></span>
        </div>
        <p class="ai-card__desc mb-3">Escolha um usuário para carregar o perfil e ver as recomendações personalizadas.</p>

        <div id="user-select-loading" class="d-flex align-items-center gap-2 text-second" style="font-size:13px;">
            <div class="spinner-border spinner-border-sm" role="status" style="color:var(--primary);"></div>
            Carregando usuários...
        </div>
        <select id="select-user" class="w-100 d-none"></select>
    </div>

    <!-- ── 2. User profile card (oculto até selecionar) ───────────────────── -->
    <div class="ai-card mb-3 d-none" id="card-user-profile">
        <!-- preenchido por JS -->
    </div>

    <!-- ── 3. Filmes + Recomendações ──────────────────────────────────────── -->
    <div class="row g-4">

        <!-- ── Coluna esquerda: Busca de Filmes ─────────────────────────── -->
        <div class="col-12 col-lg-6 d-flex flex-column">
            <div class="ai-card" style="flex:1;display:flex;flex-direction:column;min-height:0;">

                <div class="ai-card__header">
                    <i class="bi bi-search fs-5 icon-blue"></i>
                    <h2 class="ai-card__title">Busca de Filmes</h2>
                    <span id="movies-total-tag" class="ai-tag ai-tag--neutral d-none"></span>
                </div>

                <input type="text" id="search-movies" class="ai-input mb-2"
                       placeholder="Digite o nome do filme...">

                <div class="filter-bar">
                    <label>
                        <input type="checkbox" id="filter-watched"
                               style="accent-color:var(--primary);" disabled>
                        Só assistidos
                    </label>
                    <select id="sort-movies">
                        <option value="rate">Ordenar: Por nota</option>
                        <option value="date">Ordenar: Por data</option>
                        <option value="az">Ordenar: A → Z</option>
                    </select>
                </div>

                <div id="no-user-movies-alert" class="ai-alert ai-alert--info mb-2">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    Selecione um usuário acima para poder avaliar filmes.
                </div>

                <div id="movies-loading" class="ai-empty d-none">
                    <div class="spinner-border spinner-border-sm mb-2" style="color:var(--primary);"></div>
                    <span>Buscando...</span>
                </div>
                <div id="movies-empty" class="ai-empty d-none">
                    <i class="bi bi-film"></i>Nenhum filme encontrado
                </div>

                <div id="movies-list" class="movie-list-scroll" style="flex:1;"></div>
            </div>
        </div>

        <!-- ── Coluna direita: Recomendações ────────────────────────────── -->
        <div class="col-12 col-lg-6 d-flex flex-column">
            <div class="ai-card" style="flex:1;display:flex;flex-direction:column;min-height:0;">

                <div class="ai-card__header">
                    <i class="bi bi-fire fs-5 icon-orange"></i>
                    <h2 class="ai-card__title" id="reco-title">Recomendações</h2>
                    <span id="reco-algo-tag" class="ai-tag ai-tag--orange d-none" style="font-size:11px;">
                        <i class="bi bi-robot"></i> Placeholder — TF.js em breve
                    </span>
                </div>

                <div id="reco-no-user" class="ai-empty">
                    <i class="bi bi-person-x"></i>
                    Selecione um usuário para ver as recomendações personalizadas.
                </div>
                <div id="reco-loading" class="ai-empty d-none">
                    <div class="spinner-border spinner-border-sm mb-2" style="color:var(--warning);"></div>
                    <span>Carregando recomendações...</span>
                </div>
                <div id="reco-algo-alert" class="ai-alert ai-alert--warning mb-2 d-none">
                    <i class="bi bi-robot flex-shrink-0 mt-1"></i>
                    <span id="reco-algo-text"></span>
                </div>
                <div id="reco-empty" class="ai-empty d-none">
                    <i class="bi bi-stars"></i>Nenhuma recomendação disponível
                </div>
                <div id="reco-list" class="movie-list-scroll" style="flex:1;"></div>
            </div>
        </div>

    </div><!-- /row -->

    <hr class="ai-divider">
    <p class="ai-footer">
        Próxima etapa: adaptar <code>treinamento.js</code> para treinar o modelo de recomendação
        diretamente no browser com TensorFlow.js.
    </p>

</div><!-- /ai-content -->

<!-- ── Modal de Treinamento ─────────────────────────────────────────────────── -->
@include('modals.treinar-modal')

<!-- ── Scripts ──────────────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-vis@latest"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/treinamento.js') }}?v={{ time() }}"></script>

<script>
/* ════════════════════════════════════════════════════════════════════════════
   App State
   ════════════════════════════════════════════════════════════════════════════ */
const AVATAR_COLORS = ['#1677ff','#52c41a','#fa8c16','#eb2f96','#722ed1','#13c2c2','#faad14','#f5222d'];
const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const GENRE_STYLE = {
    'Action':      { bg:'#fff1f0', color:'#cf1322', bd:'#ffa39e' },
    'Adventure':   { bg:'#fff7e6', color:'#d46b08', bd:'#ffd591' },
    'Animation':   { bg:'#e6fffb', color:'#08979c', bd:'#87e8de' },
    'Biography':   { bg:'#f9f0ff', color:'#531dab', bd:'#d3adf7' },
    'Comedy':      { bg:'#fffbe6', color:'#d48806', bd:'#ffe58f' },
    'Crime':       { bg:'#fff2e8', color:'#d4380d', bd:'#ffbb96' },
    'Documentary': { bg:'#f0f5ff', color:'#1d39c4', bd:'#adc6ff' },
    'Drama':       { bg:'#e6f4ff', color:'#0958d9', bd:'#91caff' },
    'Family':      { bg:'#f6ffed', color:'#389e0d', bd:'#b7eb8f' },
    'Fantasy':     { bg:'#fff0f6', color:'#c41d7f', bd:'#ffadd2' },
    'History':     { bg:'#fcffe6', color:'#5b8c00', bd:'#eaff8f' },
    'Horror':      { bg:'#fff1f0', color:'#cf1322', bd:'#ffa39e' },
    'Music':       { bg:'#e6fffb', color:'#08979c', bd:'#87e8de' },
    'Musical':     { bg:'#fff0f6', color:'#c41d7f', bd:'#ffadd2' },
    'Mystery':     { bg:'#f9f0ff', color:'#531dab', bd:'#d3adf7' },
    'Romance':     { bg:'#fff0f6', color:'#c41d7f', bd:'#ffadd2' },
    'Sci-Fi':      { bg:'#f0f5ff', color:'#1d39c4', bd:'#adc6ff' },
    'Thriller':    { bg:'#fff2e8', color:'#d4380d', bd:'#ffbb96' },
    'War':         { bg:'#fafafa', color:'#595959', bd:'#d9d9d9' },
    'Western':     { bg:'#fff7e6', color:'#d46b08', bd:'#ffd591' },
};

const app = {
    users:           [],
    selectedUser:    null,
    userDetail:      null,
    movies:          [],   // resultado bruto da API
    reco:            [],
    searchQuery:     '',
    sortBy:          'rate',
    filterWatched:   false,
    pendingRatings:  {},   // { movieId: 1-5 }
    searchTimer:     null,
};

/* ════════════════════════════════════════════════════════════════════════════
   Helpers
   ════════════════════════════════════════════════════════════════════════════ */
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

function avatarHtml(name, id, size = 40) {
    const color    = AVATAR_COLORS[id % AVATAR_COLORS.length];
    const initials = name.split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
    return `<div class="ai-avatar" style="width:${size}px;height:${size}px;background:${color};font-size:${Math.round(size/3)}px;">${initials}</div>`;
}

function genreTagsHtml(genreStr) {
    return (genreStr || '').split(',').map(g => g.trim()).filter(Boolean).map(g => {
        const s = GENRE_STYLE[g] || { bg:'#fafafa', color:'#595959', bd:'#d9d9d9' };
        return `<span class="ai-genre-tag" style="background:${s.bg};color:${s.color};border-color:${s.bd};">${esc(g)}</span>`;
    }).join('');
}

function starsHtml(movieId, rating) {
    return [1,2,3,4,5].map(n => {
        const filled = n <= rating;
        return `<i class="bi ${filled ? 'bi-star-fill' : 'bi-star'} star-icon"
                   data-mid="${movieId}" data-star="${n}"
                   style="color:${filled ? '#faad14' : '#d9d9d9'};"></i>`;
    }).join('');
}

function movieCardHtml(movie, ratedIds, isHighlight = false) {
    const isWatched  = ratedIds.includes(movie.id);
    const rating     = app.pendingRatings[movie.id] ?? 3;
    const hasUser    = !!app.selectedUser;

    const watchedBadge = isWatched
        ? `<span class="ai-tag ai-tag--green" style="font-size:10px;padding:0 5px;"><i class="bi bi-check-circle-fill"></i> Assistido</span>`
        : '';

    const btnLabel = isWatched ? 'Atualizar' : 'Marcar como assistido';
    const btnClass = isWatched ? 'ai-btn--default' : 'ai-btn--primary';

    return `
    <div class="movie-card${isHighlight ? ' movie-card--highlight' : ''}" data-mid="${movie.id}">
        <div class="movie-card__left">
            <div class="movie-card__title" title="${esc(movie.name)}">${esc(movie.name)}</div>
            <div class="movie-card__meta">
                ${movie.release_year ? `<span>${movie.release_year}</span>` : ''}
                ${movie.rate        ? `<span class="imdb-tag"><i class="bi bi-star-fill" style="color:#faad14;font-size:9px;"></i> ${parseFloat(movie.rate).toFixed(1)}</span>` : ''}
                ${movie.duration    ? `<span>${movie.duration} min</span>` : ''}
                ${watchedBadge}
            </div>
            <div class="movie-card__genres">${genreTagsHtml(movie.genre)}</div>
        </div>
        <div class="movie-card__right">
            <div class="stars-row" id="stars-${movie.id}">${starsHtml(movie.id, rating)}</div>
            <button class="ai-btn ${btnClass} btn-marcar"
                    data-mid="${movie.id}"
                    style="font-size:11px;padding:3px 10px;height:auto;"
                    ${hasUser ? '' : 'disabled'}>
                ${btnLabel}
            </button>
        </div>
    </div>`;
}

/* ════════════════════════════════════════════════════════════════════════════
   Render
   ════════════════════════════════════════════════════════════════════════════ */
function renderUserProfile() {
    const u      = app.selectedUser;
    const detail = app.userDetail;
    if (!u) { $('#card-user-profile').addClass('d-none'); return; }

    const color    = AVATAR_COLORS[u.id % AVATAR_COLORS.length];
    const count    = detail?.ratings_count ?? u.ratings_count;
    const genres   = (u.favorite_genres ?? []);
    const genreTags = genres.length
        ? genres.map((g,i) => {
            const colors = ['#1677ff','#52c41a','#fa8c16','#722ed1','#13c2c2','#faad14','#eb2f96','#d4380d'];
            return `<span class="ai-tag" style="font-size:11px;padding:0 6px;border-color:${colors[i%8]}33;color:${colors[i%8]};background:${colors[i%8]}11;">${esc(g)}</span>`;
          }).join('')
        : `<span class="text-second" style="font-size:12px;">Sem gêneros favoritos</span>`;

    const coldStart = u.is_cold_start
        ? `<span class="ai-tag ai-tag--orange" style="font-size:11px;"><i class="bi bi-flask"></i> Cold Start</span>`
        : '';

    $('#card-user-profile')
        .css('border-left-color', color)
        .removeClass('d-none')
        .html(`
            <div class="d-flex align-items-center gap-3 flex-wrap">
                ${avatarHtml(u.name, u.id, 52)}
                <div style="flex:1;min-width:0;">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <strong style="font-size:15px;">${esc(u.name)}</strong>
                        <span class="text-second" style="font-size:13px;">${u.age} anos</span>
                        ${coldStart}
                        <span class="ai-tag ai-tag--blue" style="font-size:11px;">${count} ${count === 1 ? 'filme avaliado' : 'filmes avaliados'}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">${genreTags}</div>
                </div>
            </div>
        `);
}

function getFilteredMovies() {
    let list = [...app.movies];
    const ratedIds = app.userDetail?.rated_movie_ids ?? [];

    if (app.filterWatched) {
        list = list.filter(m => ratedIds.includes(m.id));
    }
    if (app.sortBy === 'date') {
        list.sort((a, b) => (b.release_year || 0) - (a.release_year || 0));
    } else if (app.sortBy === 'az') {
        list.sort((a, b) => a.name.localeCompare(b.name));
    }
    // 'rate' já vem ordenado da API
    return list;
}

function renderMovies() {
    const ratedIds = app.userDetail?.rated_movie_ids ?? [];
    const list     = getFilteredMovies();

    $('#movies-loading').addClass('d-none');

    if (list.length === 0) {
        $('#movies-empty').removeClass('d-none');
        $('#movies-list').html('');
        return;
    }

    $('#movies-empty').addClass('d-none');
    $('#movies-list').html(list.map(m => movieCardHtml(m, ratedIds)).join(''));
}

function renderReco() {
    const ratedIds = app.userDetail?.rated_movie_ids ?? [];

    if (app.reco.length === 0) {
        $('#reco-empty').removeClass('d-none');
        $('#reco-list').html('');
        return;
    }
    $('#reco-empty').addClass('d-none');
    $('#reco-list').html(app.reco.map(m => movieCardHtml(m, ratedIds, true)).join(''));
}

/* ════════════════════════════════════════════════════════════════════════════
   API calls
   ════════════════════════════════════════════════════════════════════════════ */
async function loadUsers() {
    const res   = await fetch('/api/users');
    app.users   = await res.json();

    // Preenche o select com as opções
    const $sel = $('#select-user');
    $sel.empty().append('<option></option>');
    app.users.forEach(u => $sel.append(new Option(u.name, u.id)));

    // Inicializa Select2 com template personalizado
    $sel.select2({
        placeholder:       'Digite o nome para buscar...',
        allowClear:        true,
        width:             '100%',
        dropdownAutoWidth: false,
        templateResult:    renderUserOption,
        templateSelection: opt => opt.id ? app.users.find(u => u.id == opt.id)?.name ?? opt.text : opt.text,
    });

    // Mostra o select, esconde o loading
    $('#user-select-loading').addClass('d-none');
    $sel.removeClass('d-none');

    // Atualiza badge de contagem
    $('#users-count').text(`${app.users.length} usuários`).removeClass('d-none');
}

function renderUserOption(opt) {
    if (!opt.id) return opt.text;
    const u = app.users.find(u => u.id == opt.id);
    if (!u) return opt.text;

    const color    = AVATAR_COLORS[u.id % AVATAR_COLORS.length];
    const initials = u.name.split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
    const genres   = (u.favorite_genres ?? []).slice(0,2)
        .map(g => `<span style="font-size:10px;padding:0 5px;background:#f5f5f5;border-radius:3px;border:1px solid #d9d9d9;color:#595959;">${esc(g)}</span>`)
        .join('');
    const cold = u.is_cold_start
        ? `<span style="font-size:10px;padding:0 5px;background:#fff7e6;border-radius:3px;border:1px solid #ffd591;color:#d46b08;">Cold Start</span>`
        : '';

    return $(`
        <div style="display:flex;align-items:center;gap:8px;padding:2px 0;">
            <div style="width:28px;height:28px;border-radius:50%;background:${color};display:flex;align-items:center;
                        justify-content:center;color:#fff;font-size:11px;font-weight:700;flex-shrink:0;">
                ${initials}
            </div>
            <span style="font-weight:500;">${esc(u.name)}</span>
            <span style="color:#999;font-size:12px;">${u.age} anos</span>
            ${cold}${genres}
        </div>
    `);
}

async function selectUser(userId) {
    if (!userId) {
        app.selectedUser = null;
        app.userDetail   = null;
        app.reco         = [];
        renderUserProfile();
        resetRecoPanel();
        updateMoviesPanel();
        return;
    }

    app.selectedUser = app.users.find(u => u.id == userId) ?? null;
    app.userDetail   = null;
    app.reco         = [];
    renderUserProfile();
    updateMoviesPanel();

    // Carrega detalhes e recomendações em paralelo
    showRecoLoading(true);

    const [detailRes, recoRes] = await Promise.allSettled([
        fetch(`/api/users/${userId}`).then(r => r.json()),
        fetch(`/api/recommendations/${userId}`).then(r => r.json()),
    ]);

    if (detailRes.status === 'fulfilled') {
        app.userDetail = detailRes.value;
        renderUserProfile();
        updateMoviesPanel();
        renderMovies(); // re-render para atualizar badges "Assistido"
    }

    if (recoRes.status === 'fulfilled') {
        app.reco = recoRes.value.recommendations ?? [];
        showRecoLoading(false);

        // Atualiza título
        const firstName = app.selectedUser?.name?.split(' ')[0] ?? '';
        $('#reco-title').text(`Recomendações para ${firstName}`);
        $('#reco-algo-tag').removeClass('d-none');

        // Alerta do algoritmo
        const data = recoRes.value;
        const msg  = data.is_cold_start
            ? 'Cold Start: sem gêneros favoritos — mostrando os filmes mais bem avaliados do IMDB.'
            : `Filtro por gêneros: ${(data.favorite_genres ?? []).slice(0,3).join(', ')}${(data.favorite_genres?.length ?? 0) > 3 ? '...' : ''}. O TF.js substituirá isto com um modelo treinado no browser.`;
        $('#reco-algo-text').text(msg);
        $('#reco-algo-alert').removeClass('d-none');

        renderReco();
    } else {
        showRecoLoading(false);
    }
}

async function loadMovies(query = '') {
    $('#movies-loading').removeClass('d-none');
    $('#movies-empty').addClass('d-none');
    $('#movies-list').html('');

    const url = `/api/movies?per_page=50${query ? `&search=${encodeURIComponent(query)}` : ''}`;
    const res = await fetch(url);
    const data = await res.json();

    app.movies = data.data ?? [];

    const total = data.total ?? 0;
    $('#movies-total-tag')
        .text(`${total.toLocaleString('pt-BR')} filmes`)
        .removeClass('d-none');

    renderMovies();
}

async function saveRating(movieId, rating) {
    if (!app.selectedUser) return;

    const res = await fetch('/api/ratings', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body:    JSON.stringify({ user_id: app.selectedUser.id, movie_id: movieId, rating }),
    });

    if (!res.ok) return;

    // Atualiza rated_movie_ids localmente
    if (app.userDetail) {
        if (!app.userDetail.rated_movie_ids.includes(movieId)) {
            app.userDetail.rated_movie_ids.push(movieId);
            app.userDetail.ratings_count = (app.userDetail.ratings_count ?? 0) + 1;
        }
        renderUserProfile();
    }

    renderMovies();
    renderReco();
}

/* ════════════════════════════════════════════════════════════════════════════
   Panel helpers
   ════════════════════════════════════════════════════════════════════════════ */
function resetRecoPanel() {
    $('#reco-title').text('Recomendações');
    $('#reco-algo-tag').addClass('d-none');
    $('#reco-algo-alert').addClass('d-none');
    $('#reco-empty').addClass('d-none');
    $('#reco-list').html('');
    $('#reco-no-user').removeClass('d-none');
}

function showRecoLoading(show) {
    $('#reco-no-user').addClass('d-none');
    $('#reco-empty').addClass('d-none');
    $('#reco-list').html('');
    if (show) {
        $('#reco-loading').removeClass('d-none');
    } else {
        $('#reco-loading').addClass('d-none');
    }
}

function updateMoviesPanel() {
    const hasUser = !!app.selectedUser;
    $('#no-user-movies-alert').toggleClass('d-none', hasUser);
    $('#filter-watched').prop('disabled', !hasUser);
    renderMovies();
}

/* ════════════════════════════════════════════════════════════════════════════
   Events
   ════════════════════════════════════════════════════════════════════════════ */
$(document).ready(async function () {

    await loadUsers();
    await loadMovies();

    // Seleção de usuário
    $('#select-user').on('change', function () {
        selectUser($(this).val());
    });

    // Busca com debounce
    $('#search-movies').on('input', function () {
        clearTimeout(app.searchTimer);
        app.searchQuery = $(this).val().trim();
        app.searchTimer = setTimeout(() => loadMovies(app.searchQuery), 450);
    });

    // Filtro "só assistidos"
    $('#filter-watched').on('change', function () {
        app.filterWatched = $(this).is(':checked');
        renderMovies();
    });

    // Sort
    $('#sort-movies').on('change', function () {
        app.sortBy = $(this).val();
        renderMovies();
    });

    // Click nas estrelas (delegado)
    $(document).on('click', '.star-icon', function () {
        const mid    = parseInt($(this).data('mid'));
        const rating = parseInt($(this).data('star'));
        app.pendingRatings[mid] = rating;
        $(`#stars-${mid}`).html(starsHtml(mid, rating));
    });

    // Botão marcar como assistido (delegado)
    $(document).on('click', '.btn-marcar', async function () {
        const mid    = parseInt($(this).data('mid'));
        const rating = app.pendingRatings[mid] ?? 3;

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        await saveRating(mid, rating);
    });
});
</script>

</body>
</html>
