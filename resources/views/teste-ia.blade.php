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
    <link href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}" rel="stylesheet">

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
{{--<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest"></script>--}}
<!-- TensorFlow.js Core -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs/dist/tf.min.js"></script>
<!-- Biblioteca de Visualização (Visor) -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-vis/dist/tfjs-vis.umd.min.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('assets/js/pageBlade.js') }}?v={{ filemtime(public_path('assets/js/pageBlade.js')) }}"></script>
<script src="{{ asset('assets/js/TFVisorView.js') }}?v={{ filemtime(public_path('assets/js/TFVisorView.js')) }}"></script>
<script src="{{ asset('assets/js/workers/modelTrainingWorker.js') }}?v={{ filemtime(public_path('assets/js/workers/modelTrainingWorker.js')) }}"></script>

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

        // Busca com debounce — re-fetch no servidor
        $('#search-movies').on('input', function () {
            clearTimeout(app.searchTimer);
            app.searchQuery = $(this).val().trim();
            app.searchTimer = setTimeout(() => loadMovies(), 450);
        });

        // Filtro "só assistidos" — re-fetch: GET normal ou POST com IDs do usuário
        $('#filter-watched').on('change', function () {
            app.filterWatched = $(this).is(':checked');
            loadMovies();
        });

        // Sort — ordenação client-side, sem re-fetch
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

        $(document).on('click', '#btn-treinar', async function () {
            await trainModel();
        });

    });
</script>

</body>
</html>
