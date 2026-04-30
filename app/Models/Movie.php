<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model: Movie
 *
 * Representa um filme importado do imdb.csv.
 *
 * Campos principais para o sistema de recomendação:
 * - genre: string com gêneros separados por vírgula (ex: "Action, Drama").
 *   O frontend converte isso em um vetor one-hot de 27 posições (um por gênero).
 * - embedding: vetor pré-calculado como JSON. Quando presente, o Web Worker
 *   do navegador usa diretamente sem recalcular, economizando CPU no iPad.
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $genre
 * @property float|null  $rate
 * @property int|null    $duration
 * @property int|null    $release_year
 * @property array|null  $embedding
 */
class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'genre',
        'rate',
        'duration',
        'release_year',
        'embedding',
    ];

    /**
     * Converte 'embedding' de/para array PHP automaticamente.
     * Assim o controller recebe array, não string JSON.
     */
    protected $casts = [
        'rate'      => 'float',
        'embedding' => 'array',
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    /**
     * Todas as avaliações que este filme recebeu de usuários.
     * Usado para calcular métricas de popularidade no backend.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(MovieUserRating::class);
    }

    /**
     * Usuários que avaliaram este filme (via tabela movie_user_ratings).
     * Permite consultas como: $movie->raters()->where('age', '>', 25)->get()
     */
    public function raters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'movie_user_ratings')
                    ->withPivot('rating')
                    ->withTimestamps();
    }

    // ─── Helpers para o Sistema de Recomendação ───────────────────────────────

    /**
     * Retorna os gêneros do filme como array PHP.
     * Ex: "Action, Drama" → ["Action", "Drama"]
     *
     * Usado pelo comando de geração de embeddings para montar o vetor one-hot.
     */
    public function getGenresArrayAttribute(): array
    {
        if (empty($this->genre)) {
            return [];
        }

        return array_map('trim', explode(',', $this->genre));
    }

    /**
     * Retorna a nota normalizada para o intervalo [0, 1].
     * rate de 0-10 → divide por 10.
     *
     * Útil como feature de entrada para a rede neural no TensorFlow.js.
     */
    public function getNormalizedRateAttribute(): float
    {
        return $this->rate ? round($this->rate / 10, 3) : 0.0;
    }

    /**
     * Retorna a duração normalizada para [0, 1], assumindo máx. de 300 min.
     * Útil como feature numérica para a rede neural.
     */
    public function getNormalizedDurationAttribute(): float
    {
        return $this->duration ? round(min($this->duration, 300) / 300, 3) : 0.0;
    }
}
