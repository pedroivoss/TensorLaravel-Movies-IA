<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder: UserSeeder
 *
 * Cria usuários com perfis demograficamente coerentes para treinar o modelo.
 *
 * Estratégia de design dos perfis:
 * ─────────────────────────────────────────────────────────────────────────────
 * Cada "arquétipo" combina faixa etária + gêneros favoritos de forma realista,
 * simulando padrões que a rede neural consegue aprender:
 *
 * | Arquétipo          | Idade  | Gêneros Favoritos              |
 * |--------------------|--------|--------------------------------|
 * | Jovem Aventureiro  | 16-25  | Action, Adventure, Sci-Fi      |
 * | Cinéfilo Adulto    | 26-40  | Drama, Crime, Thriller         |
 * | Família            | 30-50  | Family, Animation, Comedy      |
 * | Terror Enthusiast  | 18-35  | Horror, Thriller, Mystery      |
 * | Clássicos          | 40-70  | Drama, Biography, History      |
 * | Comédia & Romance  | 20-45  | Comedy, Romance                |
 * | Documentarista     | 25-60  | Documentary, Biography, News   |
 * | Cold Start (sem gênero) | qualquer | NULL                    |
 * | Sem histórico (gênero, sem notas) | qualquer | definido     |
 *
 * Casos de borda cobertos:
 * - 2 usuários com favorite_genres = NULL (Cold Start absoluto)
 * - 2 usuários com gêneros definidos mas SEM notas (tratado no RatingSeeder)
 *
 * Isso garante que o TensorFlow.js receba exemplos de todos os cenários durante
 * o treinamento, tornando o modelo mais robusto.
 */
class UserSeeder extends Seeder
{
    /**
     * Gêneros válidos extraídos do imdb.csv (27 gêneros únicos).
     * A ordem aqui deve coincidir com o vetor one-hot no frontend.
     */
    public const ALL_GENRES = [
        'Action', 'Adventure', 'Animation', 'Biography', 'Comedy',
        'Crime', 'Documentary', 'Drama', 'Family', 'Fantasy',
        'Film-Noir', 'Game-Show', 'History', 'Horror', 'Music',
        'Musical', 'Mystery', 'News', 'Reality-TV', 'Romance',
        'Sci-Fi', 'Short', 'Sport', 'Talk-Show', 'Thriller',
        'War', 'Western',
    ];

    /**
     * Arquétipos de usuário: cada entrada define nome parcial, faixa etária
     * e gêneros favoritos. O seeder instancia N cópias de cada arquétipo.
     *
     * Estrutura:
     * [
     *   'archetype'  => string  (usado no nome do usuário)
     *   'age_range'  => [min, max]
     *   'genres'     => array de strings | null (null = Cold Start)
     *   'count'      => int (quantos usuários deste arquétipo criar)
     * ]
     */
    private const ARCHETYPES = [
        // ── Jovens que amam ação e ficção científica ───────────────────────
        [
            'archetype' => 'Aventureiro',
            'age_range' => [16, 25],
            'genres'    => ['Action', 'Adventure', 'Sci-Fi'],
            'count'     => 6,
        ],

        // ── Adultos que preferem dramas intensos e thrillers ───────────────
        [
            'archetype' => 'Cinéfilo',
            'age_range' => [26, 40],
            'genres'    => ['Drama', 'Crime', 'Thriller'],
            'count'     => 6,
        ],

        // ── Famílias que buscam conteúdo leve e animações ──────────────────
        [
            'archetype' => 'Família',
            'age_range' => [30, 50],
            'genres'    => ['Family', 'Animation', 'Comedy'],
            'count'     => 5,
        ],

        // ── Fãs de terror e suspense ───────────────────────────────────────
        [
            'archetype' => 'Terror',
            'age_range' => [18, 35],
            'genres'    => ['Horror', 'Thriller', 'Mystery'],
            'count'     => 5,
        ],

        // ── Amantes de filmes clássicos e históricos ───────────────────────
        [
            'archetype' => 'Clássicos',
            'age_range' => [40, 70],
            'genres'    => ['Drama', 'Biography', 'History'],
            'count'     => 4,
        ],

        // ── Fãs de comédia romântica ───────────────────────────────────────
        [
            'archetype' => 'Romance',
            'age_range' => [20, 45],
            'genres'    => ['Comedy', 'Romance'],
            'count'     => 4,
        ],

        // ── Fãs de documentários e conteúdo real ──────────────────────────
        [
            'archetype' => 'Documentarista',
            'age_range' => [25, 60],
            'genres'    => ['Documentary', 'Biography', 'News'],
            'count'     => 3,
        ],

        // ── Fãs de fantasia e mundo mágico ────────────────────────────────
        [
            'archetype' => 'Fantasia',
            'age_range' => [12, 35],
            'genres'    => ['Fantasy', 'Animation', 'Adventure'],
            'count'     => 3,
        ],

        // ── Fãs de guerra e filmes de época ───────────────────────────────
        [
            'archetype' => 'Guerra',
            'age_range' => [30, 65],
            'genres'    => ['War', 'History', 'Drama'],
            'count'     => 2,
        ],

        // ── CASO DE BORDA: Cold Start Absoluto (sem gêneros, sem histórico) ─
        // A rede neural receberá tf.zeros() para o vetor de gêneros destes
        // usuários. Testamos se o modelo consegue recomendar usando só a idade.
        [
            'archetype' => 'ColdStart',
            'age_range' => [18, 60],
            'genres'    => null,  // NULL = Cold Start
            'count'     => 2,
        ],

        // ── CASO DE BORDA: Gênero definido, SEM histórico de notas ─────────
        // Testamos o Cold Start parcial: há preferências declaradas,
        // mas nenhuma nota. O RatingSeeder vai pular estes usuários propositalmente.
        [
            'archetype' => 'SemHistorico',
            'age_range' => [20, 50],
            'genres'    => ['Action', 'Drama'],
            'count'     => 2,
        ],
    ];

    public function run(): void
    {
        $this->command->info('Criando usuários com perfis coerentes...');

        $userNumber = 1; // contador global para emails únicos

        foreach (self::ARCHETYPES as $archetype) {
            for ($i = 0; $i < $archetype['count']; $i++) {
                $age = rand($archetype['age_range'][0], $archetype['age_range'][1]);

                User::create([
                    'name'            => "{$archetype['archetype']} #{$userNumber}",
                    // Email único e previsível (facilita testes manuais)
                    'email'           => "user{$userNumber}@recommendation.test",
                    'password'        => Hash::make('password'),
                    'age'             => $age,
                    // NULL para Cold Start, array para usuários com preferências
                    'favorite_genres' => $archetype['genres'],
                ]);

                $userNumber++;
            }
        }

        $total = array_sum(array_column(self::ARCHETYPES, 'count'));
        $this->command->info("✓ {$total} usuários criados com sucesso.");

        // Mostra distribuição dos arquétipos para conferência
        $this->command->table(
            ['Arquétipo', 'Quantidade', 'Gêneros Favoritos'],
            array_map(fn($a) => [
                $a['archetype'],
                $a['count'],
                $a['genres'] ? implode(', ', $a['genres']) : '— Cold Start —',
            ], self::ARCHETYPES)
        );
    }
}
