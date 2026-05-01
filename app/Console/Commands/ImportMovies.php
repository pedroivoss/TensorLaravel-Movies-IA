<?php

namespace App\Console\Commands;

use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando Artisan: ImportMovies
 *
 * Lê o arquivo imdb.csv da raiz do projeto e popula a tabela 'movies'.
 *
 * Uso:
 *   php artisan movies:import
 *   php artisan movies:import --fresh   (limpa a tabela antes de importar)
 *
 * O comando trata os seguintes casos de borda do CSV:
 *   - "No Rate" / "None" → armazenado como NULL no banco
 *   - Duração com espaços: "163 " → 163 (inteiro)
 *   - Gêneros com espaços extras: "Action, Drama " → "Action, Drama"
 *   - Filmes duplicados: ignorados silenciosamente (INSERT IGNORE via firstOrCreate)
 */
class ImportMovies extends Command
{
    protected $signature = 'movies:import
                            {--fresh  : Limpa a tabela antes de importar}
                            {--sample : Importa apenas 20 filmes variados por gênero (modo rápido para testes)}';

    protected $description = 'Importa filmes do arquivo imdb.csv para a tabela movies';

    // Número de registros por batch (evita estouro de memória em CSVs grandes)
    private const BATCH_SIZE = 200;

    public function handle(): int
    {
        $csvPath = base_path('imdb.csv');

        // Verifica se o arquivo existe antes de qualquer operação no banco
        if (! file_exists($csvPath)) {
            $this->error("Arquivo não encontrado: {$csvPath}");
            $this->line('Certifique-se de que o imdb.csv está na raiz do projeto Laravel.');
            return self::FAILURE;
        }

        // --fresh: apaga tudo e reimporta do zero
        if ($this->option('fresh')) {
            $this->warn('Opção --fresh: limpando tabela movies...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // SQLite ignora isso (ok)
            Movie::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('Tabela limpa.');
        }

        // Modo sample: lê tudo em memória, seleciona 20 filmes variados e insere de uma vez
        if ($this->option('sample')) {
            return $this->handleSample($csvPath);
        }

        // Abre o CSV e pula o cabeçalho
        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle); // ["Name","Date","Rate","Votes","Genre","Duration",...]

        $this->info('Iniciando importação de filmes...');
        $this->newLine();

        $batch      = [];   // buffer de registros para insert em lote
        $imported   = 0;
        $skipped    = 0;
        $lineNumber = 1;    // começa em 1 porque o header foi lido

        // Barra de progresso (útil para os ~6000 registros do CSV)
        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% [%bar%] %elapsed:6s% — %message%');
        $bar->setMessage('lendo...');
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            // Protege contra linhas malformadas (menos colunas que o esperado)
            if (count($row) < 6) {
                $skipped++;
                continue;
            }

            // Mapeia colunas pelo nome do header para resistir a reordenações
            $data = array_combine($headers, $row);

            $movie = $this->parseRow($data);

            // Filmes sem nome são inválidos para o sistema de recomendação
            if (empty($movie['name'])) {
                $skipped++;
                continue;
            }

            $batch[] = array_merge($movie, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Quando o buffer atinge BATCH_SIZE, persiste no banco e limpa
            if (count($batch) >= self::BATCH_SIZE) {
                // insertOrIgnore garante idempotência: rodar o comando duas vezes
                // não duplica registros (assume name como identificador único aqui)
                DB::table('movies')->insertOrIgnore($batch);
                $imported += count($batch);
                $batch = [];
                $bar->setMessage("{$imported} filmes importados");
                $bar->advance(self::BATCH_SIZE);
            }
        }

        // Persiste o último batch (que pode ter menos que BATCH_SIZE registros)
        if (! empty($batch)) {
            DB::table('movies')->insertOrIgnore($batch);
            $imported += count($batch);
        }

        fclose($handle);

        $bar->finish();
        $this->newLine(2);

        // Resumo final
        $this->info("✓ Importação concluída!");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Filmes importados', $imported],
                ['Linhas ignoradas',  $skipped],
                ['Total no banco',    Movie::count()],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Modo sample: lê todo o CSV, seleciona 20 filmes variados por gênero e insere.
     */
    private function handleSample(string $csvPath): int
    {
        $this->info('Modo --sample: lendo CSV e selecionando 20 filmes variados...');

        $handle  = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);

        $allRows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 6) {
                continue;
            }
            $data  = array_combine($headers, $row);
            $movie = $this->parseRow($data);
            if (empty($movie['name'])) {
                continue;
            }
            $allRows[] = $movie;
        }
        fclose($handle);

        $this->line(count($allRows) . ' filmes válidos encontrados no CSV.');

        $sample = $this->selectVariedSample($allRows, 20);
        $batch  = array_map(fn ($m) => array_merge($m, [
            'created_at' => now(),
            'updated_at' => now(),
        ]), $sample);

        DB::table('movies')->insertOrIgnore($batch);
        $imported = count($batch);

        $this->newLine();
        $this->info("✓ Importação de amostra concluída!");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Filmes importados (sample)', $imported],
                ['Total no banco',             Movie::count()],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Seleciona $count filmes de forma variada, garantindo diversidade de gêneros.
     *
     * Agrupa todos os filmes por gênero principal, embaralha cada grupo e faz
     * um round-robin (um filme de cada gênero por vez) até atingir $count.
     * Isso simula situações reais com dados representativos mas reduzidos.
     */
    private function selectVariedSample(array $rows, int $count): array
    {
        $buckets = [];
        foreach ($rows as $row) {
            $firstGenre = $row['genre']
                ? trim(explode(',', $row['genre'])[0])
                : '_none';
            $buckets[$firstGenre][] = $row;
        }

        // Embaralha cada balde de gênero de forma independente
        array_walk($buckets, fn (&$b) => shuffle($b));

        $selected = [];
        $genres   = array_keys($buckets);
        $pointers = array_fill_keys($genres, 0);

        // Round-robin: um filme de cada gênero até atingir $count
        while (count($selected) < $count) {
            $added = false;
            foreach ($genres as $genre) {
                if (count($selected) >= $count) {
                    break;
                }
                if (isset($buckets[$genre][$pointers[$genre]])) {
                    $selected[] = $buckets[$genre][$pointers[$genre]++];
                    $added      = true;
                }
            }
            if (! $added) {
                break; // todos os baldes esgotados
            }
        }

        return $selected;
    }

    /**
     * Converte uma linha do CSV em array pronto para inserção no banco.
     * Centraliza toda a lógica de limpeza e normalização dos dados brutos.
     */
    private function parseRow(array $data): array
    {
        return [
            // Título: trim remove espaços e aspas residuais do CSV
            'name' => trim($data['Name'] ?? ''),

            // Gêneros: remove espaços extras entre vírgulas
            // Ex: "Action, Adventure, Thriller " → "Action, Adventure, Thriller"
            'genre' => $this->parseGenre($data['Genre'] ?? ''),

            // Nota: "No Rate" e valores não numéricos viram NULL
            'rate' => $this->parseFloat($data['Rate'] ?? ''),

            // Duração: "163 " → 163 (inteiro), "None" → NULL
            'duration' => $this->parseInt($data['Duration'] ?? ''),

            // Ano: "2021" → 2021, inválido → NULL
            'release_year' => $this->parseInt($data['Date'] ?? ''),

            // Embedding não é gerado na importação — será calculado depois
            // pelo comando: php artisan movies:generate-embeddings
            'embedding' => null,
        ];
    }

    /**
     * Normaliza a string de gêneros do CSV.
     * Retorna NULL se vazio ou inválido.
     */
    private function parseGenre(string $raw): ?string
    {
        $cleaned = trim($raw);

        if ($cleaned === '' || strtolower($cleaned) === 'no rate') {
            return null;
        }

        // Normaliza espaços ao redor de vírgulas: "Action,  Drama" → "Action, Drama"
        return preg_replace('/\s*,\s*/', ', ', $cleaned);
    }

    /**
     * Converte string para float, retornando NULL se inválido.
     * Valores aceitos: "7.6", "8", "0.5"
     * Valores rejeitados: "No Rate", "None", "", "-"
     */
    private function parseFloat(string $raw): ?float
    {
        $cleaned = trim($raw);

        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }

    /**
     * Converte string para inteiro, retornando NULL se inválido.
     * Aceita: "163", "163 " (com espaço), "2021"
     * Rejeita: "None", "N/A", "", "-"
     */
    private function parseInt(string $raw): ?int
    {
        $cleaned = trim($raw);

        // Remove vírgulas de milhares que podem aparecer em algumas colunas
        $cleaned = str_replace(',', '', $cleaned);

        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return null;
        }

        return (int) $cleaned;
    }
}
