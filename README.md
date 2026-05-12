# 🎬 TensorLaravel Movies IA

Sistema de **Recomendação de Filmes com IA no browser** construído com **Laravel**, **JavaScript**, **Bootstrap 5** e **TensorFlow.js**.

Projeto de pós-graduação que demonstra uma interface JavaScript consumindo uma API REST Laravel, com recomendações geradas por uma **rede neural treinada diretamente no browser** via TensorFlow.js, com persistência do modelo no banco de dados.

---

## 🏗️ Arquitetura

```
┌──────────────────────────────────────────────────────────────────────────┐
│  Browser                                                                 │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │  Blade + Bootstrap 5 + jQuery                                    │   │
│  │  /laravel-movie-ia                                                 │   │
│  │                                                                  │   │
│  │  • Seletor de usuários (Select2)                                 │   │
│  │  • Busca de filmes (6.178 títulos)                               │   │
│  │  • Filtros e ordenação                                           │   │
│  │  • Avaliação (1–5 ★) + persistência no banco                    │   │
│  │  • Remover filme dos assistidos                                  │   │
│  │  • Recomendações IA / fallback por gêneros                       │   │
│  │  • Modal "Treinar Modelo" (TF.js)                                │   │
│  │  • Console do Modelo (IA Log em tempo real)                      │   │
│  └──────────────────────────────┬───────────────────────────────────┘   │
│                                 │  TensorFlow.js                        │
│                    modelTrainingWorker.js                               │
│                    ┌──────────────────────────┐                        │
│                    │  Rede Neural (TF.js)      │                        │
│                    │  Dense(128, relu)         │                        │
│                    │  Dense(64,  relu)         │                        │
│                    │  Dense(32,  relu)         │                        │
│                    │  Dense(1, sigmoid)        │                        │
│                    │  → score de afinidade     │                        │
│                    └──────────────────────────┘                        │
└──────────────────────────────────────────────────────────────────────────┘
                 │  HTTP / JSON
┌────────────────▼──────────────────────────────────────────────────────────┐
│  Laravel — API REST                                                        │
│                                                                            │
│  GET  /api/users                  → lista usuários                        │
│  GET  /api/users/{id}             → perfil + filmes avaliados             │
│  POST /api/movies                 → busca de filmes                       │
│  GET  /api/recommendations/{user} → content-based filtering (fallback)    │
│  POST /api/ratings                → registra avaliação (1-5 estrelas)     │
│  DELETE /api/ratings              → remove avaliação                      │
│  POST /api/ai-models/save         → persiste modelo treinado (MySQL)      │
│  GET  /api/ai-models/load         → recupera modelo salvo                 │
│                                                                            │
│  MySQL                                                                     │
│    movies               → 6.178 filmes (IMDB CSV)                        │
│    users                → 42 usuários com perfis variados                 │
│    movie_user_ratings   → avaliações 1-5 ★                               │
│    ai_models            → topologia + pesos (base64) do modelo treinado   │
└────────────────────────────────────────────────────────────────────────────┘
```

### Stack

| Camada         | Tecnologia                                  |
| -------------- | ------------------------------------------- |
| Backend        | Laravel                                     |
| Banco          | MySQL                                       |
| Frontend       | JavaScript + Bootstrap 5 + jQuery + Select2 |
| IA (browser)   | TensorFlow.js + TFjs-Vis (CDN)              |
| Notificações | SweetAlert2 (CDN)                           |

---

## 🤖 Pipeline de IA — TensorFlow.js no browser

O treinamento acontece inteiramente no browser, sem backend de ML:

```
1. Treinar (modal "Treinar Modelo"):
   window.app.movies + window.app.users (já carregados pela Blade)
   makeContext()       → monta metadados: faixas de normalização, índice de gêneros
   createInputVector() → para cada par (usuário, filme avaliado):
                         [age_norm, rate_norm, ...genres_user(N), ...genres_movie(N)]
   tf.sequential()     → Dense(128)→Dense(64)→Dense(32)→Dense(1, sigmoid)
   model.fit()         → 50 épocas, batchSize=32, shuffle=true
   TFVisorView.js      → gráficos de Loss e Precisão por época (tfjs-vis)
   saveModelToDatabase() → POST /api/ai-models/save (pesos em base64 → MySQL)

2. Recomendar (ao selecionar usuário):
   getRecommendations(userId) → itera todos os filmes não assistidos
                               model.predict(inputVector) → score [0..1]
                               retorna Top 10 por score decrescente

3. Persistência:
   Ao abrir a página → GET /api/ai-models/load → restaura modelo salvo
   Modelo fica disponível em window._model + window._globalCtx
```

### Vetor de entrada

```
Dimensões = 2 + (numGêneros × 2)

[ age_norm      ]  → idade do usuário normalizada (min-max)
[ rate_norm     ]  → nota média do filme / 10
[ genres_user…  ]  → multi-hot encoding dos gêneros favoritos do usuário
[ genres_movie… ]  → multi-hot encoding dos gêneros do filme
```

---

## 🌐 Rota principal

| Rota                      | Descrição                         |
| ------------------------- | ----------------------------------- |
| `GET /`                 | Welcome page                        |
| `GET /laravel-movie-ia` | Interface Blade — sistema completo |

---

## 📡 API Endpoints

| Método | Rota                            | Descrição                                                  |
| ------- | ------------------------------- | ------------------------------------------------------------ |
| GET     | `/api/users`                  | Lista usuários com contagem de avaliações                 |
| GET     | `/api/users/{id}`             | Perfil + filmes avaliados + notas salvas                     |
| POST    | `/api/movies`                 | Busca de filmes (`{ search, ids? }`)                       |
| GET     | `/api/recommendations/{user}` | Recomendações content-based (fallback sem modelo treinado) |
| POST    | `/api/ratings`                | Registra / atualiza avaliação (1-5 estrelas)               |
| DELETE  | `/api/ratings`                | Remove avaliação (`{ user_id, movie_id }`)               |
| POST    | `/api/ai-models/save`         | Persiste topologia + pesos do modelo no MySQL                |
| GET     | `/api/ai-models/load`         | Recupera e restaura o último modelo salvo                   |

### `POST /api/ratings` — body esperado

```json
{
  "user_id":  1,
  "movie_id": 42,
  "rating":   4
}
```

### `POST /api/ai-models/save` — body esperado

```json
{
  "topology": "{ ...modelTopology com weightsManifest... }",
  "weights":  "<base64 dos pesos binários>"
}
```

---

## 🚀 Setup

### 1. Clonar e instalar PHP

```bash
git clone <url>
cd TensorLaravel-Movies-IA
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configurar banco de dados

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tensor_laravel
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

### 3. Criar tabelas e popular banco

#### Fluxo completo (~6 mil filmes, 42 usuários)

```bash
php artisan migrate:fresh
php artisan movies:import   # importa 6.178 filmes do IMDB CSV
php artisan db:seed         # cria 42 usuários e avaliações
```

| Tabela                 | Conteúdo                                    |
| ---------------------- | -------------------------------------------- |
| `users`              | 42 usuários com perfis e gêneros favoritos |
| `movies`             | 6.178 filmes do IMDB                         |
| `movie_user_ratings` | ~1.600 avaliações 1–5 estrelas            |
| `ai_models`          | modelo TF.js persistido (vazio até treinar) |

#### Fluxo de amostra — modo rápido para testes do modelo

Ideal para iterar no TF.js sem esperar a importação e o seed completos.

```bash
php artisan migrate:fresh
php artisan movies:import --sample                   # 20 filmes variados por gênero
php artisan db:seed --class=SampleDatabaseSeeder     # 7 usuários, ~25 avaliações
```

| Tabela                 | Conteúdo (sample)                                             |
| ---------------------- | -------------------------------------------------------------- |
| `movies`             | 20 filmes, 1–2 por gênero (round-robin no CSV)               |
| `users`              | 7 usuários — 5 com avaliações + 2 sem relação com filmes |
| `movie_user_ratings` | ~25 avaliações (5 usuários × até 5 filmes cada)           |

Os 2 usuários sem relação com filmes simulam casos de borda para a rede neural:

- **Cold Start absoluto** — sem gêneros favoritos e sem avaliações
- **Sem Histórico** — tem gêneros favoritos declarados, mas nunca avaliou nenhum filme

### 4. Rodar o servidor

```bash
php artisan serve --port=3000
```

Acesse: **http://localhost:3000/laravel-movie-ia**

> A interface Blade carrega todos os assets via CDN — não é necessário `npm run dev`.

---

## 🔁 Reset do banco

```bash
# Completo (produção / avaliação final)
php artisan migrate:fresh
php artisan movies:import
php artisan db:seed

# Amostra (desenvolvimento / testes do modelo TF.js)
php artisan migrate:fresh
php artisan movies:import --sample
php artisan db:seed --class=SampleDatabaseSeeder
```

---

## 🗂️ Estrutura de Pastas

```
TensorLaravel-Movies-IA/
├── app/
│   ├── Http/Controllers/
│   │   ├── PageBladeController.php           ← serve /laravel-movie-ia
│   │   └── Api/
│   │       ├── UserController.php
│   │       ├── MovieController.php
│   │       ├── RatingController.php
│   │       └── RecommendationController.php  ← inclui save/load do modelo
│   └── Models/
│       ├── User.php
│       ├── Movie.php
│       └── MovieUserRating.php
├── resources/views/
│   ├── teste-ia.blade.php                    ← interface principal (Blade)
│   ├── welcome.blade.php                     ← página inicial
│   └── modals/
│       └── treinar-modal.blade.php           ← modal TF.js com visor
├── public/assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── pageBlade.js                      ← lógica de UI da interface Blade
│       ├── TFVisorView.js                    ← gráficos de treinamento (tfjs-vis)
│       └── workers/
│           └── modelTrainingWorker.js        ← rede neural: makeContext,
│                                               createInputVector, trainModel,
│                                               getRecommendations, save/load
├── routes/
│   ├── web.php
│   └── api.php
└── imdb.csv                                  ← 6.178 filmes IMDB (fonte do import)
```

---

## 🧩 Algoritmo de Recomendação

### Com modelo treinado (TF.js)

Para cada filme não assistido pelo usuário, o modelo prevê um score de afinidade [0..1] baseado no vetor de entrada (idade, nota média do filme, gêneros do usuário e gêneros do filme). Os 10 filmes com maior score são retornados como recomendações.

### Fallback — Content-Based Filtering (sem modelo treinado)

| Situação                                 | Comportamento                                              |
| ------------------------------------------ | ---------------------------------------------------------- |
| Usuário com gêneros favoritos            | Filtra filmes por gêneros favoritos, ordena por nota IMDB |
| Cold Start (sem gêneros e sem histórico) | Top filmes mais bem avaliados do IMDB                      |
| Filmes já avaliados                       | Excluídos das recomendações                             |

---

## 📦 Dependências

### PHP (Composer)

- `laravel/framework`

### CDN (interface Blade — sem build step)

- Bootstrap 5 + Bootstrap Icons
- jQuery + Select2
- SweetAlert2
- `@tensorflow/tfjs` — treinamento e inferência no browser
- `@tensorflow/tfjs-vis` — gráficos de loss/precisão por época

---

## 📄 Licença

MIT
