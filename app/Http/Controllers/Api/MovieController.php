<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MovieController — API
 *
 * POST /api/movies
 *   Sem "ids" no body → browse/search paginado (carga inicial + pesquisa)
 *   Com "ids" no body → retorna os filmes desses IDs sem limite (filmes assistidos)
 *
 * Body JSON:
 *   { "search": "batman", "per_page": 50 }              ← browse
 *   { "ids": [1,5,42,...], "search": "" }               ← assistidos
 */
class MovieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search', '');

        // "ids" presente no body → retorna filmes específicos (sem paginação)
        if ($request->has('ids')) {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0,
                ]);
            }

            $movies = Movie::select('id', 'name', 'genre', 'rate', 'duration', 'release_year')
                ->whereIn('id', $ids)
                ->when($search !== '', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
                ->orderByDesc('rate')
                ->get();

            return response()->json([
                'data'         => $movies->values(),
                'current_page' => 1,
                'last_page'    => 1,
                'total'        => $movies->count(),
            ]);
        }

        // Sem "ids" → retorna todos os filmes conforme filtros
        $query = Movie::select('id', 'name', 'genre', 'rate', 'duration', 'release_year');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        } else {
            $query->whereNotNull('rate')->orderByDesc('rate');
        }

        $movies = $query->get();

        return response()->json([
            'data'         => $movies->values(),
            'current_page' => 1,
            'last_page'    => 1,
            'total'        => $movies->count(),
        ]);
    }
}
