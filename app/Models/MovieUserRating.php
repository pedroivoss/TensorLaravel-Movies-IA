<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: MovieUserRating
 *
 * Representa a avaliação de um usuário para um filme específico.
 * Esta é a "tabela de verdade" do sistema: cada linha é um par (usuário, filme)
 * com uma nota de 1 a 5 que o TensorFlow.js usará como label de treinamento.
 *
 * Fluxo de dados no treinamento:
 * 1. Laravel serve os ratings via API JSON para o frontend.
 * 2. O Web Worker normaliza: label = (rating - 1) / 4  →  [0.0, 1.0]
 * 3. O TensorFlow.js treina a rede para prever esse valor dado:
 *    - Vetor one-hot dos gêneros favoritos do usuário
 *    - Idade normalizada do usuário
 *    - Vetor one-hot dos gêneros do filme
 *    - Nota e duração normalizadas do filme
 *
 * @property int $id
 * @property int $user_id
 * @property int $movie_id
 * @property int $rating   (1 a 5)
 */
class MovieUserRating extends Model
{
    protected $fillable = [
        'user_id',
        'movie_id',
        'rating',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    /**
     * O usuário que fez esta avaliação.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * O filme que foi avaliado.
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    // ─── Helpers para o TensorFlow.js ─────────────────────────────────────────

    /**
     * Retorna a nota normalizada para [0.0, 1.0].
     * Fórmula: (rating - 1) / 4
     * Exemplos:
     *   1 → 0.00 (Odiei)
     *   3 → 0.50 (Neutro)
     *   5 → 1.00 (Amei)
     */
    public function getNormalizedRatingAttribute(): float
    {
        return round(($this->rating - 1) / 4, 4);
    }
}
