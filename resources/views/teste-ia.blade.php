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
<script src="{{ asset('assets/js/workers/modelTrainingWorker.js') }}?v={{ filemtime(public_path('assets/js/workers/modelTrainingWorker.js')) }}"></script>
<script src="{{ asset('assets/js/pageBlade.js') }}?v={{ filemtime(public_path('assets/js/pageBlade.js')) }}"></script>

<script></script>

<script>
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
