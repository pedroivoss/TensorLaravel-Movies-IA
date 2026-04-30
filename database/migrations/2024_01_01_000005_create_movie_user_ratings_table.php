<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Cria a tabela de avaliações de filmes por usuário.
 *
 * Esta é a tabela central do sistema de aprendizado supervisionado.
 *
 * Contexto Web AI (por que 1-5 e não 0-1?):
 * - Em sistemas binários (comprou/não comprou), a rede aprende apenas presença.
 * - Com escala 1-5, a rede aprende INTENSIDADE: o que o usuário odeia (1-2),
 *   é indiferente (3) ou ama (4-5). Isso melhora drasticamente a precisão
 *   das recomendações, especialmente para o filtro colaborativo.
 * - A 'label' para o TensorFlow.js será este rating normalizado para [0, 1]:
 *   label = (rating - 1) / 4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_user_ratings', function (Blueprint $table) {
            $table->id();

            // Referência ao usuário que avaliou
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Referência ao filme avaliado
            $table->foreignId('movie_id')
                  ->constrained('movies')
                  ->cascadeOnDelete();

            // Nota de 1 a 5 — label de treinamento da rede neural
            // 1 = Odiei | 2 = Não gostei | 3 = Neutro | 4 = Gostei | 5 = Amei
            $table->unsignedTinyInteger('rating');

            $table->timestamps();

            // Garante que cada usuário avalia cada filme apenas uma vez
            $table->unique(['user_id', 'movie_id']);

            // Índices para queries de recomendação (busca por usuário e por filme)
            $table->index('user_id');
            $table->index('movie_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_user_ratings');
    }
};
