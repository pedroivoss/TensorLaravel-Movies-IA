<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Cria a tabela de filmes populada a partir do imdb.csv.
 *
 * Contexto Web AI:
 * - 'genre': string com gêneros separados por vírgula (ex: "Action, Drama").
 *   O frontend fará o one-hot encoding para montar o vetor de entrada da rede.
 * - 'rate': nota média do IMDB, usada como feature de qualidade percebida.
 * - 'duration': duração em minutos (normalizada no frontend).
 * - 'release_year': ano de lançamento (feature temporal, normalizada no frontend).
 * - 'embedding': vetor pré-calculado (JSON) pelo Mac M1 Pro via Artisan Command.
 *   Salvar aqui evita que o iPad recalcule o embedding a cada sessão,
 *   economizando memória e processamento no dispositivo de debug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();

            // Título do filme (fonte: coluna "Name" do CSV)
            $table->string('name');

            // Gêneros separados por vírgula (fonte: coluna "Genre" do CSV)
            // Ex: "Action, Adventure, Thriller"
            $table->string('genre')->nullable();

            // Nota média do IMDB de 0.0 a 10.0 (fonte: coluna "Rate" do CSV)
            $table->float('rate', 4, 1)->nullable();

            // Duração em minutos (fonte: coluna "Duration" do CSV)
            $table->unsignedSmallInteger('duration')->nullable();

            // Ano de lançamento (fonte: coluna "Date" do CSV)
            $table->unsignedSmallInteger('release_year')->nullable();

            // Embedding pré-calculado: array de floats serializado como JSON.
            // Populado pelo comando: php artisan movies:generate-embeddings
            // Quando NULL, o frontend calcula o embedding em tempo real via TF.js.
            $table->longText('embedding')->nullable();

            $table->timestamps();

            // Índice para acelerar buscas por gênero no endpoint de recomendação
            $table->index('genre');
            $table->index('release_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
