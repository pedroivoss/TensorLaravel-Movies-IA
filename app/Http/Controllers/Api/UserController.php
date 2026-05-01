<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * UserController — API
 *
 * Fornece os dados de usuários para o frontend React.
 *
 * Endpoints:
 *   GET /api/users        → lista todos os usuários com contagem de avaliações
 *   GET /api/users/{id}   → detalhes de um usuário específico
 */
class UserController extends Controller
{
    /**
     * Lista todos os usuários com seus dados de perfil.
     *
     * Retorna junto:
     *   - ratings_count : número de filmes que o usuário já avaliou
     *   - is_cold_start : true se não tem gêneros NEM histórico
     *
     * O frontend React usa esses dados para exibir os cards de usuário
     * e indicar visualmente quem está em Cold Start.
     */
    public function index(): JsonResponse
    {
        $users = User::withCount('ratings')
            ->with('ratedMovies') // Carrega os filmes avaliados/assistidos
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'age'             => $user->age,
                    'email'           => $user->email,
                    'favorite_genres' => $user->favorite_genres ?? [],
                    'ratings_count'   => $user->ratings_count,
                    'watchedMovies'   => $user->ratedMovies->pluck('id')->toArray(), // IDs dos filmes assistidos
                    // Cold Start: sem gêneros E sem histórico — a rede usará tf.zeros()
                    'is_cold_start'   => empty($user->favorite_genres) && $user->ratings_count === 0,
                ];
            });

        return response()->json($users);
    }

    /**
     * Retorna os detalhes de um único usuário.
     *
     * Inclui as avaliações já feitas (para marcar filmes como "já assistidos"
     * na interface e evitar duplicatas ao marcar novo filme).
     */
    public function show(User $user): JsonResponse
    {
        $ratedMovieIds = $user->ratings()->pluck('movie_id')->toArray();

        return response()->json([
            'id'              => $user->id,
            'name'            => $user->name,
            'age'             => $user->age,
            'favorite_genres' => $user->favorite_genres ?? [],
            'ratings_count'   => $user->ratings()->count(),
            'rated_movie_ids' => $ratedMovieIds,
            'is_cold_start'   => empty($user->favorite_genres) && count($ratedMovieIds) === 0,
        ]);
    }
}
