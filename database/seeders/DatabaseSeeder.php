<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder Principal: DatabaseSeeder
 *
 * Orquestra a ordem correta de execução de todos os seeders.
 *
 * ── Ordem importa! ────────────────────────────────────────────────────────────
 * As foreign keys exigem que as tabelas pai existam antes das tabelas filho:
 *
 *   1. [Artisan Command] movies:import → popula 'movies' (pai das ratings)
 *   2. UserSeeder         → popula 'users' (pai das ratings)
 *   3. MovieUserRatingSeeder → popula 'movie_user_ratings' (filho de ambos)
 *
 * ── Como executar (fluxo completo) ───────────────────────────────────────────
 *
 *   php artisan migrate:fresh           # recria todas as tabelas
 *   php artisan movies:import           # importa ~6000 filmes do CSV
 *   php artisan db:seed                 # executa este DatabaseSeeder (42 usuários)
 *
 * Para reimportar apenas os filmes (sem apagar usuários/ratings):
 *   php artisan movies:import --fresh   # limpa e reimporta apenas movies
 *
 * ── Fluxo de amostra (modo rápido para testes) ───────────────────────────────
 *
 *   php artisan migrate:fresh
 *   php artisan movies:import --sample                    # 20 filmes variados
 *   php artisan db:seed --class=SampleDatabaseSeeder      # 7 usuários
 *
 * ── Ambiente de desenvolvimento ──────────────────────────────────────────────
 * Este seeder cria dados fictícios para desenvolvimento e testes.
 * NÃO deve ser executado em produção sem revisão dos dados.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║   Movie Recommendation System — Database Seed  ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->info('');

        // Passo 1: Verifica se os filmes já foram importados
        // O RatingSeeder depende de filmes no banco — avisa o dev se estiver vazio.
        $movieCount = \App\Models\Movie::count();
        if ($movieCount === 0) {
            $this->command->warn(
                '⚠  Nenhum filme encontrado na tabela movies!'
            );
            $this->command->warn(
                '   Execute PRIMEIRO: php artisan movies:import'
            );
            $this->command->warn(
                '   Depois rode novamente: php artisan db:seed'
            );
            $this->command->newLine();

            // Pergunta se deve continuar mesmo assim (só cria usuários sem ratings)
            if (! $this->command->confirm('Continuar mesmo sem filmes? (só usuários serão criados)')) {
                $this->command->info('Seed cancelado.');
                return;
            }
        } else {
            $this->command->info("✓ {$movieCount} filmes encontrados na base.");
        }

        $this->command->newLine();

        // Passo 2: Cria usuários com perfis coerentes (arquétipos)
        $this->call(UserSeeder::class);
        $this->command->newLine();

        // Passo 3: Gera avaliações coerentes com o perfil de cada usuário
        // (pula usuários ColdStart e SemHistorico propositalmente)
        if ($movieCount > 0) {
            $this->call(MovieUserRatingSeeder::class);
        } else {
            $this->command->warn('⚠  Ratings pulados (sem filmes no banco).');
        }

        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║              Seed concluído!                   ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->newLine();

        // Resumo final para facilitar a inspeção
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
