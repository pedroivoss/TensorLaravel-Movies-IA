
/* ════════════════════════════════════════════════════════════════════════════
App State
════════════════════════════════════════════════════════════════════════════ */
const AVATAR_COLORS = ['#1677ff', '#52c41a', '#fa8c16', '#eb2f96', '#722ed1', '#13c2c2', '#faad14', '#f5222d'];
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const GENRE_STYLE = {
    'Action': { bg: '#fff1f0', color: '#cf1322', bd: '#ffa39e' },
    'Adventure': { bg: '#fff7e6', color: '#d46b08', bd: '#ffd591' },
    'Animation': { bg: '#e6fffb', color: '#08979c', bd: '#87e8de' },
    'Biography': { bg: '#f9f0ff', color: '#531dab', bd: '#d3adf7' },
    'Comedy': { bg: '#fffbe6', color: '#d48806', bd: '#ffe58f' },
    'Crime': { bg: '#fff2e8', color: '#d4380d', bd: '#ffbb96' },
    'Documentary': { bg: '#f0f5ff', color: '#1d39c4', bd: '#adc6ff' },
    'Drama': { bg: '#e6f4ff', color: '#0958d9', bd: '#91caff' },
    'Family': { bg: '#f6ffed', color: '#389e0d', bd: '#b7eb8f' },
    'Fantasy': { bg: '#fff0f6', color: '#c41d7f', bd: '#ffadd2' },
    'History': { bg: '#fcffe6', color: '#5b8c00', bd: '#eaff8f' },
    'Horror': { bg: '#fff1f0', color: '#cf1322', bd: '#ffa39e' },
    'Music': { bg: '#e6fffb', color: '#08979c', bd: '#87e8de' },
    'Musical': { bg: '#fff0f6', color: '#c41d7f', bd: '#ffadd2' },
    'Mystery': { bg: '#f9f0ff', color: '#531dab', bd: '#d3adf7' },
    'Romance': { bg: '#fff0f6', color: '#c41d7f', bd: '#ffadd2' },
    'Sci-Fi': { bg: '#f0f5ff', color: '#1d39c4', bd: '#adc6ff' },
    'Thriller': { bg: '#fff2e8', color: '#d4380d', bd: '#ffbb96' },
    'War': { bg: '#fafafa', color: '#595959', bd: '#d9d9d9' },
    'Western': { bg: '#fff7e6', color: '#d46b08', bd: '#ffd591' },
};

const app = {
    users: [],
    selectedUser: null,
    userDetail: null,
    movies: [],   // resultado bruto da API
    reco: [],
    searchQuery: '',
    sortBy: 'rate',
    filterWatched: false,
    pendingRatings: {},   // { movieId: 1-5 }
    searchTimer: null,
};

window.app = app;

/* ════════════════════════════════════════════════════════════════════════════
Helpers
════════════════════════════════════════════════════════════════════════════ */
const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

function avatarHtml(name, id, size = 40) {
    const color = AVATAR_COLORS[id % AVATAR_COLORS.length];
    const initials = name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
    return `<div class="ai-avatar" style="width:${size}px;height:${size}px;background:${color};font-size:${Math.round(size / 3)}px;">${initials}</div>`;
}

function genreTagsHtml(genreStr) {
    return (genreStr || '').split(',').map(g => g.trim()).filter(Boolean).map(g => {
        const s = GENRE_STYLE[g] || { bg: '#fafafa', color: '#595959', bd: '#d9d9d9' };
        return `<span class="ai-genre-tag" style="background:${s.bg};color:${s.color};border-color:${s.bd};">${esc(g)}</span>`;
    }).join('');
}

function starsHtml(movieId, rating) {
    return [1, 2, 3, 4, 5].map(n => {
        const filled = n <= rating;
        return `<i class="bi ${filled ? 'bi-star-fill' : 'bi-star'} star-icon"
                    data-mid="${movieId}" data-star="${n}"
                    style="color:${filled ? '#faad14' : '#d9d9d9'};"></i>`;
    }).join('');
}

function movieCardHtml(movie, ratedIds, isHighlight = false) {
    const isWatched = ratedIds.includes(movie.id);
    const rating = app.pendingRatings[movie.id] ?? 3;
    const hasUser = !!app.selectedUser;

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
                    ${movie.rate ? `<span class="imdb-tag"><i class="bi bi-star-fill" style="color:#faad14;font-size:9px;"></i> ${parseFloat(movie.rate).toFixed(1)}</span>` : ''}
                    ${movie.duration ? `<span>${movie.duration} min</span>` : ''}
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
    const u = app.selectedUser;
    const detail = app.userDetail;
    if (!u) { $('#card-user-profile').addClass('d-none'); return; }

    const color = AVATAR_COLORS[u.id % AVATAR_COLORS.length];
    const count = detail?.ratings_count ?? u.ratings_count;
    const genres = (u.favorite_genres ?? []);
    const genreTags = genres.length
        ? genres.map((g, i) => {
            const colors = ['#1677ff', '#52c41a', '#fa8c16', '#722ed1', '#13c2c2', '#faad14', '#eb2f96', '#d4380d'];
            return `<span class="ai-tag" style="font-size:11px;padding:0 6px;border-color:${colors[i % 8]}33;color:${colors[i % 8]};background:${colors[i % 8]}11;">${esc(g)}</span>`;
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
    // O filtro "só assistidos" é resolvido pelo servidor (POST /api/movies com ids).
    // Aqui só aplicamos a ordenação client-side sobre o que já foi carregado.
    let list = [...app.movies];

    if (app.sortBy === 'date') {
        list.sort((a, b) => (b.release_year || 0) - (a.release_year || 0));
    } else if (app.sortBy === 'az') {
        list.sort((a, b) => a.name.localeCompare(b.name));
    }
    return list;
}

function renderMovies() {
    const ratedIds = app.userDetail?.rated_movie_ids ?? [];
    const list = getFilteredMovies();

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
    const res = await fetch('/api/users');
    app.users = await res.json();

    // Preenche o select com as opções
    const $sel = $('#select-user');
    $sel.empty().append('<option></option>');
    app.users.forEach(u => $sel.append(new Option(u.name, u.id)));

    // Inicializa Select2 com template personalizado
    $sel.select2({
        placeholder: 'Digite o nome para buscar...',
        allowClear: true,
        width: '100%',
        dropdownAutoWidth: false,
        templateResult: renderUserOption,
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

    const color = AVATAR_COLORS[u.id % AVATAR_COLORS.length];
    const initials = u.name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
    const genres = (u.favorite_genres ?? []).slice(0, 2)
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
        app.userDetail = null;
        app.reco = [];
        // Reseta o filtro "só assistidos" ao desselecionar usuário
        app.filterWatched = false;
        $('#filter-watched').prop('checked', false);
        renderUserProfile();
        resetRecoPanel();
        updateMoviesPanel();
        await loadMovies(); // volta para o browse normal
        return;
    }

    app.selectedUser = app.users.find(u => u.id == userId) ?? null;
    app.userDetail = null;
    app.reco = [];
    renderUserProfile();
    updateMoviesPanel();

    // Carrega detalhes e recomendações em paralelo
    showRecoLoading(true);

    // 1. Buscamos os detalhes do usuário (obrigatório para saber o que ele já assistiu)
    const detailRes = await fetch(`/api/users/${userId}`).then(r => r.json());

    if (detailRes) {
        app.userDetail = detailRes;
        renderUserProfile();
        updateMoviesPanel();

        if (app.filterWatched) {
            await loadMovies();
        } else {
            renderMovies();
        }
    }

    // 2. Lógica de Recomendações: IA Local vs API Laravel
    // Verificamos se o modelo foi treinado no worker e está disponível
    const isIaReady = (typeof getRecommendations === 'function' || typeof window.getRecommendations === 'function') && (window._model || _model);

    if (isIaReady) {
        console.log("Gerando recomendações via Inteligência Artificial local...");

        const fn = window.getRecommendations || getRecommendations;
        const recommendations = await fn(userId);

        // Chamamos a função de predição do Worker[cite: 4]
        //const recommendations = await window.getRecommendations(userId);
        app.reco = recommendations;

        const firstName = app.selectedUser?.name?.split(' ')[0] ?? '';
        $('#reco-title').text(`Sugestões de IA para ${firstName}`);

        // Ajustamos o alerta para indicar sucesso da IA[cite: 5, 7]
        $('#reco-algo-tag').removeClass('d-none').html('<i class="bi bi-robot"></i> Inteligência Artificial');
        $('#reco-algo-text').text("Baseado no modelo treinado localmente com seu histórico e preferências.");
        $('#reco-algo-alert').removeClass('d-none').removeClass('ai-alert--warning').addClass('ai-alert--success');

        showRecoLoading(false);
        renderReco();
    } else {
        // Fallback: Se a IA não foi treinada, usa a lógica simples do servidor[cite: 12]
        console.log("IA não treinada. Usando recomendações baseadas em gêneros (API)...");
        const recoRes = await fetch(`/api/recommendations/${userId}`).then(r => r.json());

        app.reco = recoRes.recommendations ?? [];
        showRecoLoading(false);

        const firstName = app.selectedUser?.name?.split(' ')[0] ?? '';
        $('#reco-title').text(`Recomendações para ${firstName}`);
        $('#reco-algo-tag').removeClass('d-none').text('Filtro por Gêneros');

        const data = recoRes;
        const msg = data.is_cold_start
            ? 'Cold Start: sem histórico ou gêneros — mostrando os mais bem avaliados.'
            : `Filtro por gêneros: ${(data.favorite_genres ?? []).slice(0, 3).join(', ')}. Clique em "Treinar Modelo" para ativar a IA.`;

        $('#reco-algo-text').text(msg);
        $('#reco-algo-alert').removeClass('d-none').addClass('ai-alert--warning');

        renderReco();
    }
}

async function loadMovies() {
    $('#movies-loading').removeClass('d-none');
    $('#movies-empty').addClass('d-none');
    $('#movies-list').html('');

    const ratedIds = app.userDetail?.rated_movie_ids ?? [];

    if (app.filterWatched) {
        // POST: busca no servidor TODOS os filmes assistidos pelo usuário (sem limite)
        if (ratedIds.length === 0) {
            app.movies = [];
            $('#movies-loading').addClass('d-none');
            $('#movies-total-tag').text('0 filmes assistidos').removeClass('d-none');
            renderMovies();
            return;
        }

        const res = await fetch('/api/movies', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ ids: ratedIds, search: app.searchQuery }),
        });
        const data = await res.json();
        app.movies = data.data ?? [];
        $('#movies-total-tag')
            .text(`${(data.total ?? 0).toLocaleString('pt-BR')} filmes assistidos`)
            .removeClass('d-none');
    } else {
        const res = await fetch('/api/movies', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ search: app.searchQuery }),
        });

        const data = await res.json();
        app.movies = data.data ?? [];
        $('#movies-total-tag')
            .text(`${(data.total ?? 0).toLocaleString('pt-BR')} filmes`)
            .removeClass('d-none');
    }

    renderMovies();
}

async function saveRating(movieId, rating) {
    if (!app.selectedUser) return;

    const res = await fetch('/api/ratings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ user_id: app.selectedUser.id, movie_id: movieId, rating }),
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

async function loadModelFromDatabase() {
    try {
        const res = await fetch('/api/ai-models/load').then(r => r.json());

        if (!res || !res.weights_base64 || !res.model_topology) {
            console.log("ℹ️ Nenhum modelo no banco.");
            return;
        }

        // 1. Converte o Base64 que veio do banco para ArrayBuffer
        const weightData = Uint8Array.from(atob(res.weights_base64), c => c.charCodeAt(0)).buffer;

        // 2. Garante que a topologia seja um objeto
        const modelTopology = typeof res.model_topology === 'string'
            ? JSON.parse(res.model_topology)
            : res.model_topology;

        // 3. A MONTAGEM CORRETA PARA O TF.JS (Onde você estava errando):
        // O fromMemory espera um objeto que contenha modelTopology e weightSpecs/weightData
        // formatados exatamente como um "WeightsManifest".

        const weightSpecs = modelTopology.weightsManifest[0].weights;

        const modelJSON = {
            modelTopology: modelTopology,
            weightSpecs: weightSpecs,
            weightData: weightData
        };

        // 4. Carrega. Se der erro aqui, é a versão do TFJS ou o Shape (56).
        const loadedModel = await tf.loadLayersModel(tf.io.fromMemory({
            modelTopology: modelJSON.modelTopology,
            weightSpecs: modelJSON.weightSpecs,
            weightData: modelJSON.weightData
        }));

        _model = loadedModel;
        window._model = loadedModel;

        _globalCtx = makeContext(app.movies, app.users);
        window._globalCtx = _globalCtx;

        console.log("🚀 IA carregada do banco e pronta para recomendar!");
    } catch (err) {
        console.error(" ❌ Falha ao carregar modelo:", err);
    }
}
