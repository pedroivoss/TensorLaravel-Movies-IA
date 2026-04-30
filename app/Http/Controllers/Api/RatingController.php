<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovieUserRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * RatingController — API
 *
 * Registra ou atualiza a avaliação de um usuário para um filme.
 *
 * Este é o endpoint central do sistema: cada chamada gera um novo par
 * (usuário, filme, nota) que enriquece o dataset de treinamento do TF.js.
 *
 * Endpoint:
 *   POST /api/ratings   → cria ou atualiza uma avaliação
 *
 * Body JSON:
 *   {
 *     "user_id"  : 1,
 *     "movie_id" : 42,
 *     "rating"   : 4   → escala 1-5
 *   }
 */
class RatingController extends Controller
{
    /**
     * Armazena ou atualiza uma avaliação.
     *
     * Usa updateOrCreate para garantir idempotência: se o usuário já avaliou
     * o filme, a nota é simplesmente atualizada. Assim o botão "Marcar como
     * Assistido" pode ser chamado múltiplas vezes sem criar duplicatas.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'  => ['required', 'integer', 'exists:users,id'],
            'movie_id' => ['required', 'integer', 'exists:movies,id'],
            // Escala 1-5: 1=Odiei, 2=Não gostei, 3=Neutro, 4=Gostei, 5=Amei
            'rating'   => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
        ]);

        $rating = MovieUserRating::updateOrCreate(
            // Chave de busca: par único usuário + filme
            ['user_id' => $validated['user_id'], 'movie_id' => $validated['movie_id']],
            // Dado a atualizar/criar
            ['rating'  => $validated['rating']]
        );

        // wasRecentlyCreated: true = nova avaliação | false = atualização
        $action = $rating->wasRecentlyCreated ? 'created' : 'updated';

        return response()->json([
            'success'  => true,
            'action'   => $action,
            'rating'   => [
                'id'       => $rating->id,
                'user_id'  => $rating->user_id,
                'movie_id' => $rating->movie_id,
                'rating'   => $rating->rating,
                // Label normalizado para o TF.js: (rating - 1) / 4 → [0.0, 1.0]
                'label'    => round(($rating->rating - 1) / 4, 4),
            ],
        ], $rating->wasRecentlyCreated ? 201 : 200);
    }
}
