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

window.makeContext = makeContext;
window.createInputVector = createInputVector;
window._globalCtx = _globalCtx;
window._model = _model;

// Função para criar o vetor de gêneros (Multi-Hot Encoding)
function getGenreVector(itemGenres, context) {
    const vector = new Array(context.numGenres).fill(0);
    const genres = Array.isArray(itemGenres)
        ? itemGenres
        : (itemGenres ? itemGenres.split(',').map(g => g.trim()) : []);

    genres.forEach(g => {
        if (context.genreIndex[g] !== undefined) {
            vector[context.genreIndex[g]] = 1;
        }
    });
    return vector;
}

// Função que monta a "linha" completa de entrada para o modelo
function createInputVector(user, movie, context) {
    const ageNorm = normalize(user.age, context.minAge, context.maxAge);
    //const movieRateNorm = normalize(movie.rate, context.minRating, context.maxRating);


    // divide por 10 pois depois que o usuario da nota de 1 a 5
    // o sistema classifica de 1 a 10, entao pra normalizar entre 0 e 1,
    // dividimos por 10
    const movieRateNorm = movie.rate / 10; // já que as notas são de 1 a 10, podemos simplesmente dividir por 10 para normalizar

    const userGenreVec = getGenreVector(user.favorite_genres, context);
    const movieGenreVec = getGenreVector(movie.genre, context);

    // Estrutura: [Idade, Nota_Média_Filme, ...Gêneros_User, ...Gêneros_Filme]
    return [
        ageNorm,
        movieRateNorm,
        ...userGenreVec,
        ...movieGenreVec
    ];
}

window.trainModel = async function trainModel() {
    console.log('Training model with users');

    resetTrainingVisor();

    const movies = window.app?.movies ?? app.movies;
    const users = window.app?.users ?? app.users;

    const context = makeContext(movies, users);

    //agora temos nosso contexto global com todas as informações necessárias para treinar o modelo
    _globalCtx = context;

    const inputs = [];
    const outputs = [];

    // Percorremos cada usuário que tem histórico
    /*context.users.forEach(user => {
        if (user.watchedMovies && user.watchedMovies.length > 0) {
            user.watchedMovies.forEach(movie => {
                // X: Características do par Usuário/Filme
                inputs.push(createInputVector(user, movie, context));

                // Y: A nota que o usuário DEU (está no pivot)
                // Normalizamos de 1-5 para 0-1
                // usuario da de 1 a 5 porque ele da em forma de estrelas,
                //  mas o modelo vai prever um valor entre 0 e 1,
                // então normalizamos dividindo por 5
                const ratingReal = movie.pivot.rating / 5;
                outputs.push([ratingReal]);
            });
        }
    });
    */

    context.users.forEach(user => {
        // Verifique se a propriedade é watchedMovies (como no seu JS)
        // ou se o Laravel enviou como ratings
        const movies = user.watchedMovies || user.ratings || [];

        movies.forEach(movie => {
            inputs.push(createInputVector(user, movie, context));

            // Correção aqui: Garantir que pegamos a nota independente da estrutura
            const score = movie.pivot ? movie.pivot.rating : movie.rating;
            const ratingReal = (score || 3) / 5;
            outputs.push([ratingReal]);
        });
    });

    if (inputs.length === 0) {
        console.error("Nenhum dado de avaliação encontrado para treino!");
        return;
    }

    // Convertendo para Tensores
    const xs = tf.tensor2d(inputs);
    const ys = tf.tensor2d(outputs);

    // 3. DEFINIÇÃO DO MODELO
    const model = tf.sequential();
    model.add(tf.layers.dense({
        units: 128,
        activation: 'relu',
        inputShape: [context.dimentions]
    }));
    model.add(tf.layers.dense({ units: 64, activation: 'relu' }));
    model.add(tf.layers.dense({ units: 32, activation: 'relu' }));
    model.add(tf.layers.dense({ units: 1, activation: 'sigmoid' }));

    model.compile({
        optimizer: tf.train.adam(0.01),
        loss: 'meanSquaredError'
    });

    // 4. TREINAMENTO REAL
    console.log("Iniciando treinamento real...");
    await model.fit(xs, ys, {
        epochs: 50, // número de épocas para treinar o modelo, ou seja, quantas vezes o modelo vai passar por todo o conjunto de dados de treinamento. Um número maior de épocas pode levar a um modelo mais preciso, mas também pode aumentar o risco de overfitting, onde o modelo aprende muito bem os dados de treinamento, mas tem um desempenho ruim em dados novos.
        batchSize: 32, // tamanho do lote para o treinamento, ou seja, quantas amostras de treinamento o modelo vai processar antes de atualizar os pesos. Um tamanho de lote de 32 é uma escolha comum que pode ajudar a acelerar o treinamento e melhorar a estabilidade do modelo, mas pode ser ajustado conforme necessário com base no tamanho do conjunto de dados e na capacidade computacional disponível.
        shuffle: true, // embaralha os dados de treinamento a cada época, o que pode ajudar a melhorar a generalização do modelo e reduzir o risco de overfitting, garantindo que o modelo não veja os dados na mesma ordem repetidamente durante o treinamento.
        callbacks: {
            onEpochEnd: (epoch, logs) => {
                updateTrainingVisor({
                    epoch: epoch + 1,
                    loss: logs.loss,
                    accuracy: 1 - logs.loss // Simplificação para o visor
                });
            }
        }
    });

    _model = model;

    //isso garante que o modelo treinado fique disponível globalmente para a função de recomendação
    window._model = model;
    console.log("Modelo treinado com sucesso!");

    // Chamada automática para persistir no MySQL
    await saveModelToDatabase();

};

async function saveModelToDatabase() {

    if (!_model) return;

    // O artifacts contém modelTopology e weightData separadamente
    const artifacts = await _model.save(tf.io.withSaveHandler(async (art) => art));

    // Criamos o manifesto de pesos que estava faltando na sua topologia
    const weightsManifest = [{
        paths: ["./weights.bin"],
        weights: artifacts.weightSpecs
    }];

    // Mesclamos a topologia com o manifesto
    const fullTopology = {
        ...artifacts.modelTopology,
        weightsManifest: weightsManifest
    };

    const modelData = {
        // Converte a topologia (JSON) para string

        topology: JSON.stringify(fullTopology),
        // Converte os pesos binários para uma String Base64 para caber no MySQL
        weights: btoa(String.fromCharCode(...new Uint8Array(artifacts.weightData)))
    };

    await fetch('/api/ai-models/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify(modelData)
    });
    Swal.fire('Sucesso', 'Modelo persistido com manifesto de pesos no MySQL!', 'success');

    console.log("Modelo salvo no banco de dados com sucesso!");
}

// Adicione esta função ao final do arquivo modelTrainingWorker.js
window.getRecommendations = async function (userId) {
    if (!_model || !_globalCtx) {
        console.warn("IA: Modelo ou Contexto de dados faltando.");
        return [];
    }

    const user = _globalCtx.users.find(u => u.id == userId);
    if (!user) {
        console.error("IA: Usuário não encontrado no contexto:", userId);
        return [];
    }

    const recommendations = [];
    const watchedIds = user.watchedMovies ? user.watchedMovies.map(m => m.id) : [];

    console.log(`IA: Gerando para ${user.name}. Já assistiu: ${watchedIds.length} filmes.`);

    for (const movie of _globalCtx.movies) {
        if (watchedIds.includes(movie.id)) continue;

        // Use tf.tidy para limpar tensores intermediários automaticamente
        const score = tf.tidy(() => {
            const inputVector = createInputVector(user, movie, _globalCtx);
            const inputTensor = tf.tensor2d([inputVector]);
            return _model.predict(inputTensor).dataSync()[0];
        });

        recommendations.push({
            ...movie,
            predictedRating: (score * 5).toFixed(1),
            score: score
        });

        if (recommendations.length % 100 === 0) await tf.nextFrame();
    }

    const final = recommendations
        .sort((a, b) => b.score - a.score)
        .slice(0, 10);

    console.log("IA: Top 10 gerado com sucesso:", final);
    return final;
};

