# 🎬 TensorLaravel Movies IA

Sistema de Recomendação de Filmes com **Laravel**, **React 18**, **Ant Design 5**, **Bootstrap 5** e **MySQL**.

Projeto de pós-graduação que demonstra duas abordagens de frontend consumindo a mesma API REST Laravel:  
uma **SPA React** completa com Ant Design e uma **interface Blade** com Bootstrap 5 + jQuery.  
O objetivo final é integrar **TensorFlow.js** para treinar e executar o modelo de recomendação diretamente no browser.

---

## 🏗️ Arquitetura

```
┌──────────────────────────────────────────────────────────────────┐
│  Browser                                                         │
│                                                                  │
│  ┌───────────────────────────┐   ┌──────────────────────────┐   │
│  │  React 18 + Ant Design 5  │   │  Blade + Bootstrap 5     │   │
│  │  /react-movie-ia          │   │  /blade-movie-ia         │   │
│  │                           │   │                          │   │
│  │  • Seletor de usuários    │   │  • Seletor de usuários   │   │
│  │  • Busca paginada filmes  │   │  • Busca de filmes       │   │
│  │  • Avaliação (1-5 ★)     │   │  • Filtros e ordenação   │   │
│  │  • Recomendações          │   │  • Avaliação (1-5 ★)    │   │
│  │    content-based          │   │  • Recomendações         │   │
│  │                           │   │  • Modal Treinar Modelo  │   │
│  │                           │   │    (TF.js via CDN — WIP) │   │
│  └─────────────┬─────────────┘   └────────────┬─────────────┘   │
└────────────────│────────────────────────────────│────────────────┘
                 │  HTTP / JSON                   │
┌────────────────▼────────────────────────────────▼────────────────┐
│  Laravel — API REST                                               │
│                                                                   │
│  GET  /api/users                  → lista usuários               │
│  GET  /api/users/{id}             → perfil + filmes avaliados    │
│  GET  /api/movies?search=&page=   → busca paginada de filmes     │
│  GET  /api/recommendations/{user} → content-based filtering      │
│  POST /api/ratings                → registra avaliação (1-5)     │
│                                                                   │
│  MySQL                                                            │
│    movies               → 6.178 filmes (IMDB CSV)                │
│    users                → 42 usuários com perfis variados        │
│    movie_user_ratings   → avaliações 1-5 ★                      │
└───────────────────────────────────────────────────────────────────┘
```

### Stack

| Camada         | Tecnologia                     |
| -------------- | ------------------------------ |
| Backend        | Laravel                        |
| Banco          | MySQL                          |
| Frontend React | React 18 + Vite                |
| UI React       | Ant Design 5 + icons           |
| Frontend Blade | Bootstrap 5 + jQuery + Select2 |
| IA (em breve)  | TensorFlow.js (CDN)            |

---

## 🌐 Rotas Web

| Rota               | Descrição                                         |
| ------------------ | ------------------------------------------------- |
| `GET /`            | Welcome page (Laravel default)                    |
| `GET /react-movie-ia` | SPA React com Ant Design                       |
| `GET /blade-movie-ia` | Interface Blade com Bootstrap 5                |

---

## 📡 API Endpoints

| Método | Rota                          | Descrição                                       |
| ------ | ----------------------------- | ----------------------------------------------- |
| GET    | `/api/users`                  | Lista usuários com contagem de avaliações       |
| GET    | `/api/users/{id}`             | Perfil + IDs de filmes já avaliados             |
| GET    | `/api/movies`                 | Busca paginada (`?search=`, `?page=`, `?per_page=`) |
| GET    | `/api/recommendations/{user}` | Recomendações por content-based filtering       |
| POST   | `/api/ratings`                | Registra / atualiza avaliação (1-5 estrelas)    |

### `POST /api/ratings` — body esperado

```json
{
  "user_id":  1,
  "movie_id": 42,
  "rating":   4
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

| Tabela                | Conteúdo                                    |
| --------------------- | ------------------------------------------- |
| `users`               | 42 usuários com perfis e gêneros favoritos  |
| `movies`              | 6.178 filmes do IMDB                        |
| `movie_user_ratings`  | ~1.600 avaliações 1–5 estrelas              |

#### Fluxo de amostra — modo rápido para testes do modelo

Ideal para iterar no TF.js sem esperar a importação e o seed completos.

```bash
php artisan migrate:fresh
php artisan movies:import --sample                   # 20 filmes variados por gênero
php artisan db:seed --class=SampleDatabaseSeeder     # 7 usuários, ~25 avaliações
```

| Tabela                | Conteúdo (sample)                                              |
| --------------------- | -------------------------------------------------------------- |
| `movies`              | 20 filmes, 1–2 por gênero (round-robin no CSV)                |
| `users`               | 7 usuários — 5 com avaliações + 2 sem relação com filmes       |
| `movie_user_ratings`  | ~25 avaliações (5 usuários × até 5 filmes cada)               |

Os 2 usuários sem relação com filmes simulam casos de borda para a rede neural:
- **Cold Start absoluto** — sem gêneros favoritos e sem avaliações (`favorite_genres = NULL`)
- **Sem Histórico** — tem gêneros favoritos declarados, mas nunca avaliou nenhum filme

Para resetar e re-popular no modo sample:

```bash
php artisan migrate:fresh
php artisan movies:import --fresh --sample
php artisan db:seed --class=SampleDatabaseSeeder
```

### 4. Instalar dependências JavaScript

```bash
npm install
```

### 5. Rodar os servidores

```bash
# Terminal 1 — Laravel (porta 8000 por padrão)
php artisan serve

# Terminal 2 — Vite (HMR para o React)
npm run dev
```

Acesse:
- React → **http://localhost:8000/react-movie-ia**
- Blade → **http://localhost:8000/blade-movie-ia**

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
│   │   ├── PageBladeController.php      ← serve /blade-movie-ia
│   │   ├── PageReactController.php      ← serve /react-movie-ia
│   │   └── Api/
│   │       ├── UserController.php
│   │       ├── MovieController.php
│   │       ├── RatingController.php
│   │       └── RecommendationController.php
│   └── Models/
│       ├── User.php
│       ├── Movie.php
│       └── MovieUserRating.php
├── resources/
│   ├── js/
│   │   ├── app.jsx                      ← entry point React
│   │   ├── Pages/
│   │   │   └── RecommendationSystem.jsx ← SPA principal
│   │   ├── components/
│   │   │   ├── MovieCard.jsx
│   │   │   └── UserCard.jsx
│   │   ├── services/
│   │   │   └── api.js                   ← chamadas HTTP
│   │   ├── workers/                     ← (reservado — TF.js Web Worker em breve)
│   │   └── events/                      ← (reservado — contrato UI ↔ Worker em breve)
│   └── views/
│       ├── app.blade.php                ← entry point React (Vite)
│       ├── teste-ia.blade.php           ← interface Blade completa
│       └── modals/
│           └── treinar-modal.blade.php  ← modal TF.js (WIP)
├── public/assets/js/
│   └── treinamento.js                   ← lógica TF.js em desenvolvimento
├── routes/
│   ├── web.php
│   └── api.php
└── imdb.csv                             ← 6.178 filmes IMDB (fonte do import)
```

---

## 🔜 Próxima etapa: TF.js no browser

O modal **"Treinar Modelo"** na interface Blade está preparado para receber a lógica de treinamento em `public/assets/js/treinamento.js`. A integração planejada é:

```
1. Treinar IA (modal):
   Blade → GET /api/users + /api/movies + /api/ratings
   treinamento.js: monta dataset → treina tf.Sequential no browser
   Progresso exibido no log terminal do modal

2. Recomendar:
   Substitui o content-based atual por model.predict()
   executando localmente via TensorFlow.js (sem novo endpoint)
```

A arquitetura de rede planejada:

```
Input: [ age_norm, genres_user×N, rate_norm, duration_norm, genres_movie×N ]

Dense(128, relu)
Dense(64,  relu)
Dense(32,  relu)
Dense(1, sigmoid) → probabilidade de gostar [0.0 .. 1.0]

Loss:      binaryCrossentropy
Optimizer: Adam
Label:     (rating - 1) / 4  →  1★=0.00  3★=0.50  5★=1.00
```

---

## 🧩 Algoritmo atual de recomendação

Enquanto o TF.js não está integrado, as recomendações usam **content-based filtering** simples:

| Situação          | Comportamento                                                   |
| ----------------- | --------------------------------------------------------------- |
| Usuário com gêneros | Filtra filmes por gêneros favoritos, ordena por nota IMDB    |
| Cold Start (sem gêneros e sem histórico) | Top 20 filmes mais bem avaliados do IMDB |
| Filmes já avaliados | Excluídos das recomendações                                |

---

## 📦 Dependências principais

### PHP (Composer)

- `laravel/framework`

### JavaScript (npm)

- `react` + `react-dom`
- `antd` + `@ant-design/icons`
- `axios`
- `vite` + `@vitejs/plugin-react` + `laravel-vite-plugin`

### CDN (interface Blade)

- Bootstrap 5
- Bootstrap Icons
- jQuery + Select2
- `@tensorflow/tfjs` + `@tensorflow/tfjs-vis`

---

## 📄 Licença

MIT
