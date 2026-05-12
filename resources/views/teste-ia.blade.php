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

    <style>
        /* ── Terminal card ──────────────────────────────── */
        .console-card {
            background: #1e1e2e;
            border-radius: var(--radius-card);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            height: 100%;
        }
        .console-titlebar {
            background: #2a2a3e;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #3a3a5a;
        }
        .console-dots { display: flex; gap: 6px; margin-right: 4px; }
        .console-dot  { width: 12px; height: 12px; border-radius: 50%; }
        .console-dot--red    { background: #ff5f57; }
        .console-dot--yellow { background: #ffbd2e; }
        .console-dot--green  { background: #28c840; }
        .console-titlebar-label {
            color: #6e7681;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 12px;
        }
        .console-tag-term {
            margin-left: auto;
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 4px;
            padding: 1px 8px;
            color: #58a6ff;
            font-size: 11px;
            white-space: nowrap;
        }
        .console-clear-btn {
            background: transparent;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #6e7681;
            padding: 1px 10px;
            font-size: 11px;
            cursor: pointer;
            font-family: 'Consolas', monospace;
            transition: border-color .15s, color .15s;
            flex-shrink: 0;
        }
        .console-clear-btn:hover { border-color: #484f58; color: #c9d1d9; }

        /* ── Terminal output area ───────────────────────── */
        .console-panel {
            background: #0d1117;
            padding: 12px 16px;
            height: 200px;
            overflow-y: auto;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.8;
        }
        .console-panel::-webkit-scrollbar       { width: 6px; }
        .console-panel::-webkit-scrollbar-track { background: #161b22; }
        .console-panel::-webkit-scrollbar-thumb { background: #30363d; border-radius: 3px; }
        .console-panel::-webkit-scrollbar-thumb:hover { background: #484f58; }

        .console-entry { display: flex; gap: 10px; min-width: 0; padding: 1px 0; }
        .console-time  {
            color: #484f58;
            flex-shrink: 0;
            user-select: none;
            font-size: 11px;
            padding-top: 2px;
        }
        .console-log         { color: #c9d1d9; }
        .console-warn        { color: #e3b341; }
        .console-error       { color: #f85149; }
        .console-placeholder { color: #30363d; font-style: italic; }
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

    <!-- ── 1. User selector + Console do Modelo ────────────────────────────── -->
    <div class="row g-3 mb-3">

        <!-- Seletor de usuário (compacto) -->
        <div class="col-12 col-lg-4">
            <div class="ai-card h-100">
                <div class="ai-card__header">
                    <i class="bi bi-person fs-5 icon-blue"></i>
                    <h2 class="ai-card__title">Selecione um Usuário</h2>
                    <span id="users-count" class="ai-tag ai-tag--blue d-none"></span>
                </div>
                <div id="user-select-loading" class="d-flex align-items-center gap-2 text-second" style="font-size:13px;">
                    <div class="spinner-border spinner-border-sm" role="status" style="color:var(--primary);"></div>
                    Carregando usuários...
                </div>
                <select id="select-user" class="w-100 d-none"></select>
            </div>
        </div>

        <!-- Console do Modelo — terminal UI -->
        <div class="col-12 col-lg-8">
            <div class="console-card">

                <div class="console-titlebar">
                    <div class="console-dots">
                        <div class="console-dot console-dot--red"></div>
                        <div class="console-dot console-dot--yellow"></div>
                        <div class="console-dot console-dot--green"></div>
                    </div>
                    <span class="console-titlebar-label">modelTrainingWorker.js</span>
                    <span class="console-tag-term">IA Log</span>
                    <button id="btn-clear-console" class="console-clear-btn ms-2">clear</button>
                </div>

                <div id="console-log-panel" class="console-panel">
                    <div class="console-entry console-placeholder">
                        <span class="console-time">—</span>
                        <span>Aguardando atividade do modelo...</span>
                    </div>
                </div>

            </div>
        </div>

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
        Modelo treinado com <code>TensorFlow.js</code> diretamente no browser — use o botão
        <strong>Treinar Modelo</strong> para iniciar o treinamento e acompanhe as métricas no visor.
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
<script>
    /* ── Console interceptor: captura saídas do ModelTrainingWorker ── */
    (function () {
        const _log   = console.log.bind(console);
        const _warn  = console.warn.bind(console);
        const _error = console.error.bind(console);
        let _firstEntry = true;

        function addEntry(level, args) {
            const panel = document.getElementById('console-log-panel');
            if (!panel) return;

            if (_firstEntry) {
                panel.innerHTML = '';
                _firstEntry = false;
            }

            const time = new Date().toTimeString().slice(0, 8);

            const entry   = document.createElement('div');
            entry.className = `console-entry console-${level}`;

            const timeSpan = document.createElement('span');
            timeSpan.className = 'console-time';
            timeSpan.textContent = time;

            const msgSpan = document.createElement('span');
            msgSpan.style.wordBreak = 'break-all';
            msgSpan.textContent = args.map(a => {
                if (a === null || a === undefined) return String(a);
                if (typeof a === 'object') { try { return JSON.stringify(a); } catch { return String(a); } }
                return String(a);
            }).join(' ');

            entry.appendChild(timeSpan);
            entry.appendChild(msgSpan);

            const entries = panel.querySelectorAll('.console-entry');
            if (entries.length >= 200) entries[0].remove();

            panel.appendChild(entry);
            panel.scrollTop = panel.scrollHeight;
        }

        console.log   = function (...a) { _log(...a);   addEntry('log',   a); };
        console.warn  = function (...a) { _warn(...a);  addEntry('warn',  a); };
        console.error = function (...a) { _error(...a); addEntry('error', a); };

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('btn-clear-console')?.addEventListener('click', function () {
                const panel = document.getElementById('console-log-panel');
                panel.innerHTML = '<div class="console-entry console-placeholder"><span class="console-time">—</span><span>Console limpo.</span></div>';
            });
        });
    })();
</script>
<script src="{{ asset('assets/js/workers/modelTrainingWorker.js') }}?v={{ filemtime(public_path('assets/js/workers/modelTrainingWorker.js')) }}"></script>

<script>
    /* ════════════════════════════════════════════════════════════════════════════
    Events
    ════════════════════════════════════════════════════════════════════════════ */
    $(document).ready(async function () {
        // 1. Carrega os usuários e filmes iniciais da API
        await loadUsers();
        await loadMovies();

        // 2. SOLUÇÃO DEFINITIVA: Inicializa o contexto global da IA
        // Isso deve acontecer ANTES de qualquer tentativa de recomendação
        if (typeof makeContext === 'function') {
            _globalCtx = makeContext(app.movies, app.users);
            window._globalCtx = _globalCtx;
            console.log("✅ Contexto da IA (Data Context) sincronizado.");
        } else {
            console.error("❌ Função makeContext não encontrada. Verifique a ordem dos scripts.");
        }

        // 3. Tenta carregar o modelo salvo no MySQL
        await loadModelFromDatabase();

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

        // Botão remover dos assistidos (delegado)
        $(document).on('click', '.btn-remover', async function () {
            const mid = parseInt($(this).data('mid'));
            const btn = $(this);

            const result = await Swal.fire({
                title: 'Remover dos assistidos?',
                text: 'A nota será apagada do banco de dados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#cf1322',
                reverseButtons: true,
            });

            if (!result.isConfirmed) return;

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            await removeRating(mid);
        });

        // No bloco de script do Blade, ajuste o evento do botão treinar:
        $(document).on('click', '#btn-treinar', async function () {
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Treinando...');

            await trainModel(); // Chama a função no worker[cite: 4]

            btn.html('<i class="bi bi-check-lg"></i> Modelo Pronto');

            // Se houver um usuário selecionado, atualiza as recomendações agora com a IA
            if (app.selectedUser) {
                selectUser(app.selectedUser.id);
            }
        });

    });
</script>

</body>
</html>
