<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class PageBladeController extends Controller
{
    public function index()
    {
        //lista todos os generos de filmes disponiveis
        $genres = Movie::select('genre')->distinct()->pluck('genre');

        // os generos vem em formato de string geralmente mais de um Ex "Action, Adventure, Sci-Fi"
        // entao vamos separar por virgula e criar um array unico de generos e ordenar alfabeticamente
        $genres = $genres->flatMap(function ($genre) { // flatMap para transformar cada string em um array de generos
            return explode(', ', $genre); // separa por virgula e espaço
        })->unique()->sort()->values(); // unique para remover duplicatas, sort para ordenar e values para resetar os índices

        $data = [
            'genres' => $genres,
        ];

        return view('teste-ia', $data);
    }
}
