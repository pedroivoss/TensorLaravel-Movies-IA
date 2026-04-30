/**
 * services/api.js
 *
 * Camada de comunicação com a API Laravel.
 * Centraliza todas as chamadas HTTP aqui para que os componentes React
 * não precisem conhecer URLs ou headers manualmente.
 *
 * Quando o TF.js for integrado, o Web Worker também importará daqui
 * os dados brutos necessários para o treinamento.
 */
import axios from 'axios';

// Lê o token CSRF do meta tag gerado pelo Blade (@vite já inclui isso)
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const http = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
    },
});

// ── Usuários ─────────────────────────────────────────────────────────────────

/** Lista todos os usuários com contagem de avaliações. */
export const getUsers = () =>
    http.get('/users').then(r => r.data);

/** Retorna detalhes de um usuário incluindo IDs dos filmes já avaliados. */
export const getUser = (userId) =>
    http.get(`/users/${userId}`).then(r => r.data);

// ── Filmes ───────────────────────────────────────────────────────────────────

/**
 * Busca filmes por título.
 * @param {string} search  - termo de busca (opcional; se vazio retorna top filmes)
 * @param {number} page    - página da busca (padrão: 1)
 */
export const getMovies = (search = '', page = 1) =>
    http.get('/movies', { params: { search, page } }).then(r => r.data);

// ── Recomendações ────────────────────────────────────────────────────────────

/**
 * Busca recomendações de filmes para um usuário.
 * Atualmente usa Content-Based Filtering (gêneros favoritos).
 * Será substituído pelo TF.js na próxima etapa do curso.
 */
export const getRecommendations = (userId) =>
    http.get(`/recommendations/${userId}`).then(r => r.data);

// ── Avaliações ───────────────────────────────────────────────────────────────

/**
 * Registra ou atualiza a avaliação de um usuário para um filme.
 * @param {{ user_id: number, movie_id: number, rating: number }} payload
 */
export const saveRating = (payload) =>
    http.post('/ratings', payload).then(r => r.data);
