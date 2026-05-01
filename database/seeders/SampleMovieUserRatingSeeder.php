<?php

namespace Database\Seeders;

/**
 * Seeder: SampleMovieUserRatingSeeder
 *
 * Versão reduzida do MovieUserRatingSeeder para o modo sample.
 * Cada usuário avalia no máximo 5 filmes (vs. 40 no fluxo completo).
 *
 * Usuários sem relação com filmes (já excluídos pelo seeder pai):
 *   - Cold Start absoluto  → favorite_genres IS NULL
 *   - Sem Histórico        → email @semhistorico.test
 */
class SampleMovieUserRatingSeeder extends MovieUserRatingSeeder
{
    protected function getRatingsPerUser(): int
    {
        return 5;
    }
}
