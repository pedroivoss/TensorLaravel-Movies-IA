<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder: UserSeeder
 *
 * Cria 42 usuários com perfis realistas e demograficamente coerentes,
 * projetados para gerar um sinal de treinamento rico para o TensorFlow.js.
 *
 * ── Problema que este seeder resolve ─────────────────────────────────────────
 * Quando todos os usuários de uma mesma persona recebem exatamente os mesmos
 * gêneros favoritos (ex: toda "Família" → ["Family","Animation","Comedy"]),
 * a rede neural aprende a associar PERSONAS a filmes, não PESSOAS.
 * Isso reduz drasticamente a qualidade das recomendações individuais.
 *
 * ── Solução: gêneros dinâmicos por persona ────────────────────────────────────
 * Cada persona define três camadas de afinidade:
 *
 *   core_genres      → gêneros centrais da persona (1-2 sempre presentes)
 *   secondary_genres → gêneros complementares (1-3 por usuário, aleatório)
 *   occasional_genres→ gêneros ocasionais (30% de chance de aparecer)
 *
 * Com isso, dois usuários da mesma persona podem ter perfis distintos:
 *
 *   Família A: ["Family", "Animation", "Comedy", "Music"]
 *   Família B: ["Family", "Adventure", "Fantasy"]
 *   Família C: ["Animation", "Comedy", "Sci-Fi"]  ← gênero eclético ocasional
 *
 * ── Casos de borda modelados ─────────────────────────────────────────────────
 *
 *   Cold Start absoluto (2 usuários):
 *     favorite_genres = NULL → a rede recebe tf.zeros() para o vetor de gêneros
 *     e tenta recomendar usando apenas a idade normalizada.
 *
 *   Sem histórico / Cold Start parcial (2 usuários):
 *     favorite_genres preenchido, mas SEM avaliações no banco.
 *     Identificados pelo domínio de email: @semhistorico.test
 *     O MovieUserRatingSeeder os exclui automaticamente pelo email.
 *
 * ── Nomes ────────────────────────────────────────────────────────────────────
 * Pool de 50 nomes brasileiros reais. Cada usuário recebe um nome único
 * retirado sequencialmente do pool (sem repetição).
 */
class UserSeeder extends Seeder
{
    /**
     * Os 27 gêneros únicos extraídos do imdb.csv.
     *
     * A ordem deve ser mantida, pois o frontend usa este índice para
     * montar o vetor one-hot de entrada da rede neural. Exemplo:
     *   Action → índice 0 → [1, 0, 0, 0, ...]
     *   Comedy → índice 4 → [0, 0, 0, 0, 1, ...]
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
     * Pool de 50 nomes brasileiros reais.
     *
     * São retirados sequencialmente — cada usuário recebe um nome único.
     * O pool é maior que o total de usuários (42) para garantir margem de folga.
     */
    private const NAMES = [
        'Ana Lima',          'Beatriz Oliveira',  'Bruno Ferreira',
        'Camila Santos',     'Carlos Silva',      'Daniela Souza',
        'Diego Pereira',     'Eduardo Costa',     'Elisa Rodrigues',
        'Felipe Gomes',      'Fernanda Alves',    'Gabriel Martins',
        'Giovanna Ribeiro',  'Helena Carvalho',   'Henrique Almeida',
        'Igor Nascimento',   'Isabela Barbosa',   'João Rocha',
        'Juliana Dias',      'Karen Vieira',      'Laura Moreira',
        'Leonardo Nunes',    'Lucas Machado',     'Mariana Lima',
        'Marcos Freitas',    'Natalia Andrade',   'Nicolas Lopes',
        'Olivia Mendes',     'Otávio Cunha',      'Patricia Pinto',
        'Paulo Ramos',       'Rafael Azevedo',    'Renata Borges',
        'Ricardo Correia',   'Sabrina Melo',      'Sandro Castro',
        'Sofia Carvalho',    'Tainá Monteiro',    'Thiago Soares',
        'Valentina Morais',  'Victor Cardoso',    'Yasmin Araújo',
        'Amanda Coelho',     'Breno Xavier',      'Claudia Matos',
        'Denise Paiva',      'Estevão Leite',     'Flávio Campos',
        'Gisele Torres',     'Hudson Guimarães',
    ];

    /**
     * Definição das personas de usuário.
     *
     * Cada entrada representa um "arquétipo humano" com:
     *   - persona          : rótulo descritivo (apenas para exibição no seed)
     *   - age_range        : faixa etária realista para este perfil
     *   - core_genres      : gêneros centrais (1-2 sempre incluídos)
     *   - secondary_genres : gêneros complementares (1-3 incluídos aleatoriamente)
     *   - occasional_genres: gêneros incomuns para esta persona (30% de chance)
     *   - count            : número de usuários a criar nesta persona
     *   - cold_start       : true = favorite_genres = NULL (sem preferências)
     *   - skip_ratings     : true = MovieUserRatingSeeder não gera notas para eles
     *
     * Personas marcadas com skip_ratings=true são identificadas no RatingSeeder
     * pelo domínio de email "@semhistorico.test" (Cold Start parcial) ou
     * por favorite_genres=null (Cold Start absoluto).
     */
    private const PERSONAS = [

        // ── 1. Jovem Aventureiro ──────────────────────────────────────────────
        // Perfil: 16-25 anos, vive para ação, explosões e mundos futuristas.
        // Exemplos esperados:
        //   ["Action", "Adventure", "Sci-Fi"]
        //   ["Action", "Thriller", "Animation", "Comedy"]
        //   ["Adventure", "Sci-Fi", "Fantasy", "Action"]
        [
            'persona'           => 'Jovem Aventureiro',
            'age_range'         => [16, 25],
            'core_genres'       => ['Action', 'Adventure'],
            'secondary_genres'  => ['Sci-Fi', 'Thriller', 'Fantasy', 'Animation', 'Comedy'],
            'occasional_genres' => ['Crime', 'Horror', 'Sport', 'War'],
            'count'             => 6,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 2. Cinéfilo ───────────────────────────────────────────────────────
        // Perfil: 26-40 anos, aprecia narrativas complexas e personagens densos.
        // Exemplos esperados:
        //   ["Drama", "Crime", "Thriller", "Mystery"]
        //   ["Crime", "Thriller", "Biography", "Film-Noir"]
        //   ["Drama", "Mystery", "History"]
        [
            'persona'           => 'Cinéfilo',
            'age_range'         => [26, 40],
            'core_genres'       => ['Drama', 'Crime'],
            'secondary_genres'  => ['Thriller', 'Mystery', 'Biography', 'History'],
            'occasional_genres' => ['Documentary', 'War', 'Film-Noir', 'Romance'],
            'count'             => 6,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 3. Família ────────────────────────────────────────────────────────
        // Perfil: 30-50 anos, busca conteúdo que toda a família pode assistir.
        // Exemplos esperados:
        //   ["Family", "Animation", "Comedy"]
        //   ["Family", "Adventure", "Fantasy", "Music"]
        //   ["Animation", "Comedy", "Musical"]
        [
            'persona'           => 'Família',
            'age_range'         => [30, 50],
            'core_genres'       => ['Family', 'Animation'],
            'secondary_genres'  => ['Comedy', 'Adventure', 'Fantasy', 'Music'],
            'occasional_genres' => ['Drama', 'Musical', 'Romance', 'Sci-Fi'],
            'count'             => 5,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 4. Fã de Terror ───────────────────────────────────────────────────
        // Perfil: 18-35 anos, busca adrenalina, sustos e tensão psicológica.
        // Exemplos esperados:
        //   ["Horror", "Thriller", "Mystery"]
        //   ["Horror", "Crime", "Drama", "Mystery"]
        //   ["Thriller", "Mystery", "Comedy"]  ← horror comedy ocasional
        [
            'persona'           => 'Fã de Terror',
            'age_range'         => [18, 35],
            'core_genres'       => ['Horror', 'Thriller'],
            'secondary_genres'  => ['Mystery', 'Crime', 'Drama'],
            'occasional_genres' => ['Comedy', 'Sci-Fi', 'Documentary', 'Romance'],
            'count'             => 5,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 5. Amante de Clássicos ────────────────────────────────────────────
        // Perfil: 40-70 anos, valoriza profundidade histórica e atuações dramáticas.
        // Exemplos esperados:
        //   ["Drama", "Biography", "History", "War"]
        //   ["Drama", "Crime", "Film-Noir"]
        //   ["Biography", "History", "Western"]
        [
            'persona'           => 'Amante de Clássicos',
            'age_range'         => [40, 70],
            'core_genres'       => ['Drama', 'Biography'],
            'secondary_genres'  => ['History', 'War', 'Crime', 'Mystery'],
            'occasional_genres' => ['Documentary', 'Film-Noir', 'Musical', 'Western'],
            'count'             => 4,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 6. Comédia & Romance ──────────────────────────────────────────────
        // Perfil: 20-45 anos, prefere leveza, sentimentos e boas risadas.
        // Exemplos esperados:
        //   ["Romance", "Comedy", "Drama"]
        //   ["Comedy", "Romance", "Music", "Family"]
        //   ["Romance", "Fantasy", "Musical"]
        [
            'persona'           => 'Comédia & Romance',
            'age_range'         => [20, 45],
            'core_genres'       => ['Romance', 'Comedy'],
            'secondary_genres'  => ['Drama', 'Music', 'Family'],
            'occasional_genres' => ['Fantasy', 'Mystery', 'Biography', 'Musical'],
            'count'             => 4,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 7. Documentarista ─────────────────────────────────────────────────
        // Perfil: 25-60 anos, prefere conteúdo baseado em fatos reais.
        // Exemplos esperados:
        //   ["Documentary", "Biography", "History"]
        //   ["Documentary", "News", "Crime", "Reality-TV"]
        //   ["Biography", "History", "War"]
        [
            'persona'           => 'Documentarista',
            'age_range'         => [25, 60],
            'core_genres'       => ['Documentary', 'Biography'],
            'secondary_genres'  => ['History', 'News', 'Reality-TV'],
            'occasional_genres' => ['Crime', 'Drama', 'War', 'Sport'],
            'count'             => 3,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 8. Mundo Fantástico ───────────────────────────────────────────────
        // Perfil: 12-35 anos, ama mundos mágicos, criaturas e épicos visuais.
        // Exemplos esperados:
        //   ["Fantasy", "Animation", "Adventure"]
        //   ["Fantasy", "Sci-Fi", "Family", "Music"]
        //   ["Animation", "Adventure", "Musical"]
        [
            'persona'           => 'Mundo Fantástico',
            'age_range'         => [12, 35],
            'core_genres'       => ['Fantasy', 'Animation'],
            'secondary_genres'  => ['Adventure', 'Family', 'Sci-Fi', 'Music'],
            'occasional_genres' => ['Musical', 'Comedy', 'Action', 'Romance'],
            'count'             => 3,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 9. Guerra & História ──────────────────────────────────────────────
        // Perfil: 30-65 anos, aprecia narrativas bélicas e retratos históricos.
        // Exemplos esperados:
        //   ["War", "History", "Drama"]
        //   ["War", "Biography", "Crime"]
        //   ["History", "Drama", "Western", "Documentary"]
        [
            'persona'           => 'Guerra & História',
            'age_range'         => [30, 65],
            'secondary_genres'  => ['Drama', 'Biography', 'Crime'],
            'core_genres'       => ['War', 'History'],
            'occasional_genres' => ['Western', 'Documentary', 'Thriller', 'Mystery'],
            'count'             => 2,
            'cold_start'        => false,
            'skip_ratings'      => false,
        ],

        // ── 10. Cold Start Absoluto ───────────────────────────────────────────
        // Usuário novo: sem preferências declaradas E sem histórico de notas.
        // favorite_genres = NULL → TF.js usa tf.zeros() para o vetor de gêneros.
        // A rede precisa aprender a lidar com este cenário de entrada zerada.
        [
            'persona'           => 'Cold Start',
            'age_range'         => [18, 60],
            'core_genres'       => [],
            'secondary_genres'  => [],
            'occasional_genres' => [],
            'count'             => 2,
            'cold_start'        => true,   // favorite_genres = null
            'skip_ratings'      => true,   // sem notas no banco
        ],

        // ── 11. Cold Start Parcial (Sem Histórico) ────────────────────────────
        // Usuário com preferências declaradas, mas que nunca avaliou um filme.
        // Representa alguém que configurou o perfil mas ainda não usou o app.
        // Identificado pelo domínio "@semhistorico.test" no email.
        // O MovieUserRatingSeeder os exclui por este domínio.
        [
            'persona'           => 'Sem Histórico',
            'age_range'         => [20, 50],
            'core_genres'       => ['Action', 'Drama'],
            'secondary_genres'  => ['Thriller', 'Comedy', 'Romance', 'Crime'],
            'occasional_genres' => ['Sci-Fi', 'Mystery', 'Biography'],
            'count'             => 2,
            'cold_start'        => false,  // tem gêneros favoritos
            'skip_ratings'      => true,   // mas não tem notas
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('Criando usuários com perfis realistas e variados...');

        // Embaralha o pool de nomes para que a ordem não siga nenhum padrão
        $names = self::NAMES;
        shuffle($names);
        $nameIndex = 0;

        $summary = [];

        foreach (self::PERSONAS as $persona) {
            $genresSamples = []; // coleta exemplos para exibir na tabela final

            for ($i = 0; $i < $persona['count']; $i++) {
                $name  = $names[$nameIndex++];
                $age   = rand($persona['age_range'][0], $persona['age_range'][1]);
                $genres = $this->buildFavoriteGenres($persona);

                // Usuários de Cold Start parcial recebem domínio especial.
                // O MovieUserRatingSeeder usa este domínio para excluí-los.
                $emailDomain = $persona['skip_ratings'] && ! $persona['cold_start']
                    ? 'semhistorico.test'
                    : 'movietest.dev';

                $email = $this->toEmailSlug($name) . '@' . $emailDomain;

                User::create([
                    'name'            => $name,
                    'email'           => $email,
                    'password'        => Hash::make('password'),
                    'age'             => $age,
                    'favorite_genres' => $genres,
                ]);

                // Coleta até 2 exemplos de gêneros para exibir na tabela
                if (count($genresSamples) < 2 && $genres !== null) {
                    $genresSamples[] = implode(', ', $genres);
                }
            }

            $summary[] = [
                $persona['persona'],
                $persona['count'],
                $persona['age_range'][0] . '–' . $persona['age_range'][1],
                $persona['cold_start']
                    ? '— null (Cold Start) —'
                    : implode(' | ', $genresSamples),
            ];
        }

        $total = array_sum(array_column(self::PERSONAS, 'count'));
        $this->command->info("✓ {$total} usuários criados com sucesso.");
        $this->command->newLine();

        $this->command->table(
            ['Persona', 'Qtd', 'Idade', 'Exemplos de gêneros (2 usuários)'],
            $summary
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Gera dinamicamente o array de gêneros favoritos para um usuário,
     * garantindo variação dentro da mesma persona.
     *
     * Algoritmo:
     *   1. Embaralha cada camada independentemente (novo seed por chamada).
     *   2. Seleciona 1-2 gêneros do core (sempre presentes).
     *   3. Seleciona 1-3 gêneros do secondary (variação principal).
     *   4. 30% de chance: adiciona 1 gênero occasional.
     *   5.  8% de chance: adiciona 1-2 gêneros de fora da persona ("eclético").
     *   6. Remove duplicatas, embaralha o array final e retorna.
     *
     * Resultado típico:
     *   - Mínimo: 2 gêneros (1 core + 1 secondary, sem extras)
     *   - Comum: 3–5 gêneros
     *   - Com occasional: +1
     *   - Eclético: +1–2 (simula usuários com gostos amplos)
     *
     * @param  array      $persona  Entrada de PERSONAS
     * @return array|null           Array de gêneros ou null (Cold Start)
     */
    private function buildFavoriteGenres(array $persona): ?array
    {
        // Cold Start absoluto: sem preferências declaradas
        if ($persona['cold_start']) {
            return null;
        }

        // Copia e embaralha cada camada para garantir aleatoriedade por chamada
        $core       = $persona['core_genres'];
        $secondary  = $persona['secondary_genres'];
        $occasional = $persona['occasional_genres'];

        shuffle($core);
        shuffle($secondary);
        shuffle($occasional);

        // 1-2 gêneros principais (âncora da persona)
        $coreCount = rand(1, min(2, count($core)));
        $picked    = array_slice($core, 0, $coreCount);

        // 1-3 gêneros secundários (principal fonte de variação)
        $secCount  = rand(1, min(3, count($secondary)));
        $picked    = array_merge($picked, array_slice($secondary, 0, $secCount));

        // 30% de chance de adicionar um gênero ocasional
        if (! empty($occasional) && rand(1, 100) <= 30) {
            $picked[] = $occasional[0]; // já embaralhado acima
        }

        // 8% de chance de ser "eclético": adiciona 1-2 gêneros de fora da persona.
        // Simula usuários com gostos amplos que assistem de tudo um pouco.
        if (rand(1, 100) <= 8) {
            $outsideGenres = array_values(
                array_diff(self::ALL_GENRES, array_merge($core, $secondary, $occasional))
            );
            shuffle($outsideGenres);
            $extraCount = rand(1, min(2, count($outsideGenres)));
            $picked     = array_merge($picked, array_slice($outsideGenres, 0, $extraCount));
        }

        // Remove duplicatas e embaralha para que a ordem não seja previsível
        $result = array_values(array_unique($picked));
        shuffle($result);

        return $result;
    }

    /**
     * Converte um nome completo em slug para uso em email.
     *
     * Remove acentos e converte para minúsculas com separador de ponto.
     * Ex: "Otávio Cunha" → "otavio.cunha"
     * Ex: "Tainá Monteiro" → "taina.monteiro"
     */
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
