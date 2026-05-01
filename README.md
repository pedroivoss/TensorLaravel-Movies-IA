# 🎬 TensorLaravel Movies IA

Sistema de Recomendação de Filmes com **Laravel 13**, **React 18**, **Ant Design 5**, **TensorFlow.js 4** e **MySQL 9**.

Projeto de pós-graduação: demonstra como uma rede neural pode ser **treinada diretamente no browser** via Web Worker com TF.js, enquanto o Laravel 13 serve os dados e persiste o modelo treinado.

---

## 🏗️ Arquitetura

```
┌────────────────────────────────────────────────────────────────┐
│  Browser (React 18 + Ant Design 5)                             │
│                                                                │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Web Worker — TensorFlow.js 4 (WebGL)                   │   │
│  │                                                         │   │
│  │  • makeContext()      → min/max features, mapas de ID   │   │
│  │  • encodeUser()       → vetor 28-dim do usuário         │   │
│  │  • encodeMovie()      → vetor 29-dim do filme           │   │
│  │  • buildAndTrain()    → tf.Sequential (57→128→64→32→1)  │   │
│  │  • model.predict()    → scores em batch (WebGL)         │   │
│  │  • saveModelToServer()→ POST /api/model (base64)        │   │
│  └──────────────────┬──────────────────────────────────────┘   │
│                     │ postMessage (epochEnd, recommendations)   │
│  ┌──────────────────▼──────────────────────────────────────┐   │
│  │  RecommendationSystem.jsx                               │   │
│  │                                                         │   │
│  │  • Modal "Treinar IA" com tfjs-vis (loss + acurácia)    │   │
│  │  • Select2 de usuários com avatares                     │   │
│  │  • Painel de busca geral de filmes                      │   │
│  │  • Painel de recomendações (TF.js ou Content-Based)     │   │
│  └──────────────────┬──────────────────────────────────────┘   │
└─────────────────────│──────────────────────────────────────────┘
                      │ HTTP / JSON
┌─────────────────────▼──────────────────────────────────────────┐
│  Laravel 13 (API)                                              │
│                                                                │
│  GET  /api/training-data     → movies + users + ratings        │
│  GET  /api/model/status      → modelo salvo?                   │
│  GET  /api/model             → artefatos TF.js (base64)        │
│  POST /api/model             → salva modelo treinado           │
│  POST /api/embeddings/movies → salva vetores dos filmes        │
│  GET  /api/users             → lista usuários                  │
│  GET  /api/users/{id}        → perfil + filmes avaliados       │
│  GET  /api/movies            → busca paginada de filmes        │
│  GET  /api/recommendations/{user} → fallback content-based     │
│  POST /api/ratings           → registra avaliação (1-5)        │
│                                                                │
│  Banco: MySQL 9                                                │
│    movies          → 6.178 filmes (IMDB CSV)                   │
│    users           → 42 usuários com perfis variados           │
│    movie_user_ratings → 1.520 avaliações (1-5)                 │
│    ai_models       → modelo TF.js serializado (JSON+base64)    │
└────────────────────────────────────────────────────────────────┘
```

### Stack

| Camada     | Tecnologia              | Versão |
| ---------- | ----------------------- | ------ |
| Backend    | Laravel                 | 13     |
| Banco      | MySQL                   | 9      |
| Frontend   | React + Vite            | 18 / 5 |
| UI         | Ant Design              | 5.x    |
| IA Browser | TensorFlow.js           | 4.x    |
| Vis        | @tensorflow/tfjs-vis    | 2.x    |

---

## 🧠 Rede Neural

```
Input (57 features):
  [ age_norm, genres_user×27, rate_norm, duration_norm, genres_movie×27 ]
    └── 28 dims (usuário) ──┘  └──────────── 29 dims (filme) ────────────┘

Dense(128, relu)   ← captura combinações gênero × idade × qualidade
Dense(64,  relu)   ← refina e comprime representações
Dense(32,  relu)   ← embedding latente do par (usuário, filme)
Dense(1, sigmoid)  ← probabilidade de gostar [0.0 .. 1.0]

Loss:      binaryCrossentropy
Optimizer: Adam(lr=0.001)
Épocas:    30 | Batch: 32 | Shuffle: true

Label:  (rating - 1) / 4   →  1★=0.00  3★=0.50  5★=1.00
```

### Fluxo de recomendação com TF.js

```
1. Treinar IA (modal):
   React → GET /api/training-data
   React → Worker: { action: 'TRAIN_MODEL', movies, users, ratings }
   Worker: treina modelo (WebGL) + envia epochEnd por época → tfjs-vis
   Worker → POST /api/model         (artefatos base64)
   Worker → POST /api/embeddings/movies (vetores 29-dim dos filmes)
   Worker → React: { type: 'TRAINING_COMPLETE' }

2. Recomendar (usuário selecionado):
   React → Worker: { action: 'RECOMMEND', user }
   Worker: model.predict([6178 filmes × 57 features]) em batch WebGL
   Worker → React: { type: 'RECOMMENDATIONS', recommendations: [...top20] }

3. Recarregar após refresh:
   React → GET /api/model/status → trained: true
   React → Worker: { action: 'LOAD_MODEL' }
   Worker → GET /api/model → reconstrói tf.loadLayersModel(tf.io.fromMemory(...))
   Worker → React: { type: 'MODEL_LOADED', success: true }
```

---

## 🚀 Setup completo

### 1. Clonar e instalar PHP

```bash
git clone <url>
cd TensorLaravel-Movies-IA
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configurar MySQL 9

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tensor_laravel
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

### 3. Criar tabelas

```bash
php artisan migrate:fresh
```

Tabelas criadas:
- `users` — 42 usuários com perfis e gêneros favoritos
- `movies` — 6.178 filmes do IMDB (com coluna `embedding` para vetores TF.js)
- `movie_user_ratings` — 1.520 avaliações (1-5 estrelas)
- `ai_models` — modelo TF.js serializado (topologia + pesos em base64)

### 4. Importar filmes

```bash
php artisan movies:import
```

### 5. Popular usuários e avaliações

```bash
php artisan db:seed
```

42 usuários com 11 personas variadas, 1.520 avaliações coerentes para treinamento.

### 6. Instalar dependências JavaScript

```bash
npm install --legacy-peer-deps
```

### 7. Rodar os servidores

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (HMR)
npm run dev
```

Acesse: **http://localhost:8000**

---

## 🔁 Reset completo

```bash
php artisan migrate:fresh
php artisan movies:import
php artisan db:seed
```

---

## 📡 API Endpoints

| Método | Rota                          | Descrição                                     |
| ------ | ----------------------------- | --------------------------------------------- |
| GET    | `/api/users`                  | Lista usuários com contagem de avaliações     |
| GET    | `/api/users/{id}`             | Perfil + IDs de filmes já avaliados           |
| GET    | `/api/movies?search=&page=`   | Busca paginada de filmes                      |
| GET    | `/api/recommendations/{user}` | Fallback content-based (quando sem modelo)    |
| POST   | `/api/ratings`                | Registra/atualiza avaliação (1-5)             |
| GET    | `/api/training-data`          | Dataset completo p/ o Worker treinar          |
| GET    | `/api/model/status`           | Verifica se modelo treinado existe            |
| GET    | `/api/model`                  | Retorna artefatos do modelo (base64)          |
| POST   | `/api/model`                  | Salva modelo treinado pelo Worker             |
| POST   | `/api/embeddings/movies`      | Salva vetores de feature dos filmes em lote   |

---

## 🗂️ Estrutura de Pastas

```
TensorLaravel-Movies-IA/
├── app/Http/Controllers/Api/
│   ├── UserController.php
│   ├── MovieController.php
│   ├── RatingController.php
│   ├── RecommendationController.php    ← fallback content-based
│   └── TrainingController.php          ← training-data, model save/load, embeddings
├── app/Models/
│   ├── User.php | Movie.php | MovieUserRating.php
│   └── AiModel.php                     ← modelo TF.js no banco
├── database/migrations/
│   └── ..._create_ai_models_table.php  ← tabela de persistência do modelo
├── resources/js/
│   ├── app.jsx                         ← React entry point
│   ├── events/
│   │   └── workerEvents.js             ← contrato de eventos UI ↔ Worker
│   ├── workers/
│   │   └── modelTrainingWorker.js      ← TF.js Web Worker (treina + recomenda)
│   ├── services/api.js                 ← chamadas HTTP para a API Laravel
│   ├── Components/MovieCard.jsx
│   └── Pages/RecommendationSystem.jsx  ← SPA principal com modal de treinamento
├── routes/api.php
└── imdb.csv                            ← 6.178 filmes IMDB
```

---

## 🧩 Casos de Borda Modelados

| Cenário              | Tratamento                                                |
| -------------------- | --------------------------------------------------------- |
| Cold Start absoluto  | `favorite_genres = null` → `encodeUser` retorna zeros    |
| Sem histórico        | Usuário com gêneros mas sem avaliações (não treina)       |
| Filme sem gênero     | `genre = null` → `genresToOneHot` retorna zeros          |
| Filme sem nota       | `rate = null` → normaliza 5.0 (mediana IMDB)             |
| Refresh de página    | Worker auto-carrega modelo do servidor via `LOAD_MODEL`   |
| Re-treinamento       | Sobrescreve modelo e embeddings no banco (updateOrCreate) |

---

## 📦 Dependências principais

### PHP (composer)
- `laravel/framework` ^13.0

### JavaScript (npm)
- `react` + `react-dom` ^18.3
- `antd` ^5.22 + `@ant-design/icons` ^5.5
- `@tensorflow/tfjs` ^4.x
- `@tensorflow/tfjs-vis` ^2.x
- `axios` ^1.7
- `vite` ^5.4 + `@vitejs/plugin-react` ^4.3
- `laravel-vite-plugin` ^1.3

---

## 📄 Licença

MIT
