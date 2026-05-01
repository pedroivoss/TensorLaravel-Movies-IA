//importScripts('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs');

let _globalCtx = {};

let _model = null

// essa constante define o peso relativo de cada tipo de dado (
// gênero, idade, avaliação) na entrada da rede neural
// por exemplo, se gênero tem peso 0.3, idade tem peso 0.2 e avaliação tem peso 0.1,
// isso significa que o gênero terá mais influência na recomendação do que a idade ou avaliação
const WEIGHTS = {
    genre: 0.3,
    age: 0.2,
    rating: 0.1
}

//Normaliza um valor entre 0 e 1, para evitar que a rede precise lidar com escalas diferentes
//exemplo de idade entre 18 e 65, ou preço entre 10 e 1000
//entao a idade 18 vira 0, a idade 65 vira 1, e a idade 40 vira algo em torno de 0.4

const normalize = (value, min, max) => (value - min) / (max - min) || 1

const makeContext = (movies, users) => {
    // 1. Extrair extremos para normalização
    const ages = users.map(u => u.age);
    const ratingsMovie = movies
        .map(m => Number(m.rate)) // converte para número, caso seja string
        .filter(r => !Number.isNaN(r)); // filtra valores não numéricos (ex: "N/A")

    const minAge = Math.min(...ages);
    const maxAge = Math.max(...ages);

    const minRating = Math.min(...ratingsMovie);
    const maxRating = Math.max(...ratingsMovie);

    // 2. Mapeamento de Gêneros (Categorização)
    // Criamos uma lista única de todos os gêneros disponíveis no catálogo
    // 2.1. Extrair todos os gêneros possíveis em uma lista única e limpa
    const allGenres = movies.flatMap(m => {
        // Se for string "Action, Drama", vira ["Action", "Drama"]
        if (typeof m.genre === 'string') {
            return m.genre.split(',').map(g => g.trim());
        }
        return m.genre || [];
    });

    // 2.2. Criar lista única, ordenada e sem duplicatas
    const uniqueGenres = Array.from(new Set(allGenres)).sort();

    const genreIndex = Object.fromEntries(
        uniqueGenres.map((genre, index) => {
            return [genre, index];
        })
    );

    //computar a media de idade dos comprados por produtos
    // (ajuda a personalizarr a recomendação, por exemplo, se um filme é mais popular entre jovens ou idosos)
    const midAge = (minAge + maxAge) / 2;
    const ageSums = {};
    const ageCounts = {};

    // Para cada usuário, para cada filme que ele assistiu,
    // somamos a idade e contamos quantas vezes esse filme foi assistido
    users.forEach(user => {
        (user.watchedMovies || []).forEach(wm => {
            ageSums[wm] = (ageSums[wm] || 0) + user.age;
            ageCounts[wm] = (ageCounts[wm] || 0) + 1;
        });
    });

    // Agora podemos calcular a idade média dos espectadores para cada filme
    // assim, se um filme é mais assistido por jovens, a idade média será menor, e vice-versa
    // E normalizamos essa idade média para ficar entre 0 e 1, usando o minAge e maxAge do dataset
    const movieAvgAgeNorm = Object.fromEntries(
        movies.map(m => {
            const avgAge = ageCounts[m.id] ? ageSums[m.id] / ageCounts[m.id] : midAge;
            return [m.id, normalize(avgAge, minAge, maxAge)];
        })
    )

    return {
        movies,
        users,
        genreIndex,
        minAge,
        maxAge,
        minRating,
        maxRating,
        numGenres: uniqueGenres.length,
        movieAvgAgeNorm,
        // age + rating + genre one-hot + ageMid do filme
        // 3 = idade + avaliação + idade média dos espectadores
        // e são normalizados entre 0 e 1, então a rede não precisa lidar com escalas diferentes
        // uniqueGenres.length = número de gêneros únicos, que serão representados como one-hot (0 ou 1)
        // indicando se o filme pertence a cada gênero
        dimentions: 3 + uniqueGenres.length

    }

}// fim makeContext

const oneHotWeight = (index, length, weight) =>
    tf.oneHot(index, length).cast('float32').mul(weight)

function encodeMovie(movie, context) {
    // normalizando dados para ficar de 0 a 1 e
    // aplicar os pesos para recomendação

    //normalizar o movie.genre usando o índice do gênero e o número total de gêneros,
    // e aplicar o peso definido em WEIGHTS
    // pois o movie.genre é uma string, precisamos converter para índice usando context.genreIndex
    // e ela vem como por exemplo "Action, Adventure, Thriller"

    const movieGenres = typeof movie.genre === 'string'
        ? movie.genre.split(',').map(g => g.trim()).filter(Boolean)
        : movie.genre || [];

    const genreVecArray = new Array(context.numGenres).fill(0);

    movieGenres.forEach(genre => {
        const index = context.genreIndex[genre];

        if (index !== undefined) {
            genreVecArray[index] = 1 * WEIGHTS.genre;
        }
    });

    const genreVec = tf.tensor1d(genreVecArray);

    const age = tf.tensor1d([
        (
            context.movieAvgAgeNorm[movie.id] || 0.5 // se não tiver dados, assume idade média normalizada
        ) * WEIGHTS.age
    ])

    return tf.concat1d([genreVec, age]);

}// fim encodeMovie

function encodeUser(user, context) {
    if (user.watchedMovies.length || user.is_cold_start) {
        return tf.stack(
            user.watchedMovies.map(movie => encodeMovie(movie, context))
        ).mean(0) // se o nao tiver assistido nenhum filme, retorna um vetor de zeros (cold start)
            .reshape([1, context.dimentions]) // garante que a saída tenha o formato correto, mesmo que seja um vetor de zeros
    }

    return tf.concat1d(
        [
            tf.tensor1d([
                normalize(user.age, context.minAge, context.maxAge) * WEIGHTS.age,
            ]),
            tf.zeros([context.numGenres]).cast('float32').mul(WEIGHTS.genre), // vetor de zeros para os gêneros, pois não temos essa informação do usuário
            tf.zeros([1]).cast('float32').mul(WEIGHTS.rating) // vetor de zeros para a avaliação, pois não temos essa informação do usuário
        ]
    ).reshape([1, context.dimentions]) // garante que a saída tenha o formato correto, mesmo que seja um vetor de zeros
} // fim encodeUser


window.trainModel = async function trainModel() {
    console.log('Training model with users');

    resetTrainingVisor();

    const movies = window.app?.movies ?? app.movies;
    const users = window.app?.users ?? app.users;

    console.log('Movies:', movies.length);
    console.log('Users:', users.length);

    const context = makeContext(movies, users);

    context.movieVectors = movies.map(movie => {
        return {
            name: movie.name,
            meta: { ...movie },
            vector: encodeMovie(movie, context).dataSync() // converte o tensor para array normal
        }
    })

    debugger

    // treino fake só pra testar gráfico
    for (let epoch = 1; epoch <= 50; epoch++) {
        const loss = 1 / epoch;
        const accuracy = epoch / 50;

        updateTrainingVisor({
            epoch,
            loss,
            accuracy
        });

        await new Promise(resolve => setTimeout(resolve, 100));
    }
};
