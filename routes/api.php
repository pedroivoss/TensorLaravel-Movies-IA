<?php

use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
 * API Routes — Movie Recommendation System
 *
 * Todos os endpoints retornam JSON e são consumidos pelo React SPA.
 * Não há autenticação nesta fase (sistema de demonstração / curso).
 *
 * ── Endpoints disponíveis ────────────────────────────────────────────────────
 *
 *   GET  /api/users                   → lista todos os usuários com perfil
 *   GET  /api/users/{user}            → detalhes + filmes já assistidos
 *   GET  /api/movies?search=&page=    → busca paginada de filmes
 *   GET  /api/recommendations/{user}  → recomendações por gênero (placeholder)
 *   POST /api/ratings                 → registra / atualiza uma avaliação
 *
 * ── Próximos passos (TF.js) ───────────────────────────────────────────────────
 *   Quando o Web Worker for implementado, ele consumirá GET /api/users/{user}
 *   para obter o perfil do usuário e GET /api/movies para o catálogo.
 *   O treinamento acontecerá inteiramente no browser — sem novos endpoints aqui.
 */

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);

Route::post('/movies', [MovieController::class, 'index']);

Route::get('/recommendations/{user}', [RecommendationController::class, 'index']);
Route::post('/ai-models/save', [RecommendationController::class, 'storeModel']);
Route::get('/ai-models/load', [RecommendationController::class, 'loadModel']);

Route::post('/ratings', [RatingController::class, 'store']);
