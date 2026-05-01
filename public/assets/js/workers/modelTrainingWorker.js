//importScripts('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs');

let _globalCtx = {};

let _model = null

//Normaliza um valor entre 0 e 1, para evitar que a rede precise lidar com escalas diferentes
//exemplo de idade entre 18 e 65, ou preço entre 10 e 1000
//entao a idade 18 vira 0, a idade 65 vira 1, e a idade 40 vira algo em torno de 0.4

const normalize = (value, min, max) => (value - min) / (max - min) || 1

const makeContext = (movies, users) => {

    const ages = users.map(u => u.age);
    const ratingsMovie = movies.map(m => m.rate);

    const ageMin = Math.min(...ages);
    const ageMax = Math.max(...ages);

    const ratingMin = Math.min(...ratingsMovie);
    const ratingMax = Math.max(...ratingsMovie);

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
