<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model: User
 *
 * Representa um usuário do sistema de recomendação.
 *
 * Campos relevantes para a rede neural:
 * - age: feature numérica (normalizada dividindo por 100 no frontend).
 * - favorite_genres: JSON com array de gêneros preferidos.
 *   Ex: ["Action", "Sci-Fi"]
 *   Quando NULL → Cold Start absoluto: a rede usará tf.zeros() para o
 *   vetor de gêneros favoritos e tentará recomendar com base apenas na idade.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property int|null    $age
 * @property array|null  $favorite_genres
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'age',
        'favorite_genres',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            // Converte o JSON do banco em array PHP automaticamente
            'favorite_genres'   => 'array',
        ];
    }

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    /**
     * Todas as avaliações que este usuário já fez.
     * Usado para montar o histórico de treinamento do TensorFlow.js.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(MovieUserRating::class);
    }

    /**
     * Filmes que este usuário avaliou (via tabela movie_user_ratings).
     * Permite: $user->ratedMovies()->where('genre', 'like', '%Action%')->get()
     */
    public function ratedMovies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_user_ratings')
                    ->withPivot('rating')
                    ->withTimestamps();
    }

    // ─── Helpers para o Sistema de Recomendação ───────────────────────────────

    /**
     * Retorna se este usuário está em modo Cold Start.
     * Cold Start ocorre quando não há gêneros favoritos E não há histórico.
     *
     * No TensorFlow.js, este cenário usa tf.zeros() para ambos os vetores
     * de gênero, deixando apenas a idade como feature válida.
     */
    public function isColdStart(): bool
    {
        return empty($this->favorite_genres) && $this->ratings()->count() === 0;
    }

    /**
     * Retorna a idade normalizada para [0, 1] (dividindo por 100).
     * Feature numérica direta para a camada de entrada da rede neural.
     */
    public function getNormalizedAgeAttribute(): float
    {
        return $this->age ? round($this->age / 100, 2) : 0.0;
    }

    /**
     * Retorna os gêneros favoritos garantidamente como array (nunca null).
     * Quando favorite_genres é null, retorna [] para facilitar o one-hot encoding.
     */
    public function getFavoriteGenresArrayAttribute(): array
    {
        return $this->favorite_genres ?? [];
    }
}
