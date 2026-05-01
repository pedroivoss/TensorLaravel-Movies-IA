//importScripts('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs');

let _globalCtx = {};

let _model = null

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

}

window.trainModel = async function trainModel() {
    console.log('Training model with users');

    resetTrainingVisor();

    const movies = window.app?.movies ?? app.movies;
    const users = window.app?.users ?? app.users;

    console.log('Movies:', movies.length);
    console.log('Users:', users.length);

    const context = makeContext(movies, users);

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
