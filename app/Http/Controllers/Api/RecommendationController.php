<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * RecommendationController — API
 *
 * Gera recomendações de filmes para um usuário específico.
 *
 * ── IMPORTANTE: Este é um placeholder para o TensorFlow.js ──────────────────
 * Por enquanto, as recomendações são geradas por um filtro de conteúdo simples
 * (filmes com gêneros favoritos, ordenados por nota, não assistidos ainda).
 *
 * Quando o TF.js for integrado (próxima etapa do curso), este endpoint
 * continuará existindo mas passará a retornar scores calculados pelo modelo
 * treinado no cliente — ou o próprio browser calculará tudo sem chamar aqui.
 *
 * ── Algoritmo atual (Content-Based Filtering) ────────────────────────────────
 * 1. Lê os gêneros favoritos do usuário.
 * 2. Busca filmes que contenham pelo menos um desses gêneros.
 * 3. Exclui filmes que o usuário já avaliou.
 * 4. Ordena por nota IMDB (proxy de qualidade).
 * 5. Retorna os top 20.
 *
 * Caso Cold Start (sem gêneros): retorna os top 20 filmes mais bem avaliados
 * do banco inteiro — a recomendação menos personalizada possível.
 *
 * Endpoint:
 *   GET /api/recommendations/{user}
 */
class RecommendationController extends Controller
{
    private const LIMIT = 5;

    public function index(User $user): JsonResponse
    {
        // IDs dos filmes que o usuário já avaliou (para excluir da lista)
        $alreadyRatedIds = $user->ratings()->pluck('movie_id')->toArray();

        $favoriteGenres = $user->favorite_genres ?? [];

        $query = Movie::select('id', 'name', 'genre', 'rate', 'duration', 'release_year')
            ->whereNotNull('rate')
            ->orderByDesc('rate')
            ->limit(self::LIMIT);

        // Exclui filmes já assistidos pelo usuário
        if (! empty($alreadyRatedIds)) {
            $query->whereNotIn('id', $alreadyRatedIds);
        }

        if (! empty($favoriteGenres)) {
            /*
             * Filtra filmes que contenham pelo menos um gênero favorito.
             * Como o campo "genre" é uma string CSV ("Action, Drama, Sci-Fi"),
             * usamos LIKE para cada gênero e combinamos com OR.
             *
             * Exemplo para ["Action", "Sci-Fi"]:
             *   WHERE (genre LIKE '%Action%' OR genre LIKE '%Sci-Fi%')
             */
            $query->where(function ($q) use ($favoriteGenres) {
                foreach ($favoriteGenres as $genre) {
                    $q->orWhere('genre', 'like', '%' . $genre . '%');
                }
            });
        }
        // Cold Start: sem gêneros → retorna top filmes sem filtro de gênero

        $movies = $query->get()->map(function (Movie $movie) use ($favoriteGenres) {
            // Calcula quantos gêneros favoritos coincidem (útil para debug no frontend)
            $movieGenres   = array_map('trim', explode(',', $movie->genre ?? ''));
            $matchingCount = count(array_intersect($favoriteGenres, $movieGenres));

            return [
                'id'             => $movie->id,
                'name'           => $movie->name,
                'genre'          => $movie->genre,
                'rate'           => $movie->rate,
                'duration'       => $movie->duration,
                'release_year'   => $movie->release_year,
                // Indica ao frontend quantos gêneros coincidiram (visualização futura)
                'genre_matches'  => $matchingCount,
            ];
        });

        return response()->json([
            'user_id'         => $user->id,
            'user_name'       => $user->name,
            'favorite_genres' => $favoriteGenres,
            // Sinaliza Cold Start para o frontend exibir aviso específico
            'is_cold_start'   => empty($favoriteGenres),
            'recommendations' => $movies,
            // Mensagem contextual exibida na UI
            'source'          => empty($favoriteGenres)
                ? 'Top filmes do IMDB (Cold Start — sem gêneros favoritos)'
                : 'Filmes baseados nos seus gêneros favoritos',
        ]);
    }
}
