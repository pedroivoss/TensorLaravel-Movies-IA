<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\MovieUserRating;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeder: MovieUserRatingSeeder
 *
 * Gera avaliações (1-5) coerentes com o perfil de cada usuário.
 *
 * ── Por que "coerente" importa para o treinamento? ──────────────────────────
 * Se as notas fossem puramente aleatórias, a rede neural não encontraria
 * nenhum padrão nos dados e o modelo aprenderia apenas "ruído".
 *
 * Com notas coerentes, a rede consegue descobrir que:
 *   - Usuários que amam Action tendem a dar 4-5 para filmes de Action.
 *   - Usuários que preferem Drama dão 1-2 para filmes de Terror.
 *   - Usuários mais velhos (Clássicos) valorizam filmes com alto 'rate' IMDB.
 *
 * Esse padrão é o "sinal de treinamento" que o TensorFlow.js vai capturar.
 *
 * ── Algoritmo de pontuação ────────────────────────────────────────────────────
 * Para cada par (usuário, filme), calculamos um "score de afinidade":
 *
 *   1. Sobreposição de gêneros: +2 por cada gênero favorito que o filme tem.
 *   2. Qualidade IMDB: filmes com rate ≥ 7.5 ganham +1 (bom para todos).
 *   3. Ruído gaussiano: ±1 aleatório para simular humor e variação humana.
 *
 * O score final é mapeado para a escala 1-5 e clampado para [1, 5].
 *
 * ── Casos de borda tratados ─────────────────────────────────────────────────
 * - Usuários "ColdStart" (genres = null): ignorados (sem ratings).
 * - Usuários "SemHistorico": ignorados intencionalmente (sem ratings).
 * - Filmes sem gênero: tratados como sobreposição zero (nota próxima de 3).
 * - Constraint UNIQUE (user_id, movie_id): garantida via uniqueBy no upsert.
 */
class MovieUserRatingSeeder extends Seeder
{
    // Quantos filmes cada usuário avalia (equilibra diversidade e velocidade)
    private const RATINGS_PER_USER = 40;

    // Nota base quando não há sobreposição de gêneros (neutro)
    private const BASE_RATING = 3;

    // Bonus por gênero favorito encontrado no filme.
    // Valor 1 (não 2) garante distribuição equilibrada:
    //   0 matches → ~2 | 1 match → ~4 | 2+ matches → ~5
    private const GENRE_MATCH_BONUS = 1;

    // Bonus para filmes muito bem avaliados no IMDB (≥ 7.5)
    private const HIGH_RATE_BONUS = 1;

    public function run(): void
    {
        $this->command->info('Gerando avaliações coerentes por perfil de usuário...');

        // Carrega todos os filmes de uma vez (evita N+1 queries no loop)
        $allMovies = Movie::all();

        if ($allMovies->isEmpty()) {
            $this->command->error(
                'Nenhum filme encontrado! Execute primeiro: php artisan movies:import'
            );
            return;
        }

        // Carrega apenas usuários que DEVEM ter ratings
        // (exclui ColdStart e SemHistorico propositalmente)
        $users = User::all()->filter(function (User $user) {
            $archetype = $this->extractArchetype($user->name);
            return ! in_array($archetype, ['ColdStart', 'SemHistorico'], true);
        });

        $totalRatings = 0;
        $bar = $this->command->getOutput()->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $ratingsForUser = $this->generateRatingsForUser($user, $allMovies);

            // Insert em lote com ignore de duplicatas (idempotente)
            foreach (array_chunk($ratingsForUser, 100) as $chunk) {
                MovieUserRating::upsert(
                    $chunk,
                    ['user_id', 'movie_id'],  // chaves únicas (conflict detection)
                    ['rating', 'updated_at']  // campos a atualizar em caso de conflito
                );
            }

            $totalRatings += count($ratingsForUser);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);
        $this->command->info("✓ {$totalRatings} avaliações coerentes geradas.");

        // Mostra estatísticas de distribuição das notas
        $this->printRatingStats();
    }

    /**
     * Gera um array de avaliações coerentes com o perfil do usuário.
     *
     * O processo:
     *   1. Seleciona RATINGS_PER_USER filmes aleatoriamente (sem repetição).
     *   2. Para cada filme, calcula um score de afinidade com base nos gêneros.
     *   3. Adiciona ruído gaussiano para simular variação humana.
     *   4. Mapeia o score para a escala [1, 5].
     *
     * @param  User       $user
     * @param  Collection $allMovies
     * @return array<int, array{user_id:int, movie_id:int, rating:int, ...}>
     */
    private function generateRatingsForUser(User $user, Collection $allMovies): array
    {
        $favoriteGenres = $user->favorite_genres ?? [];

        // Seleciona filmes aleatoriamente (sem reposição)
        $selectedMovies = $allMovies->random(
            min(self::RATINGS_PER_USER, $allMovies->count())
        );

        $ratings = [];
        $now = now()->toDateTimeString();

        foreach ($selectedMovies as $movie) {
            $rating = $this->calculateRating($movie, $favoriteGenres);

            $ratings[] = [
                'user_id'    => $user->id,
                'movie_id'   => $movie->id,
                'rating'     => $rating,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $ratings;
    }

    /**
     * Calcula a nota (1-5) de um usuário para um filme com base nos gêneros.
     *
     * Lógica:
     *   - Começa em BASE_RATING (3 = neutro).
     *   - Adiciona GENRE_MATCH_BONUS (2) por cada gênero favorito encontrado.
     *     (limitado a +4 máximo, para não extrapolar a escala)
     *   - Adiciona HIGH_RATE_BONUS (1) para filmes IMDB ≥ 7.5.
     *   - Aplica ruído de ±1 (simulando humor e variação individual).
     *   - Clampeia o resultado para [1, 5].
     *
     * Exemplos (usuário ama Action, Sci-Fi):
     *   - "Dune" (Action, Adventure, Drama, rate=8.3): 3 + 1(Action) + 1(rate) + ruído → ~5
     *   - "Inception" (Action, Sci-Fi, rate=8.8): 3 + 2(Action+Sci-Fi) + 1 + ruído → ~5
     *   - "The Notebook" (Drama, Romance, rate=7.9): 3 + 0 - 1 + 1(rate) + ruído → ~3
     *   - "Hereditary" (Horror, rate=7.3): 3 - 1 + 0 + ruído → ~2
     */
    private function calculateRating(Movie $movie, array $favoriteGenres): int
    {
        $score = self::BASE_RATING;

        // Converte a string de gêneros do filme em array para comparação
        $movieGenres = $movie->genres_array; // accessor definido no Model

        // Bônus por sobreposição de gêneros (máximo de 2 matches conta)
        if (! empty($favoriteGenres) && ! empty($movieGenres)) {
            $matches = count(array_intersect($favoriteGenres, $movieGenres));
            // Limita a 2 matches para não extrapolar: 3+4=7 > 5
            $matchBonus = min($matches, 2) * self::GENRE_MATCH_BONUS;

            // Se há matches, o score sobe (usuário gosta deste tipo de filme)
            // Se não há matches, aplicamos penalidade leve (-1)
            $score += $matches > 0 ? $matchBonus : -1;
        }

        // Bônus de qualidade: filmes muito bem avaliados no IMDB agradam mais
        if ($movie->rate !== null && $movie->rate >= 7.5) {
            $score += self::HIGH_RATE_BONUS;
        }

        // Ruído humano: simula que às vezes gostamos de algo fora do padrão
        // ou não gostamos de algo que "deveria" ser nossa cara
        $noise = $this->gaussianNoise();
        $score += $noise;

        // Garante que a nota final está dentro da escala [1, 5]
        return (int) max(1, min(5, round($score)));
    }

    /**
     * Gera ruído pseudo-gaussiano no intervalo aproximado [-1.5, +1.5].
     *
     * Usamos a soma de três uniformes (Irwin-Hall distribution), que
     * aproxima uma gaussiana sem depender de extensões PHP.
     * Resultado centrado em 0, desvio padrão ≈ 0.7.
     */
    private function gaussianNoise(): float
    {
        // Soma de 3 uniformes [0,1] e centraliza: distribui como gaussiana truncada
        $u = (mt_rand() / mt_getrandmax())
           + (mt_rand() / mt_getrandmax())
           + (mt_rand() / mt_getrandmax());

        // Centraliza: 3/2 = 1.5 (média da Irwin-Hall com n=3)
        // Escala por 0.5 para limitar o ruído a ≈ ±1
        return ($u - 1.5) * 0.5;
    }

    /**
     * Extrai o nome do arquétipo a partir do nome do usuário.
     * Ex: "Aventureiro #3" → "Aventureiro"
     */
    private function extractArchetype(string $name): string
    {
        return trim(explode('#', $name)[0]);
    }

    /**
     * Imprime estatísticas de distribuição das notas geradas.
     * Útil para verificar que a distribuição está coerente e não viesada.
     */
    private function printRatingStats(): void
    {
        $distribution = MovieUserRating::selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('total', 'rating');

        $labels = [1 => 'Odiei', 2 => 'Não gostei', 3 => 'Neutro', 4 => 'Gostei', 5 => 'Amei'];

        $this->command->table(
            ['Nota', 'Descrição', 'Quantidade'],
            $distribution->map(fn($count, $rating) => [
                $rating,
                $labels[$rating] ?? '?',
                $count,
            ])->values()->toArray()
        );
    }
}
