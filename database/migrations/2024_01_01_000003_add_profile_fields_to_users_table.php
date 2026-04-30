<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Adiciona campos de perfil na tabela de usuários.
 *
 * Contexto Web AI:
 * - 'age' será normalizado (dividido por 100) antes de entrar na rede neural,
 *   servindo como feature numérica contínua.
 * - 'favorite_genres' é salvo como JSON para permitir múltiplos gêneros sem
 *   uma tabela pivot separada. Quando NULL, o sistema está em modo "Cold Start":
 *   o TensorFlow.js usará tf.zeros() para o vetor de gêneros, forçando a rede
 *   a depender apenas da idade para o primeiro palpite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Idade do usuário (feature numérica para a rede neural)
            $table->unsignedSmallInteger('age')->nullable()->after('name');

            // Gêneros favoritos como array JSON (ex: ["Action","Comedy"])
            // NULL = Cold Start: usuário novo sem preferências declaradas
            $table->json('favorite_genres')->nullable()->after('age');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['age', 'favorite_genres']);
        });
    }
};
