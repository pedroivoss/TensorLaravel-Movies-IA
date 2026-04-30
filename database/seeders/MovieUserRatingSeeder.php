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
 * Gera avaliações (1–5) coerentes com o perfil individual de cada usuário.
 *
 * ── Por que "coerente" importa para o TensorFlow.js? ─────────────────────────
 * Se as notas fossem aleatórias, a rede neural aprenderia apenas "ruído"
 * e os pesos convergiriam para a média global, sem nenhuma personalização.
 *
 * Com notas coerentes, a rede descobre padrões reais, por exemplo:
 *   - Usuários com ["Horror","Thriller"] → notas 4–5 para filmes de horror.
 *   - Usuários com ["Documentary","Biography"] → notas 1–2 para ação pura.
 *   - Filmes com alta nota IMDB (≥ 7.5) recebem notas melhores em geral.
 *
 * Esses padrões são o "sinal de treinamento" que o modelo vai capturar.
 *
 * ── Algoritmo de pontuação ────────────────────────────────────────────────────
 * Para cada par (usuário, filme), calculamos um score de afinidade:
 *
 *   Score base          = 3  (neutro — o usuário não tem opinião a priori)
 *   + Genre match bonus = +1 por gênero favorito presente no filme (máx 2)
 *   − Genre miss penalty= −1 se NENHUM gênero do filme coincide com os favoritos
 *   + Quality bonus     = +1 se nota IMDB ≥ 7.5 (bons filmes agradam mais)
 *   + Gaussian noise    = ±0.7 (simula humor, variação de humor, re-assistências)
 *
 * Score mapeado e clampado para [1, 5].
 *
 * Exemplos com um usuário que ama ["Action", "Sci-Fi"]:
 *   Dune      (Action, Adventure, rate=8.3) → 3 +1(Action)         +1 +ruído → ~5
 *   Inception (Action, Sci-Fi,    rate=8.8) → 3 +1+1(Action+Sci-Fi)+1 +ruído → ~5
 *   Notebook  (Drama, Romance,    rate=7.9) → 3 −1(miss)           +1 +ruído → ~3
 *   Hereditary(Horror,            rate=7.3) → 3 −1(miss)           +0 +ruído → ~2
 *
 * ── Distribuição esperada de notas ───────────────────────────────────────────
 *   Nota 1 (Odiei)     →  ~2%   (miss + sem qualidade + ruído muito negativo)
 *   Nota 2 (Não gostei)→ ~36%   (miss + sem qualidade, cenário mais comum)
 *   Nota 3 (Neutro)    → ~18%   (miss + qualidade, ou match único sem qualidade)
 *   Nota 4 (Gostei)    → ~22%   (1 match ou match + qualidade com ruído negativo)
 *   Nota 5 (Amei)      → ~22%   (2 matches ou 1 match + qualidade)
 *
 * ── Usuários excluídos deste seeder ──────────────────────────────────────────
 *   Cold Start absoluto → favorite_genres IS NULL (excluído via whereNotNull)
 *   Cold Start parcial  → email LIKE '%@semhistorico.test' (excluído via where)
 *
 * Estes dois grupos são casos de borda testados pelo sistema: existem no banco,
 * mas sem avaliações, forçando a rede a lidar com inputs zerados ou incompletos.
 */
class MovieUserRatingSeeder extends Seeder
{
    // Quantos filmes cada usuário avalia por rodada de seed
    private const RATINGS_PER_USER = 40;

    // Ponto neutro da escala antes de qualquer bônus ou penalidade
    private const BASE_RATING = 3;

    // Bônus por gênero favorito encontrado no filme (máx. 2 contam).
    // Valor 1 (e não 2) mantém distribuição equilibrada:
    //   0 matches → ~2  |  1 match → ~4  |  2 matches → ~5
    private const GENRE_MATCH_BONUS = 1;

    // Penalidade quando o filme não tem nenhum gênero favorito do usuário
    private const GENRE_MISS_PENALTY = -1;

    // Bônus para filmes bem avaliados no IMDB — qualidade agrada a todos
    private const HIGH_RATE_BONUS = 1;

    // Limiar de "boa avaliação" no IMDB
    private const HIGH_RATE_THRESHOLD = 7.5;

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('Gerando avaliações coerentes por perfil de usuário...');

        // Carrega todos os filmes de uma vez para evitar N+1 queries no loop
        $allMovies = Movie::all();

        if ($allMovies->isEmpty()) {
            $this->command->error(
                'Nenhum filme encontrado. Execute primeiro: php artisan movies:import'
            );
            return;
        }

        /*
         * Filtra apenas usuários que devem receber avaliações:
         *
         *   whereNotNull('favorite_genres')
         *     → exclui Cold Start absoluto (esses têm favorite_genres = null)
         *
         *   where('email', 'not like', '%@semhistorico.test')
         *     → exclui Cold Start parcial (esses têm gêneros mas sem histórico)
         *       A convenção de domínio é definida no UserSeeder.
         */
        $users = User::whereNotNull('favorite_genres')
                     ->where('email', 'not like', '%@semhistorico.test')
                     ->get();

        if ($users->isEmpty()) {
            $this->command->warn('Nenhum usuário elegível encontrado. Execute o UserSeeder primeiro.');
            return;
        }

        $totalRatings = 0;
        $bar = $this->command->getOutput()->createProgressBar($users->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('iniciando...');
        $bar->start();

        foreach ($users as $user) {
            $ratingsForUser = $this->generateRatingsForUser($user, $allMovies);

            // Upsert em lotes de 100 para evitar estouro de memória
            // e garantir idempotência (rodar o seeder duas vezes não duplica)
            foreach (array_chunk($ratingsForUser, 100) as $chunk) {
                MovieUserRating::upsert(
                    $chunk,
                    uniqueBy: ['user_id', 'movie_id'],
                    update:   ['rating', 'updated_at']
                );
            }

            $totalRatings += count($ratingsForUser);
            $bar->setMessage("{$totalRatings} avaliações inseridas");
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);
        $this->command->info("✓ {$totalRatings} avaliações coerentes geradas para {$users->count()} usuários.");
        $this->command->newLine();

        $this->printRatingStats();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Gera todas as avaliações de um único usuário.
     *
     * Seleciona RATINGS_PER_USER filmes aleatoriamente (sem repetição),
     * calcula a nota de cada um via score de afinidade e retorna como array
     * pronto para inserção via upsert.
     *
     * @param  User       $user
     * @param  Collection $allMovies  Todos os filmes carregados em memória
     * @return array<int, array{user_id:int, movie_id:int, rating:int, created_at:string, updated_at:string}>
     */
    private function generateRatingsForUser(User $user, Collection $allMovies): array
    {
        // favorite_genres já é array PHP graças ao cast no Model User
        $favoriteGenres = $user->favorite_genres ?? [];

        $selectedMovies = $allMovies->random(
            min(self::RATINGS_PER_USER, $allMovies->count())
        );

        $now     = now()->toDateTimeString();
        $ratings = [];

        foreach ($selectedMovies as $movie) {
            $ratings[] = [
                'user_id'    => $user->id,
                'movie_id'   => $movie->id,
                'rating'     => $this->calculateRating($movie, $favoriteGenres),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $ratings;
    }

    /**
     * Calcula a nota (1–5) que um usuário daria a um filme.
     *
     * O score é construído em camadas e depois clampado para [1, 5]:
     *
     *   1. Base neutra (3)
     *   2. Bônus por sobreposição de gêneros (máx. +2 por 2 matches)
     *      OU penalidade se não houver nenhum match (−1)
     *   3. Bônus de qualidade IMDB (opcional, +1)
     *   4. Ruído gaussiano (±0.7) para simular variação humana
     *
     * Por que limitar a 2 matches?
     *   Com BASE=3 e BONUS=1, se contássemos todos os matches:
     *   Um filme com 4 gêneros todos favoritos daria 3+4=7 → sempre 5.
     *   Limitando a 2, preservamos a influência do ruído e da qualidade.
     *
     * @param  Movie $movie
     * @param  array $favoriteGenres  Array de gêneros favoritos do usuário
     * @return int                    Nota de 1 a 5
     */
    private function calculateRating(Movie $movie, array $favoriteGenres): int
    {
        $score = self::BASE_RATING;

        // genres_array é um accessor do Model Movie que converte a string
        // "Action, Drama" em ['Action', 'Drama']
        $movieGenres = $movie->genres_array;

        if (! empty($favoriteGenres) && ! empty($movieGenres)) {
            $matchCount = count(array_intersect($favoriteGenres, $movieGenres));

            if ($matchCount > 0) {
                // Bônus proporcional ao número de matches (limitado a 2)
                $score += min($matchCount, 2) * self::GENRE_MATCH_BONUS;
            } else {
                // Penalidade: nenhum gênero favorito presente no filme
                $score += self::GENRE_MISS_PENALTY;
            }
        }

        // Filmes com alta avaliação no IMDB tendem a agradar mais
        if ($movie->rate !== null && $movie->rate >= self::HIGH_RATE_THRESHOLD) {
            $score += self::HIGH_RATE_BONUS;
        }

        // Ruído pseudo-gaussiano: simula variação de humor, expectativas e gosto pessoal
        $score += $this->gaussianNoise();

        return (int) max(1, min(5, round($score)));
    }

    /**
     * Gera ruído pseudo-gaussiano centrado em zero (desvio padrão ≈ 0.7).
     *
     * Usamos a aproximação de Irwin-Hall (soma de N uniformes):
     *   - n=3 uniformes somadas → curva em sino suave
     *   - centralizada em 1.5 (média da soma)
     *   - escalada por 0.5 → resultado em ≈ [−0.75, +0.75]
     *
     * Por que não usar rand(-1, 1)?
     *   Uma uniforme [-1,1] superestima os extremos. A Irwin-Hall dá mais
     *   peso ao centro (0), tornando os ruídos intermediários mais prováveis,
     *   o que é fisiologicamente mais realista.
     */
    private function gaussianNoise(): float
    {
        $u = (mt_rand() / mt_getrandmax())
           + (mt_rand() / mt_getrandmax())
           + (mt_rand() / mt_getrandmax());

        return ($u - 1.5) * 0.5;
    }

    /**
     * Exibe uma tabela com a distribuição das notas geradas.
     *
     * Útil para verificar rapidamente se o algoritmo está produzindo
     * uma distribuição coerente (sem concentração extrema em um único valor).
     */
    private function printRatingStats(): void
    {
        $distribution = MovieUserRating::selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('total', 'rating');

        $total  = $distribution->sum();
        $labels = [1 => 'Odiei', 2 => 'Não gostei', 3 => 'Neutro', 4 => 'Gostei', 5 => 'Amei'];

        $rows = $distribution->map(function ($count, $rating) use ($total, $labels) {
            $pct      = $total > 0 ? round($count / $total * 100, 1) : 0.0;
            $filled   = (int) round($pct / 5); // 0–20 blocos (cada bloco = 5%)
            $empty    = 20 - $filled;
            $bar      = str_repeat('#', $filled) . str_repeat('-', $empty);
            return [
                $rating,
                $labels[$rating] ?? '?',
                (int) $count,
                "{$pct}%",
                "[{$bar}]",
            ];
        })->values()->toArray();

        $this->command->table(
            ['Nota', 'Descrição', 'Total', '%', 'Distribuição'],
            $rows
        );
    }
}
