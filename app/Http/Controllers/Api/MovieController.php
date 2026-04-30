<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MovieController — API
 *
 * Fornece busca geral de filmes para o frontend React.
 *
 * Endpoints:
 *   GET /api/movies?search=batman&page=1   → busca paginada por título
 */
class MovieController extends Controller
{
    // Número de filmes por página na busca geral
    private const PER_PAGE = 20;

    /**
     * Busca filmes por título (ou lista os mais bem avaliados se sem query).
     *
     * Query params:
     *   - search : string parcial do título (opcional)
     *   - page   : número da página (padrão: 1)
     *
     * Retorna dados leves — sem embedding, para não inflar a resposta.
     * O embedding será buscado separadamente pelo Web Worker quando necessário.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Movie::select('id', 'name', 'genre', 'rate', 'duration', 'release_year');

        // Se há termo de busca, filtra por nome (case-insensitive)
        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        } else {
            // Sem busca: exibe os mais bem avaliados (útil como "descoberta")
            $query->whereNotNull('rate')->orderByDesc('rate');
        }

        $paginated = $query->paginate(self::PER_PAGE);

        return response()->json([
            'data'         => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
        ]);
    }
}
