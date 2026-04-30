<?php

use Illuminate\Support\Facades\Route;

/*
 * Rota única: serve o template Blade que monta o React SPA.
 *
 * Toda a lógica de interface (usuários, filmes, recomendações) é gerenciada
 * pelo React via chamadas à API em /api/*. Esta rota só existe para
 * entregar o HTML inicial com os assets do Vite.
 *
 * Quando o TF.js for integrado (próxima etapa do curso), o treinamento
 * acontecerá inteiramente no browser via Web Worker — sem nenhum novo
 * endpoint Laravel necessário.
 */
Route::get('/', function () {
    return view('app');
});
