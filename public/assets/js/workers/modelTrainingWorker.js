console.log('Model training worker initialized');


let _globalCtx = {};

let _model = null

const WEIGHTS = {
    categoryUser: 0.4,
    categoryMovie: 0.3,
    rating: 0.2,
    age: 0.1,
}

const normalize = (value, min, max) => (value - min) / (max - min) || 1

// Nesse projeto vamos usar para o calculo:
// Idade do usuario, genero do usuario
// Genero do filme, Qualidade (media das notas)

//exemplo
// Idade 28 / 100 = 0.28
// Gostos do UsuárioAção(1), Comé(1), Rom(0), Terr(0) = [1, 1, 0, 0]
// Gêneros do FilmeAção(0), Comé(1), Rom(1), Terr(0) = [0, 1, 1, 0]
// Qualidade (Média) 4.2 / 5 = 0.84

function makeContext(movies, users) {

    // lista de idades dos usuários
    const ages = users.map(u => u.age)

    // lista de notas dos filmes
    const ratings = movies.map(m => m.rate)

    // idade mínima e máxima para normalizar
    const minAge = Math.min(...ages)
    const maxAge = Math.max(...ages)

    // nota mínima e máxima para normalizar
    const minRating = Math.min(...ratings)
    const maxRating = Math.max(...ratings)

    //  Mapeamento de Gêneros (Categorização)
    // Criamos uma lista única de todos os gêneros disponíveis no catálogo
    // Extrair todos os gêneros possíveis em uma lista única e limpa
    const allGenres = movies.flatMap(m => {
        // Se for string "Action, Drama", vira ["Action", "Drama"]
        if (typeof m.genre === 'string') {
            return m.genre.split(',').map(g => g.trim());
        }
        return m.genre || [];
    });

    // Criar lista única, ordenada e sem duplicatas
    const uniqueGenres = Array.from(new Set(allGenres)).sort();

    const genreIndex = Object.fromEntries(
        uniqueGenres.map((genre, index) => {
            return [genre, index];
        })
    );

    //computar a media de idade dos comprados por produtos
    // (ajuda a personalizarr a recomendação, por exemplo, se um filme é mais popular entre jovens ou idosos)
    const midAge = (maxAge + minAge) / 2;
    const ageSums = {};
    const ageCounts = {};

    // Para cada usuário, para cada filme que ele assistiu,
    // somamos a idade e contamos quantas vezes esse filme foi assistido
    // tem que ser por id, pois pode ter filmes com mesmo nome, mas id diferente
    users.forEach(user => {
        (user.watchedMovies || []).forEach(wm => {
            ageSums[wm.id] = (ageSums[wm.id] || 0) + user.age;
            ageCounts[wm.id] = (ageCounts[wm.id] || 0) + 1;
        });
    });

    // Agora podemos calcular a idade média dos espectadores para cada filme
    // assim, se um filme é mais assistido por jovens, a idade média será menor, e vice-versa
    // E normalizamos essa idade média para ficar entre 0 e 1, usando o minAge e maxAge do dataset
    // tem que ser por id, pois pode ter filmes com mesmo nome, mas id diferente
    const movieAvgAgeNorm = Object.fromEntries(
        movies.map(movie => {
            const avgAge = ageCounts[movie.id] ? ageSums[movie.id] / ageCounts[movie.id] : midAge;
            return [movie.id, normalize(avgAge, minAge, maxAge)];
        })
    )

    //aqui vamos criar o vinculo de usuarios e filmes, ou seja, para cada usuário,
    // quais filmes ele assistiu e qual nota deu
    const userWatchedMovies = Object.fromEntries(
        users.map(user => {
            const watched = (user.watchedMovies || []).map(wm => {
                return {
                    movieId: wm.id,
                    rating: wm.rate,
                }
            });
            return [user.id, watched];
        })
    )

    return {
        users,
        movies,
        minAge,
        maxAge,
        minRating,
        maxRating,
        numGenres: uniqueGenres.length,
        genreIndex,
        movieAvgAgeNorm,
        userWatchedMovies,
        // idade + qualidade + one-hot de gêneros (cada gênero é uma dimensão)
        // é 2 porque idade e qualidade são normalizados entre 0 e 1, então cada um ocupa uma dimensão.
        // entao 2 + o numero de generos unicos * 2 (um para o usuário, outro para o filme)
        dimentions: 2 + (uniqueGenres.length * 2),
    }

}

function encodeMovie(movie, context) {
    console.log('Encoding movie:', movie.name);
}


window.trainModel = async function trainModel() {
    console.log('Training model with users');

    resetTrainingVisor();

    const movies = window.app?.movies ?? app.movies;
    const users = window.app?.users ?? app.users;

    const context = makeContext(movies, users);

    debugger;

    //agora temos nosso contexto global com todas as informações necessárias para treinar o modelo
    _globalCtx = context;

    // treino fake só pra testar gráfico
    for (let epoch = 1; epoch <= 50; epoch++) {
        /*
            const loss = 1 / epoch;
            const accuracy = epoch / 50;

            updateTrainingVisor({
                epoch,
                loss,
                accuracy
            });
        */

        await new Promise(resolve => setTimeout(resolve, 100));
    }
};

