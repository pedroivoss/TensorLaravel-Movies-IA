<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder de Amostra: SampleDatabaseSeeder
 *
 * Versão reduzida do DatabaseSeeder para testes rápidos do modelo.
 * Cria 7 usuários e usa os 20 filmes variados importados via --sample.
 *
 * ── Fluxo completo de amostra ─────────────────────────────────────────────────
 *
 *   php artisan migrate:fresh
 *   php artisan movies:import --sample          # 20 filmes variados por gênero
 *   php artisan db:seed --class=SampleDatabaseSeeder
 *
 * Ou se já tiver as tabelas e quiser só repovoar:
 *   php artisan movies:import --fresh --sample
 *   php artisan db:seed --class=SampleDatabaseSeeder
 *
 * ── Resultado esperado ────────────────────────────────────────────────────────
 *   movies              →  20 filmes (1-2 por gênero)
 *   users               →   7 usuários
 *   movie_user_ratings  → ~100 avaliações (5 usuários × até 20 filmes)
 *
 * ── Fluxo completo (sem flag) ─────────────────────────────────────────────────
 *   php artisan migrate:fresh
 *   php artisan movies:import                   # ~6000 filmes
 *   php artisan db:seed                         # 42 usuários
 */
class SampleDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║   Movie Recommendation — Seed de Amostra      ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->warn('Modo rápido: 7 usuários, filmes já importados via --sample.');
        $this->command->newLine();

        $movieCount = \App\Models\Movie::count();

        if ($movieCount === 0) {
            $this->command->error('Nenhum filme encontrado. Execute primeiro:');
            $this->command->error('  php artisan movies:import --sample');
            return;
        }

        $this->command->info("✓ {$movieCount} filmes encontrados na base.");
        $this->command->newLine();

        $this->call(SampleUserSeeder::class);
        $this->command->newLine();

        $this->call(MovieUserRatingSeeder::class);

        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║           Seed de amostra concluído!           ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->newLine();

        $this->command->table(
            ['Tabela', 'Registros'],
            [
                ['movies',             \App\Models\Movie::count()],
                ['users',              \App\Models\User::count()],
                ['movie_user_ratings', \App\Models\MovieUserRating::count()],
            ]
        );
    }
}
