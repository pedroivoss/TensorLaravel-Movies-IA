# 🎬 TensorLaravel Movies IA

Sistema de Recomendação de Filmes com **Laravel 13**, **React 18**, **Ant Design** e **TensorFlow.js**.

O projeto faz parte de um curso de Pós-Graduação em IA aplicada ao desenvolvimento web. O objetivo é demonstrar como um modelo de Machine Learning pode ser **treinado diretamente no browser do usuário** via Web Worker com TF.js, enquanto o Laravel serve os dados e o contexto.

---

## 🏗️ Arquitetura

```
┌──────────────────────┐      API JSON      ┌─────────────────────┐
│   Browser (React)    │ ←────────────────→ │   Laravel 11 (API)  │
│                      │                    │                     │
│  • Ant Design UI     │                    │  • Usuários         │
│  • Busca de filmes   │                    │  • 6.178 filmes IMDB│
│  • Recomendações     │                    │  • Avaliações (1-5) │
│                      │                    └─────────────────────┘
│  [Web Worker — TF.js]│  ← próxima etapa
│  • Treina modelo     │
│  • Retorna scores    │
└──────────────────────┘
```

### Stack

| Camada    | Tecnologia                  | Versão |
| --------- | --------------------------- | ------- |
| Backend   | Laravel                     | 13      |
| Frontend  | React + Vite                | 18 / 5  |
| UI        | Ant Design                  | 5.x     |
| IA (soon) | TensorFlow.js (Web Worker)  | 4.x     |
| Banco     | SQLite (dev) / MySQL (prod) | —      |

---

## 🚀 Setup completo (do zero)

### 1. Clonar e instalar dependências PHP

```bash
git clone <url-do-repositorio>
cd TensorLaravel-Movies-IA

composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configurar banco de dados

Edite o `.env` para o banco desejado. Para desenvolvimento rápido com **SQLite**:

```env
DB_CONNECTION=sqlite
# DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD não são necessários para SQLite
```

Para **MySQL**:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tensor_laravel
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

### 3. Criar tabelas (migrations)

```bash
php artisan migrate:fresh
```

Tabelas criadas:

- `users` — usuários com perfil e gêneros favoritos
- `movies` — catálogo de filmes do IMDB
- `movie_user_ratings` — avaliações (1-5) de usuários por filme

### 4. Importar filmes do CSV

```bash
php artisan movies:import
```

Importa **6.178 filmes** do arquivo `imdb.csv` na raiz do projeto.
Campos importados: `name`, `genre`, `rate`, `duration`, `release_year`.

Para reimportar do zero (apaga e reimporta):

```bash
php artisan movies:import --fresh
```

### 5. Popular usuários e avaliações (seed)

```bash
php artisan db:seed
```

Cria **42 usuários** com perfis realistas e **1.520 avaliações** coerentes:

| Persona              | Qtd | Gêneros favoritos (variados)                 |
| -------------------- | --- | --------------------------------------------- |
| Jovem Aventureiro    | 6   | Action, Adventure, Sci-Fi, Thriller…         |
| Cinéfilo            | 6   | Drama, Crime, Thriller, Biography…           |
| Família             | 5   | Family, Animation, Comedy, Fantasy…          |
| Fã de Terror        | 5   | Horror, Thriller, Mystery, Crime…            |
| Amante de Clássicos | 4   | Drama, Biography, History, War…              |
| Comédia & Romance   | 4   | Romance, Comedy, Drama, Music…               |
| Documentarista       | 3   | Documentary, Biography, News…                |
| Mundo Fantástico    | 3   | Fantasy, Animation, Adventure…               |
| Guerra & História   | 2   | War, History, Drama, Biography…              |
| Cold Start           | 2   | `null` — sem gêneros, sem histórico      |
| Sem Histórico       | 2   | Gêneros definidos,**sem avaliações** |

> Os usuários da mesma persona têm gêneros **diferentes entre si** (sorteio de core + secondary + occasional), tornando o treinamento do modelo mais rico.

### 6. Instalar dependências JavaScript

```bash
npm install --legacy-peer-deps
```

### 7. Rodar o servidor

**Duas abas de terminal:**

```bash
# Aba 1 — Servidor PHP
php artisan serve

# Aba 2 — Vite (HMR do React)
npm run dev
```

Acesse: **http://localhost:8000**

---

## 🔁 Reset completo (banco + filmes + seed)

```bash
php artisan migrate:fresh
php artisan movies:import
php artisan db:seed
```

---

## 📡 API Endpoints

| Método | Rota                            | Descrição                                    |
| ------- | ------------------------------- | ---------------------------------------------- |
| GET     | `/api/users`                  | Lista todos os usuários com contagem de notas |
| GET     | `/api/users/{id}`             | Detalhes + filmes já assistidos               |
| GET     | `/api/movies?search=&page=`   | Busca paginada de filmes                       |
| GET     | `/api/recommendations/{user}` | Recomendações por gênero (placeholder)      |
| POST    | `/api/ratings`                | Registra ou atualiza uma avaliação           |

### Exemplo: Registrar avaliação

```bash
curl -X POST http://localhost:8000/api/ratings \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 42, "rating": 5}'
```

Resposta:

```json
{
  "success": true,
  "action": "created",
  "rating": {
    "id": 1521,
    "user_id": 1,
    "movie_id": 42,
    "rating": 5,
    "label": 1.0
  }
}
```

> O campo `label` é a nota normalizada para `[0.0, 1.0]` usando `(rating - 1) / 4`. Este é o valor que o TF.js usa como target durante o treinamento.

---

## 🗂️ Estrutura de Pastas

```
TensorLaravel-Movies-IA/
├── app/
│   ├── Console/Commands/
│   │   └── ImportMovies.php          ← php artisan movies:import
│   ├── Http/Controllers/Api/
│   │   ├── UserController.php
│   │   ├── MovieController.php
│   │   ├── RecommendationController.php
│   │   └── RatingController.php
│   └── Models/
│       ├── User.php
│       ├── Movie.php
│       └── MovieUserRating.php
├── database/
│   ├── migrations/                   ← 3 tabelas: users, movies, ratings
│   └── seeders/
│       ├── DatabaseSeeder.php        ← orquestrador
│       ├── UserSeeder.php            ← 42 usuários com perfis variados
│       └── MovieUserRatingSeeder.php ← 1.520 avaliações coerentes
├── resources/
│   ├── css/app.css
│   ├── js/
│   │   ├── app.jsx                   ← React entry point
│   │   ├── services/api.js           ← chamadas HTTP para a API Laravel
│   │   ├── Components/
│   │   │   ├── UserCard.jsx          ← card de usuário
│   │   │   └── MovieCard.jsx         ← card de filme com avaliação por estrelas
│   │   └── Pages/
│   │       └── RecommendationSystem.jsx ← página principal (SPA)
│   └── views/
│       └── app.blade.php             ← HTML shell que monta o React
├── routes/
│   ├── web.php                       ← rota única: serve o app.blade.php
│   └── api.php                       ← 5 endpoints JSON
└── imdb.csv                          ← dataset (6.178 filmes)
```

---

## 🧠 Próxima Etapa: TF.js Web Worker

A recomendação atual usa **Content-Based Filtering** simples (filtra por gêneros favoritos, ordena por nota IMDB).

A próxima etapa do curso adapta o `modelTrainingWorker.js` do projeto de e-commerce para este sistema:

```
resources/js/workers/
└── modelTrainingWorker.js   ← a criar na próxima etapa
```

**Fluxo planejado:**

1. O React busca o perfil do usuário e o histórico de avaliações via API.
2. Registra um Web Worker: `new Worker(new URL('./workers/modelTrainingWorker.js', import.meta.url))`.
3. Envia os dados de treino via `worker.postMessage({ users, movies, ratings })`.
4. O Worker carrega TF.js, treina um `tf.sequential()` com os dados recebidos.
5. Retorna os scores de recomendação via `self.postMessage({ scores })`.
6. O React exibe os filmes ordenados por score — **tudo no browser**, sem backend de IA.

**Vetor de entrada por par (usuário, filme):**

```
[
  age_normalizado,           // user.age / 100
  ...generos_favoritos_onehot,  // 27 posições (0 ou 1)
  rate_normalizada,          // movie.rate / 10
  duration_normalizada,      // movie.duration / 300
  ...generos_filme_onehot,   // 27 posições (0 ou 1)
]
// Total: 57 features de entrada
```

**Label:** `(rating - 1) / 4` → intervalo `[0.0, 1.0]`

---

## 🧩 Casos de Borda Modelados

| Cenário            | Como é tratado                                            |
| ------------------- | ---------------------------------------------------------- |
| Cold Start absoluto | `favorite_genres = null` → TF.js usa `tf.zeros(27)`   |
| Sem histórico      | Usuário com gêneros mas sem avaliações                 |
| Usuário eclético  | 8% de chance de receber gêneros fora da persona           |
| Filme sem gênero   | `genre = null` → `tf.zeros(27)` para o vetor do filme |

---

## 📦 Dependências

### PHP (composer)

- `laravel/framework` ^11.0

### JavaScript (npm)

- `react` + `react-dom` ^18.3
- `antd` ^5.22 + `@ant-design/icons` ^5.5
- `axios` ^1.7
- `vite` ^5.4 + `@vitejs/plugin-react` ^4.3
- `laravel-vite-plugin` ^1.0

---

## 📄 Licença

MIT
