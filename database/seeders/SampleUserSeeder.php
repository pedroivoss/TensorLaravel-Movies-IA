<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder: SampleUserSeeder
 *
 * Cria exatamente 7 usuários para testes rápidos do modelo:
 *
 *   5 usuários com gêneros favoritos + avaliações (um por persona):
 *     - Jovem Aventureiro
 *     - Cinéfilo
 *     - Família
 *     - Fã de Terror
 *     - Documentarista
 *
 *   2 usuários sem relação com filmes (casos de borda):
 *     - Cold Start absoluto  → favorite_genres = NULL, sem avaliações
 *     - Sem Histórico        → tem gêneros favoritos, mas sem avaliações
 *
 * Uso:
 *   php artisan movies:import --sample   # importa 20 filmes variados
 *   php artisan db:seed --class=SampleDatabaseSeeder
 */
class SampleUserSeeder extends Seeder
{
    private const PERSONAS = [
        [
            'persona'           => 'Jovem Aventureiro',
            'age_range'         => [16, 25],
            'core_genres'       => ['Action', 'Adventure'],
            'secondary_genres'  => ['Sci-Fi', 'Thriller', 'Fantasy', 'Animation', 'Comedy'],
            'occasional_genres' => ['Crime', 'Horror', 'Sport', 'War'],
            'count'             => 1,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],
        [
            'persona'           => 'Cinéfilo',
            'age_range'         => [26, 40],
            'core_genres'       => ['Drama', 'Crime'],
            'secondary_genres'  => ['Thriller', 'Mystery', 'Biography', 'History'],
            'occasional_genres' => ['Documentary', 'War', 'Film-Noir', 'Romance'],
            'count'             => 1,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],
        [
            'persona'           => 'Família',
            'age_range'         => [30, 50],
            'core_genres'       => ['Family', 'Animation'],
            'secondary_genres'  => ['Comedy', 'Adventure', 'Fantasy', 'Music'],
            'occasional_genres' => ['Drama', 'Musical', 'Romance', 'Sci-Fi'],
            'count'             => 1,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],
        [
            'persona'           => 'Fã de Terror',
            'age_range'         => [18, 35],
            'core_genres'       => ['Horror', 'Thriller'],
            'secondary_genres'  => ['Mystery', 'Crime', 'Drama'],
            'occasional_genres' => ['Comedy', 'Sci-Fi', 'Documentary', 'Romance'],
            'count'             => 1,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],
        [
            'persona'           => 'Documentarista',
            'age_range'         => [25, 60],
            'core_genres'       => ['Documentary', 'Biography'],
            'secondary_genres'  => ['History', 'News', 'Reality-TV'],
            'occasional_genres' => ['Crime', 'Drama', 'War', 'Sport'],
            'count'             => 1,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],
        // Usuários sem relação com filmes (casos de borda)
        [
            'persona'           => 'Cold Start',
            'age_range'         => [18, 60],
            'core_genres'       => [],
            'secondary_genres'  => [],
            'occasional_genres' => [],
            'count'             => 1,
            'cold_start'        => true,
            'skip_ratings'      => true,
        ],
        [
            'persona'           => 'Sem Histórico',
            'age_range'         => [20, 50],
            'core_genres'       => ['Action', 'Drama'],
            'secondary_genres'  => ['Thriller', 'Comedy', 'Romance', 'Crime'],
            'occasional_genres' => ['Sci-Fi', 'Mystery', 'Biography'],
            'count'             => 1,
            'cold_start'        => false,
            'skip_ratings'      => true,
        ],
    ];

    private const NAMES = [
        'Ana Lima', 'Bruno Ferreira', 'Camila Santos',
        'Diego Pereira', 'Eduardo Costa', 'Fernanda Alves', 'Gabriel Martins',
    ];

    public function run(): void
    {
        $this->command->info('Criando 7 usuários de amostra para testes rápidos...');

        $names     = self::NAMES;
        shuffle($names);
        $nameIndex = 0;
        $summary   = [];

        foreach (self::PERSONAS as $persona) {
            $genresSamples = [];

            for ($i = 0; $i < $persona['count']; $i++) {
                $name   = $names[$nameIndex++];
                $age    = rand($persona['age_range'][0], $persona['age_range'][1]);
                $genres = $this->buildFavoriteGenres($persona);

                $emailDomain = $persona['skip_ratings'] && ! $persona['cold_start']
                    ? 'semhistorico.test'
                    : 'movietest.dev';

                User::create([
                    'name'            => $name,
                    'email'           => $this->toEmailSlug($name) . '@' . $emailDomain,
                    'password'        => Hash::make('password'),
                    'age'             => $age,
                    'favorite_genres' => $genres,
                ]);

                if (count($genresSamples) < 2 && $genres !== null) {
                    $genresSamples[] = implode(', ', $genres);
                }
            }

            $summary[] = [
                $persona['persona'],
                $persona['skip_ratings'] ? 'sem ratings' : 'com ratings',
                $persona['age_range'][0] . '–' . $persona['age_range'][1],
                $persona['cold_start']
                    ? '— null (Cold Start) —'
                    : implode(' | ', $genresSamples),
            ];
        }

        $this->command->info('✓ 7 usuários criados (5 com ratings, 2 sem relação com filmes).');
        $this->command->newLine();
        $this->command->table(
            ['Persona', 'Ratings', 'Idade', 'Gêneros favoritos'],
            $summary
        );
    }

    private function buildFavoriteGenres(array $persona): ?array
    {
        if ($persona['cold_start']) {
            return null;
        }

        $core       = $persona['core_genres'];
        $secondary  = $persona['secondary_genres'];
        $occasional = $persona['occasional_genres'];

        shuffle($core);
        shuffle($secondary);
        shuffle($occasional);

        $coreCount = rand(1, min(2, count($core)));
        $picked    = array_slice($core, 0, $coreCount);

        $secCount = rand(1, min(3, count($secondary)));
        $picked   = array_merge($picked, array_slice($secondary, 0, $secCount));

        if (! empty($occasional) && rand(1, 100) <= 30) {
            $picked[] = $occasional[0];
        }

        if (rand(1, 100) <= 8) {
            $outsideGenres = array_values(
                array_diff(UserSeeder::ALL_GENRES, array_merge($core, $secondary, $occasional))
            );
            shuffle($outsideGenres);
            $extraCount = rand(1, min(2, count($outsideGenres)));
            $picked     = array_merge($picked, array_slice($outsideGenres, 0, $extraCount));
        }

        $result = array_values(array_unique($picked));
        shuffle($result);

        return $result;
    }

    private function toEmailSlug(string $name): string
    {
        $accentMap = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a',
            'É' => 'e', 'Ê' => 'e',
            'Í' => 'i',
            'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o',
            'Ú' => 'u',
            'Ç' => 'c',
        ];

        $clean = strtr($name, $accentMap);
        $parts = explode(' ', strtolower(trim($clean)));

        return implode('.', $parts);
    }
}
